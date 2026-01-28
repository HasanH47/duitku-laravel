<?php

namespace Duitku\Laravel\Events;

use Duitku\Laravel\Data\CallbackRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuitkuPaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CallbackRequest $callback
    ) {}
}
