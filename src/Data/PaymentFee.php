<?php

namespace Duitku\Laravel\Data;

/**
 * Payment Fee response from Get Payment Method API.
 *
 * @see https://docs.duitku.com/api/id/#payment-fee
 */
class PaymentFee
{
    public function __construct(
        public string $paymentMethod,
        public string $paymentName,
        public string $paymentImage,
        public string $totalFee
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            paymentMethod: $data['paymentMethod'] ?? '',
            paymentName: $data['paymentName'] ?? '',
            paymentImage: $data['paymentImage'] ?? '',
            totalFee: $data['totalFee'] ?? '0'
        );
    }

    /**
     * Convert an array of raw payment fee data to PaymentFee objects.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return PaymentFee[]
     */
    public static function fromList(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }
}
