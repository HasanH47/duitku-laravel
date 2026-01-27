<?php

namespace Duitku\Laravel\Data;

class CashOutInfo
{
    public function __construct(
        public int $amountTransfer,
        public string $bankCode, // 2010 (Indomaret) or 2011 (Pos)
        public string $accountName,
        public string $accountIdentity, // KTP
        public string $phoneNumber,
        public ?string $accountAddress = null,
        public ?string $custRefNumber = null,
        public ?string $purpose = null,
        public ?string $callbackUrl = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amountTransfer' => $this->amountTransfer,
            'bankCode' => $this->bankCode,
            'accountName' => $this->accountName,
            'accountIdentity' => $this->accountIdentity,
            'phoneNumber' => $this->phoneNumber,
            'accountAddress' => $this->accountAddress,
            'custRefNumber' => $this->custRefNumber,
            'purpose' => $this->purpose,
            'callbackUrl' => $this->callbackUrl,
        ], fn ($value) => ! is_null($value));
    }
}
