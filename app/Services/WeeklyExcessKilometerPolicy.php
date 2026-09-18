<?php

namespace App\Services;

class WeeklyExcessKilometerPolicy
{
    public function calculate(
        int $companyId,
        string $weekStart,
        float $companyPercent,
        float $weeklyKilometers,
        float $limit = 2000.0,
        float $rate = 0.10
    ): array {
        $eligible = $companyId === 1
            && $weekStart >= '2026-09-14'
            && $companyPercent <= 0.0;
        $excess = $eligible ? max(0.0, $weeklyKilometers - max(0.0, $limit)) : 0.0;

        return [
            'eligible' => $eligible,
            'limit' => max(0.0, $limit),
            'rate' => max(0.0, $rate),
            'kilometers' => $excess,
            'charge' => round($excess * max(0.0, $rate), 2),
        ];
    }
}
