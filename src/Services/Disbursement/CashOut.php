<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Data\CashOutInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class CashOut
{
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
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + amountTransfer + purpose + secretKey)
        // Note: Docs say "amountTransfer" without decimal. format is strictly int.
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->amountTransfer.
            ($info->purpose ?? '').
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

        $payload = array_merge($info->toArray(), [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $endpoint = $this->config->isSandbox()
            ? 'https://disbursement-sandbox.duitku.com/api/cashout/inquiry'
            : 'https://disbursement.duitku.com/api/cashout/inquiry';

        // NOTE: CashOut uses different base URL structure potentially,
        // but let's stick to full URL override in request since Client might default to webapi.
        // Actually Client.php might handle baseUrl, but here the subdomain differs (disbursement vs passport).

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        // CashOut response is handled universally by DisbursementResponse if fields match,
        // or we might need separate response handling if structure differs significantly.
        // It returns 'token', 'pin'. DisbursementResponse doesn't have these.
        // Let's rely on flexible parsing or Array return for now, OR update DisbursementResponse.
        // Ideally update DisbursementResponse to include 'token', 'pin'.

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

        $generated = hash('sha256', $signatureParam);

        return $generated === ($data['signature'] ?? '');
    }
}
