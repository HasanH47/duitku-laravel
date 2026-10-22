# Kebijakan keamanan

Perbaikan keamanan ditujukan ke rilis stable terbaru pada seri **3.x**. Versi 2.x dan yang lebih lama perlu dimigrasikan ke seri yang didukung. Lihat [panduan migrasi](docs/guide/upgrading.md).

## Melaporkan kerentanan secara privat

Kirim laporan ke **me@hasanh.dev** dengan subjek `Security: duitku/laravel`. Jangan membuka issue atau PR publik yang berisi detail eksploitasi sebelum ada koordinasi dengan maintainer.

Sertakan versi package/PHP/Laravel, perilaku yang diharapkan dan terjadi, dampak, prasyarat serangan, serta reproduksi minimal dengan data sintetis. Beri tahu jika laporan bergantung pada konfigurasi tertentu atau perilaku akun merchant.

Jangan mengirim API key aktif, signature callback yang masih dapat dipakai ulang, data pelanggan, nomor rekening/kartu, atau akses akun merchant. Jika reproduksi memerlukan secret, jelaskan kebutuhannya dahulu dan koordinasikan jalur pengujian terpisah.

Maintainer akan menilai dampak dan berkoordinasi mengenai perbaikan serta waktu pengungkapan. Project dikelola komunitas dan tidak menjanjikan waktu respons atau SLA tertentu.

## Cakupan

Contoh masalah yang relevan meliputi penerimaan callback palsu, pencocokan order/nominal yang salah, efek pembayaran ganda, kebocoran kredensial atau data pelanggan, input POP yang menjalankan JavaScript, dan dependency runtime yang dapat dieksploitasi.

Untuk kerentanan layanan Duitku, laporkan juga melalui kanal keamanan/support Duitku yang resmi. Jangan menguji pada akun pihak lain, mengirim transaksi production, atau membebani endpoint provider. Bug biasa dan pertanyaan integrasi menggunakan [issue tracker](https://github.com/HasanH47/duitku-laravel/issues/new/choose).
