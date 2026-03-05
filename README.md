# Duitku Laravel SDK

![Tests](https://github.com/HasanH47/duitku-laravel/workflows/Tests/badge.svg)
[![Static Analysis](https://github.com/HasanH47/duitku-laravel/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/HasanH47/duitku-laravel/actions/workflows/static-analysis.yml)
![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red)

SDK Laravel **resmi komunitas** untuk [Duitku Payment Gateway](https://www.duitku.com/) — Modern, Typed, dan Production-Ready.

> **Apa itu SDK ini?**
> SDK ini adalah "jembatan" antara aplikasi Laravel kamu dan Duitku. Tanpa SDK ini, kamu harus menulis kode HTTP request manual, menghitung signature sendiri, dan mengurus banyak hal teknis. Dengan SDK ini, semua itu sudah diurus — kamu tinggal panggil method-nya saja.

---

## ✨ Fitur Utama

| Fitur                     | Penjelasan                                                                                                     |
| ------------------------- | -------------------------------------------------------------------------------------------------------------- |
| 🔒 **Typed DTOs**         | Semua request & response pakai object, bukan array mentah. IDE auto-complete jalan, typo ketahuan saat coding. |
| 🛡️ **Auto Signature**     | Signature MD5/SHA256 digenerate & divalidasi otomatis. Tidak perlu hitung hash manual.                         |
| 🚀 **Parallel Check**     | Cek status 50+ transaksi dalam <1 detik pakai `Http::pool`.                                                    |
| 📢 **Event-Driven**       | Handle callback pakai Laravel Events — kode lebih bersih, gampang di-test.                                     |
| 💸 **Disbursement**       | Transfer dana, cek saldo, verifikasi rekening — semua dari satu SDK.                                           |
| ⚡ **Duitku POP**         | Integrasi popup pembayaran tanpa redirect halaman.                                                             |
| 🧪 **Testable**           | Mudah di-mock pakai `Http::fake()`, dibuat dengan Pest PHP.                                                    |
| 📝 **33 Payment Methods** | Semua metode pembayaran Duitku tersedia sebagai PHP Enum.                                                      |

---

## 🚀 Quick Start (5 Menit)

### Langkah 1: Install

```bash
composer require duitku/laravel
```

### Langkah 2: Publish Config

```bash
php artisan vendor:publish --tag=duitku-config
```

> Perintah ini akan membuat file `config/duitku.php` di project kamu. File ini berisi semua pengaturan yang bisa kamu ubah.

### Langkah 3: Setup `.env`

Tambahkan kredensial Duitku kamu. Kredensial ini didapat dari [Dashboard Duitku](https://passport.duitku.com/merchant/Project).

```env
# Wajib
DUITKU_MERCHANT_CODE=DXXX            # Merchant Code dari dashboard Duitku
DUITKU_API_KEY=xxx...xxx              # API Key dari dashboard Duitku
DUITKU_SANDBOX_MODE=true              # true = testing, false = production

# Opsional (untuk Disbursement / Transfer Dana)
DUITKU_USER_ID=your-user-id
DUITKU_EMAIL=your-email@example.com

# Opsional (untuk HTTP & Logging)
DUITKU_TIMEOUT=30                     # Timeout request dalam detik
DUITKU_RETRY_TIMES=0                  # Jumlah retry jika gagal
DUITKU_LOG_CHANNEL=                   # Channel log Laravel (kosongkan = tidak log)
```

### Langkah 4: Buat Pembayaran Pertamamu! 🎉

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

// 1. Buat request pembayaran
$request = new PaymentRequest(
    amount: 50000,                              // Nominal pembayaran (dalam Rupiah)
    merchantOrderId: 'INV-' . time(),           // ID unik untuk order ini
    productDetails: 'Topup 50 Diamonds',        // Deskripsi produk
    email: 'pelanggan@example.com',             // Email pelanggan
    paymentMethod: 'VC'                         // Metode bayar (opsional)
);

// 2. Kirim ke Duitku → dapat URL pembayaran
$response = Duitku::checkout($request);

// 3. Arahkan pelanggan ke halaman pembayaran Duitku
return redirect($response->paymentUrl);
```

> **Apa yang terjadi?**
> Duitku akan membuat "invoice" pembayaran dan memberikan URL. Pelanggan kamu akan diarahkan ke URL tersebut untuk menyelesaikan pembayaran (transfer VA, scan QRIS, dll).

---

## 📋 Contoh Fitur Lainnya

### Cek Status Transaksi

```php
use Duitku\Laravel\Support\PaymentCode;

$status = Duitku::checkStatus('INV-123');

if ($status->statusCode === PaymentCode::SUCCESS) {
    echo "✅ Sudah dibayar!";
}
```

### Cek Banyak Transaksi Sekaligus (Parallel) 🚀

```php
// Cek 100 transaksi dalam < 1 detik!
$statuses = Duitku::checkStatuses(['INV-001', 'INV-002', 'INV-003']);

foreach ($statuses as $status) {
    echo "{$status->merchantOrderId}: {$status->statusCode}";
}
```

### Handle Callback (Webhook) dengan Events

```php
// Di Controller — cukup 1 baris!
public function callback(Request $request)
{
    Duitku::handleCallback($request->all()); // Auto validasi + dispatch event
    return response('OK');
}

// Di Listener — tangkap event pembayaran sukses
class UpdateOrderPaid
{
    public function handle(DuitkuPaymentReceived $event)
    {
        $order = Order::where('id', $event->callback->merchantOrderId)->first();
        $order->update(['status' => 'paid']);
    }
}
```

### Duitku POP (Pembayaran Popup)

```php
// Backend: dapatkan reference token
$response = Duitku::pop()->createTransaction($request);

// Frontend: tampilkan popup dengan Blade Component
```

```html
<x-duitku-pop :reference="$response->reference" button-text="Bayar Sekarang" />
```

### Disbursement (Transfer Dana)

```php
// 1. Verifikasi rekening tujuan
$inquiry = Duitku::disbursement()->bankInquiry($info);
echo "Nama: " . $inquiry->accountName;

// 2. Eksekusi transfer
$transfer = Duitku::disbursement()->transfer(...);
```

---

## 🗺️ Kapan Pakai API vs POP?

|                     | **API (Redirect)**                    | **POP (Popup)**                     |
| ------------------- | ------------------------------------- | ----------------------------------- |
| **Cara Kerja**      | Pelanggan diarahkan ke halaman Duitku | Popup muncul di halaman kamu        |
| **Metode Bayar**    | Bisa pilih satu metode spesifik       | Pelanggan pilih sendiri di popup    |
| **Cocok Untuk**     | E-commerce, checkout standar          | SaaS, top-up, donasi                |
| **User Experience** | Redirect → bayar → kembali            | Bayar langsung tanpa pindah halaman |

---

## 📖 Dokumentasi Lengkap

Dokumentasi detail tersedia di [VitePress Docs](./docs/guide/introduction.md):

1. [Introduction](./docs/guide/introduction.md) — Kenapa pakai SDK ini
2. [Installation](./docs/guide/installation.md) — Instalasi step-by-step
3. [Configuration](./docs/guide/configuration.md) — Semua opsi konfigurasi
4. [Payments](./docs/guide/usage-payments.md) — Buat pembayaran dengan typed DTOs
5. [Duitku POP](./docs/guide/usage-pop.md) — Integrasi popup
6. [Callback System](./docs/guide/callback-system.md) — Handle webhook
7. [Error Handling](./docs/guide/error-handling.md) — Exception & error codes
8. [Disbursement](./docs/guide/usage-disbursement.md) — Transfer dana
9. [Blade Components](./docs/guide/blade-components.md) — UI components

---

## 🧪 Testing

```bash
# Jalankan semua test
composer test

# Static analysis
./vendor/bin/phpstan analyse
```

## 🤝 Contributing

Contributions are welcome! Silakan buka Issue atau Pull Request.

## 📄 Lisensi

MIT License — lihat file [LICENSE](LICENSE) untuk detail.
