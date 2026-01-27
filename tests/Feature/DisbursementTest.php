<?php

use Duitku\Laravel\Data\DisbursementInfo;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('duitku.merchant_code', 'D12345');
    Config::set('duitku.api_key', 'test-api-key');
    Config::set('duitku.user_id', '3551');
    Config::set('duitku.email', 'test@example.com');
    Config::set('duitku.sandbox_mode', true);
});

test('bank inquiry generates correct signature and parses response', function () {
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

    $response = Duitku::disbursement()->bankInquiry($info);

    expect($response->responseCode)->toBe('00')
        ->and($response->accountName)->toBe('JOHN DOE')
        ->and($response->disburseId)->toBe('DISB-1001');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/inquirysandbox' &&
               $request['userId'] === 3551 &&
               !empty($request['signature']);
    });
});

test('transfer executes successfully', function () {
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

    $response = Duitku::disbursement()->transfer(
        disburseId: 'DISB-1001',
        info: $info,
        accountName: 'JOHN DOE',
        custRefNumber: 'REF-001'
    );

    expect($response->responseCode)->toBe('00')
        ->and($response->responseDesc)->toBe('Transaction Success');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/disbursement/transfersandbox' &&
               $request['disburseId'] === 'DISB-1001' &&
               !empty($request['signature']);
    });
});
