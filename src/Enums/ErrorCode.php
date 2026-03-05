<?php

namespace Duitku\Laravel\Enums;

/**
 * Duitku API & POP Error Codes
 *
 * Combines HTTP response codes and API response codes from both:
 * - https://docs.duitku.com/api/id/#http-code
 * - https://docs.duitku.com/pop/id/#errors
 */
class ErrorCode
{
    // =========================================================================
    // HTTP Response Codes (API)
    // @see https://docs.duitku.com/api/id/#http-code
    // =========================================================================

    /** @var int 200 - Proses berhasil */
    public const HTTP_SUCCESS = 200;

    /** @var int 400 - Bad Request */
    public const HTTP_BAD_REQUEST = 400;

    /** @var int 401 - Unauthorized / Wrong Signature */
    public const HTTP_UNAUTHORIZED = 401;

    /** @var int 404 - Not Found / Merchant not found */
    public const HTTP_NOT_FOUND = 404;

    /** @var int 409 - Conflict / Transaction still in progress */
    public const HTTP_CONFLICT = 409;

    /** @var int 500 - Internal Server Error */
    public const HTTP_SERVER_ERROR = 500;

    // =========================================================================
    // HTTP 400 Bad Request - Specific Messages (API)
    // @see https://docs.duitku.com/api/id/#http-code
    // =========================================================================

    public const MIN_PAYMENT = 'Minimum Payment 10000 IDR';

    public const MAX_PAYMENT = 'Maximum Payment exceeded';

    public const PAYMENT_METHOD_REQUIRED = 'paymentMethod is mandatory';

    public const ORDER_ID_REQUIRED = 'merchantOrderId is mandatory';

    public const ORDER_ID_TOO_LONG = "length of merchantOrderId can't > 50";

    public const INVALID_EMAIL = 'Invalid Email Address';

    public const EMAIL_TOO_LONG = "length of email can't > 50";

    public const PHONE_TOO_LONG = "length of phoneNumber can't > 50";

    public const VA_NAME_REQUIRED = 'Customer VA Name must not be empty for this payment channel';

    // =========================================================================
    // HTTP 401 Unauthorized (API)
    // =========================================================================

    public const WRONG_SIGNATURE = 'Wrong signature';

    // =========================================================================
    // HTTP 404 Not Found (API)
    // =========================================================================

    public const MERCHANT_NOT_FOUND = 'Merchant not found';

    public const PAYMENT_CHANNEL_NOT_AVAILABLE = 'Payment channel not available';

    // =========================================================================
    // HTTP 409 Conflict (API)
    // =========================================================================

    public const AMOUNT_MISMATCH = 'Payment amount must be equal to all item price';

    // =========================================================================
    // POP-Specific HTTP Errors
    // @see https://docs.duitku.com/pop/id/#errors
    // =========================================================================

    public const POP_AMOUNT_DIFFERENT = 'Amount is different please try again later.';

    public const POP_SAVE_CARD_NOT_AVAILABLE = 'SaveCardToken is not available.';

    public const POP_TRANSACTION_IN_PROGRESS = 'The transaction is still in progress.';

    // =========================================================================
    // API Response Codes (Callback)
    // @see https://docs.duitku.com/api/id/#callback
    // @see https://docs.duitku.com/pop/id/#respon-api
    // =========================================================================

    /** @var string Transaksi sukses terbayarkan */
    public const CALLBACK_SUCCESS = '00';

    /** @var string Transaksi gagal terbayarkan */
    public const CALLBACK_FAILED = '02';

    // =========================================================================
    // API Response Codes (Redirect)
    // @see https://docs.duitku.com/pop/id/#respon-api
    // =========================================================================

    /** @var string Transaksi telah terbayar */
    public const REDIRECT_SUCCESS = '00';

    /** @var string Transaksi belum terbayar (Pending) */
    public const REDIRECT_PROCESS = '01';

    /** @var string Transaksi dibatalkan atau tidak terbayar */
    public const REDIRECT_CANCELED = '02';

    // =========================================================================
    // General API Status Codes
    // =========================================================================

    /** @var string Berhasil */
    public const SUCCESS = '00';

    /** @var string Pending / Dalam proses */
    public const PENDING = '01';

    /** @var string Gagal / Dibatalkan */
    public const FAILED = '02';

    /**
     * Get description for HTTP error messages.
     */
    public static function describeHttp(int $code): string
    {
        return match ($code) {
            self::HTTP_SUCCESS => 'Proses anda berhasil.',
            self::HTTP_BAD_REQUEST => 'Ada kesalahan pada saat mengirimkan permohonan pada API.',
            self::HTTP_UNAUTHORIZED => 'Akses ditolak, cek signature anda.',
            self::HTTP_NOT_FOUND => 'Halaman atau API yang di-request tidak ditemukan.',
            self::HTTP_CONFLICT => 'Transaksi masih dalam proses atau amount berbeda.',
            self::HTTP_SERVER_ERROR => 'Error internal pada server Duitku.',
            default => 'Unknown HTTP Error Code',
        };
    }

    /**
     * Get description for API status codes (callback/redirect).
     */
    public static function describeStatus(string $code, string $context = 'callback'): string
    {
        if ($context === 'redirect') {
            return match ($code) {
                self::REDIRECT_SUCCESS => 'Transaksi telah terbayar.',
                self::REDIRECT_PROCESS => 'Transaksi belum terbayar (Pending).',
                self::REDIRECT_CANCELED => 'Transaksi dibatalkan atau tidak terbayar.',
                default => 'Unknown status code',
            };
        }

        return match ($code) {
            self::CALLBACK_SUCCESS => 'Transaksi telah sukses terbayarkan.',
            self::CALLBACK_FAILED => 'Transaksi gagal terbayarkan.',
            default => 'Unknown callback code',
        };
    }

    /**
     * Check if the given status code indicates success.
     */
    public static function isSuccess(string $code): bool
    {
        return $code === self::SUCCESS;
    }

    /**
     * Check if the given status code indicates pending.
     */
    public static function isPending(string $code): bool
    {
        return $code === self::PENDING;
    }

    /**
     * Check if the given status code indicates failure.
     */
    public static function isFailed(string $code): bool
    {
        return $code === self::FAILED;
    }
}
