<?php

namespace Duitku\Laravel\Data;

use Throwable;

class BatchResult
{
    public function __construct(
        public readonly string $key,
        public readonly TransactionStatus|DisbursementResponse|null $value = null,
        public readonly ?Throwable $error = null,
    ) {
        if (($value === null) === ($error === null)) {
            throw new \InvalidArgumentException('A batch result must contain either a value or an error.');
        }
    }

    public function successful(): bool
    {
        return $this->error === null;
    }

    public function get(): TransactionStatus|DisbursementResponse
    {
        if ($this->error !== null) {
            throw $this->error;
        }

        if ($this->value === null) {
            throw new \LogicException('A successful batch result must contain a value.');
        }

        return $this->value;
    }
}
