<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('checks payment configuration offline without displaying credentials', function () {
    Http::fake();

    $this->artisan('duitku:doctor')
        ->expectsOutputToContain('Mode: sandbox')
        ->expectsOutputToContain('PASS Payment credentials')
        ->expectsOutputToContain('No network requests were made')
        ->doesntExpectOutputToContain('TEST_API_KEY')
        ->doesntExpectOutputToContain('TEST_MERCHANT')
        ->assertSuccessful()->run();

    Http::assertNothingSent();
});

it('reports configuration failures with a nonzero exit code', function (string $key, mixed $value) {
    Config::set('duitku.'.$key, $value);
    Http::fake();

    $this->artisan('duitku:doctor')->expectsOutputToContain('FAIL')->assertFailed()->run();
    Http::assertNothingSent();
})->with([
    ['api_key', ''], ['merchant_code', ''], ['timeout', 0], ['retry_times', -1],
    ['retry_sleep', 60001], ['batch_concurrency', 21], ['default_expiry', 0],
    ['signature_mode', 'unknown'], ['log_channel', 'missing-channel'],
]);

it('reports every failure in one invocation', function () {
    Config::set('duitku.api_key', '');
    Config::set('duitku.timeout', 0);

    $this->artisan('duitku:doctor')
        ->expectsOutputToContain('FAIL Payment credentials')
        ->expectsOutputToContain('FAIL HTTP settings')
        ->assertFailed();
});

it('warns about weaker callback settings and supports strict checks', function (array $options, int $exitCode) {
    Config::set('duitku.signature_mode', 'legacy');
    Config::set('duitku.verify_callback_status', false);

    $this->artisan('duitku:doctor', $options)
        ->expectsOutputToContain('WARN Legacy payment signatures')
        ->expectsOutputToContain('WARN Callback status verification is disabled')
        ->assertExitCode($exitCode);
})->with([[[], 0], [['--strict' => true], 1]]);

it('checks disbursement credentials without requiring a payment merchant code', function () {
    Config::set('duitku.merchant_code', '');
    Config::set('duitku.sandbox_mode', false);

    $this->artisan('duitku:doctor', ['--disbursement' => true])
        ->expectsOutputToContain('Mode: production')
        ->expectsOutputToContain('PASS Disbursement credentials')
        ->doesntExpectOutputToContain('merchant@example.test')
        ->assertSuccessful();
});

it('fails when disbursement credentials are missing', function () {
    Config::set('duitku.user_id', '');

    $this->artisan('duitku:doctor', ['--disbursement' => true])
        ->expectsOutputToContain('FAIL Disbursement credentials')->assertFailed();
});

it('checks published assets only when POP is requested', function () {
    $this->app->usePublicPath(sys_get_temp_dir().'/duitku-doctor-missing-'.bin2hex(random_bytes(6)));

    $this->artisan('duitku:doctor', ['--pop' => true])
        ->expectsOutputToContain('FAIL POP browser asset: Run php artisan vendor:publish --tag=duitku-assets.')
        ->assertFailed();
});

it('rejects incompatible diagnostic options', function () {
    $this->artisan('duitku:doctor', ['--pop' => true, '--disbursement' => true])->assertExitCode(2);
});

it('recognizes a published POP browser asset', function () {
    $path = sys_get_temp_dir().'/duitku-doctor-'.bin2hex(random_bytes(6));
    mkdir($path.'/vendor/duitku', 0700, true);
    copy(__DIR__.'/../../resources/js/duitku-pop.js', $path.'/vendor/duitku/duitku-pop.js');
    $this->app->usePublicPath($path);

    try {
        $this->artisan('duitku:doctor', ['--pop' => true])
            ->expectsOutputToContain('PASS POP browser asset')->assertSuccessful()->run();
    } finally {
        unlink($path.'/vendor/duitku/duitku-pop.js');
        rmdir($path.'/vendor/duitku');
        rmdir($path.'/vendor');
        rmdir($path);
    }
});
