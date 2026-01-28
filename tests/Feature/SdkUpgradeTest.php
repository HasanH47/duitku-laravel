<?php

use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Events\DuitkuPaymentFailed;
use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Exceptions\InsufficientFundsException;
use Duitku\Laravel\Exceptions\InvalidSignatureException;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('duitku.merchant_code', 'D1234');
    Config::set('duitku.api_key', 'test-key');
});

test('handleCallback dispatches success event and returns data', function () {
    Event::fake();

    $data = [
        'merchantCode' => 'D1234',
        'amount' => '10000',
        'merchantOrderId' => 'INV-001',
        'resultCode' => '00',
    ];
    $data['signature'] = md5('D1234'.'10000'.'INV-001'.'test-key');

    $callback = Duitku::handleCallback($data);

    expect($callback->merchantOrderId)->toBe('INV-001');
    Event::assertDispatched(DuitkuPaymentReceived::class);
});

test('handleCallback dispatches failed event on failure result', function () {
    Event::fake();

    $data = [
        'merchantCode' => 'D1234',
        'amount' => '10000',
        'merchantOrderId' => 'INV-002',
        'resultCode' => '02',
    ];
    $data['signature'] = md5('D1234'.'10000'.'INV-002'.'test-key');

    Duitku::handleCallback($data);

    Event::assertDispatched(DuitkuPaymentFailed::class);
});

test('handleCallback throws exception on invalid signature', function () {
    $data = [
        'merchantCode' => 'D1234',
        'amount' => '10000',
        'merchantOrderId' => 'INV-001',
        'signature' => 'invalid-sig',
    ];

    Duitku::handleCallback($data);
})->throws(InvalidSignatureException::class);

test('bulkInquiry works in parallel', function () {
    Http::fake([
        '*/webapi/api/disbursement/inquirysandbox' => Http::sequence()
            ->push(['responseCode' => '00', 'accountName' => 'USER A'])
            ->push(['responseCode' => '00', 'accountName' => 'USER B']),
    ]);

    $infos = [
        new DisbursementInfo(10000, '111', '014', 'Test 1'),
        new DisbursementInfo(20000, '222', '014', 'Test 2'),
    ];

    $results = Duitku::disbursement()->transfer()->bulkInquiry($infos);

    expect($results)->toHaveCount(2)
        ->and($results[0]->accountName)->toBe('USER A')
        ->and($results[1]->accountName)->toBe('USER B');
});

test('throwIfFailed throws correct exception for insufficient funds', function () {
    Http::fake([
        '*/webapi/api/disbursement/inquirysandbox' => Http::response([
            'responseCode' => '-510',
            'responseDesc' => 'Insufficient Balance',
        ], 200),
    ]);

    $info = new DisbursementInfo(99999999, '123', '014', 'Test');

    expect(fn () => Duitku::disbursement()->transfer()->inquiry($info)->throwIfFailed())
        ->toThrow(InsufficientFundsException::class);
});
