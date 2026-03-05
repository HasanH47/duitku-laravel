<?php

namespace Duitku\Laravel\Data;

class Address
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $address,
        public string $city,
        public string $postalCode,
        public string $phone,
        public string $countryCode = 'ID'
    ) {}

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'address' => $this->address,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'phone' => $this->phone,
            'countryCode' => $this->countryCode,
        ];
    }
}
