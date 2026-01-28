<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Exceptions\InsufficientFundsException;
use Duitku\Laravel\Support\DisbursementCode;

class DisbursementResponse
{
    public function __construct(
        public string $responseCode,
        public string $responseDesc,
        public ?string $disburseId = null,
        public ?string $accountName = null,
        public ?string $custRefNumber = null,
        public ?string $bankCode = null,
        public ?string $bankAccount = null,
        public ?int $amountTransfer = null,
        public ?string $type = null,
        public ?string $token = null,
        public ?string $pin = null,
        public ?float $balance = null,
        public ?float $effectiveBalance = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            responseCode: $data['responseCode'] ?? '',
            responseDesc: $data['responseDesc'] ?? (($data['statusDesc'] ?? '')), // Fallback
            disburseId: $data['disburseId'] ?? null,
            accountName: $data['accountName'] ?? null,
            custRefNumber: $data['custRefNumber'] ?? null,
            bankCode: $data['bankCode'] ?? null,
            bankAccount: $data['bankAccount'] ?? null,
            amountTransfer: isset($data['amountTransfer']) ? (int) $data['amountTransfer'] : null,
            type: $data['type'] ?? null,
            token: isset($data['token']) ? (string) $data['token'] : null,
            pin: $data['pin'] ?? null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            effectiveBalance: isset($data['effectiveBalance']) ? (float) $data['effectiveBalance'] : null
        );
    }

    /**
     * Throw an exception if the response indicates a failure.
     *
     * @throws DuitkuApiException
     */
    public function throwIfFailed(): self
    {
        if ($this->responseCode === DisbursementCode::SUCCESS) {
            return $this;
        }

        if ($this->responseCode === DisbursementCode::INSUFFICIENT_FUNDS) {
            throw new InsufficientFundsException($this->responseDesc, $this->responseCode);
        }

        // Generic API Exception for other codes
        throw new DuitkuApiException($this->responseDesc, $this->responseCode);
    }
}
