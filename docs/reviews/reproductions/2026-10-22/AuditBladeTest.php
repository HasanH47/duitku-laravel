<?php

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

test('audit POP component renders before facade resolution', function () {
    expect(fn () => Blade::render('<x-duitku-pop reference="SYNTHETIC-REFERENCE" />'))->not->toThrow(Throwable::class);
});

test('audit POP reference remains data in JavaScript context', function () {
    app('duitku');
    $reference = "REF');globalThis.__auditInjected=true;//";
    $html = Blade::render('<x-duitku-pop :reference="$reference" />', ['reference' => $reference]);
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $handler = $dom->getElementsByTagName('button')->item(0)->getAttribute('onclick');
    $process = new Process(['node', '-e', 'globalThis.checkout={process(){}}; new Function(process.argv[1])(); process.stdout.write(JSON.stringify(globalThis.__auditInjected===true));', $handler]);
    $process->mustRun();
    expect($process->getOutput())->toBe('false');
});

test('audit malformed HTTP 200 invoice does not count as success', function () {
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(['error' => 'synthetic malformed response'], 200)]);
    $request = new PaymentRequest(10000, 'AUDIT-001', 'Synthetic item', 'audit@example.invalid', 'VC');
    expect(fn () => Duitku::checkout($request))->toThrow(Throwable::class);
});

test('audit missing merchant credentials fail closed before accepting callbacks', function () {
    Event::fake();
    Config::set('duitku.merchant_code', '');
    Config::set('duitku.api_key', '');
    $payload = ['amount' => '10000', 'merchantOrderId' => 'AUDIT-001', 'resultCode' => '00', 'signature' => md5('10000'.'AUDIT-001')];
    expect(fn () => Duitku::handleCallback($payload))->toThrow(Throwable::class);
});
