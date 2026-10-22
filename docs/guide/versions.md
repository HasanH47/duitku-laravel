# Versi dokumentasi

Pilih dokumentasi yang cocok dengan versi `duitku/laravel` di aplikasi. Dokumentasi v3 menjadi panduan aktif dan berada di halaman utama. Dokumentasi major sebelumnya tersedia sebagai arsip, dengan penanda versi di bagian atas halaman.

| Seri package | Dokumentasi | Status | Instalasi |
| --- | --- | --- | --- |
| 3.x | [Panduan terbaru](/) | Aktif; mengikuti rilis minor dan patch v3 | `composer require duitku/laravel:^3.0` |
| 2.x | [Arsip v2.0.0](/v2/) | Dokumentasi saat rilis v2.0.0; contoh instalasi diarahkan ke seri v2 | `composer require duitku/laravel:^2.0` |

Untuk naik dari v2 ke v3, ikuti [panduan migrasi major](/guide/upgrading). Perubahan v3 mencakup dukungan Laravel, signature, validasi callback, retry, dan sejumlah API publik.

Arsip v2 diambil dari tag rilis `v2.0.0` agar perilaku SDK dan panduannya bisa ditelusuri pada satu versi yang nyata. Arsip tersebut tidak mengikuti perubahan terbaru. Perubahan keamanan atau koreksi yang mencegah kesalahan serius dapat diberi catatan pada halaman arsip.

## Kebijakan versi berikutnya

Halaman root selalu berisi dokumentasi major terbaru. Setiap kali major baru dirilis, salin dokumentasi major aktif ke `docs/vN/`, beri penanda arsip, dan ubah instruksi instalasinya agar tetap memilih seri package yang sesuai. Setelah itu perbarui `docs/` untuk versi major yang baru dan tambahkan pilihan versi di navigasi. Pembaruan minor dan patch mengikuti dokumentasi versi aktif tanpa membuat arsip baru.

Lihat [panduan kontribusi](https://github.com/HasanH47/duitku-laravel/blob/main/CONTRIBUTING.md) untuk langkah pemeliharaan dokumentasi versi.
