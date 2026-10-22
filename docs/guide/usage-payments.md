# Pembayaran API

Gunakan `checkout()` untuk membuat invoice dengan metode pembayaran yang dipilih aplikasi. Untuk membiarkan pelanggan memilih metode di popup, gunakan [POP](./usage-pop).

```php
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Enums\PaymentMethod;
use Duitku\Laravel\Facades\Duitku;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-001',
    productDetails: 'Pembayaran pesanan',
    email: 'pelanggan@example.test',
    paymentMethod: PaymentMethod::BCA_VA->value,
    customerVaName: 'Pelanggan',
    callbackUrl: route('duitku.callback'),
    returnUrl: route('checkout.return'),
);

$response = Duitku::checkout($request);
// Simpan reference pada order sebelum mengarahkan pelanggan.
return redirect($response->paymentUrl);
```

Order ID harus unik dalam merchant. Aplikasi mengotorisasi order dan menentukan nominal dari data server, bukan dari input harga browser.

## Field request

| Field | Tipe PHP | Kehadiran pada request API | Arti |
| --- | --- | --- | --- |
| `amount` | int | Wajib | Rupiah positif; diserialisasi sebagai paymentAmount |
| `merchantOrderId` | string | Wajib | ID order, maksimal 50 karakter |
| `productDetails` | string | Wajib | Deskripsi, maksimal 255 karakter |
| `email` | string | Wajib | Email pelanggan |
| `paymentMethod` | string | Wajib | Kode channel dua karakter |
| `customerVaName` | string atau null | Wajib untuk API | Nama VA, maksimal 20 karakter |
| `callbackUrl` | string atau null | Wajib | URL HTTP/HTTPS webhook, maksimal 255 karakter |
| `returnUrl` | string atau null | Wajib | URL HTTP/HTTPS redirect pelanggan, maksimal 255 karakter |
| `expiryPeriod` | int atau null | Opsional | Menit; null memakai konfigurasi default |
| `phoneNumber` | string atau null | Opsional | Nomor telepon pelanggan |
| `itemDetails` | array | Opsional, sesuai channel | ItemDetail atau array berisi nama, price integer rupiah, dan quantity integer positif |
| `customerDetail` | CustomerDetail, array, atau null | Sesuai channel | Detail pelanggan dan alamat |
| `additionalParam` | string atau null | Opsional | Metadata aplikasi yang dikembalikan provider |
| `merchantUserInfo` | string atau null | Opsional | Informasi pengguna merchant |
| `accountLink` | AccountLink, array, atau null | Sesuai channel | Credential account-link OVO/Shopee |
| `creditCardDetail` | CreditCardDetail, array, atau null | Sesuai channel | Acquirer, BIN whitelist, dan token mode |
| `merchantCustomerId` | string atau null | Deprecated | Alias migrasi; gunakan customerDetail.merchantCustomerId |

Field null, string kosong, dan array kosong tidak dikirim. SDK memvalidasi persyaratan dasar; batas nominal, expiry, dan aktivasi channel tetap mengikuti provider. Tidak ada minimum Rp10.000 global yang dipaksakan SDK.

## DTO untuk item dan pelanggan

```php
use Duitku\Laravel\Data\ItemDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\Address;

$request->itemDetails = [new ItemDetail('Pesanan', 50000, 1)];
$request->customerDetail = new CustomerDetail(
    firstName: 'Pelanggan', lastName: 'Contoh',
    email: 'pelanggan@example.test', phoneNumber: '08000000000',
    billingAddress: new Address('Pelanggan', 'Contoh', 'Jl. Contoh', 'Jakarta', '11530', '08000000000'),
);
```

Sesuaikan jumlah item dengan nominal menurut ketentuan channel. Array biasa tetap didukung; DTO memberi informasi tipe pada IDE. `AccountLink`, `OvoDetail`, dan `ShopeeDetail` tersedia untuk channel account-link yang sudah diaktifkan merchant. Credential tersebut tidak boleh berasal dari pelanggan lain atau dicatat mentah ke log.

## Response dan status

`checkout()` mengembalikan `PaymentResponse` dengan merchant code, reference, dan payment URL yang terisi. Field opsional mencakup status code/message, VA number, amount, QR string, dan app URL. Respons rusak menghasilkan `MalformedResponseException`; penolakan bisnis menghasilkan `DuitkuApiException`.

```php
$status = Duitku::checkStatus('INV-001');

if ($status->statusCode === '00') {
    // Cocokkan dengan order dan proses secara idempotent.
}
```

Status API: `00` paid, `01` pending, dan `02` canceled/failed. Jangan memakai result code redirect browser untuk menetapkan status order.

## Batch dan daftar metode

```php
foreach (Duitku::checkStatuses(['INV-001', 'INV-002']) as $result) {
    if ($result->successful()) {
        $status = $result->get();
        echo $result->key.': '.$status->statusCode;
    } else {
        report($result->error);
    }
}

$methods = Duitku::paymentMethods(50000); // PaymentFee[] sesuai akun merchant
$knownActiveMethods = PaymentMethod::active(); // Enum lokal, bukan bukti channel aktif pada akun
```

Batch mempertahankan urutan dan item duplikat, dengan concurrency default 5. Lihat [kontrak BatchResult](./upgrading#hasil-batch) untuk detail error dan batas. Daftar enum mempertahankan GQ sebagai deprecated, tetapi invoice baru menolaknya.

Lanjutkan ke [callback terverifikasi](./callback-system) untuk menyimpan pembayaran dengan benar.
