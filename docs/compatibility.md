# Kompatibilitas dan hasil verifikasi

Halaman ini menjelaskan cakupan package `duitku/laravel` v3.0.0 per 22 Oktober 2026. “Tersedia” berarti implementasinya ada di package; “terverifikasi sandbox” hanya berarti alur tersebut benar-benar dijalankan pada environment sandbox yang tercatat di bawah. Status sandbox tidak menjamin hasil yang sama untuk semua akun, channel, atau production.

## Framework dan runtime

| PHP | Laravel | Status |
| --- | --- | --- |
| 8.2 | 12 | Didukung dan diuji di CI |
| 8.3–8.5 | 12 | Didukung dan diuji di CI |
| 8.3–8.5 | 13 | Didukung dan diuji di CI |

Laravel 13 pada PHP 8.2 tidak didukung. Package v3 adalah major upgrade dari v2; ikuti [panduan migrasi](./guide/upgrading) sebelum memperbarui aplikasi.

## Cakupan integrasi Duitku

| Area | Tersedia di package | Verifikasi langsung provider |
| --- | --- | --- |
| Payment API V2 | Membuat invoice, melihat status dan metode pembayaran, batch status, callback terverifikasi | BCA VA sandbox: invoice Rp10.000, status pending hingga paid, callback HMAC diterima, reference cocok, dan listener contoh memperbarui order |
| POP | Membuat invoice popup, asset browser/Blade, status dan daftar metode lewat API V2, callback terverifikasi | BCA VA sandbox: invoice Rp10.000, nomor VA diterbitkan setelah pemilihan channel, status pending lalu paid, callback HMAC diterima |
| Disbursement | Balance, bank list, inquiry dan eksekusi transfer, clearing, cash out, serta bulk inquiry | Belum diuji langsung ke provider |
| Direct Credit Card API | API lifecycle kartu langsung belum tersedia. DTO invoice hanya menyediakan beberapa field detail kartu/token; itu bukan dukungan untuk seluruh API kartu | Belum diuji langsung ke provider |
| SNAP | Modul API SNAP belum tersedia | Belum diuji langsung ke provider |

Daftar channel berasal dari akun merchant dan hasil `paymentMethods()` tidak berarti setiap channel sudah aktif atau tersedia untuk semua merchant. Pada pengujian akun sandbox yang dicatat, daftar API mengembalikan 26 metode; invoice dan pelunasan yang diuji hanya BCA VA.

## Batas pengujian

- Seluruh pengujian provider yang tercatat memakai sandbox. Tidak ada transaksi production yang dijalankan.
- Signature HMAC-SHA256 diuji untuk alur BCA VA API dan POP. Protokol legacy tidak diuji langsung ke provider.
- Lookup status invoice POP mengembalikan HTTP 404 sebelum VA diterbitkan pada alur yang diuji. Setelah pelanggan memilih BCA VA dan nomor VA diterbitkan, status bisa dibaca. Jangan menyamakan 404 tersebut dengan transaksi gagal atau lunas.
- Pembayaran gagal/canceled, channel selain BCA VA, tokenisasi/charge kartu, dan operasi disbursement belum diuji langsung ke provider. Kegagalan dan status tersebut tetap memiliki pengujian lokal dengan fixture.
- Callback API yang sama diuji ulang: respons tetap 200 dan timestamp pembayaran pada listener contoh tidak berubah. Ini menguji contoh integrasi, bukan menjamin exactly-once delivery atau idempotensi efek bisnis di semua aplikasi.

CI menguji matriks PHP/Laravel di atas serta kontrak lokal package. CI tidak menghubungi Duitku. Detail skenario provider ada di [catatan verifikasi sandbox](./reviews/2026-10-22-sandbox).

## Hubungan dengan Duitku

Ini package komunitas independen, bukan SDK resmi, afiliasi, atau endorsement Duitku. Duitku menyediakan [library PHP resminya sendiri](https://github.com/duitkupg/duitku-php); package ini menyediakan pengalaman integrasi Laravel tersendiri dan tidak mengklaim menggantikan atau membungkus library tersebut.

Untuk detail protokol, rujuk [Payment API V2](https://docs.duitku.com/api/id/), [POP](https://docs.duitku.com/pop/id/), [Disbursement](https://docs.duitku.com/disbursement/id/), [Direct Credit Card](https://docs.duitku.com/direct-credit-card/id/), dan [SNAP](https://docs.duitku.com/snap-api/id/). Ketersediaan fitur provider tetap mengikuti aktivasi dan ketentuan akun merchant.
