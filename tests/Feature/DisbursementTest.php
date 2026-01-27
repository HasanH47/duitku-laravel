<?php

use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Support\DisbursementCode;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('duitku.merchant_code', 'D12345');
    Config::set('duitku.api_key', 'test-api-key');
    Config::set('duitku.user_id', '3551');
    Config::set('duitku.email', 'test@example.com');
    Config::set('duitku.sandbox_mode', true);
});

test('transfer inquiry generates correct signature', function () {
    Http::fake([
        '*/webapi/api/disbursement/inquirysandbox' => Http::response([
            'responseCode' => '00',
            'responseDesc' => 'Success',
            'accountName' => 'JOHN DOE',
            'bankCode' => '014',
            'disburseId' => 'DISB-1001',
            'custRefNumber' => 'REF-001',
        ], 200),
    ]);

    $info = new DisbursementInfo(
        amountTransfer: 50000,
        bankAccount: '1234567890',
        bankCode: '014',
        purpose: 'Test Withdrawal'
    );

    // New Syntax: ->transfer()->inquiry()
    $response = Duitku::disbursement()->transfer()->inquiry($info);

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS)
        ->and($response->accountName)->toBe('JOHN DOE')
        ->and($response->disburseId)->toBe('DISB-1001');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/inquirysandbox' &&
               $request['userId'] === 3551 &&
               ! empty($request['signature']);
    });
});

test('transfer execute works', function () {
    Http::fake([
        '*/webapi/api/disbursement/transfersandbox' => Http::response([
            'responseCode' => '00',
            'responseDesc' => 'Transaction Success',
            'disburseId' => 'DISB-1001',
        ], 200),
    ]);

    $info = new DisbursementInfo(
        amountTransfer: 50000,
        bankAccount: '1234567890',
        bankCode: '014',
        purpose: 'Test Withdrawal'
    );

    // New Syntax: ->transfer()->execute()
    $response = Duitku::disbursement()->transfer()->execute(
        disburseId: 'DISB-1001',
        info: $info,
        accountName: 'JOHN DOE',
        custRefNumber: 'REF-001'
    );

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS);
});

test('clearing inquiry generates correct signature', function () {
    Http::fake([
        '*/webapi/api/disbursement/inquiryclearingsandbox' => Http::response([
            'responseCode' => '00',
            'responseDesc' => 'Success',
            'type' => 'BIFAST',
            'disburseId' => 'DISB-CLR-1',
        ], 200),
    ]);

    $info = new DisbursementInfo(
        amountTransfer: 1000000,
        bankAccount: '1234567890',
        bankCode: '014',
        purpose: 'Clearing Test',
        type: 'BIFAST'
    );

    // New Syntax: ->clearing()->inquiry()
    $response = Duitku::disbursement()->clearing()->inquiry($info);

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS)
        ->and($response->type)->toBe('BIFAST');
});

test('clearing execute works', function () {
    Http::fake([
        '*/webapi/api/disbursement/transferclearingsandbox' => Http::response([
            'responseCode' => '00',
            'responseDesc' => 'Success',
            'disburseId' => 'DISB-CLR-1',
        ], 200),
    ]);

    $info = new DisbursementInfo(
        amountTransfer: 1000000,
        bankAccount: '1234567890',
        bankCode: '014',
        purpose: 'Clearing Test',
        type: 'BIFAST'
    );

    $response = Duitku::disbursement()->clearing()->execute(
        disburseId: 'DISB-CLR-1',
        info: $info,
        accountName: 'JOHN DOE',
        custRefNumber: 'REF-001'
    );

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS);
});

test('clearing validate callback works', function () {
    $data = [
        'bankCode' => '014',
        'bankAccount' => '123',
        'accountName' => 'JOHN',
        'custRefNumber' => 'REF',
        'amountTransfer' => '1000',
        'disburseId' => 'DSB',
    ];

    $signature = hash('sha256', 'test@example.com'.'014'.'123'.'JOHN'.'REF'.'1000'.'DSB'.'test-api-key');
    $data['signature'] = $signature;

    expect(Duitku::disbursement()->clearing()->validateCallback($data))->toBeTrue();
});

test('cash out inquiry generated correct signature', function () {
    Http::fake([
        '*/api/cashout/inquiry' => Http::response([
            'responseCode' => '00',
            'token' => '123456',
            'disburseId' => 'CS-1001',
        ], 200),
    ]);

    $info = new \Duitku\Laravel\Data\CashOutInfo(
        amountTransfer: 100000,
        bankCode: '2010', // Indomaret
        accountName: 'John Doe',
        accountIdentity: '1234567890123456',
        phoneNumber: '08123456789'
    );

    $response = Duitku::disbursement()->cashOut()->inquiry($info);

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS)
        ->and($response->token)->toBe('123456');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/api/cashout/inquiry') &&
               ! empty($request['signature']);
    });
});

test('finance status check generates correct signature', function () {
    Http::fake([
        '*/webapi/api/disbursement/inquirystatus' => Http::response([
            'responseCode' => '00',
            'responseDesc' => 'Success',
            'bankCode' => '014',
            'amountTransfer' => 50000,
        ], 200),
    ]);

    $response = Duitku::disbursement()->finance()->status('DISB-1001');

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS)
        ->and($response->amountTransfer)->toBe(50000);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/inquirystatus' &&
               $request['disburseId'] === 'DISB-1001' &&
               ! empty($request['signature']);
    });
});

test('finance check balance generates correct signature', function () {
    Http::fake([
        '*/webapi/api/disbursement/checkbalance' => Http::response([
            'responseCode' => '00',
            'balance' => 1000000,
            'effectiveBalance' => 900000,
        ], 200),
    ]);

    $response = Duitku::disbursement()->finance()->balance();

    expect($response->responseCode)->toBe(DisbursementCode::SUCCESS)
        ->and($response->balance)->toBe(1000000.0)
        ->and($response->effectiveBalance)->toBe(900000.0);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/checkbalance' &&
               ! empty($request['signature']);
    });
});

test('finance list bank generates correct signature', function () {
    Http::fake([
        '*/webapi/api/disbursement/listBank' => Http::response([
            'responseCode' => '00',
            'listBank' => [
                ['bankCode' => '014', 'bankName' => 'BCA'],
                ['bankCode' => '008', 'bankName' => 'MANDIRI'],
            ],
        ], 200),
    ]);

    $banks = Duitku::disbursement()->finance()->listBank();

    expect($banks)->toBeArray()
        ->and($banks[0]['bankCode'])->toBe('014');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/listBank' &&
               ! empty($request['signature']);
    });
});
