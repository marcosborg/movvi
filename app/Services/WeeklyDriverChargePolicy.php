<?php

namespace App\Services;

use App\Models\CombustionTransaction;

class WeeklyDriverChargePolicy
{
    public function isElectricTransaction(string $supplier, string $unit): bool
    {
        return $unit === 'kWh' || $supplier === CombustionTransaction::SUPPLIER_PRIO;
    }

    public function companyPaidCharging(int $companyId, string $weekStart, float $companyPercent, float $electricCost): float
    {
        if ($companyId !== 1 || $weekStart < '2026-09-14'
            || !in_array($companyPercent, [50.0, 55.0, 60.0], true)) {
            return 0.0;
        }

        return max(0.0, $electricCost);
    }
}
