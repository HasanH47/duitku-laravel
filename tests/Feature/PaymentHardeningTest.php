<?php

use Duitku\Laravel\Data\CreditCardDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\PaymentResponse;
use Duitku\Laravel\Enums\PaymentMethod;
use Duitku\Laravel\Events\DuitkuCallbackReceived;
use Duitku\Laravel\Events\DuitkuPaymentFailed;
use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Exceptions\CallbackMismatchException;
use Duitku\Laravel\Exceptions\ConfigurationException;
use Duitku\Laravel\Exceptions\MalformedResponseException;
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

function duitkuTestInvoice(): array
{
    return [
        'merchantCode' => 'TEST_MERCHANT',
        'reference' => 'REF-001',
        'paymentUrl' => 'https://sandbox.duitku.com/pay/REF-001',
        'statusCode' => '00',
        'statusMessage' => 'Success',
    ];
}

it('signs API invoices using HMAC with the API key as key', function () {
    Http::fake(['*' => Http::response(duitkuTestInvoice())]);
    Duitku::checkout(duitkuTestPayment());

    Http::assertSent(fn ($request) => $request['signature'] ===
        hash_hmac('sha256', 'TEST_MERCHANTORDER-00110000', 'TEST_API_KEY'));
});

it('signs POP headers using HMAC', function () {
    Http::fake(['*' => Http::response(duitkuTestInvoice())]);
    Duitku::pop()->createTransaction(duitkuTestPayment());

    Http::assertSent(fn ($request) => $request->header('x-duitku-signature')[0] ===
        hash_hmac('sha256', 'TEST_MERCHANT'.$request->header('x-duitku-timestamp')[0], 'TEST_API_KEY'));
});

it('only enables legacy signatures through an explicit setting', function () {
    $data = duitkuTestCallback();
    $data['signature'] = md5('TEST_MERCHANT10000ORDER-001TEST_API_KEY');
    expect(Duitku::validateCallback($data))->toBeFalse();

    app()->forgetInstance('duitku');
    app()->forgetInstance(DuitkuConfig::class);
    Duitku::clearResolvedInstance('duitku');
    Config::set('duitku.signature_mode', 'legacy');

    expect(Duitku::validateCallback($data))->toBeTrue();
});

it('refuses empty credentials before accepting a callback', function (string $field) {
    Config::set('duitku.'.$field, '');
    Event::fake();

    expect(fn () => Duitku::handleCallback(duitkuTestCallback()))->toThrow(ConfigurationException::class);
    Event::assertNothingDispatched();
})->with(['api_key', 'merchant_code']);

it('rejects callback changes without normalizing signed data', function (string $field, mixed $value) {
    $callback = duitkuTestCallback();
    $callback[$field] = $value;

    expect(Duitku::validateCallback($callback))->toBeFalse();
})->with([
    ['merchantCode', 'OTHER'],
    ['amount', '10000junk'],
    ['amount', '10000.0'],
    ['amount', true],
    ['amount', ['10000']],
    ['signature', []],
    ['merchantOrderId', 'OTHER-ORDER'],
]);

it('requires callback result code and reference before sending events', function (string $field) {
    Event::fake();
    $callback = duitkuTestCallback();
    unset($callback[$field]);

    expect(fn () => Duitku::handleCallback($callback))->toThrow(ValidationException::class);
    Event::assertNothingDispatched();
    Http::assertNothingSent();
})->with(['resultCode', 'reference']);

it('uses provider status rather than an unsigned resultCode to dispatch payment events', function () {
    Event::fake();
    $callback = duitkuTestCallback();
    duitkuTestFakeStatus($callback, '02');

    $result = Duitku::handleCallback($callback);

    expect($result->verifiedStatus->statusCode)->toBe('02');
    Event::assertNotDispatched(DuitkuPaymentReceived::class);
    Event::assertDispatched(DuitkuPaymentFailed::class);
});

it('does not turn a pending status into a failed or paid event', function () {
    Event::fake();
    $callback = duitkuTestCallback();
    duitkuTestFakeStatus($callback, '01');
    Duitku::handleCallback($callback);

    Event::assertDispatched(DuitkuCallbackReceived::class);
    Event::assertNotDispatched(DuitkuPaymentReceived::class);
    Event::assertNotDispatched(DuitkuPaymentFailed::class);
});

it('binds callback verification to the provider order amount and reference', function (array $changes) {
    Event::fake();
    Http::fake(['*' => Http::response(array_replace([
        'merchantOrderId' => 'ORDER-001',
        'reference' => 'REF-001',
        'amount' => '10000',
        'statusCode' => '00',
        'statusMessage' => 'Success',
    ], $changes))]);

    expect(fn () => Duitku::handleCallback(duitkuTestCallback()))->toThrow(CallbackMismatchException::class);
    Event::assertNothingDispatched();
})->with([
    [['merchantOrderId' => 'OTHER-ORDER']],
    [['amount' => '9999']],
    [['reference' => 'OTHER-REFERENCE']],
]);

it('preserves callback issuer metadata and unknown fields', function () {
    $data = duitkuTestCallback(['customerName' => 'Cus***', 'futureField' => 'future']);
    duitkuTestFakeStatus($data, '00');

    $callback = Duitku::verifyCallback($data);

    expect($callback->customerName)->toBe('Cus***')
        ->and($callback->raw['futureField'])->toBe('future');
});

it('never retries invoice creation automatically', function () {
    Config::set('duitku.retry_times', 2);
    Config::set('duitku.retry_sleep', 0);
    Http::fake(['*' => Http::sequence()->push(['error' => 'ambiguous'], 500)->push(duitkuTestInvoice())]);

    expect(fn () => Duitku::checkout(duitkuTestPayment()))->toThrow(RequestException::class);
    Http::assertSentCount(1);
});

it('retries transient status failures but not invalid signatures', function (int $httpStatus, int $attempts) {
    Config::set('duitku.retry_times', 1);
    Config::set('duitku.retry_sleep', 0);
    Http::fake(['*' => Http::sequence()->push(['error' => 'failure'], $httpStatus)->push([
        'merchantOrderId' => 'ORDER-001', 'reference' => 'REF-001', 'amount' => '10000',
        'statusCode' => '00', 'statusMessage' => 'Success',
    ])]);

    if ($httpStatus === 401) {
        expect(fn () => Duitku::checkStatus('ORDER-001'))->toThrow(RequestException::class);
    } else {
        expect(Duitku::checkStatus('ORDER-001')->statusCode)->toBe('00');
    }

    Http::assertSentCount($attempts);
})->with([[500, 2], [429, 2], [401, 1]]);

it('records operational metadata without logging customer data or signatures', function () {
    Config::set('duitku.log_channel', 'audit');
    $context = null;
    $logger = Mockery::mock();
    $logger->shouldReceive('debug')->once()->andReturnUsing(function ($message, $data) use (&$context) {
        $context = $data;
    });
    Log::shouldReceive('channel')->with('audit')->andReturn($logger);
    Http::fake(['*' => Http::response(duitkuTestInvoice())]);

    Duitku::checkout(duitkuTestPayment());

    expect($context)->toBe([
        'method' => 'POST', 'host' => 'sandbox.duitku.com', 'path' => '/webapi/api/merchant/v2/inquiry',
    ]);
});

it('rejects malformed invoice success bodies', function (mixed $body) {
    Http::fake(['*' => Http::response($body, 200)]);

    expect(fn () => Duitku::checkout(duitkuTestPayment()))->toThrow(MalformedResponseException::class);
})->with([
    [['error' => 'unexpected']],
    ['<html>unavailable</html>'],
    [['statusCode' => '00']],
]);

it('accepts valid API invoices that omit an optional status code', function () {
    $data = duitkuTestInvoice();
    unset($data['statusCode']);

    expect(PaymentResponse::fromArray($data)->reference)->toBe('REF-001');
});

it('applies default expiry without mutating the request and respects an override', function () {
    $request = duitkuTestPayment();
    expect($request->validatedPayload(17)['expiryPeriod'])->toBe(17)
        ->and($request->expiryPeriod)->toBeNull();

    $request->expiryPeriod = 5;
    expect($request->validatedPayload(17)['expiryPeriod'])->toBe(5);
});

it('serializes tokenization modes and customer identifiers with the documented types', function (int $mode) {
    $request = duitkuTestPayment([
        'customerDetail' => new CustomerDetail('Test', 'User', 'test@example.test', '08000000000', merchantCustomerId: 'CUSTOMER-1'),
        'creditCardDetail' => new CreditCardDetail(saveCardToken: $mode),
    ]);
    $payload = $request->validatedPayload(10, pop: true);

    expect($payload['customerDetail']['merchantCustomerId'])->toBe('CUSTOMER-1')
        ->and($payload)->not->toHaveKey('merchantCustomerId')
        ->and($payload['creditCardDetail']['saveCardToken'])->toBe($mode);
})->with([1, 2]);

it('does not offer discontinued QRIS as an active method', function () {
    expect(PaymentMethod::active())->not->toContain(PaymentMethod::GUDANG_VOUCHER_QRIS);
    expect(fn () => duitkuTestPayment(['paymentMethod' => 'GQ'])->validatedPayload(10))->toThrow(ValidationException::class);
});

it('renders POP references as data and loads each script once', function () {
    $reference = "REF');globalThis.injected=true;//";
    $html = Blade::render('<x-duitku-pop :reference="$reference" /><x-duitku-pop reference="SECOND" />', compact('reference'));
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $button = $dom->getElementsByTagName('button')->item(0);

    expect($button->getAttribute('data-duitku-reference'))->toBe($reference)
        ->and($button->hasAttribute('onclick'))->toBeFalse()
        ->and($button->getAttribute('type'))->toBe('button')
        ->and(substr_count($html, '/lib/js/duitku.js'))->toBe(1)
        ->and(substr_count($html, '/vendor/duitku/duitku-pop.js'))->toBe(1);
});
