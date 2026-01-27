<?php

namespace Duitku\Laravel\Services;

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\PopResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Pop
{
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
        $timestamp = round(microtime(true) * 1000);
        $merchantCode = $this->config->getMerchantCode();
        $apiKey = $this->config->getApiKey();

        // Signature for Headers: SHA256(merchantCode + timestamp + apiKey)
        $signature = hash('sha256', $merchantCode.$timestamp.$apiKey);

        $baseUrl = $this->config->isSandbox()
            ? 'https://api-sandbox.duitku.com'
            : 'https://api-prod.duitku.com';

        $endpoint = $baseUrl.'/api/merchant/createInvoice';

        $response = $this->client->request()
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
        return $this->config->isSandbox()
            ? 'https://app-sandbox.duitku.com/lib/js/duitku.js'
            : 'https://app-prod.duitku.com/lib/js/duitku.js';
    }
}
