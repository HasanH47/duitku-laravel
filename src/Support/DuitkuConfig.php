<?php

namespace Duitku\Laravel\Support;

use Duitku\Laravel\Exceptions\ConfigurationException;

class DuitkuConfig
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getMerchantCode(): string
    {
        return (string) ($this->config['merchant_code'] ?? '');
    }

    public function getApiKey(): string
    {
        return (string) ($this->config['api_key'] ?? '');
    }

    public function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox_mode'] ?? true);
    }

    public function getDefaultExpiry(): int
    {
        return $this->integerSetting('default_expiry', 60, 1, PHP_INT_MAX);
    }

    public function getUserId(): string
    {
        return (string) ($this->config['user_id'] ?? '');
    }

    public function getEmail(): string
    {
        return (string) ($this->config['email'] ?? '');
    }

    /**
     * Get Host for Main Payment/Disbursement/Finance
     */
    public function getPassportHost(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.duitku.com'
            : 'https://passport.duitku.com';
    }

    /**
     * Get Host for POP Checkout API
     */
    public function getApiHost(): string
    {
        return $this->isSandbox()
            ? 'https://api-sandbox.duitku.com'
            : 'https://api-prod.duitku.com';
    }

    /**
     * Get Host for Frontend Assets (JS)
     */
    public function getAppHost(): string
    {
        return $this->isSandbox()
            ? 'https://app-sandbox.duitku.com'
            : 'https://app-prod.duitku.com';
    }

    /**
     * Get Host for CashOut Inquiry
     */
    public function getDisbursementHost(): string
    {
        return $this->isSandbox()
            ? 'https://disbursement-sandbox.duitku.com'
            : 'https://disbursement.duitku.com';
    }

    public function getTimeout(): int
    {
        return $this->integerSetting('timeout', 30, 1, 300);
    }

    public function getRetryTimes(): int
    {
        return $this->integerSetting('retry_times', 0, 0, 5);
    }

    public function getRetrySleep(): int
    {
        return $this->integerSetting('retry_sleep', 100, 0, 60000);
    }

    public function getLogChannel(): ?string
    {
        return $this->config['log_channel'] ?? null;
    }

    public function getSignatureMode(): string
    {
        $mode = $this->config['signature_mode'] ?? 'hmac-sha256';

        if (! in_array($mode, ['hmac-sha256', 'legacy'], true)) {
            throw new ConfigurationException('Set duitku.signature_mode to hmac-sha256 or legacy.');
        }

        return $mode;
    }

    public function shouldVerifyCallbackStatus(): bool
    {
        return (bool) ($this->config['verify_callback_status'] ?? true);
    }

    public function getBatchConcurrency(): int
    {
        return $this->integerSetting('batch_concurrency', 5, 1, 20);
    }

    public function assertApiKey(): void
    {
        if (trim($this->getApiKey()) === '') {
            throw new ConfigurationException('Set DUITKU_API_KEY before using the Duitku API.');
        }
    }

    public function assertPaymentCredentials(): void
    {
        $this->assertApiKey();

        if (trim($this->getMerchantCode()) === '') {
            throw new ConfigurationException('Set DUITKU_MERCHANT_CODE before using payments or validating callbacks.');
        }
    }

    public function assertDisbursementCredentials(): void
    {
        $this->assertApiKey();

        if (! filter_var($this->getUserId(), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ||
            ! filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new ConfigurationException('Set a positive DUITKU_USER_ID and a valid DUITKU_EMAIL before using disbursement.');
        }
    }

    private function integerSetting(string $key, int $default, int $minimum, int $maximum): int
    {
        $value = filter_var($this->config[$key] ?? $default, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $minimum, 'max_range' => $maximum],
        ]);

        if ($value === false) {
            throw new ConfigurationException("Set duitku.{$key} to an integer between {$minimum} and {$maximum}.");
        }

        return $value;
    }
}
