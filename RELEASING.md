# Menerbitkan rilis

Maintainer menerbitkan rilis dari commit yang sudah dipush ke `main` dan lolos seluruh CI. Pilih versi SemVer: major untuk kontrak yang breaking, minor untuk fitur kompatibel, dan patch untuk perbaikan kompatibel. Gunakan prerelease jika verifikasi yang diperlukan belum selesai.

## Menyiapkan commit rilis

Perbarui changelog, panduan migrasi jika perlu, serta metadata versi dokumentasi. Versi Composer berasal dari tag Git; jangan menambahkan field `version` pada `composer.json`.

Periksa manifest dan distribusi bersih, test PHP/JavaScript, PHPStan, Pint, serta build dokumentasi. Untuk perubahan integrasi framework, uji instalasi dan auto-discovery pada aplikasi Laravel terpisah.

Perubahan protokol pembayaran memerlukan verifikasi sandbox yang relevan: request, pending/paid, callback provider, pencocokan nominal/reference, dan callback berulang. Simpan ringkasan tanpa secret di `docs/reviews/`; fixture lokal dan hasil provider harus dibedakan. Jangan menyebut semua channel terverifikasi berdasarkan satu channel.

## Tag dan publikasi

Ganti placeholder versi dan SHA pada perintah berikut dengan versi baru serta commit yang sudah lolos pemeriksaan. Siapkan catatan rilis dalam file lokal yang sudah direview.

```sh
git tag -a vX.Y.Z TESTED_COMMIT_SHA -m "Release vX.Y.Z"
git push origin refs/tags/vX.Y.Z
gh release create vX.Y.Z --verify-tag --notes-file RELEASE_NOTES_FILE
```

Untuk prerelease, sertakan suffix SemVer pada tag dan flag `--prerelease` saat membuat GitHub Release. Jangan memindahkan atau menimpa tag yang sudah dipublikasikan; koreksi source diterbitkan sebagai versi baru.

Verifikasi tag menunjuk commit yang dimaksud, status draft/prerelease benar, dan versi muncul di [Packagist](https://packagist.org/packages/duitku/laravel). Uji instalasi versi tersebut dari registry dalam project sementara. Jika indeks belum diperbarui, periksa integrasi Packagist menggunakan akun maintainer; jangan mengomit token registry atau menaruhnya dalam log.
