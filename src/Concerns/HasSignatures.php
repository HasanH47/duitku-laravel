<?php

namespace Duitku\Laravel\Concerns;

trait HasSignatures
{
    protected function generateSignature(string $params, string $algo = 'md5'): string
    {
        if ($algo === 'sha256') {
            return hash('sha256', $params);
        }

        return md5($params);
    }
}
