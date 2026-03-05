# Error Handling

Tidak semua transaksi akan berhasil. Pelanggan bisa salah input, saldo tidak cukup, atau server Duitku sedang bermasalah. Halaman ini menjelaskan cara **menangani semua kemungkinan error** dengan baik.

---

## 🤔 Kenapa Error Handling Penting?

Tanpa error handling yang baik:

- ❌ Aplikasi kamu crash saat Duitku mengembalikan error
- ❌ User melihat halaman error yang tidak informatif
- ❌ Kamu tidak tahu apa yang salah (susah debugging)

Dengan error handling yang baik:

- ✅ Aplikasi kamu tetap berjalan normal
- ✅ User mendapat pesan error yang jelas
- ✅ Kamu bisa melacak dan memperbaiki masalah

---

## 🛡️ Custom Exceptions

SDK ini menyediakan exception spesifik agar kamu bisa menangani setiap jenis error secara berbeda.

### Daftar Exceptions

| Exception                    | Kapan Terjadi?                            | Contoh                                |
| ---------------------------- | ----------------------------------------- | ------------------------------------- |
| `DuitkuException`            | Base exception untuk semua error SDK      | —                                     |
| `DuitkuApiException`         | API Duitku mengembalikan error            | Order ID sudah dipakai, email invalid |
| `InsufficientFundsException` | Saldo merchant tidak cukup (Disbursement) | Mau transfer tapi saldo kosong        |
| `InvalidSignatureException`  | Signature callback tidak valid            | Ada yang mengirim callback palsu      |

### Contoh Penggunaan

```php
use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Exceptions\InsufficientFundsException;
use Duitku\Laravel\Exceptions\InvalidSignatureException;

// === Contoh 1: Checkout ===
try {
    $response = Duitku::checkout($request);
    return redirect($response->paymentUrl);

} catch (DuitkuApiException $e) {
    // Error dari API Duitku (400, 401, 404, 409, dll)
    return back()->with('error', 'Gagal membuat pembayaran: ' . $e->getMessage());
}

// === Contoh 2: Disbursement ===
try {
    $result = Duitku::disbursement()
        ->transfer()
        ->execute($info)
        ->throwIfFailed();

} catch (InsufficientFundsException $e) {
    // Saldo merchant tidak cukup
    return back()->with('error', 'Transfer gagal: saldo merchant tidak cukup.');

} catch (DuitkuApiException $e) {
    // Error lainnya
    $kodeError = $e->getDuitkuCode(); // Kode error asli dari Duitku
    return back()->with('error', 'Transfer gagal: ' . $e->getMessage());
}

// === Contoh 3: Callback ===
try {
    Duitku::handleCallback($request->all());
    return response('OK');

} catch (InvalidSignatureException $e) {
    // Signature tidak valid — kemungkinan callback palsu!
    Log::warning('Duitku callback palsu terdeteksi!', $request->all());
    abort(403);
}
```

---

## 📋 Referensi Error Code

SDK menyediakan class `ErrorCode` yang berisi **semua kode error** dari dokumentasi resmi Duitku.

### HTTP Status Codes (dari API Duitku)

| HTTP Code | Constant                       | Penjelasan                                         |
| --------- | ------------------------------ | -------------------------------------------------- |
| **200**   | `ErrorCode::HTTP_SUCCESS`      | ✅ Request berhasil                                |
| **400**   | `ErrorCode::HTTP_BAD_REQUEST`  | ❌ Ada kesalahan di request (parameter salah)      |
| **401**   | `ErrorCode::HTTP_UNAUTHORIZED` | ❌ Signature/credential salah                      |
| **404**   | `ErrorCode::HTTP_NOT_FOUND`    | ❌ Merchant tidak ditemukan                        |
| **409**   | `ErrorCode::HTTP_CONFLICT`     | ❌ Konflik (order ID duplikat, amount tidak cocok) |
| **500**   | `ErrorCode::HTTP_SERVER_ERROR` | ❌ Error internal server Duitku                    |

### Error Messages (dari HTTP 400 Bad Request)

| Constant                             | Pesan                                 | Solusi                            |
| ------------------------------------ | ------------------------------------- | --------------------------------- |
| `ErrorCode::MIN_PAYMENT`             | Minimum Payment 10000 IDR             | Naikkan nominal minimal Rp 10.000 |
| `ErrorCode::MAX_PAYMENT`             | Maximum Payment exceeded              | Turunkan nominal di bawah batas   |
| `ErrorCode::PAYMENT_METHOD_REQUIRED` | paymentMethod is mandatory            | Isi parameter paymentMethod       |
| `ErrorCode::ORDER_ID_REQUIRED`       | merchantOrderId is mandatory          | Isi parameter merchantOrderId     |
| `ErrorCode::ORDER_ID_TOO_LONG`       | length of merchantOrderId can't > 50  | Pendekkan order ID (max 50 char)  |
| `ErrorCode::INVALID_EMAIL`           | Invalid Email Address                 | Perbaiki format email             |
| `ErrorCode::EMAIL_TOO_LONG`          | length of email can't > 50            | Pendekkan email (max 50 char)     |
| `ErrorCode::PHONE_TOO_LONG`          | length of phoneNumber can't > 50      | Pendekkan nomor HP (max 50 char)  |
| `ErrorCode::VA_NAME_REQUIRED`        | Customer VA Name must not be empty... | Isi parameter customerVaName      |

### Error Messages Lainnya

| Constant                                   | HTTP | Pesan                                          | Solusi                                   |
| ------------------------------------------ | ---- | ---------------------------------------------- | ---------------------------------------- |
| `ErrorCode::WRONG_SIGNATURE`               | 401  | Wrong signature                                | Cek API Key dan Merchant Code            |
| `ErrorCode::MERCHANT_NOT_FOUND`            | 404  | Merchant not found                             | Cek Merchant Code                        |
| `ErrorCode::PAYMENT_CHANNEL_NOT_AVAILABLE` | 404  | Payment channel not available                  | Metode bayar belum aktif, hubungi Duitku |
| `ErrorCode::AMOUNT_MISMATCH`               | 409  | Payment amount must be equal to all item price | Total itemDetails harus = amount         |

### Error Khusus POP

| Constant                                 | Pesan                                      | Solusi                                                               |
| ---------------------------------------- | ------------------------------------------ | -------------------------------------------------------------------- |
| `ErrorCode::POP_AMOUNT_DIFFERENT`        | Amount is different please try again later | Jangan kirim request ulang dengan nominal berbeda tapi order ID sama |
| `ErrorCode::POP_SAVE_CARD_NOT_AVAILABLE` | SaveCardToken is not available             | Akun belum mendukung tokenisasi, hubungi Duitku                      |
| `ErrorCode::POP_TRANSACTION_IN_PROGRESS` | The transaction is still in progress       | Request sebelumnya masih diproses, tunggu sebentar                   |

### API Response Codes (Callback & Redirect)

| Constant             | Kode   | Context             | Arti                        |
| -------------------- | ------ | ------------------- | --------------------------- |
| `ErrorCode::SUCCESS` | `'00'` | Callback & Redirect | ✅ Transaksi berhasil       |
| `ErrorCode::PENDING` | `'01'` | Redirect            | ⏳ Belum terbayar (pending) |
| `ErrorCode::FAILED`  | `'02'` | Callback & Redirect | ❌ Gagal / dibatalkan       |

### Helper Methods

```php
use Duitku\Laravel\Enums\ErrorCode;

// Cek status code
ErrorCode::isSuccess('00');   // true
ErrorCode::isPending('01');   // true
ErrorCode::isFailed('02');    // true

// Deskripsi HTTP code
ErrorCode::describeHttp(400);
// → 'Ada kesalahan pada saat mengirimkan permohonan pada API.'

// Deskripsi status code
ErrorCode::describeStatus('00', 'callback');
// → 'Transaksi telah sukses terbayarkan.'

ErrorCode::describeStatus('01', 'redirect');
// → 'Transaksi belum terbayar (Pending).'
```

---

## 🔍 Debugging Tips

### 1. Aktifkan Logging

Set `DUITKU_LOG_CHANNEL` di `.env` untuk mencatat semua request:

```env
DUITKU_LOG_CHANNEL=duitku
```

Semua request ke Duitku API akan dicatat di log file, termasuk URL dan body request.

### 2. Cek Response Asli

Jika menggunakan `try-catch` dengan `DuitkuApiException`:

```php
catch (DuitkuApiException $e) {
    $kodeAsli = $e->getDuitkuCode();  // Kode error dari Duitku
    $pesan = $e->getMessage();         // Pesan error
    Log::error('Duitku error', ['code' => $kodeAsli, 'message' => $pesan]);
}
```

### 3. Gunakan Sandbox

Selalu test di Sandbox terlebih dahulu. Duitku menyediakan kredensial test untuk berbagai skenario (sukses, gagal, pending).

---

Lanjut ke fitur transfer dana di [Disbursement](./usage-disbursement). 💸
