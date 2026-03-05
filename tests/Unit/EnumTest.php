<?php

use Duitku\Laravel\Enums\ErrorCode;
use Duitku\Laravel\Enums\PaymentMethod;

// =========================================================================
// PaymentMethod Enum
// =========================================================================

it('PaymentMethod has correct code values', function () {
    expect(PaymentMethod::CREDIT_CARD->value)->toBe('VC')
        ->and(PaymentMethod::BCA_VA->value)->toBe('BC')
        ->and(PaymentMethod::ATM_BERSAMA->value)->toBe('A1')
        ->and(PaymentMethod::ARTHA_GRAHA->value)->toBe('AG')
        ->and(PaymentMethod::PEGADAIAN_ALFA_POS->value)->toBe('FT')
        ->and(PaymentMethod::INDOMARET->value)->toBe('IR')
        ->and(PaymentMethod::SHOPEEPAY_APP->value)->toBe('SA')
        ->and(PaymentMethod::SAHABAT_SAMPOERNA->value)->toBe('S1')
        ->and(PaymentMethod::TOKOPEDIA_CARD->value)->toBe('T1');
});

it('PaymentMethod label returns human-readable name', function () {
    expect(PaymentMethod::BCA_VA->label())->toBe('BCA Virtual Account')
        ->and(PaymentMethod::ATM_BERSAMA->label())->toBe('ATM Bersama')
        ->and(PaymentMethod::ARTHA_GRAHA->label())->toBe('Bank Artha Graha')
        ->and(PaymentMethod::PEGADAIAN_ALFA_POS->label())->toBe('Pegadaian / ALFA / Pos')
        ->and(PaymentMethod::INDOMARET->label())->toBe('Indomaret');
});

it('PaymentMethod isVirtualAccount identifies VA types', function () {
    expect(PaymentMethod::BCA_VA->isVirtualAccount())->toBeTrue()
        ->and(PaymentMethod::BRI_VA->isVirtualAccount())->toBeTrue()
        ->and(PaymentMethod::CREDIT_CARD->isVirtualAccount())->toBeFalse()
        ->and(PaymentMethod::OVO->isVirtualAccount())->toBeFalse();
});

it('PaymentMethod isEcommerce identifies e-commerce types', function () {
    expect(PaymentMethod::TOKOPEDIA_CARD->isEcommerce())->toBeTrue()
        ->and(PaymentMethod::TOKOPEDIA_EWALLET->isEcommerce())->toBeTrue()
        ->and(PaymentMethod::TOKOPEDIA_OTHERS->isEcommerce())->toBeTrue()
        ->and(PaymentMethod::BCA_VA->isEcommerce())->toBeFalse();
});

it('PaymentMethod requiresCustomerDetail identifies credit types', function () {
    expect(PaymentMethod::CREDIT_CARD->requiresCustomerDetail())->toBeTrue()
        ->and(PaymentMethod::INDODANA->requiresCustomerDetail())->toBeTrue()
        ->and(PaymentMethod::ATOME->requiresCustomerDetail())->toBeTrue()
        ->and(PaymentMethod::BCA_VA->requiresCustomerDetail())->toBeFalse();
});

it('PaymentMethod isQris identifies QRIS types', function () {
    expect(PaymentMethod::SHOPEEPAY_QRIS->isQris())->toBeTrue()
        ->and(PaymentMethod::NOBU_QRIS->isQris())->toBeTrue()
        ->and(PaymentMethod::OVO->isQris())->toBeFalse();
});

it('PaymentMethod covers all 33 payment methods', function () {
    expect(PaymentMethod::cases())->toHaveCount(33);
});

// =========================================================================
// ErrorCode Class
// =========================================================================

it('ErrorCode has correct status constants', function () {
    expect(ErrorCode::SUCCESS)->toBe('00')
        ->and(ErrorCode::PENDING)->toBe('01')
        ->and(ErrorCode::FAILED)->toBe('02');
});

it('ErrorCode has correct HTTP constants', function () {
    expect(ErrorCode::HTTP_SUCCESS)->toBe(200)
        ->and(ErrorCode::HTTP_BAD_REQUEST)->toBe(400)
        ->and(ErrorCode::HTTP_UNAUTHORIZED)->toBe(401)
        ->and(ErrorCode::HTTP_NOT_FOUND)->toBe(404)
        ->and(ErrorCode::HTTP_CONFLICT)->toBe(409)
        ->and(ErrorCode::HTTP_SERVER_ERROR)->toBe(500);
});

it('ErrorCode has correct error message constants', function () {
    expect(ErrorCode::WRONG_SIGNATURE)->toBe('Wrong signature')
        ->and(ErrorCode::MERCHANT_NOT_FOUND)->toBe('Merchant not found')
        ->and(ErrorCode::VA_NAME_REQUIRED)->toBe('Customer VA Name must not be empty for this payment channel');
});

it('ErrorCode describeHttp returns descriptions', function () {
    expect(ErrorCode::describeHttp(200))->toContain('berhasil')
        ->and(ErrorCode::describeHttp(401))->toContain('signature')
        ->and(ErrorCode::describeHttp(999))->toContain('Unknown');
});

it('ErrorCode describeStatus for callback', function () {
    expect(ErrorCode::describeStatus('00', 'callback'))->toContain('sukses')
        ->and(ErrorCode::describeStatus('02', 'callback'))->toContain('gagal');
});

it('ErrorCode describeStatus for redirect', function () {
    expect(ErrorCode::describeStatus('00', 'redirect'))->toContain('terbayar')
        ->and(ErrorCode::describeStatus('01', 'redirect'))->toContain('Pending')
        ->and(ErrorCode::describeStatus('02', 'redirect'))->toContain('dibatalkan');
});

it('ErrorCode helper methods work correctly', function () {
    expect(ErrorCode::isSuccess('00'))->toBeTrue()
        ->and(ErrorCode::isSuccess('01'))->toBeFalse()
        ->and(ErrorCode::isPending('01'))->toBeTrue()
        ->and(ErrorCode::isPending('00'))->toBeFalse()
        ->and(ErrorCode::isFailed('02'))->toBeTrue()
        ->and(ErrorCode::isFailed('00'))->toBeFalse();
});
