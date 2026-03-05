# Configuration

Halaman ini menjelaskan **semua opsi konfigurasi** yang tersedia di SDK ini — apa fungsinya, kapan perlu diubah, dan nilai yang direkomendasikan.

---

## File Konfigurasi

Setelah menjalankan `vendor:publish`, file `config/duitku.php` akan muncul di project kamu. Berikut isi lengkapnya beserta penjelasan:

```php
return [
    // Kode unik merchant dari Dashboard Duitku
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    // API Key rahasia dari Dashboard Duitku
    'api_key' => env('DUITKU_API_KEY', ''),

    // Mode sandbox: true = testing, false = production
    'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),

    // Waktu expired pembayaran dalam menit
    'default_expiry' => env('DUITKU_DEFAULT_EXPIRY', 60),

    // Kredensial untuk fitur Disbursement
    'user_id' => env('DUITKU_USER_ID', ''),
    'email' => env('DUITKU_EMAIL', ''),

    // HTTP Client settings
    'timeout' => env('DUITKU_TIMEOUT', 30),
    'retry_times' => env('DUITKU_RETRY_TIMES', 0),
    'retry_sleep' => env('DUITKU_RETRY_SLEEP', 100),
    'log_channel' => env('DUITKU_LOG_CHANNEL', null),
];
```

---

## Penjelasan Detail Setiap Opsi

### `merchant_code`

|                      |                                                                     |
| -------------------- | ------------------------------------------------------------------- |
| **Apa ini?**         | Kode unik yang mengidentifikasi toko/merchant kamu di sistem Duitku |
| **Dapat dari mana?** | [Dashboard Duitku](https://passport.duitku.com/merchant/Project)    |
| **Wajib?**           | ✅ Ya — tanpa ini, SDK tidak bisa berkomunikasi dengan Duitku       |
| **Contoh**           | `'D0001'`, `'DS1234'`                                               |

> [!WARNING]
> Merchant Code untuk **Sandbox** dan **Production** berbeda! Pastikan kamu menggunakan yang sesuai dengan mode yang aktif.

---

### `api_key`

|                      |                                                                                                 |
| -------------------- | ----------------------------------------------------------------------------------------------- |
| **Apa ini?**         | Kunci rahasia untuk menghasilkan signature (tanda tangan digital)                               |
| **Fungsinya**        | Setiap request ke Duitku harus disertai signature agar Duitku yakin request-nya benar dari kamu |
| **Dapat dari mana?** | [Dashboard Duitku](https://passport.duitku.com/merchant/Project) → klik "API Keys"              |
| **Wajib?**           | ✅ Ya                                                                                           |

> [!IMPORTANT]
> **Jangan pernah** meng-commit API Key ke Git! Selalu simpan di `.env` dan pastikan `.env` ada di `.gitignore`.

---

### `sandbox_mode`

|              |                                                                                               |
| ------------ | --------------------------------------------------------------------------------------------- |
| **Apa ini?** | Menentukan apakah SDK mengarah ke server testing (Sandbox) atau server sungguhan (Production) |
| **`true`**   | Mode testing — transaksi simulasi, tidak ada uang sungguhan                                   |
| **`false`**  | Mode production — transaksi sungguhan dengan uang asli                                        |
| **Default**  | `true` (supaya developer baru tidak langsung kena biaya)                                      |

> [!CAUTION]
> **Jangan lupa** ubah ke `false` saat deploy ke production! Jika lupa, pelanggan kamu tidak bisa bayar sungguhan.

---

### `default_expiry`

|                   |                                                                       |
| ----------------- | --------------------------------------------------------------------- |
| **Apa ini?**      | Berapa lama (dalam menit) invoice pembayaran berlaku sebelum expired  |
| **Default**       | `60` (1 jam)                                                          |
| **Kapan diubah?** | Jika kamu ingin pembayaran berumur lebih lama (misal 24 jam = `1440`) |

**Contoh:**

- Toko online biasa: `60` menit (1 jam)
- Tiket event: `30` menit (supaya cepat expired dan tiket bisa dijual lagi)
- Tagihan bulanan: `1440` menit (24 jam)

> Kamu juga bisa mengatur expiry **per transaksi** melalui parameter `expiryPeriod` di `PaymentRequest`.

---

### `user_id` & `email`

|                      |                                                                |
| -------------------- | -------------------------------------------------------------- |
| **Apa ini?**         | Kredensial khusus untuk fitur **Disbursement** (Transfer Dana) |
| **Wajib?**           | ❌ Tidak — hanya jika kamu pakai fitur transfer dana           |
| **Dapat dari mana?** | Hubungi tim Duitku untuk mengaktifkan fitur Disbursement       |

---

### `timeout`

|                          |                                                                              |
| ------------------------ | ---------------------------------------------------------------------------- |
| **Apa ini?**             | Berapa lama (dalam detik) SDK menunggu response dari Duitku sebelum menyerah |
| **Default**              | `30` detik                                                                   |
| **Kalau terlalu kecil?** | Request bisa gagal padahal Duitku belum selesai memproses                    |
| **Kalau terlalu besar?** | User menunggu terlalu lama jika Duitku sedang lambat                         |

**Rekomendasi:**

- Development: `30` detik (default)
- Production: `15` detik (supaya user tidak menunggu terlalu lama)

---

### `retry_times`

|                   |                                                                                    |
| ----------------- | ---------------------------------------------------------------------------------- |
| **Apa ini?**      | Berapa kali SDK mencoba ulang jika request gagal (misal timeout atau server error) |
| **Default**       | `0` (tidak retry sama sekali)                                                      |
| **Kapan diubah?** | Di production, disarankan set ke `2` atau `3` agar lebih resilient                 |

> **Analogi:** Bayangkan kamu telepon seseorang tapi tidak diangkat. `retry_times` = berapa kali kamu mau telepon ulang sebelum menyerah.

---

### `retry_sleep`

|                   |                                                             |
| ----------------- | ----------------------------------------------------------- |
| **Apa ini?**      | Jeda (dalam milidetik) antara setiap percobaan ulang        |
| **Default**       | `100` ms                                                    |
| **Kapan diubah?** | Jika ingin menunggu lebih lama antar retry (misal `500` ms) |

---

### `log_channel`

|                       |                                                                     |
| --------------------- | ------------------------------------------------------------------- |
| **Apa ini?**          | Nama channel log Laravel untuk mencatat semua request ke Duitku API |
| **Default**           | `null` (tidak log)                                                  |
| **Kapan diaktifkan?** | Saat debugging atau di production untuk audit trail                 |
| **Contoh value**      | `'stack'`, `'daily'`, `'duitku'` (channel custom kamu)              |

**Jika diaktifkan**, SDK akan mencatat:

- URL yang dipanggil
- Method HTTP (POST)
- Body request (untuk debugging)

> [!TIP]
> Untuk production, buat channel log terpisah agar log Duitku tidak bercampur dengan log aplikasi lain:
>
> ```php
> // config/logging.php
> 'channels' => [
>     'duitku' => [
>         'driver' => 'daily',
>         'path' => storage_path('logs/duitku.log'),
>         'days' => 14,
>     ],
> ],
> ```
>
> Lalu set `DUITKU_LOG_CHANNEL=duitku` di `.env`.

---

## 🏭 Contoh Konfigurasi Production

```env
DUITKU_MERCHANT_CODE=DPROD01
DUITKU_API_KEY=your-production-api-key
DUITKU_SANDBOX_MODE=false
DUITKU_TIMEOUT=15
DUITKU_RETRY_TIMES=3
DUITKU_RETRY_SLEEP=500
DUITKU_LOG_CHANNEL=duitku
```

## 🧪 Contoh Konfigurasi Development

```env
DUITKU_MERCHANT_CODE=DSAND01
DUITKU_API_KEY=your-sandbox-api-key
DUITKU_SANDBOX_MODE=true
DUITKU_TIMEOUT=30
DUITKU_RETRY_TIMES=0
DUITKU_LOG_CHANNEL=
```

---

Setelah konfigurasi siap, saatnya mulai membuat pembayaran! Lanjut ke [Usage - Payments](./usage-payments). 🚀
