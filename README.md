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

### 4. Disbursement (Transfer Online)

To use Disbursement features, add `DUITKU_USER_ID` and `DUITKU_EMAIL` to your `.env` file.

**Step 1: Bank Inquiry (Check Account)**
First, check the destination account validity.

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\DisbursementInfo;

$info = new DisbursementInfo(
    amountTransfer: 50000,
    bankAccount: '1234567890',
    bankCode: '014', // BCA
    purpose: 'Withdrawal'
);

$inquiry = Duitku::disbursement()->bankInquiry($info);

echo $inquiry->accountName; // "JOHN DOE"
echo $inquiry->disburseId; // Keep this ID for step 2!
```

**Step 2: Execute Transfer**
After confirming the account name, execute the transfer using the `disburseId` from step 1.

```php
$transfer = Duitku::disbursement()->transfer(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber
);

echo $transfer->responseCode; // 00 = Success
```

### 5. Clearing (BIFAST / RTGS / LLG)

For higher amounts or specific transfer types, use Clearing methods.

```php
$info = new DisbursementInfo(
    amountTransfer: 50000000,
    bankAccount: '1234567890',
    bankCode: '014',
    purpose: 'Big Transfer',
    type: 'BIFAST' // or 'RTGS', 'LLG'
);

// 1. Inquiry
$inquiry = Duitku::disbursement()->clearing()->inquiry($info);

// 2. Transfer
$transfer = Duitku::disbursement()->clearing()->execute(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber
);
```

### 6. Cash Out (Indomaret / Pos Indonesia)

Withdraw funds via retail outlets.

```php
use Duitku\Laravel\Data\CashOutInfo;

$info = new CashOutInfo(
    amountTransfer: 50000,
    bankCode: '2010', // 2010 = Indomaret, 2011 = Pos
    accountName: 'John Doe',
    accountIdentity: '350...', // No KTP
    phoneNumber: '08123...'
);

$response = Duitku::disbursement()->cashOut()->inquiry($info);

echo $response->token; // Token used for withdrawal
```

### 7. Finance Features (Status, Balance, List Bank)

You can check transaction status, account balance, and list available banks.

```php
use Duitku\Laravel\Support\DisbursementCode;

// Check Disbursement Status
$status = Duitku::disbursement()->finance()->status('DISB-1001');

if ($status->responseCode === DisbursementCode::SUCCESS) {
    echo "Transaction Successful!";
}

// Check Balance
$balance = Duitku::disbursement()->finance()->balance();
echo "Saldo: " . number_format($balance->balance);
echo "Efektif: " . number_format($balance->effectiveBalance);

// List Available Banks
$banks = Duitku::disbursement()->finance()->listBank();
foreach ($banks as $bank) {
    echo $bank['bankName'] . ' (' . $bank['bankCode'] . ')';
}
```

## Testing

Run the tests:

```bash
composer test
```

## License

MIT
