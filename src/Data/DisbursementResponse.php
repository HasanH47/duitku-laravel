<?php

namespace Duitku\Laravel\Data;

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
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            responseCode: $data['responseCode'] ?? '',
            responseDesc: $data['responseDesc'] ?? '',
            disburseId: $data['disburseId'] ?? null,
            accountName: $data['accountName'] ?? null,
            custRefNumber: $data['custRefNumber'] ?? null,
            bankCode: $data['bankCode'] ?? null,
            bankAccount: $data['bankAccount'] ?? null,
            amountTransfer: isset($data['amountTransfer']) ? (int) $data['amountTransfer'] : null
        );
    }
}
