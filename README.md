# Duitku Laravel

![Tests](https://github.com/HasanH47/duitku-laravel/workflows/Tests/badge.svg)
![Static Analysis](https://github.com/HasanH47/duitku-laravel/workflows/Static%20Analysis/badge.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)
![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red)

Package Laravel untuk Duitku Payment Gateway yang **Modern, Typed, dan Teroptimasi untuk Concurrency (Parallel Check)**.

## Fitur Utama

- 🚀 **Cek Status Paralel**: Menggunakan `Http::pool` bawaan Laravel 10+ untuk mengecek status banyak transaksi sekaligus dalam waktu singkat (<1 detik untuk 50+ order).
- 🔒 **Ketik Ketat (Strictly Typed)**: Tidak ada lagi magic array. Gunakan `PaymentRequest` dan `PaymentResponse` (DTO) agar kodingan lebih aman dan auto-complete jalan.
- 🛡️ **Auto Signature**: Generate dan validasi signature (MD5/SHA256) otomatis. Tidak perlu pusing hitung hash manual.
- 🧪 **Testable**: Dibuat dengan Pest PHP dan sangat mudah di-mock menggunakan `Http::fake()` untuk pengujian aplikasi Anda.

## Instalasi

```bash
composer require duitku/laravel
```

Publish konfigurasi:

```bash
php artisan vendor:publish --tag=duitku-config
```

## Konfigurasi

Tambahkan kredensial Duitku Anda di file `.env`:

```env
DUITKU_MERCHANT_CODE=kode_merchant_anda
DUITKU_API_KEY=api_key_anda
DUITKU_SANDBOX_MODE=true
```

## Cara Penggunaan (Usage)

### 1. Buat Pembayaran (Checkout)

Membuat link pembayaran (Payment URL).

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\PaymentRequest;

$request = new PaymentRequest(
    amount: 50000,
    merchantOrderId: 'INV-' . time(),
    productDetails: 'Topup Game Diamonds',
    email: 'pelanggan@example.com',
    paymentMethod: 'VC' // Opsional (Virtual Account, dll)
);

$response = Duitku::checkout($request);

return redirect($response->paymentUrl);
```

### 2. Handle Callback (Webhook)

Menangani notifikasi pembayaran dari Duitku.

```php
use Duitku\Laravel\Facades\Duitku;
use Illuminate\Http\Request;

public function handleCallback(Request $request)
{
    // Validasi Signature (Cek keaslian data dari Duitku)
    if (!Duitku::validateCallback($request->all())) {
        abort(403, 'Invalid Signature');
    }

    // Proses Order
    $orderId = $request->merchantOrderId;
    $status = $request->resultCode; // '00' = Sukses

    use Duitku\Laravel\Support\PaymentCode;

    if ($status === PaymentCode::SUCCESS) {
        // Update database: Order Telah Dibayar
    }
}
```

### 3. Cek Status Transaksi (Optimasi Paralel) 🚀

Fitur unggulan paket ini. Cek satu status atau cek banyak sekaligus (Bulk) dengan sangat cepat.

```php
// Cek Satu Transaksi
$status = Duitku::checkStatus('INV-123');

// Cek Banyak Transaksi (Pakai Http::pool otomatis)
$statuses = Duitku::checkStatuses(['INV-123', 'INV-124', 'INV-125']);

foreach ($statuses as $status) {
    if ($status->statusCode === '00') {
         echo "Order {$status->merchantOrderId} Sukses";
    }
}
```

### 4. Duitku POP (Popup / Snap Integration) ⚡

Metode integrasi menggunakan popup (tidak redirect halaman). Sangat cocok untuk pengalaman pengguna yang lebih mulus.

**Langkah 1: Backend (Dapatkan Reference)**

```php
use Duitku\Laravel\Support\PaymentCode;

$request = new PaymentRequest(
    amount: 10000,
    merchantOrderId: 'INV-001',
    productDetails: 'Test Item',
    email: 'customer@example.com'
);

$response = Duitku::pop()->createTransaction($request);

echo $response->reference; // Gunakan ini di frontend JS
```

**Langkah 2: Frontend (Tampilkan Popup)**
Muat script Duitku dan panggil fungsi checkout.

```html
<script src="{{ Duitku::pop()->scriptUrl() }}"></script>

<script type="text/javascript">
  function bayar() {
    checkout.process("{{ $response->reference }}", {
      successEvent: function (result) {
        // Callback sukses di frontend
        // result.resultCode = '00'
        window.location.href = "/success";
      },
      pendingEvent: function (result) {
        // result.resultCode = '01'
        window.location.href = "/pending";
      },
      errorEvent: function (result) {
        // result.resultCode = '02'
        window.location.href = "/error";
      },
      closeEvent: function (result) {
        // Saat popup ditutup
      },
    });
  }
</script>
```

### 5. Disbursement (Transfer Dana / Payout)

Untuk menggunakan fitur Disbursement, tambahkan `DUITKU_USER_ID` dan `DUITKU_EMAIL` di `.env` terlebih dahulu.

**Langkah 1: Bank Inquiry (Cek Rekening Tujuan)**
Cek dulu apakah rekening tujuan valid dan atas nama siapa.

```php
use Duitku\Laravel\Facades\Duitku;
use Duitku\Laravel\Data\DisbursementInfo;

$info = new DisbursementInfo(
    amountTransfer: 50000,
    bankAccount: '1234567890',
    bankCode: '014', // Contoh: BCA
    purpose: 'Withdrawal'
);

// Inquiry
$inquiry = Duitku::disbursement()->bankInquiry($info);

echo $inquiry->accountName; // "JOHN DOE"
echo $inquiry->disburseId; // Simpan ID ini untuk langkah eksekusi!
```

**Langkah 2: Eksekusi Transfer**
Setelah nama rekening valid, lakukan transfer menggunakan `disburseId` dari hasil inquiry tadi.

```php
$transfer = Duitku::disbursement()->transfer(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber
);

echo $transfer->responseCode; // 00 = Sukses
```

### 5. Clearing (BIFAST / RTGS / LLG)

Untuk transfer nominal besar atau metode spesifik.

```php
$info = new DisbursementInfo(
    amountTransfer: 50000000, // 50 Juta
    bankAccount: '1234567890',
    bankCode: '014',
    purpose: 'Transfer Besar',
    type: 'BIFAST' // Opsional: 'RTGS', 'LLG', 'BIFAST'
);

// 1. Inquiry
$inquiry = Duitku::disbursement()->clearing()->inquiry($info);

// 2. Eksekusi
$transfer = Duitku::disbursement()->clearing()->execute(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber
);
```

### 6. Cash Out (Tarik Tunai via Retail)

Tarik tunai lewat Indomaret atau Pos Indonesia.

```php
use Duitku\Laravel\Data\CashOutInfo;

$info = new CashOutInfo(
    amountTransfer: 50000,
    bankCode: '2010', // 2010 = Indomaret, 2011 = Pos
    accountName: 'John Doe',
    accountIdentity: '350...', // No KTP (Wajib)
    phoneNumber: '08123...'   // No HP
);

$response = Duitku::disbursement()->cashOut()->inquiry($info);

// Berikan token ini ke kasir
echo $response->token;
```

### 7. Fitur Finance (Cek Status, Saldo, List Bank)

Menggunakan helper `DisbursementCode` agar pengecekan status lebih rapi.

```php
use Duitku\Laravel\Support\DisbursementCode;

// Cek Status Transaksi Disbursement
$status = Duitku::disbursement()->finance()->status('DISB-1001');

if ($status->responseCode === DisbursementCode::SUCCESS) {
    echo "Transaksi Berhasil!";
} elseif ($status->responseCode === DisbursementCode::INSUFFICIENT_FUNDS) {
    echo "Saldo Merchant Tidak Cukup";
}

// Cek Saldo Merchant
$balance = Duitku::disbursement()->finance()->balance();
echo "Saldo: " . number_format($balance->balance);
echo "Efektif: " . number_format($balance->effectiveBalance);

// Lihat Daftar Bank yang Tersedia
$banks = Duitku::disbursement()->finance()->listBank();
foreach ($banks as $bank) {
    echo $bank['bankName'] . ' (' . $bank['bankCode'] . ')';
}
```

## Testing

Jalankan test suite untuk memastikan integrasi berjalan lancar:

```bash
composer test
```

## Lisensi

MIT
