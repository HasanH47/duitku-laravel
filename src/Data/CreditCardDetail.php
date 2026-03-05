<?php

namespace Duitku\Laravel\Data;

/**
 * Credit Card Detail for credit card transactions.
 *
 * @see https://docs.duitku.com/api/id/#credit-card-detail
 */
class CreditCardDetail
{
    /**
     * @param  string|null  $acquirer  Bank acquirer code (e.g., '014' for BCA, '022' for CIMB)
     * @param  string[]  $binWhitelist  Whitelist of BIN numbers allowed
     * @param  bool|null  $saveCardToken  Whether to save card token for tokenization (POP only)
     */
    public function __construct(
        public ?string $acquirer = null,
        public array $binWhitelist = [],
        public ?bool $saveCardToken = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'acquirer' => $this->acquirer,
            'binWhitelist' => $this->binWhitelist ?: null,
            'saveCardToken' => $this->saveCardToken,
        ], fn ($value) => ! is_null($value));
    }
}
