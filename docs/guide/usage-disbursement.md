# Usage: Disbursement

Fitur Disbursement memungkinkan Anda untuk mengirimkan dana ke berbagai bank di Indonesia secara otomatis.

## 1. Cek Saldo Disbursement

Sebelum melakukan transfer, pastikan saldo Anda cukup.

```php
$balance = Duitku::disbursement()->finance()->checkBalance();

echo "Saldo: " . $balance->balance;
```

## 2. Inkuiri Rekening Bank

Selalu lakukan inkuiri (verifikasi) nomor rekening tujuan sebelum melakukan transfer untuk memastikan nama pemilik rekening sesuai.

### Inkuiri Tunggal

```php
use Duitku\Laravel\Data\DisbursementInfo;

$info = new DisbursementInfo(
    amount: 100000,
    bankAccount: '1234567890',
    bankCode: '014', // Kode Bank (misal: BCA)
    custRefNumber: 'REF-001'
);

$response = Duitku::disbursement()->transfer()->inquiry($info);

if ($response->responseCode === '00') {
    echo "Nama Pemilik: " . $response->accountName;
}
```

### Inkuiri Massal (Parallel)

SDK v1.3.1 mendukung pengecekan banyak rekening secara bersamaan menggunakan `Http::pool` untuk performa maksimal.

```php
$infos = [
    new DisbursementInfo(50000, '111', '014', 'REF-A'),
    new DisbursementInfo(75000, '222', '008', 'REF-B'),
];

$responses = Duitku::disbursement()->transfer()->bulkInquiry($infos);

foreach ($responses as $res) {
    echo "Status: " . $res->responseCode . " - " . $res->accountName;
}
```

## 3. Eksekusi Cash Out (Transfer)

Setelah inkuiri berhasil, Anda bisa melakukan eksekusi transfer.

```php
use Duitku\Laravel\Data\CashOutInfo;

$cashOut = new CashOutInfo(
    amount: 100000,
    bankAccount: '1234567890',
    bankCode: '014',
    custRefNumber: 'REF-001',
    disburseId: 'DISB-2026-999' // Gunakan ID unik Anda
);

$transfer = Duitku::disbursement()->transfer()->cashOut($cashOut);

if ($transfer->responseCode === '00') {
    echo "Dana sedang diproses!";
}
```

---

Pelajari cara menangani notifikasi pembayaran di [Callback System](./callback-system).
