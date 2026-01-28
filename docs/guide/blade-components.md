# Blade Components

SDK ini menyertakan komponen Blade untuk mempercepat pengembangan UI, khususnya untuk integrasi Duitku POP.

## Komponen `<x-duitku-pop />`

Komponen ini merender tombol pembayaran yang sudah otomatis memuat script Javascript Duitku POP dan menghandle inisialisasi popup.

### Penggunaan Dasar

```blade
<x-duitku-pop
    reference="{{ $reference }}"
    button-text="Bayar Sekarang"
    class="btn btn-primary"
/>
```

### Opsi Lanjutan

Anda bisa menyesuaikan behavior setelah pembayaran selesai melalui atribut:

```blade
<x-duitku-pop
    :reference="$reference"
    button-text="Pilih Metode Pembayaran"
    success-path="/payment/success"
    callback-path="/api/duitku/callback"
/>
```

### Atribut yang Tersedia

| Atribut         | Deskripsi                           | Default      |
| --------------- | ----------------------------------- | ------------ |
| `reference`     | Token reference dari `createPop()`. | **Required** |
| `button-text`   | Teks yang muncul di tombol.         | `Pay Now`    |
| `success-path`  | URL redirect setelah sukses.        | `/`          |
| `callback-path` | URL redirect callback (browser).    | `/`          |
| `class`         | Class CSS tambahan untuk button.    | `""`         |

---

Sekarang Anda sudah menguasai seluruh fitur **Duitku Laravel**! 🏆🚀
