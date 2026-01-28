# Usage: Payments

Bagian ini menjelaskan cara membuat transaksi pembayaran dasar menggunakan facade `Duitku`.

## Membuat Transaksi

Untuk membuat transaksi baru, gunakan method `createInvoice`. Method ini menerima detail item, jumlah pembayaran, dan ID order Anda.

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\ItemDetails;

$itemDetails = [
    new ItemDetails('Kaos Developer', 150000, 1),
    new ItemDetails('Sticker Pack', 10000, 2),
];

$response = Duitku::payment()->createInvoice(
    amount: 170000,
    merchantOrderId: 'INV-2026-001',
    productDetails: 'Pembelian Merchandise',
    customerVaName: 'Hasan H',
    email: 'me@hasanh.dev',
    itemDetails: $itemDetails,
    paymentMethod: 'VC', // Opsional: Kosongkan untuk menampilkan semua metode di halaman Duitku
    expiryPeriod: 60 // Dalam menit
);

if ($response->statusCode === '00') {
    // Arahkan user ke URL pembayaran
    return redirect($response->paymentUrl);
}
```

## Cek Status Transaksi

Anda dapat mengecek status pembayaran berdasarkan ID order yang Anda kirimkan sebelumnya.

```php
$status = Duitku::payment()->checkStatus('INV-2026-001');

if ($status->statusCode === '00') {
    echo "Pembayaran Berhasil!";
} elseif ($status->statusCode === '01') {
    echo "Pembayaran Pending.";
} else {
    echo "Pembayaran Gagal/Expired.";
}
```

## Daftar Metode Pembayaran

Untuk menampilkan daftar metode pembayaran yang tersedia beserta biaya administrasinya:

```php
$methods = Duitku::payment()->listMethods(170000);

foreach ($methods as $method) {
    echo "Method: " . $method->paymentName;
    echo "Fee: " . $method->fee;
}
```

---

Selanjutnya, pelajari [Duitku POP](./usage-pop) untuk integrasi popup yang lebih seamless.
