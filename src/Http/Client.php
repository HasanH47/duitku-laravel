<?php

namespace Duitku\Laravel\Http;

use Duitku\Laravel\Data\BatchResult;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Data\TransactionStatus;
use Duitku\Laravel\Exceptions\MalformedResponseException;
use Duitku\Laravel\Support\DuitkuConfig;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Utils as HttpUtils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Promises\LazyPromise;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Client
{
    public function __construct(private DuitkuConfig $config) {}

    public function request(?string $baseUrl = null, bool $retryable = false): PendingRequest
    {
        $this->config->assertApiKey();

        return $this->configure(Http::baseUrl($baseUrl ?? $this->config->getPassportHost()), $retryable);
    }

    public function disbursementRequest(?string $baseUrl = null, bool $retryable = false): PendingRequest
    {
        $this->config->assertDisbursementCredentials();

        return $this->request($baseUrl, $retryable);
    }

    /** @return array<string, mixed> */
    public function json(Response $response): array
    {
        $response->throw();

        if (! $response->successful()) {
            throw new MalformedResponseException('Duitku returned an unexpected HTTP status.');
        }

        $data = $response->json();

        if (! is_array($data) || array_is_list($data)) {
            throw new MalformedResponseException('Duitku returned a response that is not a JSON object.');
        }

        return $data;
    }

    /**
     * @param  list<array{key: string, payload: array}>  $requests
     * @param  callable(array, string): (TransactionStatus|DisbursementResponse)  $decode
     * @return list<BatchResult>
     */
    public function batch(string $endpoint, array $requests, callable $decode, bool $disbursement = false): array
    {
        if ($disbursement) {
            $this->config->assertDisbursementCredentials();
        } else {
            $this->config->assertPaymentCredentials();
        }

        $results = [];
        $url = $this->config->getPassportHost().$endpoint;
        $handler = HttpUtils::chooseHandler();

        // Settled promises preserve partial failures, including on early Laravel 12 releases.
        foreach (array_chunk($requests, $this->config->getBatchConcurrency()) as $chunk) {
            $promises = [];

            foreach ($chunk as $index => $request) {
                try {
                    $promise = $this->request(retryable: true)->setHandler($handler)->async()->post($url, $request['payload']);
                    $promises[$index] = $this->startPromise($promise);
                } catch (Throwable $error) {
                    $promises[$index] = Create::rejectionFor($error);
                }
            }

            $responses = Utils::settle($promises)->wait();

            foreach ($chunk as $index => $request) {
                try {
                    if ($responses[$index]['state'] === 'rejected') {
                        throw $responses[$index]['reason'];
                    }

                    $response = $responses[$index]['value'];

                    if ($response instanceof Throwable) {
                        throw $response;
                    }

                    $results[] = new BatchResult($request['key'], $decode($this->json($response), $request['key']));
                } catch (Throwable $error) {
                    $results[] = new BatchResult($request['key'], error: $error);
                }
            }
        }

        return $results;
    }

    private function startPromise(PromiseInterface $promise): PromiseInterface
    {
        // Recent Laravel releases defer dispatch; older Laravel 12 returns an eager promise.
        return $promise instanceof LazyPromise ? $promise->buildPromise() : $promise;
    }

    private function configure(PendingRequest $request, bool $retryable): PendingRequest
    {
        $retryTimes = $this->config->getRetryTimes();
        $retrySleep = $this->config->getRetrySleep();

        $request->acceptJson()->asJson()
            ->timeout($this->config->getTimeout())
            ->withOptions(['allow_redirects' => false]);

        if ($retryable && $retryTimes > 0) {
            $request->retry(
                $retryTimes + 1,
                $retrySleep,
                fn (Throwable $error) => $error instanceof ConnectionException ||
                    ($error instanceof RequestException &&
                        ($error->response->serverError() || $error->response->status() === 429)),
                throw: false,
            );
        }

        if ($channel = $this->config->getLogChannel()) {
            $request->beforeSending(function (Request $request) use ($channel) {
                // Bodies, query strings, and headers may contain personal data or replay credentials.
                Log::channel($channel)->debug('[Duitku] Request', [
                    'method' => $request->method(),
                    'host' => parse_url($request->url(), PHP_URL_HOST),
                    'path' => parse_url($request->url(), PHP_URL_PATH),
                ]);
            });
        }

        return $request;
    }
}
