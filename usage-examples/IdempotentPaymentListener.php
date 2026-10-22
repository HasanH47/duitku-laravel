<?php

namespace Duitku\Laravel\Examples;

use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Exceptions\CallbackMismatchException;
use Illuminate\Support\Facades\DB;

/**
 * Example application listener. The application owns the orders table and fulfillment.
 * Add a UNIQUE constraint on (merchant_code, merchant_order_id).
 */
class IdempotentPaymentListener
{
    public function handle(DuitkuPaymentReceived $event): void
    {
        $callback = $event->callback;

        if ($callback->verifiedStatus === null || $callback->verifiedStatus->statusCode !== '00') {
            throw new CallbackMismatchException('Verify the provider transaction status before marking an order as paid.');
        }

        DB::transaction(function () use ($callback) {
            $order = DB::table('orders')
                ->where('merchant_code', $callback->merchantCode)
                ->where('merchant_order_id', $callback->merchantOrderId)
                ->lockForUpdate()
                ->first();

            if ($order === null || (int) $order->amount !== $callback->amount ||
                $order->duitku_reference !== $callback->reference) {
                throw new CallbackMismatchException('The callback does not match the stored order.');
            }

            if ($order->status === 'paid') {
                return;
            }

            DB::table('orders')->where('id', $order->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Persist an outbox record here if fulfillment must survive a process crash.
            // Do not call shipping, email, or other external services inside this transaction.
        });
    }
}
