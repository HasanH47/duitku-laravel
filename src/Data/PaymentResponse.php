<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Support\PaymentCode;
use Duitku\Laravel\Support\ResponseValidator;

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
        if (isset($data['statusCode']) && $data['statusCode'] !== PaymentCode::SUCCESS) {
            throw new DuitkuApiException('Duitku rejected the payment request.', (string) $data['statusCode']);
        }

        ResponseValidator::validate($data, [
            'merchantCode' => ['required', 'string'],
            'reference' => ['required', 'string'],
            'paymentUrl' => ['required', 'url:http,https'],
        ], 'payment');

        return new self(
            merchantCode: $data['merchantCode'] ?? '',
            reference: $data['reference'] ?? '',
            paymentUrl: $data['paymentUrl'] ?? '',
            statusCode: $data['statusCode'] ?? null,
            statusMessage: $data['statusMessage'] ?? null,
            vaNumber: $data['vaNumber'] ?? null,
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
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
        if ($this->statusCode === PaymentCode::SUCCESS || $this->statusCode === null) {
            return $this;
        }

        throw new DuitkuApiException($this->statusMessage ?? 'Payment request failed', $this->statusCode);
    }
}
