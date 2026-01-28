# Installation

Pastikan aplikasi Anda memenuhi persyaratan minimum:

- **PHP** v8.2 atau lebih tinggi.
- **Laravel** v10.0, v11.0, atau v12.0.

## Langkah 1: Install via Composer

Jalankan perintah berikut di direktori root project Laravel Anda:

```bash
composer require duitku/laravel
```

## Langkah 2: Publish Konfigurasi

Jalankan perintah artisan untuk menyalin file konfigurasi ke project Anda:

```bash
php artisan vendor:publish --provider="Duitku\Laravel\DuitkuServiceProvider" --tag="config"
```

Perintah ini akan membuat file `config/duitku.php` yang bisa Anda sesuaikan.

## Langkah 3: Setup Environment

Buka file `.env` Anda dan tambahkan informasi kredensial yang didapat dari Dashboard Duitku:

```env
DUITKU_MERCHANT_CODE=DXXX
DUITKU_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DUITKU_SANDBOX_MODE=true
```

> [!TIP]
> Mulailah dengan mode sandbox (`true`) untuk pengujian sebelum pindah ke lingkungan produksi.

Setelah instalasi selesai, lanjut ke tahap [Konfigurasi](./configuration).
