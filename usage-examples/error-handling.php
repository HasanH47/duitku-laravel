<?php

use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Client\RequestException;

/**
 * EXAMPLE: Handling API Errors (400, 401, 404, etc)
 *
 * Since we use Laravel's Http Client, all non-2xx responses
 * will throw an Illuminate\Http\Client\RequestException.
 */

try {
    // Example: Bad Request (Invalid credentials or parameters)
    $response = Duitku::checkout(new PaymentRequest(
         amount: 10000,
         merchantOrderId: 'INV-INVALID',
         productDetails: 'Test',
         email: 'invalid-email', // This might trigger 400
         phoneNumber: '08123',
         callbackUrl: 'https://example.com',
         returnUrl: 'https://example.com'
    ));

} catch (RequestException $e) {
    // 1. Get the HTTP Status Code (e.g., 400, 401, 404)
    $statusCode = $e->response->status();

    // 2. Get the specific error message from Duitku
    // Duitku returns JSON like: {"Message": "Invalid Email Address"}
    $errorBody = $e->response->json();
    $serverMessage = $errorBody['Message'] ?? $e->getMessage();

    if ($statusCode === 400) {
        // Handle Validation Error
        echo "Validasi Gagal: " . $serverMessage;
    } elseif ($statusCode === 401) {
        // Handle Wrong Signature
        echo "Masalah Config/Signature: " . $serverMessage;
    } else {
        echo "Error Lain ($statusCode): " . $serverMessage;
    }
}
