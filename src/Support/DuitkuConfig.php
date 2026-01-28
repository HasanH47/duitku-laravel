<?php

namespace Duitku\Laravel\Support;

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
        return (int) ($this->config['default_expiry'] ?? 60);
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
}
