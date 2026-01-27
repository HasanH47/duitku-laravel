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
}
