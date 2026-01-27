<?php

use Duitku\Laravel\Data\CallbackRequest;
use Duitku\Laravel\Facades\Duitku;

it('can validate callback signature', function () {
    // Parameters
    $merchantCode = 'TEST_MERCHANT';
    $apiKey = 'TEST_API_KEY';
    $amount = 10000;
    $orderId = 'ORDER-SUCCESS';

    // Generate valid signature
    $signature = md5($merchantCode . $amount . $orderId . $apiKey);

    $payload = [
        'merchantCode' => $merchantCode,
        'amount' => $amount,
        'merchantOrderId' => $orderId,
        'signature' => $signature,
        'resultCode' => '00'
    ];

    // Act
    $isValid = Duitku::validateCallback($payload);

    // Assert
    expect($isValid)->toBeTrue();
});

it('rejects invalid callback signature', function () {
    $payload = [
        'merchantCode' => 'TEST_MERCHANT',
        'amount' => 10000,
        'merchantOrderId' => 'ORDER-FAKE',
        'signature' => 'invalid_signature',
        'resultCode' => '00'
    ];

    $isValid = Duitku::validateCallback($payload);

    expect($isValid)->toBeFalse();
});
