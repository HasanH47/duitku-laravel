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
        public array|CustomerDetail|null $customerDetail = null,
        public ?string $callbackUrl = null,
        public ?string $returnUrl = null,
        public ?int $expiryPeriod = null,
        public ?string $additionalParam = null,
        public ?string $merchantUserInfo = null,
        public array|AccountLink|null $accountLink = null,
        public array|CreditCardDetail|null $creditCardDetail = null,
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
            'itemDetails' => $this->serializeItems(),
            'customerDetail' => $this->serializeValue($this->customerDetail),
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'expiryPeriod' => $this->expiryPeriod,
            'additionalParam' => $this->additionalParam,
            'merchantUserInfo' => $this->merchantUserInfo,
            'accountLink' => $this->serializeValue($this->accountLink),
            'creditCardDetail' => $this->serializeValue($this->creditCardDetail),
            'merchantCustomerId' => $this->merchantCustomerId,
        ], fn ($value) => ! is_null($value) && $value !== [] && $value !== '');
    }

    /**
     * Serialize itemDetails — supports both raw arrays and ItemDetail objects.
     */
    private function serializeItems(): array
    {
        if (empty($this->itemDetails)) {
            return [];
        }

        // If first item is an ItemDetail object, convert all
        if (($this->itemDetails[0] ?? null) instanceof ItemDetail) {
            return ItemDetail::toPayload($this->itemDetails);
        }

        return $this->itemDetails;
    }

    /**
     * Serialize a value that can be either a DTO or a raw array.
     */
    private function serializeValue(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }
}
