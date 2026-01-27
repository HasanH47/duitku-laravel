<?php

namespace Duitku\Laravel;

use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Disbursement
{
    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Step 1: Bank Inquiry (Check Account)
     */
    public function bankInquiry(DisbursementInfo $info): DisbursementResponse
    {
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + bankCode + bankAccount + amountTransfer + purpose + secretKey)
        $signatureParams =
            $this->config->getEmail() .
            $timestamp .
            $info->bankCode .
            $info->bankAccount .
            $info->amountTransfer .
            $info->purpose .
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

        $payload = array_merge($info->toArray(), [
            'userId' => (int) $this->config->getUserId(),
            'email' => $this->config->getEmail(),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/inquirysandbox'
            : '/webapi/api/disbursement/inquiry';

        $response = $this->client->request()->post($endpoint, $payload);

        if (!$response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Step 2: Transfer (Execute Disbursement)
     */
    public function transfer(string $disburseId, DisbursementInfo $info, string $accountName, string $custRefNumber): DisbursementResponse
    {
        $timestamp = round(microtime(true) * 1000);

        // Formula: SHA256(email + timestamp + bankCode + bankAccount + accountName + custRefNumber + amountTransfer + purpose + disburseId + secretKey)
        $signatureParams =
            $this->config->getEmail() .
            $timestamp .
            $info->bankCode .
            $info->bankAccount .
            $accountName .
            $custRefNumber .
            $info->amountTransfer .
            $info->purpose .
            $disburseId .
            $this->config->getApiKey();

        $signature = hash('sha256', $signatureParams);

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
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/transfersandbox'
            : '/webapi/api/disbursement/transfer';

        $response = $this->client->request()->post($endpoint, $payload);

        if (!$response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }
}
