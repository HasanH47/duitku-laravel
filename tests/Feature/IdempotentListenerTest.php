<?php

use Duitku\Laravel\Data\CallbackRequest;
use Duitku\Laravel\Data\TransactionStatus;
use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Examples\IdempotentPaymentListener;
use Duitku\Laravel\Exceptions\CallbackMismatchException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/../../usage-examples/IdempotentPaymentListener.php';

beforeEach(function () {
    config(['database.default' => 'testing']);
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('merchant_code');
        $table->string('merchant_order_id');
        $table->integer('amount');
        $table->string('duitku_reference');
        $table->string('status');
        $table->timestamp('paid_at')->nullable();
        $table->unique(['merchant_code', 'merchant_order_id']);
    });
    DB::table('orders')->insert([
        'merchant_code' => 'TEST_MERCHANT', 'merchant_order_id' => 'ORDER-001',
        'amount' => 10000, 'duitku_reference' => 'REF-001', 'status' => 'pending',
    ]);
});

function duitkuTestVerifiedEvent(): DuitkuPaymentReceived
{
    $callback = CallbackRequest::fromArray(duitkuTestCallback());
    $callback->verifiedStatus = new TransactionStatus('ORDER-001', 'REF-001', '10000', '00', 'Success');

    return new DuitkuPaymentReceived($callback);
}

it('keeps duplicate callback delivery from changing a paid order twice', function () {
    $listener = new IdempotentPaymentListener;
    $event = duitkuTestVerifiedEvent();
    $listener->handle($event);
    $paidAt = DB::table('orders')->value('paid_at');
    $this->travel(5)->minutes();
    $listener->handle($event);

    expect(DB::table('orders')->value('status'))->toBe('paid')
        ->and(DB::table('orders')->value('paid_at'))->toBe($paidAt);
});

it('refuses to mark a differently priced or referenced order as paid', function (string $field, mixed $value) {
    DB::table('orders')->update([$field => $value]);

    expect(fn () => (new IdempotentPaymentListener)->handle(duitkuTestVerifiedEvent()))
        ->toThrow(CallbackMismatchException::class);
    expect(DB::table('orders')->value('status'))->toBe('pending');
})->with([['amount', 20000], ['duitku_reference', 'OTHER']]);

it('refuses unverified payment events', function () {
    $event = duitkuTestVerifiedEvent();
    $event->callback->verifiedStatus = null;

    expect(fn () => (new IdempotentPaymentListener)->handle($event))->toThrow(CallbackMismatchException::class);
    expect(DB::table('orders')->value('status'))->toBe('pending');
});
