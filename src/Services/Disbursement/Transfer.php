<?php

namespace Duitku\Laravel\Services\Disbursement;

use Duitku\Laravel\Concerns\InteractsWithApi;
use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Data\DisbursementResponse;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

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

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
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

        $response = $this->client->request()->post($endpoint, $payload);

        if (! $response->successful()) {
            $response->throw();
        }

        return DisbursementResponse::fromArray($response->json());
    }

    /**
     * Bulk Bank Inquiry (Parallel)
     * Useful for checking multiple accounts before mass payouts.
     *
     * @param  DisbursementInfo[]  $infos
     * @return DisbursementResponse[]
     */
    public function bulkInquiry(array $infos): array
    {
        $responses = Http::pool(function (Pool $pool) use ($infos) {
            $requests = [];
            foreach ($infos as $info) {
                $timestamp = $this->getTimestamp();

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

                $url = $this->config->getPassportHost().($this->config->isSandbox()
                    ? '/webapi/api/disbursement/inquirysandbox'
                    : '/webapi/api/disbursement/inquiry');

                $requests[] = $pool->post($url, $payload);
            }

            return $requests;
        });

        return array_map(function ($response) {
            if ($response instanceof \Exception) {
                return new DisbursementResponse(
                    responseCode: 'EE',
                    responseDesc: 'Request failed: '.$response->getMessage()
                );
            }

            return DisbursementResponse::fromArray($response->json());
        }, $responses);
    }
}
