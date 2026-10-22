# Blade component POP

Component menampilkan tombol yang membuka Duitku POP. Reference dipasang sebagai atribut data; JavaScript tidak dibentuk dengan interpolasi reference.

Publish asset penghubung terlebih dahulu:

```sh
php artisan vendor:publish --tag=duitku-assets
```

```blade
<x-duitku-pop
    :reference="$response->reference"
    button-text="Bayar Sekarang"
    class="btn btn-primary"
/>
```

| Atribut | Tipe | Wajib | Default | Arti |
| --- | --- | --- | --- | --- |
| `reference` | string | Ya | Tidak ada | Reference dari invoice yang dibuat server |
| `button-text` | string | Tidak | `Bayar Sekarang` | Teks tombol saat slot kosong |
| slot | Blade content | Tidak | Kosong | Isi tombol, mengalahkan button text |
| atribut HTML | Sesuai HTML | Tidak | `type=button` | Class, disabled, ID, dan atribut HTML lain |

SDK memuat script provider dan `vendor/duitku/duitku-pop.js` sekali per render halaman, menggunakan `defer`. Penghubung tidak membutuhkan inline handler atau evaluasi reference sebagai JavaScript. Sesuaikan CSP aplikasi agar mengizinkan host provider dan asset lokal; kemampuan script pihak provider mengikuti persyaratan provider sendiri.

## Event browser

Tambahkan listener berikut ke file JavaScript aplikasi:

```js
document.addEventListener('duitku:success', (event) => {
    // Perbarui tampilan atau buka halaman status order.
    console.log(event.detail.reference);
});
```

Event yang tersedia adalah `duitku:success`, `duitku:pending`, `duitku:error`, dan `duitku:close`. Event berasal dari tombol dan bubble ke document. Detail berisi respons callback JavaScript provider. Jika script provider belum tersedia, event error berisi pesan penjelasan.

Gunakan webhook terverifikasi untuk menetapkan order paid. Event browser dan parameter redirect dapat diubah pelanggan.

`success-path` dan `callback-path` bukan prop component. URL webhook harus diisi pada request invoice; navigasi browser dapat ditangani listener aplikasi.

Setelah memperbarui package, publish ulang asset dengan `--force` jika file lokal masih versi lama. Periksa perubahan bila aplikasi pernah menyesuaikan asset tersebut.
