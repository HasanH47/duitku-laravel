<?php

namespace Duitku\Laravel\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Client
{
    public function __construct(
        protected \Duitku\Laravel\Support\DuitkuConfig $config
    ) {}

    public function request(?string $baseUrl = null): PendingRequest
    {
        return Http::baseUrl($baseUrl ?? $this->config->getPassportHost())
            ->timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }
}
