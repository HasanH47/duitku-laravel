<?php

namespace Duitku\Laravel\Data;

class DisbursementInfo
{
    public function __construct(
        public int $amountTransfer,
        public string $bankAccount,
        public string $bankCode,
        public string $purpose,
        public ?string $senderId = null,
        public ?string $senderName = null,
        public ?string $type = null, // RTGS, LLG, BIFAST, H2H
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amountTransfer' => $this->amountTransfer,
            'bankAccount' => $this->bankAccount,
            'bankCode' => $this->bankCode,
            'purpose' => $this->purpose,
            'senderId' => $this->senderId,
            'senderName' => $this->senderName,
            'type' => $this->type,
        ], fn ($value) => ! is_null($value));
    }
}
