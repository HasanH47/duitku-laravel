# Usage: Payments

Halaman ini menjelaskan cara **menerima pembayaran** dari pelanggan menggunakan Duitku API. Ini adalah fitur paling dasar dan paling sering digunakan.

---

## 🔄 Alur Pembayaran (Flow)

Sebelum masuk ke kode, penting untuk memahami **alur** dari awal sampai akhir:

```
1. Pelanggan klik "Bayar"
   ↓
2. Aplikasi kamu buat invoice ke Duitku (via SDK)
   ↓
3. Duitku kirim balik URL pembayaran
   ↓
4. Pelanggan diarahkan ke URL tersebut (redirect)
   ↓
5. Pelanggan bayar (transfer VA, scan QRIS, dll)
   ↓
6. Duitku kirim "callback" ke server kamu
   ↓
7. Aplikasi kamu update database: "Order sudah dibayar!"
```

> **Intinya:** Kamu buat invoice → dapat URL → pelanggan bayar di sana → Duitku kasih tahu kamu.

---

## 💳 Membuat Pembayaran (Checkout)

### Cara Paling Sederhana

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

// Buat objek request pembayaran
$request = new PaymentRequest(
    amount: 50000,                         // Nominal pembayaran (Rupiah)
    merchantOrderId: 'INV-' . time(),      // ID unik order kamu
    productDetails: 'Topup 50 Diamonds',   // Deskripsi produk (muncul di halaman bayar)
    email: 'pelanggan@example.com',        // Email pelanggan
);

// Kirim ke Duitku → terima response
$response = Duitku::checkout($request);

// Arahkan pelanggan ke halaman pembayaran
return redirect($response->paymentUrl);
```

**Penjelasan setiap parameter:**

| Parameter         | Wajib? | Penjelasan                                                                                                              |
| ----------------- | ------ | ----------------------------------------------------------------------------------------------------------------------- |
| `amount`          | ✅     | Nominal dalam Rupiah. Minimum Rp 10.000.                                                                                |
| `merchantOrderId` | ✅     | ID unik untuk order ini. **Harus unik** per transaksi, max 50 karakter.                                                 |
| `productDetails`  | ✅     | Deskripsi produk yang akan muncul di halaman pembayaran Duitku.                                                         |
| `email`           | ✅     | Email pelanggan. Duitku akan mengirim notifikasi ke email ini. Max 50 karakter.                                         |
| `paymentMethod`   | ❌     | Kode metode bayar (misal `'VC'` untuk kartu kredit). Kalau dikosongkan, pelanggan bisa pilih sendiri di halaman Duitku. |
| `customerVaName`  | ❌     | Nama yang muncul di VA. **Wajib** untuk metode E-Commerce (Tokopedia).                                                  |
| `phoneNumber`     | ❌     | Nomor HP pelanggan. Max 50 karakter.                                                                                    |
| `expiryPeriod`    | ❌     | Waktu expired dalam menit. Override `default_expiry` di config.                                                         |
| `callbackUrl`     | ❌     | URL callback custom. Override setting di dashboard Duitku.                                                              |
| `returnUrl`       | ❌     | URL redirect setelah pelanggan selesai bayar.                                                                           |

### Response yang Kamu Dapat

```php
$response = Duitku::checkout($request);

$response->merchantCode;    // Merchant Code kamu
$response->reference;       // Reference ID dari Duitku (simpan ini!)
$response->paymentUrl;      // URL untuk redirect pelanggan
$response->statusCode;      // '00' = berhasil dibuat
$response->statusMessage;   // Pesan status
$response->vaNumber;        // Nomor VA (jika pakai Virtual Account)
$response->amount;          // Nominal pembayaran
$response->qrString;        // String QRIS (jika pakai QRIS)
```

---

## 🏷️ Menggunakan PaymentMethod Enum (Recommended)

Daripada menghafal kode pembayaran (`'VC'`, `'BC'`, dll), gunakan Enum yang sudah disediakan:

```php
use Duitku\Laravel\Enums\PaymentMethod;

// ❌ Cara lama (rentan typo)
$request = new PaymentRequest(
    // ...
    paymentMethod: 'VCA'  // Typo! Harusnya 'VC'. Error baru ketahuan saat runtime.
);

// ✅ Cara baru (IDE kasih auto-complete)
$request = new PaymentRequest(
    // ...
    paymentMethod: PaymentMethod::CREDIT_CARD->value  // 'VC' — aman dari typo!
);
```

### Daftar Lengkap Metode Pembayaran

| Enum                  | Kode | Nama                              |
| --------------------- | ---- | --------------------------------- |
| `CREDIT_CARD`         | `VC` | Credit Card (Visa/MasterCard/JCB) |
| `BCA_VA`              | `BC` | BCA Virtual Account               |
| `MANDIRI_VA`          | `M2` | Mandiri Virtual Account           |
| `MAYBANK_VA`          | `VA` | Maybank Virtual Account           |
| `BNI_VA`              | `I1` | BNI Virtual Account               |
| `CIMB_VA`             | `B1` | CIMB Niaga Virtual Account        |
| `PERMATA_VA`          | `BT` | Permata Bank Virtual Account      |
| `ATM_BERSAMA`         | `A1` | ATM Bersama                       |
| `ARTHA_GRAHA`         | `AG` | Bank Artha Graha                  |
| `NEO_COMMERCE`        | `NC` | Bank Neo Commerce / BNC           |
| `BRI_VA`              | `BR` | BRIVA                             |
| `SAHABAT_SAMPOERNA`   | `S1` | Bank Sahabat Sampoerna            |
| `DANAMON_VA`          | `DM` | Danamon Virtual Account           |
| `BSI_VA`              | `BV` | BSI Virtual Account               |
| `PEGADAIAN_ALFA_POS`  | `FT` | Pegadaian / ALFA / Pos            |
| `INDOMARET`           | `IR` | Indomaret                         |
| `OVO`                 | `OV` | OVO (Support Void)                |
| `SHOPEEPAY_APP`       | `SA` | Shopee Pay Apps (Support Void)    |
| `LINKAJA_FIXED`       | `LF` | LinkAja Apps (Fixed Fee)          |
| `LINKAJA_PERCENTAGE`  | `LA` | LinkAja Apps (Percentage Fee)     |
| `DANA`                | `DA` | DANA                              |
| `SHOPEE_ACCOUNT_LINK` | `SL` | Shopee Pay Account Link           |
| `OVO_ACCOUNT_LINK`    | `OL` | OVO Account Link                  |
| `SHOPEEPAY_QRIS`      | `SP` | Shopee Pay QRIS                   |
| `NOBU_QRIS`           | `NQ` | Nobu QRIS                         |
| `GUDANG_VOUCHER_QRIS` | `GQ` | Gudang Voucher QRIS               |
| `NUSAPAY_QRIS`        | `SQ` | Nusapay QRIS                      |
| `INDODANA`            | `DN` | Indodana Paylater                 |
| `ATOME`               | `AT` | ATOME                             |
| `JENIUS_PAY`          | `JP` | Jenius Pay                        |
| `TOKOPEDIA_CARD`      | `T1` | Tokopedia Card Payment            |
| `TOKOPEDIA_EWALLET`   | `T2` | Tokopedia E-Wallet                |
| `TOKOPEDIA_OTHERS`    | `T3` | Tokopedia Others                  |

### Helper Methods

Enum ini juga punya method pembantu:

```php
use Duitku\Laravel\Enums\PaymentMethod;

PaymentMethod::CREDIT_CARD->label();              // 'Credit Card (Visa / Master Card / JCB)'
PaymentMethod::BCA_VA->isVirtualAccount();         // true
PaymentMethod::TOKOPEDIA_CARD->isEcommerce();      // true (customerVaName wajib!)
PaymentMethod::CREDIT_CARD->requiresCustomerDetail(); // true (customerDetail & itemDetails wajib!)
PaymentMethod::SHOPEEPAY_QRIS->isQris();           // true
```

> [!IMPORTANT]
>
> - Untuk metode bayar **Kredit** (`VC`, `DN`, `AT`): parameter `customerDetail` dan `itemDetails` menjadi **wajib**.
> - Untuk metode bayar **E-Commerce** (`T1`, `T2`, `T3`): parameter `customerVaName` menjadi **wajib**.

---

## 📦 Menggunakan Typed DTOs (Recommended)

SDK ini menyediakan DTO (Data Transfer Object) typed untuk setiap parameter kompleks. Keuntungannya:

- ✅ IDE auto-complete
- ✅ Typo ketahuan saat coding, bukan saat runtime
- ✅ Kode lebih mudah dibaca

> Kamu **tetap bisa** pakai array biasa jika mau. SDK mendukung keduanya.

### ItemDetail

```php
use Duitku\Laravel\Data\ItemDetail;

// ✅ Pakai DTO (recommended)
$items = [
    new ItemDetail(name: 'Kaos Developer', price: 150000, quantity: 1),
    new ItemDetail(name: 'Sticker Pack',   price: 20000,  quantity: 2),
];

// ❌ Pakai array biasa (masih bisa, tapi kurang aman)
$items = [
    ['name' => 'Kaos Developer', 'price' => 150000, 'quantity' => 1],
    ['name' => 'Sticker Pack',   'price' => 20000,  'quantity' => 2],
];
```

> [!WARNING]
> Total dari semua `price × quantity` di `itemDetails` **harus sama** dengan `amount` di `PaymentRequest`. Jika tidak cocok, Duitku akan menolak dengan error `409 Payment amount must be equal to all item price`.

### CustomerDetail & Address

```php
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\Address;

// Buat objek Address (untuk billing dan shipping)
$address = new Address(
    firstName: 'John',
    lastName: 'Doe',
    address: 'Jl. Kembangan Raya No. 10',
    city: 'Jakarta',
    postalCode: '11530',
    phone: '08123456789',
    countryCode: 'ID'     // Default 'ID', bisa diubah jika perlu
);

// Buat CustomerDetail
$customer = new CustomerDetail(
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com',
    phoneNumber: '08123456789',
    billingAddress: $address,   // Opsional
    shippingAddress: $address   // Opsional
);
```

### Contoh Lengkap (Checkout dengan semua DTOs)

```php
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\ItemDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\Address;
use Duitku\Laravel\Enums\PaymentMethod;

$items = [
    new ItemDetail('Kaos Developer', 150000, 1),
    new ItemDetail('Sticker Pack', 20000, 1),
];

$address = new Address('John', 'Doe', 'Jl. X No. 10', 'Jakarta', '11530', '081234');

$customer = new CustomerDetail(
    'John', 'Doe', 'john@example.com', '081234',
    billingAddress: $address
);

$request = new PaymentRequest(
    amount: 170000,                                    // 150000 + 20000
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Beli Merchandise',
    email: 'john@example.com',
    paymentMethod: PaymentMethod::CREDIT_CARD->value,  // Wajib pakai DTO karena Credit Card
    itemDetails: $items,                               // Wajib untuk Credit Card
    customerDetail: $customer,                         // Wajib untuk Credit Card
    expiryPeriod: 60
);

$response = Duitku::checkout($request);
return redirect($response->paymentUrl);
```

---

## 🔗 Account Link (OVO & Shopee)

Untuk metode **OVO Account Link** (`OL`) dan **Shopee Account Link** (`SL`), kamu perlu menambahkan parameter `accountLink`:

```php
use Duitku\Laravel\Data\AccountLink;
use Duitku\Laravel\Data\OvoDetail;
use Duitku\Laravel\Data\ShopeeDetail;

// === OVO Account Link ===
$accountLink = new AccountLink(
    credentialCode: 'A0F22572-4AF1-E111-812C-B01224449936',  // Credential dari Duitku
    ovo: OvoDetail::cash(10000)  // Shortcut: pembayaran OVO Cash
);

// === Shopee Account Link ===
$accountLink = new AccountLink(
    credentialCode: 'A0F22572-4AF1-E111-812C-B01224449936',
    shopee: new ShopeeDetail(
        promo_ids: 'campaign111',   // Kode voucher promo (max 50 karakter)
        useCoin: true               // Gunakan koin Shopee
    )
);

// Masukkan ke PaymentRequest
$request = new PaymentRequest(
    // ...parameter lainnya
    paymentMethod: PaymentMethod::OVO_ACCOUNT_LINK->value,
    accountLink: $accountLink,
);
```

---

## 💳 Credit Card Detail

Untuk customisasi tambahan saat pakai kartu kredit:

```php
use Duitku\Laravel\Data\CreditCardDetail;

$ccDetail = new CreditCardDetail(
    acquirer: '014',                            // Kode bank acquirer (014=BCA, 022=CIMB)
    binWhitelist: ['014', '022', '400000'],      // BIN kartu yang diperbolehkan
);

$request = new PaymentRequest(
    // ...parameter lainnya
    paymentMethod: PaymentMethod::CREDIT_CARD->value,
    creditCardDetail: $ccDetail,
);
```

---

## 📊 Cek Status Transaksi

Setelah membuat pembayaran, kamu pasti ingin tahu: **sudah dibayar belum?**

### Cek Satu Transaksi

```php
use Duitku\Laravel\Support\PaymentCode;

$status = Duitku::checkStatus('INV-123');

if ($status->statusCode === PaymentCode::SUCCESS) {
    echo "✅ Pembayaran berhasil!";
    echo "Fee: Rp " . number_format($status->fee);
} elseif ($status->statusCode === PaymentCode::PENDING) {
    echo "⏳ Menunggu pembayaran...";
} else {
    echo "❌ Pembayaran gagal/expired.";
}
```

### Cek Banyak Transaksi Sekaligus (Parallel) 🚀

Fitur unggulan SDK ini! Cek 50+ transaksi dalam waktu kurang dari 1 detik:

```php
$statuses = Duitku::checkStatuses(['INV-001', 'INV-002', 'INV-003']);

foreach ($statuses as $status) {
    if ($status === null) {
        continue; // Skip jika request gagal
    }
    echo "{$status->merchantOrderId}: {$status->statusCode}\n";
}
```

> **Kenapa cepat?** SDK menggunakan `Http::pool` bawaan Laravel yang mengirim semua request secara bersamaan (paralel), bukan satu per satu (sekuensial).

---

## 📋 Daftar Metode Pembayaran

Ambil daftar metode pembayaran yang tersedia untuk jumlah tertentu:

```php
$methods = Duitku::paymentMethods(170000);

foreach ($methods as $method) {
    echo $method->paymentName;   // Contoh: "BCA Virtual Account"
    echo $method->paymentMethod; // Contoh: "BC"
    echo $method->paymentImage;  // URL gambar ikon
    echo $method->totalFee;      // Fee transaksi
}
```

> Response adalah array `PaymentFee` objects — typed, bukan array mentah.

---

## ⚠️ Kesalahan Umum

| Error                                            | Penyebab                                          | Solusi                                       |
| ------------------------------------------------ | ------------------------------------------------- | -------------------------------------------- |
| `Minimum Payment 10000 IDR`                      | Nominal terlalu kecil                             | Set `amount` minimal 10000                   |
| `merchantOrderId telah digunakan`                | Order ID sudah pernah dipakai                     | Gunakan ID unik (misal `'INV-' . time()`)    |
| `Invalid Email Address`                          | Format email salah                                | Cek format email pelanggan                   |
| `Payment amount must be equal to all item price` | Total itemDetails ≠ amount                        | Pastikan total `price × quantity` = `amount` |
| `Customer VA Name must not be empty`             | Pakai metode E-Commerce tapi tanpa customerVaName | Isi parameter `customerVaName`               |
| `Wrong signature`                                | API Key atau Merchant Code salah                  | Cek `.env` kamu                              |

---

Selanjutnya, pelajari [Duitku POP](./usage-pop) untuk integrasi popup yang lebih smooth. ⚡
