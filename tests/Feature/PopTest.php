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
        email: 'test@example.com'
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
