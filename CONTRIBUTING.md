# Kontribusi

Kontribusi bug fix, dokumentasi, test, dan fitur Laravel diterima melalui pull request ke `main`. Untuk perubahan API publik atau modul besar seperti kartu/SNAP, buka feature request lebih dahulu agar kontrak dan cakupannya bisa dibahas.

Baca [Code of Conduct](CODE_OF_CONDUCT.md). Untuk celah keamanan, gunakan jalur privat di [SECURITY.md](SECURITY.md), bukan issue publik.

## Menyiapkan project

Fork repository, buat branch dari `main`, lalu pasang dependency:

```sh
composer install
```

Package mendukung Laravel 12–13. Jalur lokal PHP 8.2 memakai Laravel 12/Pest 3; Laravel 13 membutuhkan PHP 8.3+. Composer memilih graph yang sesuai. CI menjalankan tujuh kombinasi PHP/Laravel yang didukung.

```sh
composer test -- --no-coverage
composer analyse
composer format:check
node tests/js/pop.test.cjs
```

Gunakan `composer format` untuk merapikan format. Untuk perubahan dokumentasi atau dependency frontend:

```sh
cd docs
npm ci
npm run docs:build
```

Dokumentasi untuk versi aktif ada di `docs/`. Dokumentasi major lama disimpan di `docs/vN/` sebagai snapshot dari rilis terakhir major tersebut. Pertahankan banner arsip dan pin perintah instalasinya ke major yang sesuai. Jangan memperbarui contoh arsip agar menyerupai versi baru; arahkan pengguna ke versi aktif untuk perilaku terbaru. Untuk rilis major berikutnya, salin dokumen aktif ke folder versi sebelum memperbarui dokumentasi utama, lalu perbarui dropdown navigasi dan [panduan versi](docs/guide/versions.md).

## Mengubah perilaku SDK

Tambahkan test pada boundary yang berubah: DTO/signature di unit test, integrasi service/provider/event di feature test, dan adapter POP di test JavaScript. Reproduksi bug sebelum perbaikan bila memungkinkan. Gunakan input sintetis dan kontrol waktu untuk kasus yang bergantung pada timestamp.

Test biasa tidak boleh menghubungi provider. Gunakan `Http::preventStrayRequests()`, `Http::fake()`, atau [`Duitku::fake()`](docs/guide/testing.md). Jangan memakai fake SDK sebagai satu-satunya oracle signature; pertahankan fixture dengan hasil yang dihitung independen dari signer.

Pengujian sandbox dilakukan terpisah dari CI, dengan kredensial milik penguji dan persetujuan pemilik akun. Jangan menyertakan API key, signature, nomor rekening, data pelanggan, atau payload callback asli dalam commit, log publik, issue, dan screenshot.

Perubahan pembayaran perlu mempertimbangkan nominal rupiah, identitas order/reference, hasil ambigu, retry, callback berulang, dan transisi status final. Aplikasi pemakai tetap memiliki database order dan efek bisnisnya. Jangan menambahkan retry eksekusi payout atau menganggap event browser sebagai bukti paid.

## Mengirim pull request

Perubahan dokumentasi atau SDK resmi Duitku dipantau lewat issue otomatis. Ikuti [panduan pemantauan](docs/guide/maintenance.md) untuk mereview perubahan upstream. Refresh baseline hanya setelah menilai dampaknya dan menjalankan verifikasi yang relevan.

Jelaskan masalah yang diperbaiki dan perilaku akhirnya. Cantumkan validasi yang dijalankan beserta hasilnya, serta pengaruh pada API publik, konfigurasi, dan migrasi. Untuk perubahan dokumentasi saja, jelaskan bahwa pemeriksaan PHP tidak relevan.

Jaga satu PR pada satu tujuan yang dapat direview. Hindari perubahan formatter di file yang tidak terkait, pembaruan dependency di luar cakupan, atau klaim performa tanpa pengukuran. Dokumentasikan perilaku yang berubah dan tambahkan catatan pada bagian Unreleased di [changelog](CHANGELOG.md).

Kontribusi mengikuti [lisensi MIT](LICENSE) project. Maintainer menerbitkan rilis mengikuti [panduan rilis](RELEASING.md).
