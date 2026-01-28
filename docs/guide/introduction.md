# Introduction

**Duitku Laravel** adalah SDK modern dan profesional untuk mengintegrasikan payment gateway [Duitku](https://www.duitku.com/) ke dalam aplikasi Laravel Anda.

## Mengapa Menggunakan SDK ini?

Meskipun Duitku menyediakan API HTTP standar, SDK ini memberikan lapisan abstraksi yang membuat kode Anda tetap bersih, aman, dan mengikuti _Best Practices_ Laravel:

- **Elegansi Laravel**: Didesain agar terasa seperti bagian asli dari framework Laravel.
- **Validasi Otomatis**: Lupakan kerumitan menghitung signature HMAC, SDK ini menanganinya untuk Anda.
- **Event-Driven**: Tangani pembayaran yang masuk menggunakan Laravel Events/Listeners.
- **Custom Exceptions**: Menangkap error dengan lebih spesifik (misal: saldo tidak cukup).
- **High Performance**: Mendukung operasi bulk yang efisien.
- **UI Ready**: Komponen Blade siap pakai untuk Duitku POP.

## Apa yang Bisa Dilakukan?

Dengan SDK ini, Anda bisa mengelola seluruh siklus transaksi Duitku:

1.  **Payments**: Membuat invoice, mengecek status, dan daftar metode pembayaran.
2.  **Duitku POP**: Integrasi popup pembayaran (Snap style).
3.  **Callbacks**: Menangani notifikasi pembayaran dari server Duitku dengan aman.
4.  **Disbursement**: Melakukan transfer bank, cek saldo, dan verifikasi nomor rekening secara massal.

Mari kita mulai dengan [Instalasi](./installation)!
