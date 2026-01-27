<?php

namespace Duitku\Laravel\Data;

class PaymentResponse
{
    public function __construct(
        public string $merchantCode,
        public string $reference,
        public string $paymentUrl,
        public ?string $statusCode = null,
        public ?string $statusMessage = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantCode: $data['merchantCode'] ?? '',
            reference: $data['reference'] ?? '',
            paymentUrl: $data['paymentUrl'] ?? '',
            statusCode: $data['statusCode'] ?? null,
            statusMessage: $data['statusMessage'] ?? null
        );
    }
}
