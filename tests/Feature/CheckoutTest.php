<?php

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Http;

it('can create a payment invoice', function () {
    // Arrange
    Http::fake([
        '*/webapi/api/merchant/v2/inquiry' => Http::response([
            'merchantCode' => 'TEST_MERCHANT',
            'reference' => 'REF123',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/xyz',
            'statusCode' => '00',
            'statusMessage' => 'Success',
        ], 200),
    ]);

    $request = new PaymentRequest(
        amount: 10000,
        merchantOrderId: 'ORDER-001',
        productDetails: 'Test Product',
        email: 'test@example.com',
        paymentMethod: 'VC'
    );

    // Act
    $response = Duitku::checkout($request);

    // Assert
    expect($response->paymentUrl)->toBe('https://sandbox.duitku.com/payment/xyz');
    expect($response->reference)->toBe('REF123');
});

it('can get payment methods', function () {
    Http::fake([
        '*/webapi/api/merchant/paymentmethod/getpaymentmethod' => Http::response([
            'paymentFee' => [
                ['paymentMethod' => 'VC', 'paymentName' => 'Visa/MasterCard'],
            ],
        ], 200),
    ]);

    $methods = Duitku::paymentMethods(10000);

    expect($methods)->toBeArray()
        ->and($methods[0]['paymentMethod'])->toBe('VC');
});

it('can check transaction status', function () {
    Http::fake([
        '*/webapi/api/merchant/transactionStatus' => Http::response([
            'merchantOrderId' => 'ORD-123',
            'reference' => 'REF-123',
            'amount' => '10000',
            'statusCode' => '00',
            'statusMessage' => 'Success',
        ], 200),
    ]);

    $status = Duitku::checkStatus('ORD-123');

    expect($status->statusCode)->toBe('00')
        ->and($status->merchantOrderId)->toBe('ORD-123');
});

it('can validate callback signature', function () {
    Config::set('duitku.merchant_code', 'M123');
    Config::set('duitku.api_key', 'test-key');

    $amount = 10000;
    $orderId = 'ORD-001';
    $signature = md5('M123'.$amount.$orderId.'test-key');

    $data = [
        'merchantCode' => 'M123',
        'amount' => $amount,
        'merchantOrderId' => $orderId,
        'signature' => $signature,
    ];

    expect(Duitku::validateCallback($data))->toBeTrue();
});
