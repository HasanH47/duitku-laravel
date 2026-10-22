<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\BatchResult;
use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;

class Transfer
{
    use InteractsWithApi;

    protected Client $client;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->client = new Client($config);
    }

    /**
     * Bank Inquiry (Check Account)
     */
    public function inquiry(DisbursementInfo $info): DisbursementResponse
    {
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + bankCode + bankAccount + amountTransfer + purpose + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->bankCode.
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
            ? '/webapi/api/disbursement/inquirysandbox'
            : '/webapi/api/disbursement/inquiry';

        $response = $this->client->disbursementRequest()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($this->client->json($response));
    }

    /**
     * Execute Transfer
     */
    public function execute(string $disburseId, DisbursementInfo $info, string $accountName, string $custRefNumber): DisbursementResponse
    {
        $timestamp = $this->getTimestamp();

        // Formula: SHA256(email + timestamp + bankCode + bankAccount + accountName + custRefNumber + amountTransfer + purpose + disburseId + secretKey)
        $signatureParams =
            $this->config->getEmail().
            $timestamp.
            $info->bankCode.
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
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/transfersandbox'
            : '/webapi/api/disbursement/transfer';

        $response = $this->client->disbursementRequest()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($this->client->json($response));
    }

    /**
     * @param  list<DisbursementInfo>  $infos
     * @return list<BatchResult>
     */
    public function bulkInquiry(array $infos): array
    {
        $requests = [];

        foreach ($infos as $index => $info) {
            $timestamp = $this->getTimestamp();
            $signature = $this->generateSignature(
                $this->config->getEmail().$timestamp.$info->bankCode.
                $info->bankAccount.$info->amountTransfer.$info->purpose.$this->config->getApiKey(),
                'sha256',
            );

            $requests[] = [
                'key' => (string) $index,
                'payload' => array_merge($info->toArray(), [
                    'userId' => (int) $this->config->getUserId(),
                    'email' => $this->config->getEmail(),
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]),
            ];
        }

        $endpoint = $this->config->isSandbox()
            ? '/webapi/api/disbursement/inquirysandbox'
            : '/webapi/api/disbursement/inquiry';

        return $this->client->batch(
            $endpoint,
            $requests,
            fn (array $data) => DisbursementResponse::fromArray($data)->throwIfFailed(),
            disbursement: true,
        );
    }
}
