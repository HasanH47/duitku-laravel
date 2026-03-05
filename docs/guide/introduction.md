# Introduction

Selamat datang di dokumentasi **Duitku Laravel SDK**! 🎉

Halaman ini menjelaskan **apa itu SDK ini**, **kenapa kamu membutuhkannya**, dan **apa saja yang bisa kamu lakukan** dengannya.

---

## 🤔 Apa Itu Payment Gateway?

Bayangkan kamu punya toko online. Saat pelanggan mau bayar, kamu butuh "kasir" yang bisa menerima pembayaran dari berbagai metode — transfer bank, e-wallet, QRIS, kartu kredit, dll.

**Payment Gateway** adalah "kasir digital" itu. **Duitku** adalah salah satu payment gateway di Indonesia yang mendukung 30+ metode pembayaran.

> **Analogi sederhana:**
>
> - **Toko online kamu** = Aplikasi Laravel
> - **Kasir digital** = Duitku Payment Gateway
> - **SDK ini** = "Telepon langsung" ke kasir, tanpa harus datang ke toko Duitku

---

## 🤔 Kenapa Pakai SDK ini? (Bukan API Langsung?)

Duitku menyediakan API HTTP biasa yang bisa kamu panggil langsung. Tapi ada beberapa masalah jika kamu pakai API langsung:

| Masalah Tanpa SDK                                              | Solusi dari SDK                                            |
| -------------------------------------------------------------- | ---------------------------------------------------------- |
| ❌ Harus menghitung signature MD5/SHA256 manual — rentan salah | ✅ **Auto Signature** — SDK hitung otomatis                |
| ❌ Response berupa array mentah — gampang typo                 | ✅ **Typed DTOs** — auto-complete di IDE                   |
| ❌ Harus handle HTTP error sendiri                             | ✅ **Custom Exceptions** — tinggal `try-catch`             |
| ❌ Cek status transaksi satu-satu (lambat)                     | ✅ **Parallel Check** — cek 50+ transaksi sekaligus        |
| ❌ Validasi callback ribet                                     | ✅ **Event-Driven** — satu baris kode                      |
| ❌ Harus baca docs Duitku terus-menerus                        | ✅ **33 Payment Method Enum** — kode jadi self-documenting |

> **Intinya:** SDK ini membuat kamu bisa fokus ke bisnis logic, bukan ngurusin detail teknis komunikasi dengan Duitku.

---

## ✅ Apa Saja yang Bisa Dilakukan?

### 1. 💳 Payments (Terima Pembayaran)

Buat invoice pembayaran, arahkan pelanggan ke halaman bayar, dan terima notifikasi saat sudah dibayar.

**Metode pembayaran yang didukung:**

- Virtual Account (BCA, Mandiri, BNI, BRI, Permata, dll)
- E-Wallet (OVO, DANA, ShopeePay, LinkAja)
- QRIS (ShopeePay, Nobu, dll)
- Kartu Kredit (Visa, Mastercard, JCB)
- Ritel (Indomaret, Alfamart/Pegadaian)
- Paylater (Indodana, ATOME)
- E-Commerce (Tokopedia)

### 2. ⚡ Duitku POP (Popup Checkout)

Alternatif dari redirect — pelanggan bisa bayar langsung di halaman kamu melalui popup, tanpa pindah halaman.

### 3. 📢 Callback System (Webhook)

Setelah pelanggan bayar, Duitku akan mengirim notifikasi ke server kamu. SDK ini memvalidasi keasliannya dan mengirim Laravel Event yang bisa kamu tangkap.

### 4. 💸 Disbursement (Transfer Dana)

Kirim uang dari akun Duitku kamu ke rekening bank lain — untuk fitur payout, withdrawal, atau gaji.

---

## 🗺️ Alur Transaksi (Flow)

Berikut alur dari pelanggan klik "Bayar" sampai kamu terima uang:

```
┌──────────┐     ┌──────────────┐     ┌──────────┐     ┌──────────┐
│ Pelanggan │────▶│ Aplikasi Kamu │────▶│  Duitku  │────▶│   Bank   │
│ (Browser) │     │  (Laravel)   │     │  (API)   │     │ / E-Wallet│
└──────────┘     └──────────────┘     └──────────┘     └──────────┘
                        │                    │
                        │   ◀── Callback ──  │  (Duitku beri tahu:
                        │   (Webhook POST)   │   "Pembayaran berhasil!")
                        ▼
                 ┌──────────────┐
                 │  Update DB   │
                 │ Order = Paid │
                 └──────────────┘
```

1. **Pelanggan** klik "Bayar" di website kamu
2. **Aplikasi kamu** membuat invoice via SDK → Duitku kasih URL pembayaran
3. **Pelanggan** bayar di halaman Duitku (transfer VA, scan QRIS, dll)
4. **Bank/E-Wallet** proses pembayaran
5. **Duitku** kirim **callback** (webhook) ke server kamu → SDK validasi otomatis
6. **Aplikasi kamu** update database: order sudah dibayar ✅

---

## 📋 Apa yang Perlu Disiapkan?

Sebelum mulai, pastikan kamu punya:

- [ ] **PHP 8.2+** dan **Laravel 10/11/12**
- [ ] **Akun Duitku** — [Daftar di sini](https://passport.duitku.com/merchant/Register) (gratis)
- [ ] **Merchant Code** dan **API Key** — dapat dari [Dashboard Duitku](https://passport.duitku.com/merchant/Project)
- [ ] **Composer** terinstal di komputer kamu

> [!TIP]
> Kamu bisa langsung memulai dengan **mode Sandbox** (testing) tanpa perlu verifikasi akun. Semua transaksi di Sandbox adalah simulasi — tidak ada uang sungguhan yang berpindah.

---

Sudah siap? Mari mulai dengan [Instalasi](./installation)! 🚀
