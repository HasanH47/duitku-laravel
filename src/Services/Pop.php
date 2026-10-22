<?php

namespace Duitku\Laravel\Services;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\PaymentFee;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\PopResponse;
use Duitku\Laravel\Data\TransactionStatus;
use Duitku\Laravel\Duitku;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;
use Duitku\Laravel\Support\PaymentSigner;

class Pop
{
    use InteractsWithApi;

    public function __construct(private DuitkuConfig $config) {}

    public function createTransaction(PaymentRequest $request): PopResponse
    {
        $payload = $request->validatedPayload($this->config->getDefaultExpiry(), pop: true);
        $timestamp = $this->getTimestamp();
        $signature = (new PaymentSigner($this->config))->sign(
            $this->config->getMerchantCode().$timestamp,
            'sha256',
        );
        $client = new Client($this->config);
        $response = $client->request($this->config->getApiHost())
            ->withHeaders([
                'x-duitku-signature' => $signature,
                'x-duitku-timestamp' => (string) $timestamp,
                'x-duitku-merchantcode' => $this->config->getMerchantCode(),
            ])
            ->post('/api/merchant/createInvoice', $payload);

        return PopResponse::fromArray($client->json($response))->throwIfFailed();
    }

    public function checkTransaction(string $merchantOrderId): TransactionStatus
    {
        return (new Duitku($this->config))->checkStatus($merchantOrderId);
    }

    /** @return list<PaymentFee> */
    public function getPaymentMethod(int $amount): array
    {
        return (new Duitku($this->config))->paymentMethods($amount);
    }

    public function scriptUrl(): string
    {
        return $this->config->getAppHost().'/lib/js/duitku.js';
    }
}
