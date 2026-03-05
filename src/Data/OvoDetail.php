<?php

namespace Duitku\Laravel\Data;

/**
 * OVO payment detail for Account Link transactions.
 *
 * @see https://docs.duitku.com/api/id/#ovo-detail
 */
class OvoDetail
{
    /**
     * @param  array<int, array{paymentType: string, amount: int|string}>  $paymentDetails
     */
    public function __construct(
        public array $paymentDetails = []
    ) {}

    /**
     * Create a simple OVO Cash payment.
     */
    public static function cash(int $amount): self
    {
        return new self([
            ['paymentType' => 'CASH', 'amount' => $amount],
        ]);
    }

    public function toArray(): array
    {
        return [
            'paymentDetails' => $this->paymentDetails,
        ];
    }
}
