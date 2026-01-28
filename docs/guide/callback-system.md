# Callback System (Event-Driven)

SDK v1.3.1 memperkenalkan cara baru yang jauh lebih elegan untuk menangani callback dari Duitku menggunakan Laravel Events.

## Cara Lama (Manual)

Sebelumnya, Anda harus memvalidasi signature secara manual di Controller:

```php
public function callback(Request $request) {
    if (Duitku::validateCallback($request->all())) {
        // Proses database...
    }
}
```

## Cara Baru (SDK Style - Recommended)

Gunakan method `handleCallback` untuk mengotomatisasi segalanya.

### 1. Di Controller

Anda hanya perlu satu baris kode:

```php
public function callback(Request $request)
{
    // Validasi signature, simpan log, dan dispatch event otomatis
    Duitku::handleCallback($request->all());

    return response('OK');
}
```

### 2. Buat Listener

Buka `App\Providers\EventServiceProvider` (atau gunakan Event Discovery di Laravel 11+) dan daftarkan Listener Anda.

```php
use Duitku\Laravel\Events\DuitkuPaymentReceived;

// Di dalam $listen:
DuitkuPaymentReceived::class => [
    UpdateOrderAsPaid::class,
],
```

### 3. Implementasi Listener

SDK akan mengirimkan objek `CallbackRequest` yang sudah divalidasi ke Listener Anda.

```php
namespace App\Listeners;

use Duitku\Laravel\Events\DuitkuPaymentReceived;

class UpdateOrderAsPaid
{
    public function handle(DuitkuPaymentReceived $event)
    {
        $data = $event->callback;

        // $data->merchantOrderId
        // $data->amount
        // $data->reference

        // Update database Anda di sini...
    }
}
```

## Daftar Events yang Tersedia

1.  **`DuitkuCallbackReceived`**: Ter-dispatch untuk SEMUA callback yang valid.
2.  **`DuitkuPaymentReceived`**: Ter-dispatch hanya jika `resultCode` adalah `00` (Success).
3.  **`DuitkuPaymentFailed`**: Ter-dispatch jika pembayaran gagal atau expired.

---

Mari kita lihat bagaimana menangani error dengan [Error Handling](./error-handling).
