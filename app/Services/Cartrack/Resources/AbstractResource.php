<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackFleetClient;

abstract class AbstractResource
{
    public function __construct(protected CartrackFleetClient $client)
    {
    }
}
