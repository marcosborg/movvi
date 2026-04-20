<?php

namespace App\Console\Commands;

use App\Services\DriverAlertService;
use Illuminate\Console\Command;

class CheckDriverAlerts extends Command
{
    protected $signature = 'driver-alerts:check';

    protected $description = 'Check drivers with weekly income but missing receipts';

    public function handle(DriverAlertService $driverAlertService): int
    {
        $count = $driverAlertService->checkMissingReceipts();

        $this->info('Driver alerts checked. Active missing receipt alerts: ' . $count);

        return self::SUCCESS;
    }
}
