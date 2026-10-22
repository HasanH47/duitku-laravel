<?php

use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Exceptions\CallbackMismatchException;
use Duitku\Laravel\Exceptions\ConfigurationException;
use Duitku\Laravel\Exceptions\InsufficientFundsException;
use Duitku\Laravel\Facades\Duitku;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

function duitkuBatchStatus(string $orderId): array
{
    return ['merchantOrderId' => $orderId, 'reference' => 'REF-'.$orderId, 'amount' => '10000',
        'statusCode' => '00', 'statusMessage' => 'Success'];
}

it('keeps successes and failures tied to their inputs', function () {
    Http::fake(function ($request) {
        return $request['merchantOrderId'] === 'GOOD'
            ? Http::response(duitkuBatchStatus('GOOD'))
            : Http::response(['error' => 'unavailable'], 500);
    });

    $results = Duitku::checkStatuses(['GOOD', 'BAD']);

    expect($results[0]->key)->toBe('GOOD')
        ->and($results[0]->get()->merchantOrderId)->toBe('GOOD')
        ->and($results[1]->key)->toBe('BAD')
        ->and($results[1]->successful())->toBeFalse()
        ->and($results[1]->error)->toBeInstanceOf(RequestException::class);
});

it('isolates connection failures without losing other results', function () {
    Http::fake([
        '*' => fn ($request) => $request['merchantOrderId'] === 'BAD'
            ? throw new ConnectionException('Synthetic connection failure')
            : Http::response(duitkuBatchStatus('GOOD')),
    ]);

    $results = Duitku::checkStatuses(['BAD', 'GOOD']);

    expect($results[0]->error)->toBeInstanceOf(ConnectionException::class)
        ->and($results[1]->get()->merchantOrderId)->toBe('GOOD');
});

it('settles rejected connection promises from the Laravel HTTP client', function () {
    Http::fake(['*' => Http::failedConnection('Synthetic connection failure')]);
    $result = Duitku::checkStatuses(['BAD'])[0];

    expect($result->error)->toBeInstanceOf(ConnectionException::class);
});

it('applies timeout and HMAC signatures to every batch item', function () {
    Config::set('duitku.timeout', 7);
    Config::set('duitku.batch_concurrency', 2);
    $timeouts = [];
    Http::fake(function ($request, $options) use (&$timeouts) {
        $timeouts[] = $options['timeout'];

        return Http::response(duitkuBatchStatus($request['merchantOrderId']));
    });

    $results = Duitku::checkStatuses(['A', 'B', 'C', 'D', 'E']);

    expect($timeouts)->toBe([7, 7, 7, 7, 7])->and($results)->toHaveCount(5);
    Http::assertSent(fn ($request) => $request['signature'] ===
        hash_hmac('sha256', 'TEST_MERCHANT'.$request['merchantOrderId'], 'TEST_API_KEY'));
});

it('rejects provider responses for another order in a batch', function () {
    Http::fake(['*' => Http::response(duitkuBatchStatus('OTHER'))]);

    expect(Duitku::checkStatuses(['EXPECTED'])[0]->error)->toBeInstanceOf(CallbackMismatchException::class);
});

it('keeps duplicate input positions and handles an empty batch', function () {
    Http::fake(['*' => Http::response(duitkuBatchStatus('A'))]);

    expect(Duitku::checkStatuses([]))->toBe([])
        ->and(Duitku::checkStatuses(['A', 'A']))->toHaveCount(2);
    Http::assertSentCount(2);
});

it('rejects an invalid batch limit before sending requests', function () {
    Config::set('duitku.batch_concurrency', 0);

    expect(fn () => Duitku::checkStatuses(['A']))->toThrow(ConfigurationException::class);
    Http::assertNothingSent();
});

it('bounds the number of unsettled requests in each chunk', function () {
    Config::set('duitku.batch_concurrency', 2);
    $active = 0;
    $maximum = 0;
    $factory = new class extends Factory
    {
        public function deferredResponses(Closure $callback): self
        {
            // The normal fake waits for on_stats, settling deferred responses immediately.
            $this->stubCallbacks = collect([$callback]);

            return $this;
        }
    };
    Http::swap($factory->deferredResponses(function ($request) use (&$active, &$maximum) {
        $active++;
        $maximum = max($maximum, $active);
        $orderId = $request['merchantOrderId'];
        $promise = null;
        $promise = new Promise(function () use (&$promise, &$active, $orderId) {
            $active--;
            $promise->resolve(new Response(
                200, ['Content-Type' => 'application/json'], json_encode(duitkuBatchStatus($orderId)),
            ));
        });

        return $promise;
    }));

    $results = Duitku::checkStatuses(['A', 'B', 'C', 'D', 'E']);

    expect($maximum)->toBe(2)->and($active)->toBe(0)->and($results)->toHaveCount(5);
    expect(array_map(fn ($result) => $result->successful(), $results))->toBe([true, true, true, true, true]);
});

it('turns inquiry business failures into per-item errors', function () {
    Http::fake(['*' => Http::response(['responseCode' => '-510', 'responseDesc' => 'Insufficient balance'])]);
    $info = new DisbursementInfo(10000, '000000000', '014', 'Synthetic');
    $result = Duitku::disbursement()->transfer()->bulkInquiry([$info])[0];

    expect($result->key)->toBe('0')
        ->and($result->error)->toBeInstanceOf(InsufficientFundsException::class);
});
