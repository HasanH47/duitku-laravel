# Duitku Laravel SDK

[![Tests](https://github.com/HasanH47/duitku-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/HasanH47/duitku-laravel/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/HasanH47/duitku-laravel/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/HasanH47/duitku-laravel/actions/workflows/static-analysis.yml)
![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![Laravel Version](https://img.shields.io/badge/laravel-12.x%20%7C%2013.x-red)

SDK komunitas untuk mengintegrasikan [Duitku Payment Gateway](https://www.duitku.com/) dengan Laravel 12–13.

> Seri v3 membawa perubahan breaking. Baca [panduan migrasi](docs/guide/upgrading.md) dan [changelog](CHANGELOG.md) sebelum memperbarui instalasi lama: signature default sekarang HMAC-SHA256, callback memverifikasi status provider, dan hasil batch memakai `BatchResult`.

> **Apa itu SDK ini?**
> SDK ini adalah "jembatan" antara aplikasi Laravel kamu dan Duitku. Tanpa SDK ini, kamu harus menulis kode HTTP request manual, menghitung signature sendiri, dan mengurus banyak hal teknis. Dengan SDK ini, semua itu sudah diurus — kamu tinggal panggil method-nya saja.

---

## ✨ Fitur Utama

| Fitur                     | Penjelasan                                                                                                     |
| ------------------------- | -------------------------------------------------------------------------------------------------------------- |
| 🔒 **Typed DTOs**         | Semua request & response pakai object, bukan array mentah. IDE auto-complete jalan, typo ketahuan saat coding. |
| 🛡️ **Auto Signature**     | HMAC-SHA256 untuk pembayaran dan POP; protokol disbursement tetap terpisah. |
| 🚀 **Parallel Check**     | Request async dengan concurrency terbatas dan hasil/error per input. |
| 📢 **Event-Driven**       | Handle callback pakai Laravel Events — kode lebih bersih, gampang di-test.                                     |
| 💸 **Disbursement**       | Transfer dana, cek saldo, verifikasi rekening — semua dari satu SDK.                                           |
| ⚡ **Duitku POP**         | Integrasi popup pembayaran tanpa redirect halaman.                                                             |
| 🧪 **Testable**           | Mudah di-mock pakai `Http::fake()`, dibuat dengan Pest PHP.                                                    |
| 📝 **Payment Method Enum** | Gunakan `PaymentMethod::active()` dan daftar metode dari API untuk pilihan yang tersedia. |

---

## 🚀 Quick Start (5 Menit)

### Langkah 1: Install

```bash
composer require duitku/laravel:^3.0
```

### Langkah 2: Publish Config

```bash
php artisan vendor:publish --tag=duitku-config
# Jika menggunakan Blade component POP:
php artisan vendor:publish --tag=duitku-assets
```

> Perintah ini akan membuat file `config/duitku.php` di project kamu. File ini berisi semua pengaturan yang bisa kamu ubah.

### Langkah 3: Setup `.env`

Tambahkan kredensial Duitku kamu. Kredensial ini didapat dari [Dashboard Duitku](https://passport.duitku.com/merchant/Project).

```dotenv
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
    paymentMethod: 'BC',                        // Wajib untuk API checkout; opsional pada POP
    customerVaName: 'Pelanggan',
    callbackUrl: route('duitku.callback'),
    returnUrl: route('checkout.return'),
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
// Maksimum 5 request aktif secara default; waktu selesai bergantung pada provider.
$statuses = Duitku::checkStatuses(['INV-001', 'INV-002', 'INV-003']);

foreach ($statuses as $result) {
    if ($result->successful()) {
        $status = $result->get();
        echo "{$result->key}: {$status->statusCode}";
    } else {
        report($result->error);
    }
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

// Di aplikasi, daftarkan listener idempotent untuk DuitkuPaymentReceived.
// Contoh lengkap yang diuji tersedia pada tautan di bawah.
```

Salin dan sesuaikan [contoh listener idempotent](usage-examples/IdempotentPaymentListener.php) ke aplikasi kamu. Event dapat dikirim lebih dari sekali; SDK tidak menyimpan order atau menjanjikan exactly-once delivery.

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
$inquiry = Duitku::disbursement()->transfer()->inquiry($info);
echo "Nama: " . $inquiry->accountName;

// 2. Eksekusi transfer
$transfer = Duitku::disbursement()->transfer()->execute(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber,
);
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

Periksa konfigurasi dengan `php artisan duitku:doctor`; tambahkan `--pop`, `--disbursement`, atau `--strict` sesuai kebutuhan. Command tidak menghubungi provider atau menampilkan kredensial.

Untuk test aplikasi, `Duitku::fake()->payment('ORDER-001', 10000, statusCode: '00')` menyediakan respons sintetis sambil mempertahankan validasi dan event SDK. Baca [panduan diagnosis dan fake provider](./docs/guide/testing.md) serta [hasil sandbox](./docs/reviews/2026-10-22-sandbox.md).

```bash
# Jalankan semua test
composer test

# Static analysis
./vendor/bin/phpstan analyse

# Formatter dan test JavaScript POP
composer format:check
node tests/js/pop.test.cjs
```

## 🤝 Contributing

Dependabot memantau dependency, sedangkan workflow mingguan memantau perubahan dokumentasi dan SDK resmi Duitku. Perubahan upstream dilaporkan melalui satu issue terbuka yang terus diperbarui. Baca [panduan pemantauan](docs/guide/maintenance.md) untuk menindaklanjuti perubahan dan memperbarui baseline.

Kontribusi bug fix, dokumentasi, test, dan fitur diterima melalui issue atau pull request. Baca [panduan kontribusi](CONTRIBUTING.md), [Code of Conduct](CODE_OF_CONDUCT.md), dan [panduan bantuan](SUPPORT.md). Kerentanan dilaporkan melalui [kebijakan keamanan](SECURITY.md).

## 📄 Lisensi

MIT License — lihat file [LICENSE](LICENSE) untuk detail.
