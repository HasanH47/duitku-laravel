# Migrasi ke rilis major berikutnya

Branch ini menargetkan Laravel 12–13. Laravel 12 membutuhkan PHP 8.2+, sedangkan Laravel 13 membutuhkan PHP 8.3+. Perubahan signature, validasi, dan hasil batch bersifat breaking; jangan menerbitkannya sebagai patch instalasi lama.

## Signature dan callback

Default pembayaran API/POP sekarang `hmac-sha256`. Disbursement tetap memakai formula SHA256 protokol disbursement. Tidak ada fallback otomatis antaralgoritma.

```dotenv
DUITKU_SIGNATURE_MODE=hmac-sha256
DUITKU_VERIFY_CALLBACK_STATUS=true
DUITKU_BATCH_CONCURRENCY=5
```

`legacy` adalah opsi eksplisit untuk akun yang masih memakai kontrak lama. Konfirmasi sandbox sebelum mengganti protokol merchant; dokumentasi dan SDK PHP resmi memiliki perbedaan signature pada saat audit.

`handleCallback()` sekarang memeriksa result code/reference dan secara default memanggil status API sebelum mengirim event. Method dapat melempar transport error ketika provider tidak tersedia. Jangan menangkap semua error lalu membalas 200 atau mengubah order menjadi gagal.

`verifyCallback(array $payload): CallbackRequest` menjalankan pemeriksaan yang sama tanpa event. `validateCallback(array $payload): bool` tetap hanya memeriksa identitas/signature dan dapat melempar `ConfigurationException` bila kredensial kosong.

| Properti callback yang berubah | Tipe | Nullable | Arti |
| --- | --- | --- | --- |
| `customerName` | string | Ya | Metadata issuer dari provider |
| `verifiedStatus` | TransactionStatus | Ya | Hasil status API; null jika pemeriksaan dimatikan |
| `raw` | array | Tidak | Payload callback asli; jangan dicatat mentah ke log |

Event pembayaran ditentukan status terverifikasi. Pending hanya mengirim event umum. Delivery tetap dapat berulang; gunakan [listener idempotent](./callback-system#listener-idempotent).

## Request dan response pembayaran

Untuk API checkout, isi `paymentMethod`, `customerVaName`, `callbackUrl`, dan `returnUrl`. POP mewajibkan kedua URL tetapi tetap mengizinkan pemilihan metode di popup. Nominal adalah integer rupiah positif; batas minimum/maksimum spesifik channel tetap diputuskan provider.

`expiryPeriod` yang kosong memakai `duitku.default_expiry`; request tidak dimutasi. Default untuk Jenius Pay dibatasi 10 menit. Override expiry harus mengikuti batas channel provider.

```php
$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: $order->merchant_order_id,
    productDetails: 'Pembayaran pesanan',
    email: $order->email,
    paymentMethod: 'BC',
    customerVaName: 'Pelanggan',
    callbackUrl: route('duitku.callback'),
    returnUrl: route('checkout.return'),
);
```

Response sukses harus memiliki bentuk yang benar. `MalformedResponseException` menggantikan DTO berisi string kosong ketika JSON rusak atau field sukses wajib tidak ada. Transport error tetap exception HTTP Laravel; business rejection menjadi `DuitkuApiException`. API invoice masih dapat menghilangkan status code opsional bila reference/URL/merchant code sah.

## Hasil batch

`checkStatuses()` dan `transfer()->bulkInquiry()` mengembalikan `BatchResult[]` sesuai urutan input, termasuk input duplikat. Kegagalan satu item tidak menghilangkan item lain.

| Properti hasil | Tipe | Nullable | Arti |
| --- | --- | --- | --- |
| `key` | string | Tidak | Order ID untuk status; indeks input berbentuk string untuk inquiry rekening |
| `value` | TransactionStatus atau DisbursementResponse | Ya | Hasil sukses, null pada kegagalan |
| `error` | Throwable | Ya | Error item, null pada sukses |

Tepat satu dari value/error terisi. `successful(): bool` memeriksa hasil; `get()` mengembalikan value atau melempar error item. Batch kosong mengembalikan array kosong. Kesalahan konfigurasi atau input batch ditolak sebelum request.

```php
foreach (Duitku::checkStatuses($orderIds) as $result) {
    if (! $result->successful()) {
        report($result->error);
        continue;
    }

    $status = $result->get();
    // Cocokkan result->key dengan order milik aplikasi.
}
```

Concurrency 1–20 diatur lewat `DUITKU_BATCH_CONCURRENCY`, default 5. Implementasi memakai request async Laravel dan settled promises Guzzle supaya partial failure tetap terpisah pada seluruh rentang Laravel yang didukung. Tidak ada jaminan durasi batch di bawah satu detik.

## Retry dan log

`DUITKU_RETRY_TIMES` berarti jumlah percobaan tambahan, 0–5. Retry hanya berlaku pada operasi baca/status yang ditandai aman, untuk connection error, HTTP 429, dan HTTP 5xx. Invoice, transfer, clearing execute, dan cash out tidak diulang otomatis. Inquiry rekening batch memakai jalur inquiry; eksekusi payout tetap terpisah.

Timeout harus 1–300 detik, dan jeda retry 0–60.000 milidetik. Semua request batch menerima pengaturan client yang sama. Redirect HTTP tidak diikuti.

Log SDK hanya memuat method, host, dan path. Body, query string, serta header tidak dicatat. Aplikasi yang membutuhkan audit transaksi harus memilih field yang aman dan mengatur penyimpanannya sendiri.

## Tokenisasi, metode pembayaran, dan POP

`CreditCardDetail::saveCardToken` sekarang integer 1 atau 2, bukan boolean. Tokenisasi membutuhkan `CustomerDetail::merchantCustomerId` (string maksimal 100 karakter). Parameter lama `PaymentRequest::merchantCustomerId` masih diserialisasi ke lokasi nested sebagai jalur migrasi.

```php
$customer = new CustomerDetail(
    firstName: 'Pelanggan', lastName: 'Contoh',
    email: 'pelanggan@example.test', phoneNumber: '08000000000',
    merchantCustomerId: 'CUSTOMER-001',
);
$card = new CreditCardDetail(saveCardToken: 1);
```

Enum `GUDANG_VOUCHER_QRIS` dipertahankan sebagai deprecated agar kode lama tetap dapat membaca nilainya, tetapi invoice baru menolaknya. Gunakan `PaymentMethod::active()` serta daftar metode API; ketersediaan channel tetap bergantung pada akun merchant.

POP status dan daftar metode kini memakai kontrak API V2 yang terdokumentasi. Blade component memakai asset eksternal penghubung, tanpa inline onclick. Publish asset pada instalasi/upgrade:

```sh
php artisan vendor:publish --tag=duitku-assets
```

Lihat [prop dan event component](./blade-components). Modul kartu langsung dan SNAP belum ditambahkan dalam perubahan ini.

## Tooling dan verifikasi

Development memakai Larastan 3/PHPStan 2. CI menguji Laravel 12 pada PHP 8.2–8.5 dan Laravel 13 pada PHP 8.3–8.5. Jalur PHP 8.2 memakai Pest 3; jalur lainnya memakai Pest 4. Tidak ada langkah menghapus Larastan agar dependency resolution lolos.

VitePress dipertahankan pada stable 1.6.4. Override Vite `^6.4.4` memperbaiki advisory pada jalur Vite 5; Vue plugin yang digunakan mendukung Vite 6. Lockfile juga memperbarui dependency transitif yang rentangnya sudah mengizinkan perbaikan. Periksa build dan dev server saat override ini berubah; hapus override setelah versi stable VitePress mendukung toolchain yang sudah diperbaiki secara langsung.
