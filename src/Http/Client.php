<?php

namespace Duitku\Laravel\Http;

use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Client
{
    protected string $baseUrl;

    public function __construct(
        protected DuitkuConfig $config
    ) {
        $this->baseUrl = $this->config->isSandbox()
            ? 'https://sandbox.duitku.com'
            : 'https://passport.duitku.com';
    }

    public function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    public function getUrl(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}
