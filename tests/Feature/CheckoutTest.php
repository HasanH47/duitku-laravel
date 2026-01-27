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
