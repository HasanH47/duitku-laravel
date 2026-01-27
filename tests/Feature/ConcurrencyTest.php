<?php

use Duitku\Laravel\Facades\Duitku;
use Illuminate\Support\Facades\Http;

it('can check multiple transaction statuses concurrently', function () {
    // Arrange
    Http::fake([
        '*/webapi/api/merchant/transactionStatus' => Http::sequence()
            ->push(['merchantOrderId' => 'ORD-1', 'statusCode' => '00', 'statusMessage' => 'Success', 'amount' => '10000', 'reference' => 'REF1'])
            ->push(['merchantOrderId' => 'ORD-2', 'statusCode' => '01', 'statusMessage' => 'Pending', 'amount' => '20000', 'reference' => 'REF2']),
    ]);

    // Act
    $statuses = Duitku::checkStatuses(['ORD-1', 'ORD-2']);

    // Assert
    expect($statuses)->toHaveCount(2);
    expect($statuses[0]->merchantOrderId)->toBe('ORD-1');
    expect($statuses[1]->merchantOrderId)->toBe('ORD-2');

    // Verify requests were made
    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus';
    });
});
