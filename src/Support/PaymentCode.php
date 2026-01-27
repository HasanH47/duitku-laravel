<?php

namespace Duitku\Laravel\Support;

class PaymentCode
{
    // Common / Success
    public const SUCCESS = '00';

    // Callback Status Codes
    public const CALLBACK_SUCCESS = '00';

    public const CALLBACK_FAILED = '02';

    // Redirect Status Codes
    public const REDIRECT_SUCCESS = '00';

    public const REDIRECT_PROCESS = '01'; // Transaction pending

    public const REDIRECT_CANCELED = '02'; // Canceled or Failed

    /**
     * Get description for a given payment status code.
     */
    public static function getDescription(string $code, string $context = 'redirect'): string
    {
        if ($context === 'callback') {
            return match ($code) {
                self::CALLBACK_SUCCESS => 'Transaksi sukses terbayarkan',
                self::CALLBACK_FAILED => 'Transaksi gagal terbayarkan',
                default => 'Unknown Callback Code',
            };
        }

        return match ($code) {
            self::REDIRECT_SUCCESS => 'Transaksi telah terbayar',
            self::REDIRECT_PROCESS => 'Transaksi belum terbayar (Pending)',
            self::REDIRECT_CANCELED => 'Transaksi dibatalkan atau tidak terbayar',
            default => 'Unknown Redirect Code',
        };
    }
}
