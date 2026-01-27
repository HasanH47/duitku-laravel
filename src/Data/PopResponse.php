<?php

namespace Duitku\Laravel\Data;

class PopResponse
{
    public function __construct(
        public string $merchantCode,
        public string $reference,
        public string $paymentUrl,
        public string $statusCode,
        public string $statusMessage
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantCode: $data['merchantCode'] ?? '',
            reference: $data['reference'] ?? '',
            paymentUrl: $data['paymentUrl'] ?? '',
            statusCode: $data['statusCode'] ?? '',
            statusMessage: $data['statusMessage'] ?? ''
        );
    }
}
