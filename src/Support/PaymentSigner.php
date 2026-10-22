<?php

namespace Duitku\Laravel\Support;

class PaymentSigner
{
    public function __construct(private DuitkuConfig $config) {}

    public function sign(string $message, string $legacyAlgorithm = 'md5'): string
    {
        $this->config->assertPaymentCredentials();

        if ($this->config->getSignatureMode() === 'legacy') {
            return hash($legacyAlgorithm, $message.$this->config->getApiKey());
        }

        return hash_hmac('sha256', $message, $this->config->getApiKey());
    }
}
