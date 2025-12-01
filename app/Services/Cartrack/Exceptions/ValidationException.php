<?php

namespace App\Services\Cartrack\Exceptions;

class ValidationException extends CartrackException
{
    public array $errors;

    public function __construct(string $message, int $status = 422, ?array $body = null, array $headers = [], array $errors = [])
    {
        parent::__construct($message, $status, $body, $headers);

        $this->errors = $errors;
    }
}
