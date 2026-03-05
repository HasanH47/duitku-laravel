<?php

use Duitku\Laravel\Data\AccountLink;
use Duitku\Laravel\Data\Address;
use Duitku\Laravel\Data\CreditCardDetail;
use Duitku\Laravel\Data\CustomerDetail;
use Duitku\Laravel\Data\ItemDetail;
use Duitku\Laravel\Data\OvoDetail;
use Duitku\Laravel\Data\PaymentFee;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\ShopeeDetail;

// =========================================================================
// ItemDetail
// =========================================================================

it('ItemDetail toArray returns correct structure', function () {
    $item = new ItemDetail(name: 'Apel', price: 50000, quantity: 2);

    expect($item->toArray())->toBe([
        'name' => 'Apel',
        'price' => 50000,
        'quantity' => 2,
    ]);
});

it('ItemDetail toPayload converts array of objects', function () {
    $items = [
        new ItemDetail('Apel', 50000, 2),
        new ItemDetail('Jeruk', 30000, 1),
    ];

    $payload = ItemDetail::toPayload($items);

    expect($payload)->toHaveCount(2)
        ->and($payload[0]['name'])->toBe('Apel')
        ->and($payload[1]['name'])->toBe('Jeruk');
});

// =========================================================================
// Address
// =========================================================================

it('Address toArray returns correct structure', function () {
    $address = new Address(
        firstName: 'John',
        lastName: 'Doe',
        address: 'Jl. Kembangan Raya',
        city: 'Jakarta',
        postalCode: '11530',
        phone: '08123456789'
    );

    expect($address->toArray())->toBe([
        'firstName' => 'John',
        'lastName' => 'Doe',
        'address' => 'Jl. Kembangan Raya',
        'city' => 'Jakarta',
        'postalCode' => '11530',
        'phone' => '08123456789',
        'countryCode' => 'ID',
    ]);
});

it('Address uses custom country code', function () {
    $address = new Address('A', 'B', 'C', 'D', '00000', '000', 'US');

    expect($address->toArray()['countryCode'])->toBe('US');
});

// =========================================================================
// CustomerDetail
// =========================================================================

it('CustomerDetail toArray without addresses', function () {
    $customer = new CustomerDetail(
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '08123456789'
    );

    $arr = $customer->toArray();

    expect($arr)->toHaveKeys(['firstName', 'lastName', 'email', 'phoneNumber'])
        ->and($arr)->not->toHaveKey('billingAddress')
        ->and($arr)->not->toHaveKey('shippingAddress');
});

it('CustomerDetail toArray with addresses', function () {
    $address = new Address('John', 'Doe', 'Jl. X', 'Jakarta', '11530', '081');
    $customer = new CustomerDetail('John', 'Doe', 'j@e.com', '081', $address, $address);

    $arr = $customer->toArray();

    expect($arr)->toHaveKeys(['billingAddress', 'shippingAddress'])
        ->and($arr['billingAddress'])->toBeArray()
        ->and($arr['billingAddress']['firstName'])->toBe('John');
});

// =========================================================================
// OvoDetail
// =========================================================================

it('OvoDetail cash factory creates correct structure', function () {
    $ovo = OvoDetail::cash(10000);

    expect($ovo->toArray())->toBe([
        'paymentDetails' => [
            ['paymentType' => 'CASH', 'amount' => 10000],
        ],
    ]);
});

// =========================================================================
// ShopeeDetail
// =========================================================================

it('ShopeeDetail toArray uses correct field names', function () {
    $shopee = new ShopeeDetail(promo_ids: 'campaign111', useCoin: true);

    expect($shopee->toArray())->toBe([
        'promo_ids' => 'campaign111',
        'useCoin' => true,
    ]);
});

// =========================================================================
// AccountLink
// =========================================================================

it('AccountLink toArray with OVO and Shopee', function () {
    $link = new AccountLink(
        credentialCode: 'ABC-123',
        ovo: OvoDetail::cash(10000),
        shopee: new ShopeeDetail()
    );

    $arr = $link->toArray();

    expect($arr)->toHaveKeys(['credentialCode', 'ovo', 'shopee'])
        ->and($arr['credentialCode'])->toBe('ABC-123')
        ->and($arr['ovo']['paymentDetails'])->toHaveCount(1);
});

it('AccountLink toArray without sub-details filters nulls', function () {
    $link = new AccountLink(credentialCode: 'ABC-123');

    $arr = $link->toArray();

    expect($arr)->toHaveKey('credentialCode')
        ->and($arr)->not->toHaveKey('ovo')
        ->and($arr)->not->toHaveKey('shopee');
});

// =========================================================================
// CreditCardDetail
// =========================================================================

it('CreditCardDetail toArray filters null values', function () {
    $cc = new CreditCardDetail(acquirer: '014');

    $arr = $cc->toArray();

    expect($arr)->toBe(['acquirer' => '014'])
        ->and($arr)->not->toHaveKey('binWhitelist')
        ->and($arr)->not->toHaveKey('saveCardToken');
});

it('CreditCardDetail toArray with all fields', function () {
    $cc = new CreditCardDetail(
        acquirer: '014',
        binWhitelist: ['014', '022', '400000'],
        saveCardToken: true
    );

    $arr = $cc->toArray();

    expect($arr['acquirer'])->toBe('014')
        ->and($arr['binWhitelist'])->toBe(['014', '022', '400000'])
        ->and($arr['saveCardToken'])->toBeTrue();
});

// =========================================================================
// PaymentFee
// =========================================================================

it('PaymentFee fromArray creates correct object', function () {
    $fee = PaymentFee::fromArray([
        'paymentMethod' => 'VA',
        'paymentName' => 'MAYBANK VA',
        'paymentImage' => 'https://images.duitku.com/hotlink-ok/VA.PNG',
        'totalFee' => '0',
    ]);

    expect($fee->paymentMethod)->toBe('VA')
        ->and($fee->paymentName)->toBe('MAYBANK VA')
        ->and($fee->totalFee)->toBe('0');
});

it('PaymentFee fromList converts array of arrays', function () {
    $list = PaymentFee::fromList([
        ['paymentMethod' => 'VA', 'paymentName' => 'MAYBANK', 'paymentImage' => '', 'totalFee' => '0'],
        ['paymentMethod' => 'BT', 'paymentName' => 'PERMATA', 'paymentImage' => '', 'totalFee' => '500'],
    ]);

    expect($list)->toHaveCount(2)
        ->and($list[0])->toBeInstanceOf(PaymentFee::class)
        ->and($list[1]->paymentMethod)->toBe('BT');
});

// =========================================================================
// PaymentRequest accepts typed DTOs
// =========================================================================

it('PaymentRequest toArray serializes ItemDetail objects', function () {
    $request = new PaymentRequest(
        amount: 100000,
        merchantOrderId: 'INV-001',
        productDetails: 'Test',
        email: 'a@b.com',
        itemDetails: [
            new ItemDetail('Item A', 50000, 1),
            new ItemDetail('Item B', 50000, 1),
        ]
    );

    $arr = $request->toArray();

    expect($arr['itemDetails'])->toHaveCount(2)
        ->and($arr['itemDetails'][0]['name'])->toBe('Item A');
});

it('PaymentRequest toArray serializes CustomerDetail DTO', function () {
    $customer = new CustomerDetail('John', 'Doe', 'j@e.com', '081');

    $request = new PaymentRequest(
        amount: 10000,
        merchantOrderId: 'INV-002',
        productDetails: 'Test',
        email: 'a@b.com',
        customerDetail: $customer
    );

    $arr = $request->toArray();

    expect($arr['customerDetail'])->toBeArray()
        ->and($arr['customerDetail']['firstName'])->toBe('John');
});

it('PaymentRequest toArray serializes AccountLink DTO', function () {
    $link = new AccountLink('CRED-123', OvoDetail::cash(10000));

    $request = new PaymentRequest(
        amount: 10000,
        merchantOrderId: 'INV-003',
        productDetails: 'Test',
        email: 'a@b.com',
        accountLink: $link
    );

    $arr = $request->toArray();

    expect($arr['accountLink'])->toBeArray()
        ->and($arr['accountLink']['credentialCode'])->toBe('CRED-123');
});

it('PaymentRequest toArray still works with raw arrays', function () {
    $request = new PaymentRequest(
        amount: 10000,
        merchantOrderId: 'INV-004',
        productDetails: 'Test',
        email: 'a@b.com',
        itemDetails: [['name' => 'X', 'price' => 10000, 'quantity' => 1]],
        customerDetail: ['firstName' => 'Raw', 'lastName' => 'Array']
    );

    $arr = $request->toArray();

    expect($arr['itemDetails'][0]['name'])->toBe('X')
        ->and($arr['customerDetail']['firstName'])->toBe('Raw');
});
