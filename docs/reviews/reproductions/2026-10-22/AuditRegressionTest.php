<?php

use Duitku\Laravel\Data\CreditCardDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Http::preventStrayRequests();
});

function auditPayment(): PaymentRequest
{
    return new PaymentRequest(10000, 'AUDIT-001', 'Synthetic item', 'audit@example.invalid', 'VC');
}

function auditLegacyCallback(): array
{
    return [
        'merchantCode' => 'TEST_MERCHANT',
        'amount' => '10000',
        'merchantOrderId' => 'AUDIT-001',
        'reference' => 'SYNTHETIC-REFERENCE',
        'resultCode' => '00',
        'signature' => md5('TEST_MERCHANT'.'10000'.'AUDIT-001'.'TEST_API_KEY'),
    ];
}

test('audit checkout uses documented HMAC signature', function () {
    Http::fake(['*' => Http::response(['statusCode' => '00', 'reference' => 'REF'], 200)]);
    Duitku::checkout(auditPayment());
    Http::assertSent(fn ($r) => $r['signature'] === hash_hmac('sha256', 'TEST_MERCHANT'.'AUDIT-001'.'10000', 'TEST_API_KEY'));
});

test('audit POP uses documented HMAC header', function () {
    Http::fake(['*' => Http::response(['statusCode' => '00'], 200)]);
    Duitku::pop()->createTransaction(auditPayment());
    Http::assertSent(fn ($r) => $r->header('x-duitku-signature')[0] === hash_hmac('sha256', 'TEST_MERCHANT'.$r->header('x-duitku-timestamp')[0], 'TEST_API_KEY'));
});

test('audit accepts documented HMAC callback', function () {
    $data = auditLegacyCallback();
    $data['signature'] = hash_hmac('sha256', 'TEST_MERCHANT'.'10000'.'AUDIT-001', 'TEST_API_KEY');
    expect(Duitku::validateCallback($data))->toBeTrue();
});

test('audit accepts documented clearing callback signature', function () {
    Config::set('duitku.email', 'merchant@example.invalid');
    $data = ['disburseId' => '123', 'custRefNumber' => '456', 'bankCode' => '014', 'bankAccount' => '000000000', 'accountName' => 'SYNTHETIC', 'amountTransfer' => 10000];
    $data['signature'] = hash('sha256', 'merchant@example.invalid'.'014'.'000000000'.'SYNTHETIC'.'456'.'10000'.'123'.'TEST_API_KEY');
    expect(Duitku::disbursement()->clearing()->validateCallback($data))->toBeTrue();
});

test('audit rejects changed merchant identity even with a captured valid signature', function () {
    $data = auditLegacyCallback();
    $data['merchantCode'] = 'OTHER-MERCHANT';
    expect(Duitku::validateCallback($data))->toBeFalse();
});

test('audit rejects malformed amount rather than normalizing signed data', function () {
    $data = auditLegacyCallback();
    $data['amount'] = '10000junk';
    expect(Duitku::validateCallback($data))->toBeFalse();
});

test('audit duplicate callbacks do not duplicate payment event', function () {
    Event::fake();
    Duitku::handleCallback(auditLegacyCallback());
    Duitku::handleCallback(auditLegacyCallback());
    Event::assertDispatchedTimes(DuitkuPaymentReceived::class, 1);
});

test('audit signed callback missing resultCode is rejected', function () {
    Event::fake();
    $data = auditLegacyCallback();
    unset($data['resultCode']);
    expect(fn () => Duitku::handleCallback($data))->toThrow(Throwable::class);
});

test('audit logs redact customer information and signatures', function () {
    Config::set('duitku.log_channel', 'audit');
    $logged = null;
    $logger = Mockery::mock();
    $logger->shouldReceive('debug')->once()->andReturnUsing(function ($message, $context) use (&$logged) {
        $logged = $context;
    });
    Log::shouldReceive('channel')->with('audit')->andReturn($logger);
    Http::fake(['*' => Http::response(['statusCode' => '00'], 200)]);
    Duitku::checkout(auditPayment());
    expect($logged['body'])->not->toContain('audit@example.invalid')
        ->not->toContain(md5('TEST_MERCHANT'.'AUDIT-001'.'10000'.'TEST_API_KEY'));
});

test('audit writes do not automatically retry an ambiguous server error', function () {
    Config::set('duitku.retry_times', 2);
    Config::set('duitku.retry_sleep', 0);
    Http::fake(['*' => Http::sequence()->push(['error' => 'ambiguous'], 500)->push(['statusCode' => '00'], 200)]);
    try {
        Duitku::checkout(auditPayment());
    } catch (Throwable) {
    }
    Http::assertSentCount(1);
});

test('audit pooled HTTP 500 remains an explicit failure', function () {
    Http::fake(['*' => Http::response(['error' => 'server error'], 500)]);
    expect(fn () => Duitku::checkStatuses(['AUDIT-001']))->toThrow(Throwable::class);
});

test('audit pooled requests use configured timeout', function () {
    Config::set('duitku.timeout', 7);
    $timeout = null;
    Http::fake(function ($request, $options) use (&$timeout) {
        $timeout = $options['timeout'] ?? null;

        return Http::response(['statusCode' => '00'], 200);
    });
    Duitku::checkStatuses(['AUDIT-001']);
    expect($timeout)->toBe(7);
});

test('audit default expiry reaches invoice payload', function () {
    Config::set('duitku.default_expiry', 17);
    Http::fake(['*' => Http::response(['statusCode' => '00'], 200)]);
    Duitku::checkout(auditPayment());
    Http::assertSent(fn ($r) => $r['expiryPeriod'] === 17);
});

test('audit tokenization mode 2 remains integer 2 in JSON', function () {
    $detail = new CreditCardDetail(saveCardToken: 2);
    expect($detail->toArray()['saveCardToken'])->toBe(2);
});

test('audit customer identifier is nested in customerDetail', function () {
    $request = auditPayment();
    $request->customerDetail = new CustomerDetail('Synthetic', 'User', 'audit@example.invalid', '08000000000');
    $request->merchantCustomerId = 'CUSTOMER-AUDIT';
    expect($request->toArray()['customerDetail']['merchantCustomerId'] ?? null)->toBe('CUSTOMER-AUDIT');
});

test('audit valid signature with changed resultCode requires independent status verification', function () {
    Event::fake();
    Http::fake(['*' => Http::response(['statusCode' => '02'], 200)]);
    $data = auditLegacyCallback();
    $data['resultCode'] = '02';
    expect(Duitku::validateCallback($data))->toBeTrue();
    $data['resultCode'] = '00';
    expect(Duitku::validateCallback($data))->toBeTrue();
    Duitku::handleCallback($data);
    Event::assertNotDispatched(DuitkuPaymentReceived::class);
});
