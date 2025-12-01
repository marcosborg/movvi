<?php

namespace App\Services\Cartrack;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class RateLimitInfo
{
    public ?int $retryAfterSeconds;
    public ?int $retryAtTimestamp;

    public function __construct(?int $retryAfterSeconds = null, ?int $retryAtTimestamp = null)
    {
        $this->retryAfterSeconds = $retryAfterSeconds;
        $this->retryAtTimestamp  = $retryAtTimestamp;
    }

    public function retryAfterMs(): ?int
    {
        return $this->retryAfterSeconds !== null ? $this->retryAfterSeconds * 1000 : null;
    }

    public function retryAt(): ?CarbonInterface
    {
        return $this->retryAtTimestamp ? Carbon::createFromTimestamp($this->retryAtTimestamp) : null;
    }
}
