<?php

namespace Duitku\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuitkuCallbackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $payload
    ) {}
}
