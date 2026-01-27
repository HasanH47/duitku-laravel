<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Finance
{
    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Check Transaction Status
     */
    public function status(string $disburseId): DisbursementResponse
    {
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + disburseId + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $disburseId.
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

        $payload = [
            'disburseId' => $disburseId,
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/inquirystatus'
            : '/webapi/api/disbursement/inquirystatus';

        // Note: Production endpoint is 'https://passport.duitku.com/webapi/api/disbursement/inquirystatus'
        // Sandbox is 'https://sandbox.duitku.com/webapi/api/disbursement/inquirystatus'
        // Client.php handles the base URL switch correctly.

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Check Balance
     */
    public function balance(): DisbursementResponse
    {
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

        $payload = [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/checkbalance'
            : '/webapi/api/disbursement/checkbalance'; // Same endpoint

        // Note: Production endpoint is 'https://passport.duitku.com/webapi/api/disbursement/checkbalance'
        // Sandbox is 'https://sandbox.duitku.com/webapi/api/disbursement/checkbalance'

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * List Available Banks
     * Returns array of [bankCode, bankName, maxAmountTransfer]
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBank(): array
    {
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

        $payload = [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/listBank'
            : '/webapi/api/disbursement/listBank';

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        $data = $response->json();

        if (isset($data['listBank']) && is_array($data['listBank'])) {
            return $data['listBank'];
        }

        // Fallback: Return raw data if we can't parse it specifically, but let's try to be helpful.
        return $data;
    }
}
