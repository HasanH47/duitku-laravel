<?php

namespace Duitku\Laravel\Data;

/**
 * Shopee payment detail for Account Link transactions.
 *
 * @see https://docs.duitku.com/api/id/#shopee-detail
 */
class ShopeeDetail
{
    /**
     * @param  string  $promo_ids  Kode voucher (max 50 chars)
     * @param  bool  $useCoin  Gunakan koin Shopee dari akun yang terhubung (khusus payment method SL)
     */
    public function __construct(
        public string $promo_ids = '',
        public bool $useCoin = false
    ) {}

    public function toArray(): array
    {
        return [
            'promo_ids' => $this->promo_ids,
            'useCoin' => $this->useCoin,
        ];
    }
}
