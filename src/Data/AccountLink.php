<?php

namespace Duitku\Laravel\Data;

/**
 * Account Link parameter for OVO and Shopee Account Link payment methods.
 *
 * @see https://docs.duitku.com/api/id/#account-link
 */
class AccountLink
{
    public function __construct(
        public string $credentialCode,
        public ?OvoDetail $ovo = null,
        public ?ShopeeDetail $shopee = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'credentialCode' => $this->credentialCode,
            'ovo' => $this->ovo?->toArray(),
            'shopee' => $this->shopee?->toArray(),
        ], fn ($value) => ! is_null($value));
    }
}
