<?php

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('duitku.merchant_code', 'D1234');
    Config::set('duitku.api_key', 'test-api-key');
    Config::set('duitku.sandbox_mode', true);
});

test('pop create transaction generates correct signature and headers', function () {
    Http::fake([
        '*/api/merchant/createInvoice' => Http::response([
            'merchantCode' => 'D1234',
            'reference' => 'REF-POP-123',
            'paymentUrl' => 'https://app-sandbox.duitku.com/redirect_checkout?reference=REF-POP-123',
            'statusCode' => '00',
            'statusMessage' => 'Success',
        ], 200),
    ]);

    $request = new PaymentRequest(
        amount: 10000,
        merchantOrderId: 'INV-001',
        productDetails: 'Test Item',
        email: 'test@example.com',
        callbackUrl: 'https://merchant.test/callback',
        returnUrl: 'https://merchant.test/return'
    );

    $response = Duitku::pop()->createTransaction($request);

    expect($response->reference)->toBe('REF-POP-123')
        ->and($response->statusCode)->toBe('00')
        ->and(Duitku::pop()->scriptUrl())->toContain('app-sandbox.duitku.com');

    Http::assertSent(function ($request) {
        $hasHeaderCode = $request->hasHeader('x-duitku-merchantcode', 'D1234');
        $hasHeaderTimestamp = $request->hasHeader('x-duitku-timestamp');
        $hasHeaderSignature = $request->hasHeader('x-duitku-signature');

        return $request->url() === 'https://api-sandbox.duitku.com/api/merchant/createInvoice' &&
               $hasHeaderCode &&
               $hasHeaderTimestamp &&
               $hasHeaderSignature;
    });
});

test('pop status and payment methods use the documented API V2 endpoints', function () {
    Http::fake([
        '*/webapi/api/merchant/transactionStatus' => Http::response([
            'merchantOrderId' => 'POP-001',
            'reference' => 'REF-POP-001',
            'amount' => '10000',
            'statusCode' => '01',
            'statusMessage' => 'Pending',
        ]),
        '*/webapi/api/merchant/paymentmethod/getpaymentmethod' => Http::response([
            'responseCode' => '00',
            'paymentFee' => [['paymentMethod' => 'BC', 'paymentName' => 'BCA VA', 'totalFee' => '1000']],
        ]),
    ]);

    expect(Duitku::pop()->checkTransaction('POP-001')->statusCode)->toBe('01')
        ->and(Duitku::pop()->getPaymentMethod(10000)[0]->paymentMethod)->toBe('BC');

    Http::assertSent(fn ($request) => $request->url() ===
        'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus' &&
        $request['signature'] === hash_hmac('sha256', 'D1234POP-001', 'test-api-key'));
    Http::assertSentCount(2);
});
