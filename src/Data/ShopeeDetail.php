<?php

namespace Duitku\Laravel\Data;

/**
 * Shopee payment detail for Account Link transactions.
 *
 * @see https://docs.duitku.com/api/id/#shopee-detail
 */
class ShopeeDetail
{
    public function __construct(
        public bool $useCoin = false,
        public string $promoId = ''
    ) {}

    public function toArray(): array
    {
        return [
            'useCoin' => $this->useCoin,
            'promoId' => $this->promoId,
        ];
    }
}
