# Usage: Duitku POP

Duitku POP (Payment Overlay Popup) memungkinkan pelanggan kamu **membayar langsung di halaman website kamu** melalui popup, tanpa redirect ke halaman lain.

---

## 🤔 Apa Bedanya POP dengan API Biasa?

|                        | **API (Redirect)**                   | **POP (Popup)**                        |
| ---------------------- | ------------------------------------ | -------------------------------------- |
| **Cara kerja**         | Pelanggan redirect ke halaman Duitku | Popup muncul di halaman kamu           |
| **User experience**    | Pindah halaman → bayar → kembali     | Bayar langsung di tempat, tidak pindah |
| **Pilih metode bayar** | Di halaman Duitku                    | Di popup (pelanggan pilih sendiri)     |
| **Cocok untuk**        | E-commerce, checkout standar         | SaaS, donasi, top-up, subscription     |
| **Butuh frontend JS?** | ❌ Tidak                             | ✅ Perlu load script JS Duitku         |

> **Analogi:** API biasa = bayar di kasir toko sebelah. POP = kasir datang ke meja kamu (popup muncul di halaman kamu).

---

## 🔄 Alur Kerja POP

```
1. Aplikasi kamu buat transaksi via SDK (Backend)
   ↓
2. SDK kirim ke Duitku → dapat "reference" token
   ↓
3. Reference dikirim ke browser (Frontend)
   ↓
4. Javascript Duitku memunculkan popup
   ↓
5. Pelanggan bayar di dalam popup
   ↓
6. Popup kirim callback ke server kamu
   ↓
7. Aplikasi kamu update database
```

---

## Langkah 1: Backend (Buat Transaksi)

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Premium Membership',
    email: 'pelanggan@example.com',
    // paymentMethod dikosongkan → pelanggan pilih sendiri di popup
);

// Buat transaksi POP → dapat reference token
$response = Duitku::pop()->createTransaction($request);

// Simpan reference untuk dipakai di frontend
$reference = $response->reference;

// Kirim ke view Blade
return view('checkout', compact('reference'));
```

**Response yang kamu dapat:**

| Property                   | Penjelasan                                                          |
| -------------------------- | ------------------------------------------------------------------- |
| `$response->reference`     | Token unik untuk memunculkan popup di frontend (**wajib disimpan**) |
| `$response->statusCode`    | `'00'` jika berhasil                                                |
| `$response->statusMessage` | Pesan status (misal: "SUCCESS")                                     |

---

## Langkah 2: Frontend (Tampilkan Popup)

Ada 2 cara untuk menampilkan popup di frontend:

### Cara 1: Blade Component (Paling Mudah) ⭐

```blade
{{-- Satu baris saja! Semua diurus oleh komponen ini --}}
<x-duitku-pop
    :reference="$reference"
    button-text="Bayar Sekarang"
    class="btn btn-primary"
/>
```

> Komponen ini otomatis memuat script JS Duitku dan menghandle inisialisasi popup. Lihat detail di [Blade Components](./blade-components).

### Cara 2: Manual JavaScript

Jika kamu butuh kontrol lebih, gunakan cara manual:

```html
{{-- 1. Load script Duitku POP --}} {{-- scriptUrl() otomatis memilih URL yang
benar berdasarkan sandbox/production --}}
<script src="{{ Duitku::pop()->scriptUrl() }}"></script>

{{-- 2. Tombol bayar --}}
<button onclick="bayar()">Bayar Sekarang</button>

{{-- 3. Logic JavaScript --}}
<script>
  function bayar() {
    // Panggil popup Duitku dengan reference token
    checkout.process("{{ $reference }}", {
      // Dipanggil saat pembayaran BERHASIL
      successEvent: function (result) {
        console.log("✅ Sukses!", result);
        alert("Pembayaran berhasil! Terima kasih.");
        window.location.href = "/payment/success";
      },

      // Dipanggil saat pembayaran masih PENDING (belum bayar)
      pendingEvent: function (result) {
        console.log("⏳ Pending...", result);
        alert("Pembayaran sedang diproses. Silakan selesaikan pembayaran.");
      },

      // Dipanggil saat terjadi ERROR
      errorEvent: function (result) {
        console.log("❌ Error!", result);
        alert("Terjadi kesalahan. Silakan coba lagi.");
      },

      // Dipanggil saat pelanggan MENUTUP popup tanpa bayar
      closeEvent: function (result) {
        console.log("🚪 Popup ditutup", result);
        // Jangan langsung tandai gagal — pelanggan mungkin bayar nanti via VA
      },
    });
  }
</script>
```

> [!WARNING]
> **Jangan gunakan URL script hardcoded!** URL berbeda untuk sandbox dan production. Selalu gunakan `Duitku::pop()->scriptUrl()` agar otomatis.

> [!TIP]
> `closeEvent` bukan berarti pembayaran gagal! Pelanggan mungkin sudah memilih metode VA dan akan bayar nanti. Gunakan **callback** (webhook) dari Duitku untuk konfirmasi final.

---

## Cek Status Transaksi POP

Sama seperti API biasa, kamu bisa cek status transaksi POP:

```php
$status = Duitku::pop()->checkTransaction('INV-001');

echo $status->statusCode;      // '00' = sukses, '01' = pending, '02' = gagal
echo $status->statusMessage;   // 'SUCCESS', dll
echo $status->amount;          // Nominal
echo $status->reference;       // Reference dari Duitku
```

---

## Daftar Metode Pembayaran POP

Ambil daftar metode pembayaran yang tersedia:

```php
$methods = Duitku::pop()->getPaymentMethod(50000);

foreach ($methods as $method) {
    echo $method->paymentName;   // 'BCA Virtual Account'
    echo $method->paymentMethod; // 'BC'
    echo $method->paymentImage;  // URL gambar ikon
    echo $method->totalFee;      // Fee transaksi
}
```

---

## ❌ Troubleshooting

### Popup tidak muncul

- **Penyebab:** Script JS Duitku tidak ter-load
- **Solusi:** Pastikan tag `script` dengan `Duitku::pop()->scriptUrl()` ada di halaman, dan tidak ada error di browser console

### Error "checkout is not defined"

- **Penyebab:** Script belum selesai loading saat function dipanggil
- **Solusi:** Taruh `<script>` di bawah (sebelum `</body>`), atau gunakan `DOMContentLoaded` event

### Error "Invalid reference"

- **Penyebab:** Reference token sudah expired atau tidak valid
- **Solusi:** Generate reference baru via `Duitku::pop()->createTransaction()`

---

Selanjutnya, pelajari cara menangani notifikasi pembayaran di [Callback System](./callback-system). 📢
