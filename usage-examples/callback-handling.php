<?php

use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

/**
 * EXAMPLE: Handling Payment Callback (Webhook)
 *
 * This is how you handle the notification from Duitku after user pays.
 * Add this logic to your Controller.
 */
class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        // 1. Validate the Signature ensures data comes from Duitku
        // and hasn't been tampered with.
        if (! Duitku::validateCallback($request->all())) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        // 2. Get Data
        $merchantOrderId = $request->input('merchantOrderId');
        $resultCode = $request->input('resultCode'); // "00" = Success, "01" = Pending
        $reference = $request->input('reference');

        // 3. Update Your Database
        $order = \App\Models\Order::where('id', $merchantOrderId)->first();

        if ($resultCode === '00') {
            $order->update(['status' => 'paid', 'payment_ref' => $reference]);
        } elseif ($resultCode === '01') {
            $order->update(['status' => 'pending']);
        } else {
            $order->update(['status' => 'failed']);
        }

        // 4. Return HTTP 200 OK
        // Duitku expects 200 OK to know you received it.
        return response()->json(['message' => 'Callback received']);
    }
}
