<?php

namespace Duitku\Laravel\Data;

class PaymentRequest
{
    public function __construct(
        public int $amount,
        public string $merchantOrderId,
        public string $productDetails,
        public string $email,
        public string $paymentMethod = '', // Optional, leave empty to show all
        public ?string $customerVaName = null,
        public ?string $phoneNumber = null,
        public array $itemDetails = [],
        public array $customerDetail = [],
        public ?string $callbackUrl = null,
        public ?string $returnUrl = null,
        public ?int $expiryPeriod = null,
        public ?string $additionalParam = null,
        public ?string $merchantUserInfo = null,
        public ?array $accountLink = null,
        public ?array $creditCardDetail = null,
        public ?string $merchantCustomerId = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'paymentAmount' => $this->amount,
            'merchantOrderId' => $this->merchantOrderId,
            'productDetails' => $this->productDetails,
            'email' => $this->email,
            'paymentMethod' => $this->paymentMethod,
            'customerVaName' => $this->customerVaName,
            'phoneNumber' => $this->phoneNumber,
            'itemDetails' => $this->itemDetails,
            'customerDetail' => $this->customerDetail,
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'expiryPeriod' => $this->expiryPeriod,
            'additionalParam' => $this->additionalParam,
            'merchantUserInfo' => $this->merchantUserInfo,
            'accountLink' => $this->accountLink,
            'creditCardDetail' => $this->creditCardDetail,
            'merchantCustomerId' => $this->merchantCustomerId,
        ], fn ($value) => ! is_null($value) && $value !== [] && $value !== '');
    }
}
