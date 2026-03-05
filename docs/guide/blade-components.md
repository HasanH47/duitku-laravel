# Blade Components

SDK ini menyertakan **Blade Component** siap pakai untuk mempermudah integrasi Duitku POP di frontend. Dengan komponen ini, kamu tidak perlu menulis JavaScript manual.

---

## 🤔 Apa Itu Blade Component?

Blade Component adalah fitur Laravel yang memungkinkan kamu membuat "tag HTML custom". Misalnya, alih-alih menulis 20 baris HTML+JS, kamu cukup tulis 1 baris:

```blade
<x-duitku-pop :reference="$reference" />
```

> **Analogi:** Blade Component seperti "bungkus kado". Isinya (HTML, CSS, JS) sudah diurus — kamu tinggal pakai.

---

## Komponen `<x-duitku-pop />`

Komponen ini merender **tombol pembayaran** yang otomatis:

- ✅ Memuat script JavaScript Duitku POP
- ✅ Menginisialisasi popup pembayaran
- ✅ Menghandle callback dari popup (success, pending, error, close)

### Penggunaan Paling Sederhana

```blade
<x-duitku-pop :reference="$reference" />
```

Ini akan menampilkan tombol "Pay Now" yang saat diklik akan memunculkan popup pembayaran Duitku.

### Customisasi Teks dan Style

```blade
<x-duitku-pop
    :reference="$reference"
    button-text="Bayar Sekarang 💳"
    class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600"
/>
```

### Dengan Redirect Setelah Bayar

```blade
<x-duitku-pop
    :reference="$reference"
    button-text="Proses Pembayaran"
    success-path="/order/success"
    callback-path="/api/duitku/callback"
    class="btn btn-primary btn-lg"
/>
```

---

## 📋 Daftar Atribut Lengkap

| Atribut         | Tipe   | Wajib? | Default     | Penjelasan                                                                                     |
| --------------- | ------ | ------ | ----------- | ---------------------------------------------------------------------------------------------- |
| `reference`     | string | ✅ Ya  | —           | Token reference dari `Duitku::pop()->createTransaction()`. Tanpa ini, popup tidak akan muncul. |
| `button-text`   | string | ❌     | `'Pay Now'` | Teks yang ditampilkan di tombol. Bisa diganti ke bahasa apa saja.                              |
| `success-path`  | string | ❌     | `'/'`       | URL tujuan redirect setelah pembayaran **berhasil**.                                           |
| `callback-path` | string | ❌     | `'/'`       | URL callback (webhook) yang dikirim setelah pembayaran selesai.                                |
| `class`         | string | ❌     | `''`        | CSS class tambahan untuk styling tombol sesuai selera kamu.                                    |

### Penjelasan Detail

#### `reference` (Wajib)

Token unik yang didapat dari backend. **Tanpa ini, popup tidak bisa muncul.**

```php
// Di Controller
$response = Duitku::pop()->createTransaction($request);
return view('checkout', ['reference' => $response->reference]);
```

```blade
{{-- Di Blade --}}
<x-duitku-pop :reference="$reference" />
```

> **Kenapa pakai `:reference` (dengan titik dua)?** Titik dua (`:`) memberitahu Blade bahwa ini adalah variabel PHP, bukan string biasa. Tanpa titik dua, Blade akan menganggapnya sebagai teks `"$reference"`.

#### `success-path`

Setelah pelanggan selesai bayar di popup, browser akan di-redirect ke URL ini. Cocok untuk halaman "Terima kasih" atau "Order berhasil".

#### `callback-path`

URL webhook yang dipanggil oleh Duitku setelah pembayaran dikonfirmasi. Biasanya sama dengan route callback kamu.

---

## 📄 Contoh Halaman Checkout Lengkap

Berikut contoh halaman checkout sederhana menggunakan Blade Component:

```blade
{{-- resources/views/checkout.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white rounded-lg shadow p-6">
        {{-- Detail Order --}}
        <h2 class="text-xl font-bold mb-4">Ringkasan Pesanan</h2>

        <div class="border-b pb-4 mb-4">
            <p>{{ $productName }}</p>
            <p class="text-2xl font-bold text-green-600">
                Rp {{ number_format($amount) }}
            </p>
        </div>

        {{-- Tombol Bayar --}}
        <x-duitku-pop
            :reference="$reference"
            button-text="Bayar Sekarang 💳"
            success-path="/order/{{ $orderId }}/success"
            class="w-full bg-blue-600 text-white py-3 rounded-lg
                   hover:bg-blue-700 transition font-semibold text-lg"
        />

        <p class="text-gray-500 text-sm mt-3 text-center">
            Pembayaran diproses aman oleh Duitku
        </p>
    </div>
</div>
@endsection
```

---

## 🎨 Tips Styling

### Bootstrap

```blade
<x-duitku-pop :reference="$ref" class="btn btn-primary btn-lg w-100" />
```

### Tailwind CSS

```blade
<x-duitku-pop :reference="$ref" class="bg-indigo-600 text-white px-8 py-3 rounded-full" />
```

### Custom CSS

```blade
<x-duitku-pop :reference="$ref" class="pay-button" />

<style>
.pay-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    cursor: pointer;
    transition: transform 0.2s;
}
.pay-button:hover {
    transform: scale(1.05);
}
</style>
```

---

Selamat! 🏆 Kamu sudah menguasai seluruh fitur **Duitku Laravel SDK**! 🚀

Jika ada pertanyaan, buka Issue di [GitHub repository](https://github.com/HasanH47/duitku-laravel).
