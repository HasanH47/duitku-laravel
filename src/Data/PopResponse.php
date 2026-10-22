<?php

namespace Duitku\Laravel\Data;

use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Support\PaymentCode;
use Duitku\Laravel\Support\ResponseValidator;

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
        if (isset($data['statusCode']) && $data['statusCode'] !== PaymentCode::SUCCESS) {
            throw new DuitkuApiException('Duitku rejected the POP invoice request.', (string) $data['statusCode']);
        }

        ResponseValidator::validate($data, [
            'merchantCode' => ['required', 'string'],
            'reference' => ['required', 'string'],
            'paymentUrl' => ['required', 'url:http,https'],
            'statusCode' => ['required', 'string', 'in:00'],
            'statusMessage' => ['required', 'string'],
        ], 'POP invoice');

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
