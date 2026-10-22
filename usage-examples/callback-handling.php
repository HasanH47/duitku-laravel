<?php

use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

/**
 * Application controller example. Register an idempotent payment listener separately.
 */
class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        // Verifies the signature, checks the provider status, then dispatches events.
        // Let failures reach the application's exception handler; do not acknowledge them as success.
        Duitku::handleCallback($request->all());

        return response('OK', 200);
    }
}
