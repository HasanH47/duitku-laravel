# Configuration

SDK ini sangat fleksibel dan dapat dikonfigurasi melalui file `config/duitku.php` atau langsung melalui `.env`.

## File Konfigurasi

Isi default dari `config/duitku.php` adalah sebagai berikut:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Duitku Merchant Code
    |--------------------------------------------------------------------------
    */
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Duitku API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('DUITKU_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    */
    'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Expiry Period
    |--------------------------------------------------------------------------
    */
    'default_expiry' => env('DUITKU_DEFAULT_EXPIRY', 60),

    /*
    |--------------------------------------------------------------------------
    | Duitku Disbursement Config
    |--------------------------------------------------------------------------
    */
    'user_id' => env('DUITKU_USER_ID', ''),
    'email' => env('DUITKU_EMAIL', ''),
];
```

## Opsi Konfigurasi Detail

### `merchant_code`

Merchant Code unik Anda yang terdaftar di sistem Duitku.

### `api_key`

API Key rahasia yang digunakan untuk menghasilkan signature HMAC.

### `sandbox_mode`

Jika diatur ke `true`, SDK akan mengarah ke endpoint sandbox Duitku.

### `default_expiry`

Waktu expired pembayaran dalam menit (default: 60 menit).

### `user_id` & `email`

Diperlukan khusus untuk fitur **Disbursement** (Transfer Online). Gunakan kredensial yang diberikan oleh tim Duitku untuk akses Disbursement.

---

Setelah konfigurasi siap, Anda bisa mulai mengeksplorasi [Usage - Payments](./usage-payments).
