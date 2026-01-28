<?php

namespace Duitku\Laravel\Events;

use Duitku\Laravel\Data\CallbackRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuitkuPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CallbackRequest $callback
    ) {}
}
