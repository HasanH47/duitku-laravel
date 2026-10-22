<?php

namespace Duitku\Laravel\Console;

use Closure;
use Duitku\Laravel\Exceptions\ConfigurationException;
use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Console\Command;
use TypeError;

class DoctorCommand extends Command
{
    protected $signature = 'duitku:doctor
        {--disbursement : Check disbursement credentials instead of payment credentials}
        {--pop : Also check the published POP browser asset}
        {--strict : Return a failure exit code for warnings too}';

    protected $description = 'Check local Duitku configuration without sending requests or displaying credentials';

    public function handle(DuitkuConfig $config): int
    {
        if ($this->option('pop') && $this->option('disbursement')) {
            $this->error('Use --pop for payments or --disbursement for payouts, in separate invocations.');

            return self::INVALID;
        }

        $this->line('Mode: '.($config->isSandbox() ? 'sandbox' : 'production'));
        $credentialsLabel = $this->option('disbursement') ? 'Disbursement credentials' : 'Payment credentials';
        $checks = [
            $credentialsLabel => fn () => $this->option('disbursement')
                    ? $config->assertDisbursementCredentials()
                    : $config->assertPaymentCredentials(),
            'HTTP settings' => function () use ($config) {
                $config->getTimeout();
                $config->getRetryTimes();
                $config->getRetrySleep();
                $config->getBatchConcurrency();
            },
            'Log channel' => function () use ($config) {
                $channel = $config->getLogChannel();

                if ($channel !== null && ! config()->has('logging.channels.'.$channel)) {
                    throw new ConfigurationException('Set duitku.log_channel to a channel in logging.channels, or use null.');
                }
            },
        ];

        if (! $this->option('disbursement')) {
            $checks['Payment settings'] = function () use ($config) {
                $config->getDefaultExpiry();
                $config->getSignatureMode();
            };
        }

        if ($this->option('pop')) {
            $checks['POP browser asset'] = function () {
                if (! is_readable(public_path('vendor/duitku/duitku-pop.js'))) {
                    throw new ConfigurationException('Run php artisan vendor:publish --tag=duitku-assets.');
                }
            };
        }

        $failed = false;

        foreach ($checks as $label => $check) {
            if (! $this->check($label, $check)) {
                $failed = true;
            }
        }

        $warnings = false;

        if (! $this->option('disbursement') && ! $failed) {
            if ($config->getSignatureMode() === 'legacy') {
                $this->warn('WARN Legacy payment signatures are enabled. Confirm this matches your merchant account.');
                $warnings = true;
            }

            if (! $config->shouldVerifyCallbackStatus()) {
                $this->warn('WARN Callback status verification is disabled. Verify provider status in the application.');
                $warnings = true;
            }
        }

        $this->line('No network requests were made. Provider credentials and connectivity were not verified.');

        return $failed || ($warnings && $this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $label, Closure $check): bool
    {
        try {
            $check();
            $this->info('PASS '.$label);

            return true;
        } catch (ConfigurationException $error) {
            $this->error('FAIL '.$label.': '.$error->getMessage());
        } catch (TypeError) {
            $this->error('FAIL '.$label.': Check the value types in config/duitku.php.');
        }

        return false;
    }
}
