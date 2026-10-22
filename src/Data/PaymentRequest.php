<?php

namespace Duitku\Laravel\Data;

use Illuminate\Support\Facades\Validator;

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
        $customerDetail = $this->serializeValue($this->customerDetail);

        if ($this->merchantCustomerId !== null) {
            $customerDetail = array_merge($customerDetail ?? [], ['merchantCustomerId' => $this->merchantCustomerId]);
        }

        return array_filter([
            'paymentAmount' => $this->amount,
            'merchantOrderId' => $this->merchantOrderId,
            'productDetails' => $this->productDetails,
            'email' => $this->email,
            'paymentMethod' => $this->paymentMethod,
            'customerVaName' => $this->customerVaName,
            'phoneNumber' => $this->phoneNumber,
            'itemDetails' => $this->serializeItems(),
            'customerDetail' => $customerDetail,
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $this->returnUrl,
            'expiryPeriod' => $this->expiryPeriod,
            'additionalParam' => $this->additionalParam,
            'merchantUserInfo' => $this->merchantUserInfo,
            'accountLink' => $this->serializeValue($this->accountLink),
            'creditCardDetail' => $this->serializeValue($this->creditCardDetail),
        ], fn ($value) => ! is_null($value) && $value !== [] && $value !== '');
    }

    /**
     * Serialize itemDetails — supports both raw arrays and ItemDetail objects.
     */
    private function serializeItems(): array
    {
        return array_map(
            fn (array|ItemDetail $item) => $item instanceof ItemDetail ? $item->toArray() : $item,
            array_values($this->itemDetails),
        );
    }

    public function validatedPayload(int $defaultExpiry, bool $pop = false): array
    {
        $payload = $this->toArray();
        $payload['expiryPeriod'] ??= $this->paymentMethod === 'JP' ? min($defaultExpiry, 10) : $defaultExpiry;

        Validator::make($payload, [
            'paymentAmount' => ['required', 'integer', 'min:1'],
            'merchantOrderId' => ['required', 'string', 'max:50'],
            'productDetails' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'paymentMethod' => [$pop ? 'sometimes' : 'required', 'string', 'size:2', 'not_in:GQ'],
            'customerVaName' => [$pop ? 'sometimes' : 'required', 'string', 'max:20'],
            'callbackUrl' => ['required', 'url:http,https', 'max:255'],
            'returnUrl' => ['required', 'url:http,https', 'max:255'],
            'expiryPeriod' => ['required', 'integer', 'min:1'],
            'itemDetails' => ['sometimes', 'array'],
            'itemDetails.*.name' => ['required', 'string', $pop ? 'max:50' : 'max:255'],
            'itemDetails.*.price' => ['required', 'integer', 'min:1'],
            'itemDetails.*.quantity' => ['required', 'integer', 'min:1'],
            'customerDetail.merchantCustomerId' => ['required_with:creditCardDetail.saveCardToken', 'string', 'max:100'],
            'creditCardDetail.saveCardToken' => ['sometimes', 'integer', 'in:1,2'],
        ])->validate();

        if (isset($payload['creditCardDetail']['saveCardToken'])) {
            $payload['creditCardDetail']['saveCardToken'] = (int) $payload['creditCardDetail']['saveCardToken'];
        }

        return $payload;
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
