# Installation

Halaman ini menjelaskan cara memasang SDK Duitku Laravel ke project kamu, **step by step**.

---

## 📋 Persyaratan

Sebelum mulai, pastikan project kamu memenuhi syarat berikut:

| Kebutuhan    | Versi Minimum         | Cara Cek                |
| ------------ | --------------------- | ----------------------- |
| **PHP**      | 8.2 atau lebih tinggi | `php -v`                |
| **Laravel**  | 10.x, 11.x, atau 12.x | `php artisan --version` |
| **Composer** | Versi terbaru         | `composer --version`    |

> [!WARNING]
> Jika PHP kamu masih di bawah 8.2, kamu perlu upgrade terlebih dahulu. SDK ini menggunakan fitur PHP modern seperti Enum dan Named Arguments yang hanya tersedia di PHP 8.1+.

---

## Langkah 1: Install via Composer

Buka terminal di folder root project Laravel kamu, lalu jalankan:

```bash
composer require duitku/laravel
```

**Apa yang terjadi?**

- Composer akan mengunduh SDK ini beserta semua dependensinya
- Laravel akan otomatis mendeteksi SDK ini (auto-discovery)
- Facade `Duitku` langsung bisa digunakan tanpa konfigurasi tambahan

> [!TIP]
> Jika kamu mendapatkan error saat instalasi, coba jalankan `composer update` terlebih dahulu untuk memastikan semua package kamu up-to-date.

---

## Langkah 2: Publish File Konfigurasi

```bash
php artisan vendor:publish --provider="Duitku\Laravel\DuitkuServiceProvider" --tag="config"
```

**Apa yang terjadi?**

- Perintah ini menyalin file `config/duitku.php` ke project kamu
- File ini berisi semua pengaturan yang bisa kamu ubah (Merchant Code, API Key, timeout, dll)
- Kamu **harus** menjalankan perintah ini agar bisa mengkonfigurasi SDK

> [!IMPORTANT]
> Jika kamu skip langkah ini, SDK tetap bisa berjalan tapi akan menggunakan nilai default. Kamu tidak akan bisa mengubah pengaturan.

**Jika berhasil**, kamu akan melihat pesan seperti:

```
Copied File [/vendor/duitku/laravel/config/duitku.php] To [/config/duitku.php]
```

---

## Langkah 3: Setup Environment Variables

Buka file `.env` di root project kamu dan tambahkan:

```env
# === WAJIB ===

# Merchant Code: Kode unik merchant kamu dari dashboard Duitku
# Dapat dari: https://passport.duitku.com/merchant/Project
DUITKU_MERCHANT_CODE=DXXX

# API Key: Kunci rahasia untuk generate signature
# Dapat dari: https://passport.duitku.com/merchant/Project (klik "API Keys")
DUITKU_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Sandbox Mode: Mode testing (true) atau production (false)
# PENTING: Selalu gunakan true saat development!
DUITKU_SANDBOX_MODE=true

# === OPSIONAL (untuk Disbursement / Transfer Dana) ===

# User ID dan Email dari Duitku — khusus fitur Disbursement
DUITKU_USER_ID=
DUITKU_EMAIL=

# === OPSIONAL (untuk HTTP & Logging) ===

# Timeout request dalam detik (default: 30 detik)
DUITKU_TIMEOUT=30

# Jumlah retry jika request gagal (default: 0 = tidak retry)
DUITKU_RETRY_TIMES=0

# Jeda antar retry dalam milidetik (default: 100ms)
DUITKU_RETRY_SLEEP=100

# Channel log Laravel untuk mencatat request (kosongkan = tidak log)
DUITKU_LOG_CHANNEL=
```

### Cara Mendapatkan Merchant Code & API Key

1. **Daftar** di [Duitku](https://passport.duitku.com/merchant/Register) (gratis)
2. **Login** ke [Dashboard Duitku](https://passport.duitku.com/merchant/Project)
3. **Buat Project** baru atau pilih project yang sudah ada
4. **Copy** Merchant Code dan API Key dari halaman project

> [!TIP]
> Untuk testing, gunakan **Sandbox Mode** (`DUITKU_SANDBOX_MODE=true`). Di mode ini, semua transaksi adalah simulasi — tidak ada uang sungguhan yang berpindah. Merchant Code dan API Key untuk sandbox berbeda dengan production!

---

## Langkah 4: Verifikasi Instalasi ✅

Untuk memastikan semuanya terpasang dengan benar, buka `php artisan tinker` dan jalankan:

```php
use Duitku\Laravel\Facades\Duitku;

// Coba ambil daftar metode pembayaran
$methods = Duitku::paymentMethods(10000);

// Jika berhasil, kamu akan melihat daftar metode pembayaran
dd($methods);
```

Jika kamu melihat daftar metode pembayaran (atau error "Wrong signature" yang berarti API Key salah), berarti instalasi berhasil! 🎉

---

## ❌ Troubleshooting

### Error: "Class Duitku not found"

**Penyebab:** Auto-discovery tidak berjalan.
**Solusi:** Jalankan `composer dump-autoload` dan coba lagi.

### Error: "Wrong signature"

**Penyebab:** Merchant Code atau API Key salah.
**Solusi:** Cek kembali `.env` kamu. Pastikan tidak ada spasi atau karakter extra. Pastikan kamu menggunakan credential Sandbox (bukan Production) jika `DUITKU_SANDBOX_MODE=true`.

### Error: "Connection timed out"

**Penyebab:** Server kamu tidak bisa mengakses API Duitku.
**Solusi:** Cek koneksi internet server. Jika pakai firewall, pastikan port 443 (HTTPS) terbuka.

### Error: "Package duitku/laravel not found"

**Penyebab:** Package belum publish ke Packagist.
**Solusi:** Hubungi maintainer SDK atau install langsung dari repository.

---

Instalasi selesai! Lanjut ke [Konfigurasi](./configuration) untuk pengaturan lebih detail. 🚀
