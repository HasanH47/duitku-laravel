<?php

use Duitku\Laravel\Data\CreditCardDetail;
use Duitku\Laravel\Exceptions\ConfigurationException;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

it('validates required API and POP fields before sending requests', function (string $field, mixed $value, bool $pop) {
    $request = duitkuTestPayment([$field => $value]);

    expect(fn () => $pop ? Duitku::pop()->createTransaction($request) : Duitku::checkout($request))
        ->toThrow(ValidationException::class);
    Http::assertNothingSent();
})->with([
    ['callbackUrl', null, false], ['returnUrl', null, false],
    ['callbackUrl', null, true], ['returnUrl', null, true],
    ['amount', -1, false], ['paymentMethod', '', false],
    ['customerVaName', null, false], ['expiryPeriod', 0, false],
]);

it('requires a merchant customer identifier when tokenization is requested', function () {
    $request = duitkuTestPayment(['creditCardDetail' => new CreditCardDetail(saveCardToken: 2)]);

    expect(fn () => $request->validatedPayload(10, pop: true))->toThrow(ValidationException::class);
});

it('rejects unsafe client settings instead of silently disabling limits', function (string $setting, mixed $value) {
    Config::set('duitku.'.$setting, $value);
    Http::fake(['*' => Http::response(['paymentFee' => []])]);

    expect(fn () => $setting === 'batch_concurrency'
        ? Duitku::checkStatuses(['ORDER-001'])
        : Duitku::paymentMethods(10000))->toThrow(ConfigurationException::class);
    Http::assertNothingSent();
})->with([
    ['timeout', 0], ['timeout', 'invalid'], ['retry_times', 6],
    ['retry_sleep', -1], ['batch_concurrency', 21],
]);

it('uses a valid default for Jenius Pay without changing an explicit expiry', function () {
    $request = duitkuTestPayment(['paymentMethod' => 'JP']);
    expect($request->validatedPayload(60)['expiryPeriod'])->toBe(10);

    $request->expiryPeriod = 5;
    expect($request->validatedPayload(60)['expiryPeriod'])->toBe(5);
});
