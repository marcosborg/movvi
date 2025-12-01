<?php

namespace App\Services\Cartrack\Exceptions;

use App\Services\Cartrack\RateLimitInfo;

class RateLimitException extends CartrackException
{
    public ?RateLimitInfo $rateLimit;

    public function __construct(string $message, int $status = 429, ?array $body = null, array $headers = [], ?RateLimitInfo $rateLimit = null)
    {
        parent::__construct($message, $status, $body, $headers);

        $this->rateLimit = $rateLimit;
    }
}
