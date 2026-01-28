# Configuration

SDK ini sangat fleksibel dan dapat dikonfigurasi melalui file `config/duitku.php` atau langsung melalui `.env`.

## File Konfigurasi

Isi default dari `config/duitku.php` adalah sebagai berikut:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Duitku Merchant Credentials
    |--------------------------------------------------------------------------
    */
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    */
    'sandbox' => env('DUITKU_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Default URLs
    |--------------------------------------------------------------------------
    | URLs ini digunakan untuk mengarahkan user setelah transaksi.
    */
    'return_url' => env('DUITKU_RETURN_URL', 'https://example.com/return'),
    'callback_url' => env('DUITKU_CALLBACK_URL', 'https://example.com/callback'),
];
```

## Opsi Konfigurasi Detail

### `merchant_code`

Merchant Code unik Anda yang terdaftar di sistem Duitku.

### `api_key`

API Key rahasia yang digunakan untuk menghasilkan signature HMAC. **Jangan pernah membagikan key ini!**

### `sandbox`

Jika diatur ke `true`, SDK akan mengarah ke endpoint sandbox Duitku. Gunakan kartu testing saat dalam mode ini.

### `callback_url`

Endpoint di aplikasi Anda yang akan menerima notifikasi HTTP POST dari Duitku saat status transaksi berubah. Pastikan endpoint ini dapat diakses secara publik dan tidak diblokir oleh CSRF protection.

---

Setelah konfigurasi siap, Anda bisa mulai mengeksplorasi [Usage - Payments](./usage-payments).
