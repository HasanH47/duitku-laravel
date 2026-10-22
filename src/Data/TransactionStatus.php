<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Support\ResponseValidator;

class TransactionStatus
{
    public function __construct(
        public string $merchantOrderId,
        public string $reference,
        public string $amount,
        public string $statusCode,
        public string $statusMessage,
        public ?string $fee = null
    ) {}

    public static function fromArray(array $data): self
    {
        ResponseValidator::validate($data, [
            'merchantOrderId' => ['required', 'string'],
            'reference' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'statusCode' => ['required', 'string', 'in:00,01,02'],
            'statusMessage' => ['required', 'string'],
        ], 'transaction status');

        return new self(
            merchantOrderId: $data['merchantOrderId'] ?? '',
            reference: $data['reference'] ?? '',
            amount: (string) $data['amount'],
            statusCode: $data['statusCode'] ?? '',
            statusMessage: $data['statusMessage'] ?? '',
            fee: $data['fee'] ?? null
        );
    }
}
