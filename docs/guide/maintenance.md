# Memantau perubahan Duitku

Repository memantau dokumentasi dan SDK resmi Duitku setiap Senin pukul 09.15 WIB melalui workflow **Duitku Upstream Watch**. Dependabot tetap menangani pembaruan dependency Composer, dokumentasi, dan GitHub Actions.

Monitor membandingkan tujuh sumber dengan baseline yang direview: overview payment gateway, Payment API V2, POP, disbursement, direct credit card, SNAP, dan commit terbaru SDK PHP resmi. Daftar URL serta fingerprint tersimpan di `tools/upstream-baseline.json`.

Jika ada perubahan, workflow membuat issue **Duitku upstream changes**. Selama issue tersebut terbuka, pemeriksaan berikutnya memperbarui isi issue yang sama. Jika tidak ada perubahan, workflow tidak membuat issue. Laporan juga tersedia pada ringkasan run dan artifact selama 30 hari.

## Menindaklanjuti perubahan

1. Buka sumber yang ditandai dalam issue dan bandingkan dengan perilaku package. Periksa formula signature, field, endpoint, metode pembayaran, serta changelog atau commit SDK yang terkait.
2. Tentukan dampaknya. Perubahan dokumentasi bisa bersifat editorial; dukungan kartu/SNAP tetap memerlukan keputusan cakupan tersendiri.
3. Untuk perubahan perilaku SDK, tambahkan regression test dan verifikasi alur sandbox yang terpengaruh. Jalankan pemeriksaan project sesuai panduan kontribusi.
4. Setelah perubahan diperbaiki atau dinyatakan tidak memerlukan perubahan kode, perbarui baseline dalam PR:

```sh
php tools/monitor-upstream.php --refresh-baseline
php tools/monitor-upstream.php
```

Jelaskan hasil review dalam PR, termasuk alasan jika perubahan upstream diabaikan. Commit baseline bersama perbaikan yang relevan, lalu tutup issue pelacakan setelah perubahan masuk ke `main`. Workflow tidak memperbarui baseline atau kode pembayaran secara otomatis.

## Menjalankan pemeriksaan

Maintainer dapat memilih **Actions → Duitku Upstream Watch → Run workflow** tanpa menyiapkan secret tambahan. Workflow menggunakan `GITHUB_TOKEN` bawaan dengan izin baca repository dan tulis issue.

Untuk pemeriksaan lokal, pasang dependency Composer dan aktifkan ekstensi PHP DOM:

```sh
php tools/monitor-upstream.php
```

Hasil ditulis ke `build/upstream/report.md`, `report.json`, dan `snapshot.json`. Folder ini diabaikan Git. Command pemeriksaan hanya menghasilkan laporan; notifikasi GitHub merupakan langkah terpisah melalui `tools/notify-upstream.sh` yang membutuhkan Bash, `jq`, `gh`, `GH_TOKEN`, dan `GITHUB_REPOSITORY`.

## Batas pemantauan

Fingerprint halaman mencakup teks dokumentasi dan link, dengan navigasi, script, variasi whitespace, serta link proteksi email Cloudflare yang berubah setiap respons diabaikan. Monitor menyimpan hash, bukan salinan halaman. SDK PHP dipantau melalui commit terbaru pada default branch resminya.

Snapshot yang sama tidak membuktikan kompatibilitas API saat runtime: provider bisa mengubah perilaku server sebelum memperbarui dokumentasi. Kegagalan unduh, redirect, atau layout halaman yang tidak dikenali membuat workflow gagal, sehingga perlu diperiksa melalui notifikasi Actions. Kondisi itu tidak dilaporkan sebagai “tidak ada perubahan”.

Jadwal GitHub dapat terlambat. Untuk repository publik, GitHub juga menonaktifkan workflow terjadwal setelah 60 hari tanpa aktivitas repository; aktifkan kembali melalui Actions jika diperlukan. Lihat [aturan schedule GitHub](https://docs.github.com/en/actions/reference/workflows-and-actions/events-that-trigger-workflows#schedule).
