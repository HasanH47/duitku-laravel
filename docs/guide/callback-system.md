# Callback pembayaran

Gunakan `handleCallback()` untuk memvalidasi signature, mencocokkan transaksi melalui API status, lalu mengirim event Laravel. Route ini menerima POST server-to-server; redirect dan event browser POP hanya membantu tampilan pelanggan.

```php
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

public function callback(Request $request)
{
    Duitku::handleCallback($request->all());

    return response('OK', 200);
}
```

Daftarkan route bernama `duitku.callback`, lalu kirim URL-nya pada `PaymentRequest::callbackUrl`. Jika route memakai middleware CSRF, kecualikan hanya path callback tersebut di `bootstrap/app.php`. Route pada grup API biasanya tidak memakai middleware CSRF.

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['duitku/callback']);
})
```

## Pemeriksaan yang dilakukan SDK

SDK menolak kredensial kosong, merchant code berbeda, nominal malformed, signature salah, dan callback tanpa reference/result code. HMAC dihitung dari merchant code, amount asli, dan merchant order ID menggunakan API key sebagai key. Metadata seperti `resultCode` tidak termasuk pesan yang ditandatangani provider.

Secara default, SDK memanggil status API sebelum mengirim event. Order ID, nominal, serta reference harus sesuai dengan callback. Status dari API menentukan event; callback yang mengaku sukses tidak mengalahkan status provider yang masih pending atau gagal.

| Event | Kondisi |
| --- | --- |
| `DuitkuCallbackReceived` | Callback lolos seluruh pemeriksaan yang diaktifkan |
| `DuitkuPaymentReceived` | Status terverifikasi `00` |
| `DuitkuPaymentFailed` | Status terverifikasi `02` |

Status `01` hanya mengirim event umum. `CallbackRequest::resultCode` menyimpan nilai laporan callback asli; gunakan `verifiedStatus->statusCode` untuk hasil verifikasi provider. Data asli tersedia pada `raw`, termasuk field provider yang belum memiliki properti DTO.

```php
$callback = Duitku::verifyCallback($request->all()); // Pemeriksaan yang sama, tanpa event
$callback->verifiedStatus; // TransactionStatus, atau null jika pemeriksaan status dimatikan
$callback->customerName;   // Metadata issuer bila dikirim provider
```

`validateCallback()` hanya memeriksa identitas dan signature. Method itu tidak memverifikasi status pembayaran, order aplikasi, atau deduplikasi.

## Listener idempotent

Provider atau aplikasi dapat mengulang callback. SDK tetap stateless dan dapat mengirim event yang sama lagi. Aplikasi harus memproses efek bisnis secara idempotent.

Gunakan [contoh listener yang diuji](https://github.com/HasanH47/duitku-laravel/blob/main/usage-examples/IdempotentPaymentListener.php) sebagai dasar. Salin ke namespace aplikasi dan sesuaikan tabelnya. Contoh memakai:

- Kolom `merchant_code`, `merchant_order_id`, `amount` dalam rupiah integer, `duitku_reference`, `status`, dan `paid_at`.
- Unique constraint pada pasangan merchant code dan merchant order ID.
- Transaksi database dengan row lock, pencocokan nominal/reference, dan pemeriksaan status paid sebelum update.

Simpan reference provider ketika membuat invoice. Jangan mengirim email, pengiriman barang, atau request eksternal dari dalam transaksi database. Bila fulfillment harus bertahan saat proses crash, simpan outbox record dalam transaksi yang sama lalu proses secara idempotent.

## Error dan pengulangan

Jangan membalas 200 sebelum verifikasi dan penyimpanan hasil berhasil. Signature salah menghasilkan `InvalidSignatureException`; shape callback salah menghasilkan `ValidationException`; ketidakcocokan status/order menghasilkan `CallbackMismatchException`. Transport error tetap menggunakan exception HTTP Laravel.

Jika pengecekan provider gagal sementara, jangan menganggap order gagal atau paid. Biarkan aplikasi mengembalikan respons gagal sementara dan mengikuti kebijakan pengulangan provider. Pengulangan callback tidak boleh menggandakan efek bisnis.

## Opsi kompatibilitas

`DUITKU_VERIFY_CALLBACK_STATUS=true` adalah default. Menonaktifkannya membuat `verifiedStatus` bernilai null dan event hanya mengikuti result code callback. Gunakan pilihan itu hanya bila aplikasi menjalankan verifikasi independen sendiri; listener contoh sengaja menolak event yang belum terverifikasi.

Mode `DUITKU_SIGNATURE_MODE=legacy` tersedia untuk akun yang masih memerlukan protokol lama. Tidak ada fallback otomatis ketika HMAC gagal. Baca [panduan migrasi](./upgrading).
