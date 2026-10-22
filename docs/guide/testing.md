# Diagnosis dan pengujian aplikasi

Gunakan `duitku:doctor` untuk memeriksa konfigurasi lokal, dan `Duitku::fake()` untuk menguji integrasi aplikasi tanpa menghubungi provider. Keduanya melengkapi pengujian sandbox; hasil lokal tidak membuktikan kredensial diterima Duitku.

## Memeriksa konfigurasi

```sh
php artisan duitku:doctor
php artisan duitku:doctor --pop
php artisan duitku:doctor --disbursement
php artisan duitku:doctor --strict
```

Command memeriksa keberadaan kredensial yang diperlukan, batas timeout/retry/concurrency, serta nama log channel. Mode pembayaran juga memeriksa signature dan expiry. Nilai API key, merchant code, email, dan user ID tidak ditampilkan. Command tidak mengirim request, membuat invoice, atau menulis ulang konfigurasi.

`--disbursement` memeriksa kredensial payout sebagai pengganti kredensial pembayaran. Merchant code tidak diperlukan pada mode ini. `--pop` menambahkan pemeriksaan asset `public/vendor/duitku/duitku-pop.js`; gunakan untuk integrasi Blade, secara terpisah dari `--disbursement`.

| Exit code | Arti |
| --- | --- |
| `0` | Semua pemeriksaan lolos; warning masih diizinkan |
| `1` | Ada pemeriksaan gagal, atau ada warning saat `--strict` aktif |
| `2` | Kombinasi opsi tidak sesuai |

Signature legacy dan verifikasi status callback yang dimatikan menghasilkan warning. `--strict` cocok untuk pemeriksaan deployment. Lolos pemeriksaan tetap tidak membuktikan API key benar, channel aktif, koneksi provider tersedia, atau URL callback dapat dijangkau.

## Fake provider pembayaran

Fake memakai [HTTP client testing bawaan Laravel](https://laravel.com/docs/13.x/http-client#testing). Request tetap melewati service, validasi, DTO, dan event SDK. Yang diganti hanya respons HTTP pembayaran. Assertion memakai dukungan PHPUnit pada Laravel; tidak ada paket testing tambahan.

Siapkan konfigurasi sintetis sebelum facade SDK pertama kali dipakai dalam setiap test:

```php
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Http;

config([
    'duitku.merchant_code' => 'TEST_MERCHANT',
    'duitku.api_key' => 'test-only-secret',
    'duitku.sandbox_mode' => true,
]);

Http::preventStrayRequests();
$fake = Duitku::fake()->payment(
    orderId: 'ORDER-001',
    amount: 10000,
    statusCode: '00',
    reference: 'REF-001',
);

$invoice = Duitku::checkout(new PaymentRequest(
    amount: 10000,
    merchantOrderId: 'ORDER-001',
    productDetails: 'Test Product',
    email: 'customer@example.test',
    paymentMethod: 'BC',
    customerVaName: 'Test Customer',
    callbackUrl: 'https://merchant.test/duitku/callback',
    returnUrl: 'https://merchant.test/checkout/return',
));

$fake->assertPaymentRequested('ORDER-001', fn ($request) => $request['paymentAmount'] === 10000);

// Payload sintetis bertanda tangan untuk menguji route dan listener aplikasi.
$this->postJson('/duitku/callback', $fake->callback('ORDER-001'))->assertSuccessful();
$fake->assertStatusChecked('ORDER-001');
```

Sesuaikan route dan authentication aplikasi. Untuk membuktikan finalisasi pembayaran idempotent, kirim payload dua kali lalu periksa hanya satu perubahan bisnis pada database aplikasi. [Contoh listener](./callback-system#listener-idempotent) menjelaskan constraint, nominal, dan reference yang perlu dicocokkan.

| Method | Kontrak |
| --- | --- |
| `payment(string $orderId, int $amount, string $statusCode = '01', ?string $reference = null): self` | Mendaftarkan atau mengganti state sintetis order. Nominal rupiah positif; status `00` paid, `01` pending, `02` failed. Reference default `TEST-` diikuti order ID |
| `paymentMethods(array $methods): self` | Menentukan array payment fee untuk API/POP. Default kosong; setiap item harus memiliki `paymentMethod` dan `paymentName` |
| `callback(string $orderId): array` | Payload callback dari fixture dengan signature sesuai konfigurasi. Result code `00` untuk paid, `01` untuk lainnya; status API tetap mengikuti fixture |
| `assertPaymentRequested(string $orderId, ?Closure $callback = null): void` | Memastikan invoice API atau POP diminta untuk order tersebut; closure opsional memeriksa isi request |
| `assertStatusChecked(string $orderId): void` | Memastikan status API diminta untuk order tersebut |
| `assertNothingSent(): void` | Memastikan tidak ada request ke host Duitku konfigurasi aktif; request integrasi lain tidak dihitung |

`Duitku::fake()` hanya tersedia pada environment `testing`. Pemanggilan berikutnya pada instance SDK yang sama memakai fake yang sama. Untuk mengubah pending menjadi paid, panggil kembali `payment()` dengan order, nominal, dan reference yang sama serta status baru.

Order yang belum didaftarkan, nominal invoice yang berbeda, dan endpoint Duitku yang belum didukung menghasilkan `LogicException`. Fixture pembayaran yang bentuknya salah menghasilkan `ValidationException`. Fake mendukung invoice API/POP, status tunggal/batch, dan daftar metode; disbursement memerlukan fixture `Http::fake()` tersendiri.

Daftarkan fake sebelum request pertama. Hindari wildcard global `Http::fake()` yang dapat menutupi handler SDK; fake HTTP integrasi lain boleh memakai pola URL yang spesifik. Untuk simulasi timeout, HTTP error, atau payload provider rusak, gunakan `Http::fake()` secara langsung dalam test terpisah.

Fake adalah alat uji aplikasi, bukan emulator lifecycle atau verifikasi kontrak server Duitku. Signature callback dibuat oleh signer SDK; test itu tidak menjadi bukti independen kecocokan algoritma dengan provider. Pertahankan fixture signature yang independen dan smoke test sandbox secara terpisah.

## Verifikasi sandbox

[Catatan sandbox 22 Oktober 2026](../reviews/2026-10-22-sandbox) membedakan request nyata, simulasi lokal, dan cakupan yang belum diuji. Jangan menyimpan kredensial sandbox maupun production dalam test yang dikomit.
