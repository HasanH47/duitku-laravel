# Usage: Duitku POP

Duitku POP (Snap) memungkinkan user Anda untuk melakukan pembayaran tanpa meninggalkan website Anda melalui _overlay popup_.

## Membuat Checkout POP

Prosesnya sangat mirip dengan pembuatan invoice biasa, namun menggunakan method `createPop`.

```php
$response = Duitku::payment()->createPop(
    amount: 170000,
    merchantOrderId: 'INV-2026-002',
    productDetails: 'Pembelian Lisensi',
    customerVaName: 'Budi Arto',
    email: 'budi@example.com'
);

// $response->reference adalah token yang dibutuhkan oleh Javascript Duitku POP
```

## Integrasi Frontend

SDK ini menyediakan Blade Component yang sangat memudahkan untuk menampilkan tombol bayar. Silakan lihat bagian [Blade Components](./blade-components) untuk detailnya.

Jika Anda ingin menggunakan Javascript manual:

```html
<script src="https://sandbox.duitku.com/2pb/js/duitku-pop.js"></script>
<!-- Gunakan link produksi jika mode sandbox false -->

<button onclick="pay()">Bayar Sekarang</button>

<script>
  function pay() {
    checkout.process("{{ $response->reference }}", {
      successPath: "/success",
      callbackPath: "/callback-frontend",
      outputPath: "/checkout-finish",
    });
  }
</script>
```

> [!IMPORTANT]
> Pastikan Anda memuat script JS Duitku POP yang sesuai dengan environment Anda (Sandbox vs Production).

---

Lanjut ke fitur lanjutan: [Disbursement](./usage-disbursement).
