<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\CashOutInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class CashOut
{
    use InteractsWithApi;

    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Cash Out Inquiry (Indomaret/Pos)
     * Response contains 'token' and 'pin' (Pos only).
     */
    public function inquiry(CashOutInfo $info): DisbursementResponse
    {
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + amountTransfer + purpose + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->amountTransfer.
            ($info->purpose ?? '').
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = array_merge($info->toArray(), [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $endpoint = $this->config->getDisbursementHost().'/api/cashout/inquiry';

        $response = $this->client->request($this->config->getDisbursementHost())->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Validate Cash Out Callback
     * Formula: SHA256(email + disburseId + custRefNumber + secretKey)
     */
    public function validateCallback(array $data): bool
    {
        $signatureParam =
            $this->config->getEmail().
            ($data['disburseId'] ?? '').
            ($data['custRefNumber'] ?? '').
            $this->config->getApiKey();

        $generated = $this->generateSignature($signatureParam, 'sha256');

        return $generated === ($data['signature'] ?? '');
    }
}
