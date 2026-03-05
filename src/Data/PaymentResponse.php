<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Support\PaymentCode;

class PaymentResponse
{
    public function __construct(
        public string $merchantCode,
        public string $reference,
        public string $paymentUrl,
        public ?string $statusCode = null,
        public ?string $statusMessage = null,
        public ?string $vaNumber = null,
        public ?string $amount = null,
        public ?string $qrString = null,
        public ?string $appUrl = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantCode: $data['merchantCode'] ?? '',
            reference: $data['reference'] ?? '',
            paymentUrl: $data['paymentUrl'] ?? '',
            statusCode: $data['statusCode'] ?? null,
            statusMessage: $data['statusMessage'] ?? null,
            vaNumber: $data['vaNumber'] ?? null,
            amount: $data['amount'] ?? null,
            qrString: $data['qrString'] ?? null,
            appUrl: $data['AppUrl'] ?? $data['appUrl'] ?? null
        );
    }

    /**
     * Throw an exception if the response indicates a failure.
     *
     * @throws DuitkuApiException
     */
    public function throwIfFailed(): self
    {
        if ($this->statusCode === PaymentCode::SUCCESS || empty($this->statusCode)) {
            return $this;
        }

        throw new DuitkuApiException($this->statusMessage ?? 'Payment request failed', $this->statusCode);
    }
}
