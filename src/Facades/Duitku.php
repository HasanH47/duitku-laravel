<?php

namespace Duitku\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Duitku\Laravel\Data\PaymentFee[] paymentMethods(int $amount)
 * @method static \Duitku\Laravel\Data\PaymentResponse checkout(\Duitku\Laravel\Data\PaymentRequest $request)
 * @method static \Duitku\Laravel\Data\TransactionStatus checkStatus(string $orderId)
 * @method static \Duitku\Laravel\Data\BatchResult[] checkStatuses(array $orderIds)
 * @method static bool validateCallback(array $data)
 * @method static \Duitku\Laravel\Data\CallbackRequest verifyCallback(array $data)
 * @method static \Duitku\Laravel\Data\CallbackRequest handleCallback(array $data)
 * @method static \Duitku\Laravel\Services\Pop pop()
 * @method static \Duitku\Laravel\Disbursement disbursement()
 * @method static \Duitku\Laravel\Testing\DuitkuFake fake()
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
