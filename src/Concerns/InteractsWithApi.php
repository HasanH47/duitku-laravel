<?php

namespace Duitku\Laravel\Concerns;

trait InteractsWithApi
{
    /**
     * Generate Duitku Signature
     */
    protected function generateSignature(string $params, string $algo = 'md5'): string
    {
        if ($algo === 'sha256') {
            return hash('sha256', $params);
        }

        return md5($params);
    }

    /**
     * Get Centralized 13-digit Timestamp
     */
    protected function getTimestamp(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
