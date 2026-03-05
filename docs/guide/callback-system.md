# Callback System (Event-Driven)

Setelah pelanggan membayar, **bagaimana aplikasi kamu tahu?** Jawabannya: **Callback** (Webhook).

---

## 🤔 Apa Itu Callback?

Callback adalah **permintaan HTTP POST** yang dikirim oleh Duitku ke server kamu setelah pelanggan menyelesaikan pembayaran.

> **Analogi:** Bayangkan kamu antar baju ke laundry. Saat baju selesai dicuci, laundry **menelepon** kamu: "Baju sudah selesai!" Callback itu "telepon" dari Duitku ke server kamu.

**Yang terjadi:**

```
Pelanggan bayar di Duitku
       ↓
Duitku kirim HTTP POST ke URL callback kamu
       ↓
Server kamu terima data (merchantOrderId, resultCode, dll)
       ↓
Aplikasi kamu update database: order = paid
```

> [!IMPORTANT]
> Callback dikirim **server-to-server** (backend ke backend). Ini berbeda dengan redirect yang terjadi di browser pelanggan. Callback lebih reliable karena tidak bergantung pada browser pelanggan.

---

## 🛡️ Kenapa Harus Validasi Signature?

Siapa saja bisa mengirim HTTP POST ke URL callback kamu — termasuk hacker. Untuk memastikan bahwa data benar-benar dari Duitku (bukan penipu), kamu harus **memvalidasi signature**.

> **Analogi:** Bayangkan ada orang ketuk pintu dan bilang "Paket dari Toko A". Kamu harus cek identitasnya dulu sebelum terima paket. Signature = identitas yang dicek.

SDK ini **otomatis** melakukan validasi signature. Kamu tidak perlu hitung MD5 manual.

---

## ⚙️ Setup Route untuk Callback

Pertama, buat route untuk menerima callback. URL ini harus didaftarkan di [Dashboard Duitku](https://passport.duitku.com/merchant/Project).

```php
// routes/api.php
Route::post('/duitku/callback', [PaymentController::class, 'callback']);
```

> [!WARNING]
> **Route callback harus exempt dari CSRF protection!** Karena Duitku mengirim POST request tanpa CSRF token.

Tambahkan URL callback ke exception list di `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'duitku/callback',  // Exempt callback Duitku dari CSRF
    ]);
})
```

Atau di `app/Http/Middleware/VerifyCsrfToken.php` (Laravel 10):

```php
protected $except = [
    'duitku/callback',
];
```

---

## Cara 1: Manual (Simple)

Jika kamu ingin handle sendiri dengan logic sederhana:

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Support\PaymentCode;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        // 1. Validasi signature (cek keaslian data)
        if (!Duitku::validateCallback($request->all())) {
            // Data tidak valid (mungkin palsu) → tolak!
            abort(403, 'Invalid Signature');
        }

        // 2. Proses berdasarkan result code
        $orderId = $request->merchantOrderId;
        $resultCode = $request->resultCode;

        if ($resultCode === PaymentCode::SUCCESS) {
            // ✅ Pembayaran berhasil → update database
            Order::where('order_id', $orderId)->update(['status' => 'paid']);
        } else {
            // ❌ Pembayaran gagal/expired
            Order::where('order_id', $orderId)->update(['status' => 'failed']);
        }

        return response('OK', 200);
    }
}
```

---

## Cara 2: Event-Driven (Recommended) ⭐

Cara yang lebih **bersih** dan **scalable** adalah menggunakan Laravel Events. Dengan cara ini, controller kamu cuma 1 baris, dan semua logic business ada di Listener terpisah.

### Langkah 1: Di Controller

```php
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        // Satu baris ini melakukan:
        // 1. Validasi signature ✓
        // 2. Dispatch event yang sesuai ✓
        // 3. Throw exception jika signature invalid ✓
        Duitku::handleCallback($request->all());

        return response('OK', 200);
    }
}
```

> **Kenapa lebih baik?** Controller cukup tipis (1 baris). Logic "apa yang dilakukan setelah bayar" ada di Listener — lebih mudah di-test dan di-maintain.

### Langkah 2: Buat Listener

```bash
php artisan make:listener UpdateOrderPaid
```

```php
// app/Listeners/UpdateOrderPaid.php

namespace App\Listeners;

use App\Models\Order;
use Duitku\Laravel\Events\DuitkuPaymentReceived;

class UpdateOrderPaid
{
    public function handle(DuitkuPaymentReceived $event): void
    {
        // Ambil data callback (sudah tervalidasi signature-nya)
        $callback = $event->callback;

        // Data yang tersedia:
        // $callback->merchantOrderId  → ID order kamu
        // $callback->amount           → Nominal pembayaran
        // $callback->resultCode       → '00' = sukses
        // $callback->reference        → Reference ID Duitku
        // $callback->signature        → Signature (sudah divalidasi)

        // Update database
        $order = Order::where('order_id', $callback->merchantOrderId)->first();

        if ($order) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'duitku_reference' => $callback->reference,
            ]);
        }
    }
}
```

### Langkah 3: Daftarkan Listener (Laravel 10)

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Duitku\Laravel\Events\DuitkuPaymentReceived::class => [
        \App\Listeners\UpdateOrderPaid::class,
    ],
    \Duitku\Laravel\Events\DuitkuPaymentFailed::class => [
        \App\Listeners\HandleFailedPayment::class,
    ],
];
```

> [!TIP]
> **Laravel 11+** mendukung Event Discovery — listener akan otomatis terdeteksi tanpa perlu didaftarkan, selama event di-type-hint dengan benar di method `handle()`.

---

## 📋 Daftar Events yang Tersedia

| Event                    | Kapan Di-dispatch?                                         |
| ------------------------ | ---------------------------------------------------------- |
| `DuitkuCallbackReceived` | **Setiap** callback yang valid (baik sukses maupun gagal)  |
| `DuitkuPaymentReceived`  | Hanya jika `resultCode` = `'00'` (pembayaran **berhasil**) |
| `DuitkuPaymentFailed`    | Jika `resultCode` ≠ `'00'` (pembayaran **gagal/expired**)  |

**Kapan pakai mana?**

- Gunakan `DuitkuPaymentReceived` untuk update order jadi "paid"
- Gunakan `DuitkuPaymentFailed` untuk notifikasi admin atau kirim email reminder
- Gunakan `DuitkuCallbackReceived` untuk logging/audit trail (semua callback)

---

## 🧪 Testing Callback di Lokal

Duitku mengirim callback ke URL yang bisa diakses dari internet. Saat development di lokal, server kamu biasanya tidak bisa diakses dari luar. Solusinya:

### Pakai ngrok (Gratis)

```bash
# 1. Install ngrok: https://ngrok.com
# 2. Jalankan tunnel ke port Laravel kamu
ngrok http 8000

# 3. Kamu akan dapat URL seperti:
# https://abc123.ngrok.io

# 4. Set URL callback di Dashboard Duitku:
# https://abc123.ngrok.io/api/duitku/callback
```

### Pakai `expose` (Alternatif)

```bash
expose share http://localhost:8000
```

> [!TIP]
> Jangan lupa update URL callback di Dashboard Duitku setiap kali URL ngrok berubah!

---

Selanjutnya, pelajari cara menangani error dengan [Error Handling](./error-handling). 🛡️
