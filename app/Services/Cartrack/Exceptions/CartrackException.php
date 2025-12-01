<?php

namespace App\Services\Cartrack\Exceptions;

use RuntimeException;
use Throwable;

class CartrackException extends RuntimeException
{
    public int $status;
    public array $headers;
    public ?array $body;

    public function __construct(string $message, int $status = 0, ?array $body = null, array $headers = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);

        $this->status  = $status;
        $this->headers = $headers;
        $this->body    = $body;
    }
}
