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
        $request = Http::baseUrl($baseUrl ?? $this->config->getPassportHost())
            ->timeout($this->config->getTimeout())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        $retryTimes = $this->config->getRetryTimes();
        if ($retryTimes > 0) {
            $request = $request->retry($retryTimes, $this->config->getRetrySleep());
        }

        return $request;
    }
}
