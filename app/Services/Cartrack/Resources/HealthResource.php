<?php

namespace App\Services\Cartrack\Resources;

use App\Services\Cartrack\CartrackResponse;

class HealthResource extends AbstractResource
{
    public function check(): CartrackResponse
    {
        return $this->client->get('/health');
    }
}
