<?php

namespace Duitku\Laravel\Enums;

/**
 * Duitku Payment Method Codes
 *
 * @see https://docs.duitku.com/api/id/#metode-pembayaran
 */
enum PaymentMethod: string
{
    // Virtual Account
    case BCA_VA = 'BC';
    case MANDIRI_VA = 'M2';
    case MAYBANK_VA = 'VA';
    case BNI_VA = 'I1';
    case BRI_VA = 'BR';
    case PERMATA_VA = 'BT';
    case CIMB_VA = 'B1';
    case DANAMON_VA = 'DM';
    case BSI_VA = 'BV';

    // Credit Card
    case CREDIT_CARD = 'VC';

    // E-Wallet
    case OVO = 'OV';
    case DANA = 'DA';
    case SHOPEEPAY = 'SP';
    case LINKAJA = 'LA';
    case SAKUKU = 'SA';

    // Retail / Convenience Store
    case INDOMARET = 'FT';
    case ALFAMART = 'A1';
    case ALFAMIDI = 'AG';

    // QRIS
    case NOBU_QRIS = 'NQ';
    case GUDANG_VOUCHER_QRIS = 'GQ';
    case SHOPEEPAY_QRIS = 'SQ';

    // Online Banking
    case JENIUS_PAY = 'JP';

    // Account Link
    case OVO_ACCOUNT_LINK = 'OL';
    case SHOPEE_ACCOUNT_LINK = 'SL';

    // Paylater
    case AKULAKU = 'LF';
    case ATOME = 'AT';
    case INDODANA = 'DN';

    // E-Commerce
    case TOKOPEDIA = 'T1';
    case BLIBLI = 'T2';
    case BUKALAPAK = 'T3';

    // Convenience
    case NUSAPAY = 'NC';
    case IRUSELL = 'IR';

    // Disbursement (for POP)
    case DISBURSEMENT = 'S1';

    /**
     * Get a human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::BCA_VA => 'BCA Virtual Account',
            self::MANDIRI_VA => 'Mandiri Virtual Account',
            self::MAYBANK_VA => 'Maybank Virtual Account',
            self::BNI_VA => 'BNI Virtual Account',
            self::BRI_VA => 'BRI Virtual Account',
            self::PERMATA_VA => 'Permata Virtual Account',
            self::CIMB_VA => 'CIMB Niaga Virtual Account',
            self::DANAMON_VA => 'Danamon Virtual Account',
            self::BSI_VA => 'BSI Virtual Account',
            self::CREDIT_CARD => 'Kartu Kredit / Debit',
            self::OVO => 'OVO',
            self::DANA => 'DANA',
            self::SHOPEEPAY => 'ShopeePay',
            self::LINKAJA => 'LinkAja',
            self::SAKUKU => 'Sakuku',
            self::INDOMARET => 'Indomaret',
            self::ALFAMART => 'Alfamart',
            self::ALFAMIDI => 'Alfamidi',
            self::NOBU_QRIS => 'NOBU QRIS',
            self::GUDANG_VOUCHER_QRIS => 'Gudang Voucher QRIS',
            self::SHOPEEPAY_QRIS => 'ShopeePay QRIS',
            self::JENIUS_PAY => 'Jenius Pay',
            self::OVO_ACCOUNT_LINK => 'OVO Account Link',
            self::SHOPEE_ACCOUNT_LINK => 'Shopee Account Link',
            self::AKULAKU => 'Akulaku Paylater',
            self::ATOME => 'Atome',
            self::INDODANA => 'Indodana Paylater',
            self::TOKOPEDIA => 'Tokopedia',
            self::BLIBLI => 'Blibli',
            self::BUKALAPAK => 'Bukalapak',
            self::NUSAPAY => 'NusaPay',
            self::IRUSELL => 'iRusell',
            self::DISBURSEMENT => 'Disbursement',
        };
    }
}
