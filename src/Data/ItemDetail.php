<?php

namespace Duitku\Laravel\Data;

class ItemDetail
{
    public function __construct(
        public string $name,
        public int $price,
        public int $quantity
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }

    /**
     * Convert an array of ItemDetail objects to a plain array.
     *
     * @param  ItemDetail[]  $items
     */
    public static function toPayload(array $items): array
    {
        return array_map(fn (self $item) => $item->toArray(), $items);
    }
}
