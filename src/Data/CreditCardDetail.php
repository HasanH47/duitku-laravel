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
     * @param  int|null  $saveCardToken  Token mode: 1 for 3DS, 2 for recurring payments (POP only)
     */
    public function __construct(
        public ?string $acquirer = null,
        public array $binWhitelist = [],
        public ?int $saveCardToken = null
    ) {
        if ($saveCardToken !== null && ! in_array($saveCardToken, [1, 2], true)) {
            throw new \InvalidArgumentException('saveCardToken must be 1 (3DS) or 2 (recurring payments).');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'acquirer' => $this->acquirer,
            'binWhitelist' => $this->binWhitelist ?: null,
            'saveCardToken' => $this->saveCardToken,
        ], fn ($value) => ! is_null($value));
    }
}
