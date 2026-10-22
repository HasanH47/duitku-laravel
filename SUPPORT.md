# Bantuan integrasi

Mulai dari [dokumentasi](docs/guide/introduction.md), [panduan migrasi v3](docs/guide/upgrading.md), dan [diagnosis konfigurasi](docs/guide/testing.md). Jalankan `php artisan duitku:doctor` untuk pemeriksaan lokal; command ini tidak membuktikan kredensial diterima provider.

Gunakan [form pertanyaan](https://github.com/HasanH47/duitku-laravel/issues/new?template=question.yml) untuk bantuan SDK. Cantumkan versi PHP/Laravel/package, langkah yang dilakukan, hasil yang diharapkan, dan pesan error yang sudah disamarkan. Untuk bug yang dapat direproduksi atau ide fitur, pilih form yang sesuai pada [issue tracker](https://github.com/HasanH47/duitku-laravel/issues/new/choose).

Jangan menyertakan API key, signature, payload callback asli, nomor rekening/kartu, atau data pelanggan. Gunakan ID dan nominal sintetis dalam reproduksi. Kerentanan dilaporkan lewat [jalur privat](SECURITY.md).

Aktivasi channel, status settlement, akses merchant, dan insiden server provider ditangani oleh support Duitku. SDK ini dikelola komunitas secara independen dari Duitku; bantuan diberikan sesuai ketersediaan maintainer tanpa SLA.
