<?php

namespace Duitku\Laravel\Data;

class CustomerDetail
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public ?Address $billingAddress = null,
        public ?Address $shippingAddress = null,
        public ?string $merchantCustomerId = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'billingAddress' => $this->billingAddress?->toArray(),
            'shippingAddress' => $this->shippingAddress?->toArray(),
            'merchantCustomerId' => $this->merchantCustomerId,
        ], fn ($value) => ! is_null($value));
    }
}
