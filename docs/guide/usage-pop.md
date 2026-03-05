# Usage: Duitku POP

Duitku POP (Snap) memungkinkan user Anda untuk melakukan pembayaran tanpa meninggalkan website Anda melalui _overlay popup_.

## Membuat Checkout POP

Prosesnya sangat mirip dengan checkout biasa, tetapi menggunakan `Duitku::pop()->createTransaction()`.

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

$request = new PaymentRequest(
    amount: 10000,
    merchantOrderId: 'INV-001',
    productDetails: 'Test Item',
    email: 'customer@example.com'
);

$response = Duitku::pop()->createTransaction($request);

// $response->reference adalah token yang dibutuhkan oleh Javascript Duitku POP
```

## Integrasi Frontend

SDK ini menyediakan Blade Component yang sangat memudahkan untuk menampilkan tombol bayar. Silakan lihat bagian [Blade Components](./blade-components) untuk detailnya.

Jika Anda ingin menggunakan Javascript manual:

```html
<!-- Muat script Duitku POP (otomatis sesuai environment sandbox/production) -->
<script src="{{ Duitku::pop()->scriptUrl() }}"></script>

<button onclick="pay()">Bayar Sekarang</button>

<script>
  function pay() {
    checkout.process("{{ $response->reference }}", {
      successEvent: function (result) {
        console.log("success", result);
      },
      pendingEvent: function (result) {
        console.log("pending", result);
      },
      errorEvent: function (result) {
        console.log("error", result);
      },
      closeEvent: function (result) {
        console.log("closed", result);
      },
    });
  }
</script>
```

## Cek Status & Daftar Metode Pembayaran (POP)

```php
// Cek status transaksi POP
$status = Duitku::pop()->checkTransaction('INV-001');

// Daftar metode pembayaran POP
$methods = Duitku::pop()->getPaymentMethod(10000);
```

---

Lanjut ke fitur lanjutan: [Disbursement](./usage-disbursement).
