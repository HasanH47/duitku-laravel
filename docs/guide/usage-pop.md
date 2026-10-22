# Duitku POP

POP menampilkan pemilihan metode pembayaran dalam popup. Buat invoice di server, lalu kirim reference ke Blade component.

```php
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Facades\Duitku;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-001',
    productDetails: 'Pembayaran pesanan',
    email: 'pelanggan@example.test',
    callbackUrl: route('duitku.callback'),
    returnUrl: route('checkout.return'),
);
$response = Duitku::pop()->createTransaction($request);

// Simpan reference pada order sebelum merender halaman checkout.
return view('checkout', ['reference' => $response->reference]);
```

`paymentMethod` opsional pada POP; isi jika ingin langsung membuka channel tertentu. Callback URL dan return URL tetap wajib. Response adalah `PopResponse` dengan merchantCode, reference, paymentUrl, statusCode, dan statusMessage.

```sh
php artisan vendor:publish --tag=duitku-assets
```

```blade
<x-duitku-pop :reference="$reference" button-text="Bayar Sekarang" />
```

Lihat [Blade component](./blade-components) untuk prop dan custom events. URL script provider mengikuti sandbox/production dari konfigurasi.

## Tokenisasi kartu

Untuk merchant yang sudah mendapat akses tokenisasi, berikan ID pelanggan pada customer detail serta token mode integer. Jangan memakai boolean untuk membedakan mode.

```php
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\CreditCardDetail;

$request->customerDetail = new CustomerDetail(
    firstName: 'Pelanggan', lastName: 'Contoh',
    email: 'pelanggan@example.test', phoneNumber: '08000000000',
    merchantCustomerId: 'CUSTOMER-001',
);
$request->creditCardDetail = new CreditCardDetail(saveCardToken: 1);
```

Mode 1 meminta token 3DS; mode 2 meminta token untuk kebutuhan recurring sesuai hak akses merchant. DTO ini melengkapi invoice POP; package belum menyediakan API untuk melakukan charge token, void/refund kartu langsung, atau scheduler subscription.

## Callback dan pengecekan status

Webhook POP menggunakan [pipeline callback](./callback-system) yang sama. Event sukses browser tidak membuktikan pembayaran sudah diterima server.

`pop()->checkTransaction($orderId)` mendelegasikan ke status API V2. `pop()->getPaymentMethod($amount)` juga memakai daftar metode API V2 yang terdokumentasi. Header autentikasi POP digunakan untuk create invoice; kedua operasi tersebut tidak menebak endpoint POP tambahan.
