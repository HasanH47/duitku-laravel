# Duitku Laravel community integration

## Tujuan

`duitku/laravel` adalah integrasi Laravel independen untuk membantu aplikasi Laravel menggunakan Duitku Payment Gateway dengan API yang mengikuti pola Laravel. Project ini bukan SDK resmi, tidak berafiliasi dengan Duitku, dan tidak mengklaim endorsement atau dukungan dari Duitku.

## Cakupan saat ini

- Package v3 menargetkan PHP 8.2+ dan Laravel 12–13.
- Integrasi yang tersedia mencakup Payment API V2, Duitku POP, callback, dan disbursement.
- API Laravel menyediakan facade/service provider, DTO, validasi/signature, exception, dan event untuk callback.
- Package juga menyediakan test lokal yang dapat memakai Laravel HTTP fake.

## Verifikasi

Pada sandbox Duitku, alur BCA Virtual Account untuk Payment API dan POP telah diuji dengan nominal Rp10.000. Pengujian mencakup pembuatan invoice, status pending hingga paid, dan penerimaan callback dengan signature HMAC yang valid. Rincian skenario dan batas verifikasi tersedia di [matriks kompatibilitas](../docs/compatibility.md) dan [catatan sandbox](../docs/reviews/2026-10-22-sandbox.md).

CI menguji matriks PHP/Laravel serta test package secara lokal. CI tidak menghubungi Duitku. Channel selain BCA VA, production, dan operasi disbursement belum diverifikasi langsung ke provider.

## Area yang ingin direview bersama Duitku

Kami ingin meminta masukan engineering/integration team mengenai:

1. Kesesuaian implementasi Payment API V2, POP, callback, dan signature dengan kontrak Duitku terbaru.
2. Skenario sandbox tambahan yang sebaiknya dijalankan sebelum menyatakan cakupan channel lebih luas.
3. Prioritas masalah integrasi Laravel yang paling sering ditemui merchant.
4. Cara terbaik menjaga package komunitas ini tetap selaras dengan dokumentasi dan SDK resmi.

Tujuan awalnya adalah technical review dan pertukaran masukan. Kami terbuka membahas bentuk kolaborasi atau endorsement jika sesuai dengan kebijakan Duitku, tanpa mengasumsikan adopsi resmi.

## Tautan

- Repository: <https://github.com/HasanH47/duitku-laravel>
- Dokumentasi: <https://duitku-laravel.hasanh.dev/>
- Matriks kompatibilitas: <https://duitku-laravel.hasanh.dev/compatibility>
- Package: <https://packagist.org/packages/duitku/laravel>
- SDK PHP resmi Duitku: <https://github.com/duitkupg/duitku-php>
