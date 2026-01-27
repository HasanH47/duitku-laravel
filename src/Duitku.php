<?php

namespace Duitku\Laravel;

use Duitku\Laravel\Concerns\HasSignatures;
use Duitku\Laravel\Data\CallbackRequest;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\PaymentResponse;
use Duitku\Laravel\Data\TransactionStatus;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class Duitku
{
    use HasSignatures;

    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Get Payment Methods
     * Direct API call without internal caching to allow developer flexibility.
     */
    public function paymentMethods(int $amount): array
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = $this->generateSignature(
            $this->config->getMerchantCode().$amount.$datetime.$this->config->getApiKey(),
            'sha256'
        );

        $response = $this->client->request()->post('/webapi/api/merchant/paymentmethod/getpaymentmethod', [
            'merchantCode' => $this->config->getMerchantCode(),
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]);

        return $response->json('paymentFee') ?? [];
    }

    /**
     * Create a Duitku Invoice/Payment
     */
    public function checkout(PaymentRequest $request): PaymentResponse
    {
        $signature = $this->generateSignature(
            $this->config->getMerchantCode().$request->merchantOrderId.$request->amount.$this->config->getApiKey()
        );

        $payload = array_merge($request->toArray(), [
            'merchantCode' => $this->config->getMerchantCode(),
            'signature' => $signature,
        ]);

        $response = $this->client->request()->post('/webapi/api/merchant/v2/inquiry', $payload);

        if (!$response->successful()) {
            $response->throw();
        }

        return PaymentResponse::fromArray($response->json());
    }

    /**
     * Check Single Transaction Status
     */
    public function checkStatus(string $merchantOrderId): TransactionStatus
    {
        $signature = $this->generateSignature(
            $this->config->getMerchantCode().$merchantOrderId.$this->config->getApiKey()
        );

        $response = $this->client->request()->post('/webapi/api/merchant/transactionStatus', [
            'merchantCode' => $this->config->getMerchantCode(),
            'merchantOrderId' => $merchantOrderId,
            'signature' => $signature,
        ]);

        if (!$response->successful()) {
            $response->throw();
        }

        return TransactionStatus::fromArray($response->json());
    }

    /**
     * Check Multiple Transaction Statuses concurrently
     * This uses Laravel Http::pool for optimization
     */
    public function checkStatuses(array $merchantOrderIds): array
    {
        $responses = Http::pool(function (Pool $pool) use ($merchantOrderIds) {
            $requests = [];
            foreach ($merchantOrderIds as $orderId) {
                $signature = $this->generateSignature(
                    $this->config->getMerchantCode().$orderId.$this->config->getApiKey()
                );

                $url = $this->client->getUrl('/webapi/api/merchant/transactionStatus');

                $requests[] = $pool->post($url, [
                    'merchantCode' => $this->config->getMerchantCode(),
                    'merchantOrderId' => $orderId,
                    'signature' => $signature,
                ]);
            }

            return $requests;
        });

        return array_map(function ($response) {
            if ($response instanceof \Exception) {
                return null; // Handle failed requests gracefully
            }

            return TransactionStatus::fromArray($response->json());
        }, $responses);
    }

    /**
     * Validate Callback Signature
     */
    public function validateCallback(array $data): bool
    {
        $callback = CallbackRequest::fromArray($data);

        $generatedSignature = $this->generateSignature(
            $this->config->getMerchantCode().$callback->amount.$callback->merchantOrderId.$this->config->getApiKey()
        );

        return $generatedSignature === $callback->signature;
    }
}
