<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Exceptions\MalformedResponseException;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Finance
{
    use InteractsWithApi;

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
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + disburseId + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $disburseId.
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = [
            'disburseId' => $disburseId,
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = '/webapi/api/disbursement/inquirystatus';

        $response = $this->client->disbursementRequest(retryable: true)->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($this->client->json($response));
    }

    /**
     * Check Balance
     */
    public function balance(): DisbursementResponse
    {
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = '/webapi/api/disbursement/checkbalance';

        $response = $this->client->disbursementRequest(retryable: true)->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($this->client->json($response));
    }

    /**
     * List Available Banks
     * Returns array of [bankCode, bankName, maxAmountTransfer]
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBank(): array
    {
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = '/webapi/api/disbursement/listBank';

        $response = $this->client->disbursementRequest(retryable: true)->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        $data = $this->client->json($response);

        if (isset($data['listBank']) && is_array($data['listBank'])) {
            return $data['listBank'];
        }

        throw new MalformedResponseException('Duitku returned a bank list without listBank.');
    }
}
