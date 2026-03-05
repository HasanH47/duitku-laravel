# Configuration

SDK ini sangat fleksibel dan dapat dikonfigurasi melalui file `config/duitku.php` atau langsung melalui `.env`.

## File Konfigurasi

Isi default dari `config/duitku.php` adalah sebagai berikut:

```php
return [
    // Merchant Code dari Duitku
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    // API Key rahasia dari Duitku
    'api_key' => env('DUITKU_API_KEY', ''),

    // Mode sandbox (true = testing, false = production)
    'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),

    // Waktu expired pembayaran dalam menit (default: 60)
    'default_expiry' => env('DUITKU_DEFAULT_EXPIRY', 60),

    // Kredensial Disbursement
    'user_id' => env('DUITKU_USER_ID', ''),
    'email' => env('DUITKU_EMAIL', ''),

    // HTTP Client Settings
    'timeout' => env('DUITKU_TIMEOUT', 30),           // Timeout dalam detik
    'retry_times' => env('DUITKU_RETRY_TIMES', 0),    // Jumlah retry (0 = tidak retry)
    'retry_sleep' => env('DUITKU_RETRY_SLEEP', 100),  // Jeda antar retry dalam ms

    // Logging
    'log_channel' => env('DUITKU_LOG_CHANNEL', null),  // Channel Laravel log
];
```

## Opsi Konfigurasi Detail

### `merchant_code`

Merchant Code unik Anda yang terdaftar di sistem Duitku.

### `api_key`

API Key rahasia yang digunakan untuk menghasilkan signature HMAC.

### `sandbox_mode`

Jika diatur ke `true`, SDK akan mengarah ke endpoint sandbox Duitku. Set `false` untuk production.

### `default_expiry`

Waktu expired pembayaran dalam menit (default: 60 menit).

### `user_id` & `email`

Diperlukan khusus untuk fitur **Disbursement** (Transfer Online). Gunakan kredensial yang diberikan oleh tim Duitku.

### `timeout`

Timeout untuk setiap request HTTP ke Duitku API, dalam detik. Default: 30 detik.

### `retry_times`

Jumlah percobaan ulang jika request gagal (misalnya timeout). Set `0` untuk tidak melakukan retry. Berguna untuk production agar lebih resilient.

### `retry_sleep`

Jeda antar percobaan ulang, dalam milidetik. Default: 100ms.

### `log_channel`

Nama channel log Laravel untuk mencatat semua request ke Duitku API. Set ke `null` untuk menonaktifkan. Contoh: `'duitku'` atau `'stack'`.

```env
# Contoh .env untuk production
DUITKU_TIMEOUT=15
DUITKU_RETRY_TIMES=3
DUITKU_RETRY_SLEEP=500
DUITKU_LOG_CHANNEL=duitku
```

---

Setelah konfigurasi siap, Anda bisa mulai mengeksplorasi [Usage - Payments](./usage-payments).
