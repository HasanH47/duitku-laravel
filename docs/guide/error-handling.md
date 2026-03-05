# Error Handling

Handling error di SDK ini menggunakan pendekatan Catchable Exceptions, sehingga kode Anda lebih bersih daripada menggunakan banyak if-else untuk mengecek status code.

## Menggunakan `throwIfFailed()`

Setiap response dari SDK memiliki method `throwIfFailed()` yang akan melemparkan exception jika Duitku mengembalikan kode selain sukses.

```php
use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Exceptions\InsufficientFundsException;

try {
    Duitku::disbursement()
        ->transfer()
        ->inquiry($info)
        ->throwIfFailed(); // Akan melempar exception jika gagal
} catch (InsufficientFundsException $e) {
    // Tangani saldo tidak cukup
    return back()->with('error', 'Pencairan dana gagal: Saldo merchant tidak cukup.');
} catch (DuitkuApiException $e) {
    // Tangani error API umum (kode salah, rekening tidak ditemukan, dll)
    return back()->with('error', $e->getMessage());
}
```

## Daftar Custom Exceptions

| Exception                    | Deskripsi                                                    |
| ---------------------------- | ------------------------------------------------------------ |
| `DuitkuException`            | Base exception untuk semua error SDK.                        |
| `DuitkuApiException`         | Terjadi saat API Duitku mengembalikan error (misal: `-500`). |
| `InsufficientFundsException` | Spesifik untuk error saldo tidak cukup (Disbursement).       |
| `InvalidSignatureException`  | Terjadi saat validasi signature callback gagal.              |

## Mendapatkan Kode Error Duitku

Anda tetap bisa mendapatkan kode error asli dari Duitku melalui method `getDuitkuCode()` pada `DuitkuApiException`.

```php
catch (DuitkuApiException $e) {
    $kodeAsli = $e->getDuitkuCode(); // Misal: '01' atau '-100'
}
```

## Referensi Error Code

SDK menyediakan class `ErrorCode` yang berisi semua kode error dari Duitku docs:

```php
use Duitku\Laravel\Enums\ErrorCode;

// === HTTP Status Codes ===
ErrorCode::HTTP_SUCCESS;       // 200
ErrorCode::HTTP_BAD_REQUEST;   // 400
ErrorCode::HTTP_UNAUTHORIZED;  // 401
ErrorCode::HTTP_NOT_FOUND;     // 404
ErrorCode::HTTP_CONFLICT;      // 409
ErrorCode::HTTP_SERVER_ERROR;  // 500

// === Error Messages dari 400 Bad Request ===
ErrorCode::MIN_PAYMENT;           // 'Minimum Payment 10000 IDR'
ErrorCode::MAX_PAYMENT;           // 'Maximum Payment exceeded'
ErrorCode::PAYMENT_METHOD_REQUIRED; // 'paymentMethod is mandatory'
ErrorCode::ORDER_ID_REQUIRED;     // 'merchantOrderId is mandatory'
ErrorCode::INVALID_EMAIL;         // 'Invalid Email Address'
ErrorCode::VA_NAME_REQUIRED;      // 'Customer VA Name must not be empty...'
ErrorCode::WRONG_SIGNATURE;       // 'Wrong signature' (401)
ErrorCode::MERCHANT_NOT_FOUND;    // 'Merchant not found' (404)
ErrorCode::AMOUNT_MISMATCH;       // 'Payment amount must be equal...' (409)

// === API Status Codes ===
ErrorCode::SUCCESS;  // '00' - Berhasil
ErrorCode::PENDING;  // '01' - Pending
ErrorCode::FAILED;   // '02' - Gagal

// === Helper Methods ===
ErrorCode::describeHttp(400);              // 'Ada kesalahan pada saat mengirimkan...'
ErrorCode::describeStatus('00', 'callback'); // 'Transaksi telah sukses terbayarkan.'
ErrorCode::isSuccess('00');                // true
ErrorCode::isPending('01');                // true
ErrorCode::isFailed('02');                 // true
```

---

Terakhir, pelajari tentang [Blade Components](./blade-components).
