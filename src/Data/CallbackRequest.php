<?php

namespace Duitku\Laravel\Data;

class CallbackRequest
{
    public function __construct(
        public string $merchantCode,
        public int $amount,
        public string $merchantOrderId,
        public ?string $productDetail,
        public ?string $additionalParam,
        public ?string $paymentMethod,
        public ?string $resultCode,
        public ?string $merchantUserId,
        public ?string $reference,
        public ?string $signature,
        public ?string $publisherOrderId = null,
        public ?string $spUserHash = null,
        public ?string $settlementDate = null,
        public ?string $issuerCode = null,
        public ?string $bankAppCode = null,
        public ?string $bankOrderId = null,
        public ?string $bankRespCode = null,
        public ?string $bankRespMsg = null,
        public ?string $cardName = null,
        public ?string $cardType = null,
        public ?string $maskedNumber = null,
        public ?string $tokenId = null,
        public ?string $transactionState = null,
        public ?string $transactionStateStatus = null,
        public ?string $merchantCustomerId = null,
        public ?string $expiryDate = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantCode: $data['merchantCode'] ?? '',
            amount: (int) ($data['amount'] ?? 0),
            merchantOrderId: $data['merchantOrderId'] ?? '',
            productDetail: $data['productDetail'] ?? null,
            additionalParam: $data['additionalParam'] ?? null,
            paymentMethod: $data['paymentCode'] ?? null, // Duitku sends paymentCode
            resultCode: $data['resultCode'] ?? null,
            merchantUserId: $data['merchantUserId'] ?? null,
            reference: $data['reference'] ?? null,
            signature: $data['signature'] ?? null,
            publisherOrderId: $data['publisherOrderId'] ?? null,
            spUserHash: $data['spUserHash'] ?? null,
            settlementDate: $data['settlementDate'] ?? null,
            issuerCode: $data['issuerCode'] ?? null,
            bankAppCode: $data['bankAppCode'] ?? null,
            bankOrderId: $data['bankOrderId'] ?? null,
            bankRespCode: $data['bankRespCode'] ?? null,
            bankRespMsg: $data['bankRespMsg'] ?? null,
            cardName: $data['cardName'] ?? null,
            cardType: $data['cardType'] ?? null,
            maskedNumber: $data['maskedNumber'] ?? null,
            tokenId: $data['tokenId'] ?? null,
            transactionState: $data['transactionState'] ?? null,
            transactionStateStatus: $data['transactionStateStatus'] ?? null,
            merchantCustomerId: $data['merchantCustomerId'] ?? null,
            expiryDate: $data['expiryDate'] ?? null
        );
    }
}
