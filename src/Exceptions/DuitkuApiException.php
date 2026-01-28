<?php

namespace Duitku\Laravel\Exceptions;

class DuitkuApiException extends DuitkuException
{
    protected string $duitkuCode;

    public function __construct(string $message, string $duitkuCode = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->duitkuCode = $duitkuCode;
        parent::__construct($message, $code, $previous);
    }

    public function getDuitkuCode(): string
    {
        return $this->duitkuCode;
    }
}
