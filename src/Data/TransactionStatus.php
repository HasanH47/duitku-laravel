<?php

namespace Duitku\Laravel\Data;

class TransactionStatus
{
    public function __construct(
        public string $merchantOrderId,
        public string $reference,
        public string $amount,
        public string $statusCode,
        public string $statusMessage
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantOrderId: $data['merchantOrderId'] ?? '',
            reference: $data['reference'] ?? '',
            amount: $data['amount'] ?? '0',
            statusCode: $data['statusCode'] ?? '',
            statusMessage: $data['statusMessage'] ?? ''
        );
    }
}
