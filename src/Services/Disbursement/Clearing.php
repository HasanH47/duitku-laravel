<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Clearing
{
    use InteractsWithApi;

    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Clearing Inquiry (RTGS/LLG/BIFAST)
     */
    public function inquiry(DisbursementInfo $info): DisbursementResponse
    {
        if (empty($info->type)) {
            throw new \InvalidArgumentException('Type (RTGS/LLG/BIFAST) is required for clearing inquiry.');
        }

        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + bankCode + type + bankAccount + amountTransfer + purpose + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->bankCode.
            $info->type.
            $info->bankAccount.
            $info->amountTransfer.
            $info->purpose.
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = array_merge($info->toArray(), [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/inquiryclearingsandbox'
            : '/webapi/api/disbursement/inquiryclearing';

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Clearing Transfer
     */
    public function execute(string $disburseId, DisbursementInfo $info, string $accountName, string $custRefNumber): DisbursementResponse
    {
        if (empty($info->type)) {
            throw new \InvalidArgumentException('Type (RTGS/LLG/BIFAST) is required for clearing transfer.');
        }

        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + bankCode + type + bankAccount + accountName + custRefNumber + amountTransfer + purpose + disburseId + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->bankCode.
            $info->type.
            $info->bankAccount.
            $accountName.
            $custRefNumber.
            $info->amountTransfer.
            $info->purpose.
            $disburseId.
            $this->config->getApiKey();

        $signature = $this->generateSignature($signatureParams, 'sha256');

        $payload = [
            'disburseId' => $disburseId,
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'bankCode' => $info->bankCode,
            'bankAccount' => $info->bankAccount,
            'amountTransfer' => $info->amountTransfer,
            'accountName' => $accountName,
            'custRefNumber' => $custRefNumber,
            'purpose' => $info->purpose,
            'type' => $info->type,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/transferclearingsandbox'
            : '/webapi/api/disbursement/transferclearing';

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Validate Clearing Callback Signature
     */
    public function validateCallback(array $data): bool
    {
        $signatureParam =
            $this->config->getEmail().
            ($data['bankCode'] ?? '').
            ($data['bankAccount'] ?? '').
            ($data['accountName'] ?? '').
            ($data['custRefNumber'] ?? '').
            ($data['amountTransfer'] ?? '').
            ($data['disburseId'] ?? '').
            $this->config->getApiKey();

        $generated = $this->generateSignature($signatureParam, 'sha256');

        return $generated === ($data['signature'] ?? '');
    }
}
