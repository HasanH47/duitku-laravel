<?php

namespace Duitku\Laravel\Enums;

/**
 * Duitku Payment Method Codes
 *
 * @see https://docs.duitku.com/api/id/#metode-pembayaran
 */
enum PaymentMethod: string
{
    // Credit Card
    case CREDIT_CARD = 'VC';

    // Virtual Account
    case BCA_VA = 'BC';
    case MANDIRI_VA = 'M2';
    case MAYBANK_VA = 'VA';
    case BNI_VA = 'I1';
    case CIMB_VA = 'B1';
    case PERMATA_VA = 'BT';
    case ATM_BERSAMA = 'A1';
    case ARTHA_GRAHA = 'AG';
    case NEO_COMMERCE = 'NC';
    case BRI_VA = 'BR';
    case SAHABAT_SAMPOERNA = 'S1';
    case DANAMON_VA = 'DM';
    case BSI_VA = 'BV';

    // Ritel
    case PEGADAIAN_ALFA_POS = 'FT';
    case INDOMARET = 'IR';

    // E-Wallet
    case OVO = 'OV';
    case SHOPEEPAY_APP = 'SA';
    case LINKAJA_FIXED = 'LF';
    case LINKAJA_PERCENTAGE = 'LA';
    case DANA = 'DA';
    case SHOPEE_ACCOUNT_LINK = 'SL';
    case OVO_ACCOUNT_LINK = 'OL';

    // QRIS
    case SHOPEEPAY_QRIS = 'SP';
    case NOBU_QRIS = 'NQ';
    case GUDANG_VOUCHER_QRIS = 'GQ';
    case NUSAPAY_QRIS = 'SQ';

    // Kredit / Paylater
    case INDODANA = 'DN';
    case ATOME = 'AT';

    // E-Banking
    case JENIUS_PAY = 'JP';

    // E-Commerce
    case TOKOPEDIA_CARD = 'T1';
    case TOKOPEDIA_EWALLET = 'T2';
    case TOKOPEDIA_OTHERS = 'T3';

    /**
     * Get a human-readable label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card (Visa / Master Card / JCB)',
            self::BCA_VA => 'BCA Virtual Account',
            self::MANDIRI_VA => 'Mandiri Virtual Account',
            self::MAYBANK_VA => 'Maybank Virtual Account',
            self::BNI_VA => 'BNI Virtual Account',
            self::CIMB_VA => 'CIMB Niaga Virtual Account',
            self::PERMATA_VA => 'Permata Bank Virtual Account',
            self::ATM_BERSAMA => 'ATM Bersama',
            self::ARTHA_GRAHA => 'Bank Artha Graha',
            self::NEO_COMMERCE => 'Bank Neo Commerce / BNC',
            self::BRI_VA => 'BRIVA',
            self::SAHABAT_SAMPOERNA => 'Bank Sahabat Sampoerna',
            self::DANAMON_VA => 'Danamon Virtual Account',
            self::BSI_VA => 'BSI Virtual Account',
            self::PEGADAIAN_ALFA_POS => 'Pegadaian / ALFA / Pos',
            self::INDOMARET => 'Indomaret',
            self::OVO => 'OVO (Support Void)',
            self::SHOPEEPAY_APP => 'Shopee Pay Apps (Support Void)',
            self::LINKAJA_FIXED => 'LinkAja Apps (Fixed Fee)',
            self::LINKAJA_PERCENTAGE => 'LinkAja Apps (Percentage Fee)',
            self::DANA => 'DANA',
            self::SHOPEE_ACCOUNT_LINK => 'Shopee Pay Account Link',
            self::OVO_ACCOUNT_LINK => 'OVO Account Link',
            self::SHOPEEPAY_QRIS => 'Shopee Pay QRIS',
            self::NOBU_QRIS => 'Nobu QRIS',
            self::GUDANG_VOUCHER_QRIS => 'Gudang Voucher QRIS',
            self::NUSAPAY_QRIS => 'Nusapay QRIS',
            self::INDODANA => 'Indodana Paylater',
            self::ATOME => 'ATOME',
            self::JENIUS_PAY => 'Jenius Pay',
            self::TOKOPEDIA_CARD => 'Tokopedia Card Payment',
            self::TOKOPEDIA_EWALLET => 'Tokopedia E-Wallet',
            self::TOKOPEDIA_OTHERS => 'Tokopedia Others',
        };
    }

    /**
     * Check if this payment method is a Virtual Account type.
     */
    public function isVirtualAccount(): bool
    {
        return in_array($this, [
            self::BCA_VA, self::MANDIRI_VA, self::MAYBANK_VA,
            self::BNI_VA, self::CIMB_VA, self::PERMATA_VA,
            self::ATM_BERSAMA, self::ARTHA_GRAHA, self::NEO_COMMERCE,
            self::BRI_VA, self::SAHABAT_SAMPOERNA, self::DANAMON_VA, self::BSI_VA,
        ]);
    }

    /**
     * Check if this payment method is E-Commerce (requires customerVaName).
     */
    public function isEcommerce(): bool
    {
        return in_array($this, [
            self::TOKOPEDIA_CARD, self::TOKOPEDIA_EWALLET, self::TOKOPEDIA_OTHERS,
        ]);
    }

    /**
     * Check if this payment method is Credit/Paylater (requires customerDetail & itemDetails).
     */
    public function requiresCustomerDetail(): bool
    {
        return in_array($this, [
            self::CREDIT_CARD, self::INDODANA, self::ATOME,
        ]);
    }

    /**
     * Check if this payment method is QRIS.
     */
    public function isQris(): bool
    {
        return in_array($this, [
            self::SHOPEEPAY_QRIS, self::NOBU_QRIS,
            self::GUDANG_VOUCHER_QRIS, self::NUSAPAY_QRIS,
        ]);
    }
}
