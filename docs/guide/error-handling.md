# Error Handling

Handling error di SDK ini menggunakan pendekatan Catchable Exceptions, sehingga kode Anda lebih bersih daripada menggunakan banyak if-else untuk mengecek status code.

## Menggunakan `throwIfFailed()`

Setiap response dari SDK memiliki method `throwIfFailed()` yang akan melemparkan exception jika Duitku mengembalikan kode selain sukses.

```php
use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Exceptions\InsufficientFundsException;

try {
    Duitku::disbursement()
        ->transfer()
        ->inquiry($info)
        ->throwIfFailed(); // Akan melempar exception jika gagal
} catch (InsufficientFundsException $e) {
    // Tangani saldo tidak cukup
    return back()->with('error', 'Pencairan dana gagal: Saldo merchant tidak cukup.');
} catch (DuitkuApiException $e) {
    // Tangani error API umum (kode salah, rekening tidak ditemukan, dll)
    return back()->with('error', $e->getMessage());
}
```

## Daftar Custom Exceptions

| Exception                    | Deskripsi                                                    |
| ---------------------------- | ------------------------------------------------------------ |
| `DuitkuException`            | Base exception untuk semua error SDK.                        |
| `DuitkuApiException`         | Terjadi saat API Duitku mengembalikan error (misal: `-500`). |
| `InsufficientFundsException` | Spesifik untuk error saldo tidak cukup (Disbursement).       |
| `InvalidSignatureException`  | Terjadi saat validasi signature callback gagal.              |

## Mendapatkan Kode Error Duitku

Anda tetap bisa mendapatkan kode error asli dari Duitku melalui method `getDuitkuCode()` pada `DuitkuApiException`.

```php
catch (DuitkuApiException $e) {
    $kodeAsli = $e->getDuitkuCode(); // Misal: '01' atau '-100'
}
```

---

Terakhir, pelajari tentang [Blade Components](./blade-components).
