<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Support\PaymentCode;

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

    /**
     * Throw an exception if the response indicates a failure.
     *
     * @throws DuitkuApiException
     */
    public function throwIfFailed(): self
    {
        if ($this->statusCode === PaymentCode::SUCCESS) {
            return $this;
        }

        throw new DuitkuApiException($this->statusMessage, $this->statusCode);
    }
}
