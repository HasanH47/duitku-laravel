<?php

namespace Duitku\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection paymentMethods(int $amount)
 * @method static \Duitku\Laravel\Data\PaymentResponse checkout(\Duitku\Laravel\Data\PaymentRequest $request)
 * @method static \Duitku\Laravel\Data\TransactionStatus checkStatus(string $orderId)
 * @method static \Illuminate\Support\Collection checkStatuses(array $orderIds)
 *
 * @see \Duitku\Laravel\Duitku
 */
class Duitku extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'duitku';
    }
}
