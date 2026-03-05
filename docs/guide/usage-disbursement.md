# Usage: Disbursement

Fitur **Disbursement** memungkinkan kamu mengirim uang dari akun Duitku kamu ke rekening bank lain. Cocok untuk fitur **payout**, **withdrawal**, **gaji karyawan**, atau **refund manual**.

---

## 🤔 Apa Itu Disbursement?

> **Analogi:** Jika fitur Payment adalah "terima uang dari pelanggan", maka Disbursement adalah "kirim uang ke orang lain". Seperti mobile banking, tapi via kode (otomatis).

**Yang bisa dilakukan:**

- 💸 **Transfer Bank** — Kirim uang ke rekening bank manapun di Indonesia
- 🏪 **Cash Out** — Tarik tunai lewat Indomaret atau Pos Indonesia
- 🏦 **Clearing** — Transfer nominal besar via BIFAST/RTGS/LLG
- 💰 **Cek Saldo** — Cek saldo akun disbursement kamu
- 🏛️ **Daftar Bank** — Lihat bank yang tersedia untuk transfer

---

## ⚠️ Persyaratan

Sebelum menggunakan Disbursement, kamu perlu:

1. **Aktivasi fitur Disbursement** — Hubungi tim Duitku
2. **Dapatkan User ID dan Email** — Dari tim Duitku
3. **Set di `.env`:**

```env
DUITKU_USER_ID=your-user-id
DUITKU_EMAIL=your-email@example.com
```

> [!WARNING]
> Tanpa `DUITKU_USER_ID` dan `DUITKU_EMAIL`, semua fitur Disbursement akan gagal.

---

## 🔄 Alur Transfer (Flow)

Transfer dana selalu mengikuti 2 langkah:

```
1. INQUIRY — Verifikasi rekening tujuan (cek nama pemilik)
   ↓
2. EXECUTE — Lakukan transfer (kirim uang sungguhan)
```

> **Kenapa 2 langkah?** Agar kamu bisa cek dulu nama pemilik rekening sebelum benar-benar mengirim uang. Mencegah salah transfer!

---

## 💰 Cek Saldo Disbursement

Sebelum melakukan transfer, sebaiknya cek dulu saldo kamu:

```php
use Duitku\Laravel\Facades\Duitku;

$balance = Duitku::disbursement()->finance()->balance();

echo "Saldo: Rp " . number_format($balance->balance);
echo "Efektif: Rp " . number_format($balance->effectiveBalance);
```

| Property           | Penjelasan                                                     |
| ------------------ | -------------------------------------------------------------- |
| `balance`          | Total saldo di akun disbursement                               |
| `effectiveBalance` | Saldo yang bisa digunakan (setelah dikurangi pending transfer) |

---

## 🏦 Transfer Bank (Online Transfer)

### Langkah 1: Inquiry (Verifikasi Rekening)

```php
use Duitku\Laravel\Data\DisbursementInfo;

$info = new DisbursementInfo(
    amountTransfer: 500000,        // Nominal transfer (Rupiah)
    bankAccount: '1234567890',     // Nomor rekening tujuan
    bankCode: '014',               // Kode bank (014 = BCA)
    purpose: 'Withdrawal User'     // Tujuan transfer
);

// Verifikasi rekening → Duitku akan cek apakah rekening valid
$inquiry = Duitku::disbursement()->bankInquiry($info);

// Cek hasilnya
if ($inquiry->responseCode === '00') {
    echo "Nama Pemilik: " . $inquiry->accountName;  // "JOHN DOE"
    echo "Disburse ID: " . $inquiry->disburseId;    // Simpan ID ini!
} else {
    echo "Rekening tidak ditemukan!";
}
```

> [!IMPORTANT]
> **Simpan `disburseId`!** Kamu butuh ID ini untuk langkah execute.

### Langkah 2: Execute (Transfer Uang)

```php
$transfer = Duitku::disbursement()->transfer(
    disburseId: $inquiry->disburseId,        // ID dari inquiry
    info: $info,                             // Info transfer yang sama
    accountName: $inquiry->accountName,       // Nama pemilik dari inquiry
    custRefNumber: $inquiry->custRefNumber    // Reference number
);

if ($transfer->responseCode === '00') {
    echo "✅ Transfer berhasil diproses!";
} else {
    echo "❌ Transfer gagal: " . $transfer->responseDesc;
}
```

### Bulk Inquiry (Verifikasi Banyak Rekening Sekaligus) 🚀

```php
$infos = [
    new DisbursementInfo(50000, '111222333', '014', 'Gaji-A'),
    new DisbursementInfo(75000, '444555666', '008', 'Gaji-B'),
    new DisbursementInfo(100000, '777888999', '009', 'Gaji-C'),
];

// Cek 3 rekening sekaligus secara paralel!
$results = Duitku::disbursement()->transfer()->bulkInquiry($infos);

foreach ($results as $result) {
    echo "{$result->bankAccount}: {$result->accountName} — {$result->responseCode}\n";
}
```

---

## 🏦 Clearing (BIFAST / RTGS / LLG)

Untuk transfer nominal besar atau metode spesifik.

| Tipe       | Nominal         | Waktu                  | Biaya        |
| ---------- | --------------- | ---------------------- | ------------ |
| **BIFAST** | s/d Rp 250 juta | Real-time (24/7)       | Paling murah |
| **RTGS**   | > Rp 100 juta   | Jam kerja (8:00-15:00) | Menengah     |
| **LLG**    | Bebas           | Batch (beberapa jam)   | Paling murah |

```php
$info = new DisbursementInfo(
    amountTransfer: 50000000,      // 50 Juta
    bankAccount: '1234567890',
    bankCode: '014',
    purpose: 'Transfer Besar',
    type: 'BIFAST'                 // 'BIFAST', 'RTGS', atau 'LLG'
);

// 1. Inquiry
$inquiry = Duitku::disbursement()->clearing()->inquiry($info);

// 2. Execute
$transfer = Duitku::disbursement()->clearing()->execute(
    disburseId: $inquiry->disburseId,
    info: $info,
    accountName: $inquiry->accountName,
    custRefNumber: $inquiry->custRefNumber
);
```

---

## 🏪 Cash Out (Tarik Tunai via Ritel)

Pelanggan bisa tarik tunai lewat **Indomaret** atau **Pos Indonesia** tanpa kartu ATM.

```php
use Duitku\Laravel\Data\CashOutInfo;

$info = new CashOutInfo(
    amountTransfer: 50000,
    bankCode: '2010',              // 2010 = Indomaret, 2011 = Pos Indonesia
    accountName: 'John Doe',
    accountIdentity: '3501...',    // No KTP (Wajib untuk cash out)
    phoneNumber: '08123456789'
);

$response = Duitku::disbursement()->cashOut()->inquiry($info);

// Berikan token ini ke pelanggan — tunjukkan ke kasir untuk tarik tunai
echo "Token: " . $response->token;
```

> **Cara kerjanya:** Pelanggan tunjukkan token ke kasir Indomaret/Pos → kasir input token → uang tunai diberikan.

---

## 📊 Cek Status Transfer

Setelah inquiry dan execute, kamu bisa cek apakah transfer sudah berhasil:

```php
use Duitku\Laravel\Support\DisbursementCode;

$status = Duitku::disbursement()->finance()->status('DISB-1001');

if ($status->responseCode === DisbursementCode::SUCCESS) {
    echo "✅ Transfer berhasil!";
} elseif ($status->responseCode === DisbursementCode::INSUFFICIENT_FUNDS) {
    echo "❌ Saldo tidak cukup!";
} elseif ($status->responseCode === DisbursementCode::PENDING) {
    echo "⏳ Masih diproses...";
} else {
    echo "❌ Gagal: " . $status->responseDesc;
}
```

---

## 🏛️ Daftar Bank yang Tersedia

```php
$banks = Duitku::disbursement()->finance()->listBank();

foreach ($banks as $bank) {
    echo $bank['bankCode'] . ' — ' . $bank['bankName'];
}
// Contoh output:
// 014 — BCA
// 008 — Mandiri
// 009 — BNI
// ...
```

---

## ⚠️ Kesalahan Umum

| Error                   | Penyebab                       | Solusi                                            |
| ----------------------- | ------------------------------ | ------------------------------------------------- |
| `Insufficient funds`    | Saldo disbursement tidak cukup | Top-up saldo di dashboard Duitku                  |
| `Account not found`     | Nomor rekening tidak valid     | Cek nomor rekening tujuan                         |
| `Invalid bank code`     | Kode bank salah                | Gunakan `listBank()` untuk daftar kode yang valid |
| `Disburse ID not found` | ID dari inquiry sudah expired  | Lakukan inquiry ulang                             |

---

Pelajari cara membuat UI pembayaran dengan [Blade Components](./blade-components). 🎨
