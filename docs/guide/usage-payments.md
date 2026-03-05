# Usage: Payments

Bagian ini menjelaskan cara membuat transaksi pembayaran menggunakan facade `Duitku`.

## Membuat Transaksi (Checkout)

Untuk membuat transaksi baru, gunakan method `checkout()`. Method ini menerima object `PaymentRequest` dan mengembalikan `PaymentResponse`.

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Topup Game Diamonds',
    email: 'pelanggan@example.com',
    paymentMethod: 'VC' // Opsional: kosongkan untuk menampilkan semua metode
);

$response = Duitku::checkout($request);

// Arahkan user ke URL pembayaran
return redirect($response->paymentUrl);
```

## Menggunakan DTO Typed (Recommended)

Gunakan DTO typed agar kode lebih aman dan IDE auto-complete jalan:

```php
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\ItemDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\Address;
use Duitku\Laravel\Enums\PaymentMethod;

// Item Details (typed, bukan array mentah)
$items = [
    new ItemDetail(name: 'Kaos Developer', price: 150000, quantity: 1),
    new ItemDetail(name: 'Sticker Pack', price: 20000, quantity: 1),
];

// Customer Detail dengan Address
$address = new Address(
    firstName: 'John',
    lastName: 'Doe',
    address: 'Jl. Kembangan Raya',
    city: 'Jakarta',
    postalCode: '11530',
    phone: '08123456789'
);

$customer = new CustomerDetail(
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com',
    phoneNumber: '08123456789',
    billingAddress: $address,
    shippingAddress: $address
);

$request = new PaymentRequest(
    amount: 170000,
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Merchandise',
    email: 'john@example.com',
    paymentMethod: PaymentMethod::CREDIT_CARD->value, // 'VC' — lebih aman dari typo!
    itemDetails: $items,
    customerDetail: $customer,
    expiryPeriod: 60
);

$response = Duitku::checkout($request);
```

> [!TIP]
> `PaymentRequest` menerima **raw arrays** maupun **DTO objects**. Gunakan mana yang lebih nyaman untuk Anda. Kedua cara akan bekerja dengan benar.

## Untuk Account Link (OVO/Shopee)

```php
use Duitku\Laravel\Data\AccountLink;
use Duitku\Laravel\Data\OvoDetail;
use Duitku\Laravel\Data\ShopeeDetail;

// OVO Account Link
$accountLink = new AccountLink(
    credentialCode: 'YOUR-CREDENTIAL-CODE',
    ovo: OvoDetail::cash(10000)  // Shortcut untuk pembayaran OVO Cash
);

// Shopee Account Link
$accountLink = new AccountLink(
    credentialCode: 'YOUR-CREDENTIAL-CODE',
    shopee: new ShopeeDetail(promo_ids: 'campaign111', useCoin: true)
);

$request = new PaymentRequest(
    // ...parameter lainnya
    accountLink: $accountLink,
);
```

## Cek Status Transaksi

```php
use Duitku\Laravel\Support\PaymentCode;

$status = Duitku::checkStatus('INV-123');

if ($status->statusCode === PaymentCode::SUCCESS) {
    echo "Pembayaran Berhasil! Fee: " . $status->fee;
} elseif ($status->statusCode === PaymentCode::PENDING) {
    echo "Pembayaran Pending.";
} else {
    echo "Pembayaran Gagal/Expired.";
}
```

## Daftar Metode Pembayaran

Mengembalikan array `PaymentFee` objects:

```php
$methods = Duitku::paymentMethods(170000);

foreach ($methods as $method) {
    echo $method->paymentName . ' - Fee: Rp ' . $method->totalFee;
    // $method->paymentMethod  → kode (misal 'VC')
    // $method->paymentImage   → URL gambar ikon
}
```

---

Selanjutnya, pelajari [Duitku POP](./usage-pop) untuk integrasi popup yang lebih seamless.
