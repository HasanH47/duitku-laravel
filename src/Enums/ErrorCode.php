<?php

namespace Duitku\Laravel\Enums;

/**
 * Duitku API / POP HTTP & API Error Codes
 *
 * @see https://docs.duitku.com/api/id/#http-code
 * @see https://docs.duitku.com/pop/id/#respon-http
 */
enum ErrorCode: string
{
    // === Success ===
    case SUCCESS = '00';

    // === API-Level Errors (statusCode in JSON Response) ===
    case PENDING = '01';
    case FAILED = '02';

    // === HTTP-Level Errors (HTTP Status Codes from Duitku) ===

    // 400 - Bad Request
    case INVALID_PAYMENT_METHOD = 'paymentMethod tidak valid';
    case DUPLICATE_ORDER_ID = 'merchantOrderId telah digunakan sebelumnya';
    case ORDER_ID_NOT_FOUND = 'merchantOrderId tidak ditemukan';
    case INVALID_EMAIL = 'Email tidak valid';
    case EMAIL_REQUIRED = 'Email wajib diisi';
    case INVALID_PHONE = 'phoneNumber tidak valid / tidak memenuhi standar';
    case CUSTOMER_VA_NAME_REQUIRED = 'customerVaName wajib diisi';
    case INVALID_SIGNATURE = 'Signature tidak valid';
    case AMOUNT_MISMATCH = 'paymentAmount tidak sesuai dengan total itemDetails';

    // 500 - Server Error
    case INTERNAL_ERROR = 'Terjadi kesalahan pada server Duitku';

    /**
     * Get a human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::SUCCESS => 'Transaksi berhasil',
            self::PENDING => 'Transaksi sedang diproses / pending',
            self::FAILED => 'Transaksi gagal / dibatalkan',
            self::INVALID_PAYMENT_METHOD => 'Metode pembayaran tidak valid',
            self::DUPLICATE_ORDER_ID => 'Order ID sudah pernah digunakan',
            self::ORDER_ID_NOT_FOUND => 'Order ID tidak ditemukan',
            self::INVALID_EMAIL => 'Format email tidak valid',
            self::EMAIL_REQUIRED => 'Email wajib diisi',
            self::INVALID_PHONE => 'Nomor telepon tidak valid',
            self::CUSTOMER_VA_NAME_REQUIRED => 'Nama VA pelanggan wajib diisi',
            self::INVALID_SIGNATURE => 'Signature/tanda tangan digital tidak cocok',
            self::AMOUNT_MISMATCH => 'Jumlah pembayaran tidak sesuai dengan total item',
            self::INTERNAL_ERROR => 'Error internal pada server Duitku',
        };
    }

    /**
     * Check if this code indicates a successful state.
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if this code indicates a pending state.
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
