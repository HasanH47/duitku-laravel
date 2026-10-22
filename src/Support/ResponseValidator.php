<?php

namespace Duitku\Laravel\Support;

use Duitku\Laravel\Exceptions\MalformedResponseException;
use Illuminate\Support\Facades\Validator;

class ResponseValidator
{
    public static function validate(array $data, array $rules, string $operation): void
    {
        if (Validator::make($data, $rules)->fails()) {
            throw new MalformedResponseException("Duitku returned an invalid {$operation} response.");
        }
    }
}
