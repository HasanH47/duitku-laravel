<?php

namespace Duitku\Laravel\Services;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\PopResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Pop
{
    use InteractsWithApi;

    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Create POP Transaction
     * Returns a reference ID to be used with duitku.js
     */
    public function createTransaction(PaymentRequest $request): PopResponse
    {
        $timestamp = $this->getTimestamp();
        $merchantCode = $this->config->getMerchantCode();
        $apiKey = $this->config->getApiKey();

        // Signature for Headers: SHA256(merchantCode + timestamp + apiKey)
        $signature = $this->generateSignature($merchantCode.$timestamp.$apiKey, 'sha256');

        $endpoint = $this->config->getApiHost().'/api/merchant/createInvoice';

        $response = $this->client->request($this->config->getApiHost())
            ->withHeaders([
                'x-duitku-signature' => $signature,
                'x-duitku-timestamp' => $timestamp,
                'x-duitku-merchantcode' => $merchantCode,
            ])
            ->post($endpoint, $request->toArray());

        if (! $response->successful()) {
            $response->throw();
        }

        return PopResponse::fromArray($response->json());
    }

    /**
     * Get the Duitku JS Script URL for Frontend
     */
    public function scriptUrl(): string
    {
        return $this->config->getAppHost().'/lib/js/duitku.js';
    }
}
