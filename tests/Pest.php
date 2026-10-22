<?php

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class)->in(__DIR__);

function duitkuTestPayment(array $overrides = []): PaymentRequest
{
    return new PaymentRequest(...array_replace([
        'amount' => 10000,
        'merchantOrderId' => 'ORDER-001',
        'productDetails' => 'Test Product',
        'email' => 'customer@example.test',
        'paymentMethod' => 'VC',
        'customerVaName' => 'Test Customer',
        'callbackUrl' => 'https://merchant.test/callback',
        'returnUrl' => 'https://merchant.test/return',
    ], $overrides));
}

function duitkuTestCallback(array $overrides = []): array
{
    $payload = array_replace([
        'merchantCode' => 'TEST_MERCHANT',
        'amount' => '10000',
        'merchantOrderId' => 'ORDER-001',
        'reference' => 'REF-001',
        'resultCode' => '00',
    ], $overrides);
    $payload['signature'] = hash_hmac(
        'sha256',
        $payload['merchantCode'].$payload['amount'].$payload['merchantOrderId'],
        'TEST_API_KEY',
    );

    return $payload;
}

function duitkuTestFakeStatus(array $callback, string $statusCode): void
{
    Http::fake([
        '*/webapi/api/merchant/transactionStatus' => Http::response([
            'merchantOrderId' => $callback['merchantOrderId'],
            'reference' => $callback['reference'],
            'amount' => $callback['amount'],
            'statusCode' => $statusCode,
            'statusMessage' => 'Synthetic status',
        ]),
    ]);
}
