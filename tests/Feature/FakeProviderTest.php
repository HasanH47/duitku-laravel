<?php

use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\AssertionFailedError;

it('runs checkout and verified callbacks through the real SDK with synthetic responses', function () {
    Event::fake();
    $fake = Duitku::fake()->payment('ORDER-001', 10000, statusCode: '00', reference: 'REF-FAKE');

    $invoice = Duitku::checkout(duitkuTestPayment());
    $callback = Duitku::handleCallback($fake->callback('ORDER-001'));

    expect($invoice->reference)->toBe('REF-FAKE')
        ->and($callback->verifiedStatus->statusCode)->toBe('00');
    Event::assertDispatched(DuitkuPaymentReceived::class);
    $fake->assertPaymentRequested('ORDER-001', fn (Request $request) => $request['paymentAmount'] === 10000);
    $fake->assertStatusChecked('ORDER-001');
});

it('does not accept a tampered success callback while the fake provider is pending', function () {
    Event::fake();
    $fake = Duitku::fake()->payment('ORDER-001', 10000);
    $callback = $fake->callback('ORDER-001');
    $callback['resultCode'] = '00';

    expect(Duitku::handleCallback($callback)->verifiedStatus->statusCode)->toBe('01');
    Event::assertNotDispatched(DuitkuPaymentReceived::class);
});

it('supports POP invoices and status batches without replacing SDK services', function () {
    $fake = Duitku::fake()->payment('A', 10000)->payment('B', 20000, statusCode: '02');
    $invoice = Duitku::pop()->createTransaction(duitkuTestPayment(['merchantOrderId' => 'A']));
    $results = Duitku::checkStatuses(['A', 'B', 'A']);

    expect($invoice->reference)->toBe('TEST-A')
        ->and(array_map(fn ($result) => $result->get()->statusCode, $results))->toBe(['01', '02', '01']);
    $fake->assertPaymentRequested('A');
});

it('supports payment method fixtures and unrelated Laravel HTTP fakes', function () {
    $fake = Duitku::fake()->paymentMethods([
        ['paymentMethod' => 'BC', 'paymentName' => 'BCA VA', 'totalFee' => '1000'],
    ]);
    Http::fake(['https://merchant.test/*' => Http::response(['ok' => true])]);

    expect(Http::get('https://merchant.test/health')->json('ok'))->toBeTrue();
    $fake->assertNothingSent();
    expect(Duitku::paymentMethods(10000)[0]->paymentMethod)->toBe('BC');
});

it('keeps request validation active while faking the provider', function () {
    $fake = Duitku::fake()->payment('ORDER-001', 10000);

    expect(fn () => Duitku::checkout(duitkuTestPayment(['amount' => 0])))->toThrow(ValidationException::class);
    $fake->assertNothingSent();
});

it('rejects unknown orders and amount mismatches without a success response', function () {
    Duitku::fake()->payment('ORDER-001', 20000);

    expect(fn () => Duitku::checkout(duitkuTestPayment()))->toThrow(LogicException::class)
        ->and(fn () => Duitku::checkStatus('UNREGISTERED'))->toThrow(LogicException::class);
});

it('refuses unsupported Duitku endpoints instead of reaching the network', function () {
    Duitku::fake();

    expect(fn () => Duitku::disbursement()->finance()->balance())->toThrow(LogicException::class);
});

it('creates valid callbacks for explicitly selected legacy mode', function () {
    Config::set('duitku.signature_mode', 'legacy');
    $fake = Duitku::fake()->payment('ORDER-001', 10000);

    expect(Duitku::validateCallback($fake->callback('ORDER-001')))->toBeTrue();
});

it('reuses the active fake and lets tests advance provider status', function () {
    $fake = Duitku::fake()->payment('ORDER-001', 10000);
    expect(Duitku::checkStatus('ORDER-001')->statusCode)->toBe('01');

    Duitku::fake()->payment('ORDER-001', 10000, statusCode: '00');

    expect(Duitku::fake())->toBe($fake)
        ->and(Duitku::checkStatus('ORDER-001')->statusCode)->toBe('00');
});

it('rejects malformed payment fixtures before any request', function (string $orderId, int $amount, string $statusCode) {
    $fake = Duitku::fake();

    expect(fn () => $fake->payment($orderId, $amount, $statusCode))->toThrow(ValidationException::class);
    $fake->assertNothingSent();
})->with([['', 10000, '01'], ['ORDER', 0, '01'], ['ORDER', 10000, 'unknown']]);

it('refuses fake activation outside the testing environment', function () {
    $this->app->instance('env', 'production');

    expect(fn () => Duitku::fake())->toThrow(LogicException::class);
});

it('fails domain assertions when the expected request was never sent', function () {
    $fake = Duitku::fake();

    expect(fn () => $fake->assertPaymentRequested('MISSING'))->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertStatusChecked('MISSING'))->toThrow(AssertionFailedError::class);
});
