<?php

namespace Duitku\Laravel\Support;

class DisbursementCode
{
    // Response Codes
    public const SUCCESS = '00';

    public const GENERAL_ERROR = 'EE';

    public const TIMEOUT = 'TO';

    public const BANK_LINK_ERROR = 'LD'; // Link Down / ATM Bersama Issue

    public const NOT_FOUND_REMITTANCE = 'NF';

    public const INVALID_ACCOUNT = '76';

    public const WAITING_CALLBACK = '80'; // Clearing H2H

    // Numeric Error Codes
    public const OTHER_ERROR = '-100';

    public const USER_NOT_FOUND = '-120';

    public const USER_BLOCKED = '-123';

    public const INVALID_AMOUNT = '-141';

    public const TRANSACTION_DONE = '-142';

    public const BANK_NOT_SUPPORT_H2H = '-148';

    public const BANK_NOT_REGISTERED = '-149';

    public const CALLBACK_URL_MISSING = '-161';

    public const INVALID_SIGNATURE = '-191';

    public const BLACKLISTED_ACCOUNT = '-192';

    public const WRONG_EMAIL = '-213';

    public const TRANSACTION_NOT_FOUND = '-420';

    public const INSUFFICIENT_FUNDS = '-510';

    public const LIMIT_EXCEEDED = '-920';

    public const IP_NOT_WHITELISTED = '-930';

    public const TIME_EXPIRED_OR_TIMEOUT = '-951';

    public const INVALID_PARAMETER = '-952';

    public const TIMESTAMP_EXPIRED = '-960';

    // Vendor Response Codes (Specific to Vendor/Bank)
    public const VENDOR_SUCCESS = '00';

    public const VENDOR_REFER_ISSUER = '01';

    public const VENDOR_NOT_ALLOWED = '05';

    public const VENDOR_EXCEPTION = '12';

    public const VENDOR_ACCOUNT_NOT_FOUND = '14';

    public const VENDOR_INVALID_FORMAT = '30';

    public const VENDOR_INVALID_BANK_CODE = '31';

    public const VENDOR_INSUFFICIENT_FUNDS = '51';

    public const VENDOR_GENERIC_ERROR = '66';

    public const VENDOR_TIMEOUT_LATE = '68'; // Pending, don't retry immediately

    public const VENDOR_ALREADY_PAID = '88';

    public const VENDOR_INVALID_CURRENCY = '90';

    public const VENDOR_BACKEND_ERROR = '91';

    /**
     * Get description for a given code.
     */
    public static function getDescription(string $code): string
    {
        return match ($code) {
            self::SUCCESS => 'Disetujui/Sukses',
            self::GENERAL_ERROR => 'General Error',
            self::TIMEOUT => 'Response Time Out dari Jaringan ATM Bersama',
            self::BANK_LINK_ERROR => 'Masalah link antara Duitku dan jaringan ATM Bersama',
            self::NOT_FOUND_REMITTANCE => 'Transaksi belum tercatat pada gateway Remittance',
            self::INVALID_ACCOUNT => 'Nomor rekening tujuan tidak valid',
            self::WAITING_CALLBACK => 'Sedang menunggu callback (Clearing H2H)',
            self::OTHER_ERROR => 'Kesalahan lainnya',
            self::USER_NOT_FOUND => 'User ID tidak ditemukan/tidak memiliki akses',
            self::INSUFFICIENT_FUNDS => 'Dana tidak cukup',
            self::VENDOR_TIMEOUT_LATE => 'Respons diterima terlambat / Time out (Pending)',
            default => 'Unknown Code',
        };
    }
}
