# Duitku Laravel

![Tests](https://github.com/HasanH47/duitku-laravel/workflows/Tests/badge.svg)
![Static Analysis](https://github.com/HasanH47/duitku-laravel/workflows/Static%20Analysis/badge.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red)

A **Modern, Typed, and Concurrency-Optimized** Laravel package for Duitku Payment Gateway.

## Features

- 🚀 **Concurrent Status Checks**: Use generic Laravel `Http::pool` implementation to check multiple transaction statuses in parallel (e.g. check 50 orders in <1 second).
- 🔒 **Strict Typing**: No more magic arrays. Use `PaymentRequest` and `PaymentResponse` DTOs.
- 🛡️ **Auto Signature**: Automatic MD5/SHA256 signature generation and validation.
- 🧪 **Modern Testing**: Built with Pest PHP and fully testable with `Http::fake()`.

## Installation

```bash
composer require duitku/laravel
```

Publish configuration:

```bash
php artisan vendor:publish --tag=duitku-config
```

## Configuration

Add your credentials in `.env`:

```env
DUITKU_MERCHANT_CODE=your_merchant_code
DUITKU_API_KEY=your_api_key
DUITKU_SANDBOX_MODE=true
```

## Usage

### 1. Create Payment (Checkout)

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Topup Game Diamonds',
    email: 'customer@example.com',
    paymentMethod: 'VC' // Optional
);

$response = Duitku::checkout($request);

return redirect($response->paymentUrl);
```

### 2. Callback Handling (Webhook)

```php
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

public function handleCallback(Request $request)
{
    // Validate Signature
    if (!Duitku::validateCallback($request->all())) {
        abort(403, 'Invalid Signature');
    }

    // Process Order
    $orderId = $request->merchantOrderId;
    $status = $request->resultCode; // '00' = Success

    // ... update database ...
}
```

### 3. Check Transaction Status (Concurrent Optimization) 🚀

This is where this package shines. Check one, or check many in parallel!

```php
// Single Check
$status = Duitku::checkStatus('INV-123');

// Bulk Check (Optimized with Http::pool)
$statuses = Duitku::checkStatuses(['INV-123', 'INV-124', 'INV-125']);

foreach ($statuses as $status) {
    echo $status->statusCode;
}
```

## Testing

Run the tests:

```bash
composer test
```

## License

MIT
