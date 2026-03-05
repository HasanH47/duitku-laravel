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

> [!TIP]
> Anda juga bisa menggunakan `PaymentMethod` Enum agar lebih aman dari typo:
>
> ```php
> use Duitku\Laravel\Enums\PaymentMethod;
>
> $request = new PaymentRequest(
>     // ...
>     paymentMethod: PaymentMethod::CREDIT_CARD->value,
> );
> ```

## Cek Status Transaksi

```php
use Duitku\Laravel\Support\PaymentCode;

$status = Duitku::checkStatus('INV-123');

if ($status->statusCode === PaymentCode::SUCCESS) {
    echo "Pembayaran Berhasil!";
} elseif ($status->statusCode === PaymentCode::PENDING) {
    echo "Pembayaran Pending.";
} else {
    echo "Pembayaran Gagal/Expired.";
}
```

## Daftar Metode Pembayaran

Untuk menampilkan daftar metode pembayaran yang tersedia beserta biaya administrasinya:

```php
$methods = Duitku::paymentMethods(170000);

foreach ($methods as $method) {
    echo $method['paymentMethod'] . ': ' . $method['paymentName'];
}
```

---

Selanjutnya, pelajari [Duitku POP](./usage-pop) untuk integrasi popup yang lebih seamless.
