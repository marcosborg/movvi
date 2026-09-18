<?php

namespace App\Services;

class WeeklyExcessKilometerPolicy
{
    private const LIMIT_2200_DRIVERS = [
        'celso cristiano',
        'bruno novo',
        'henrique bleasby',
        'luiz carlos chaves',
    ];

    private const RATE_005_DRIVERS = [
        'celso cristiano',
        'luiz carlos chaves',
        'marcelo capeleiro verde',
        'carlos ribeiro azevedo',
        'filipe gomes correia',
        'lucas ramos',
        'joao luiz souza',
        'vitor de barros',
        'antonio telinhos',
        'jose miguel beca',
    ];

    public function calculate(
        int $companyId,
        string $weekStart,
        float $companyPercent,
        float $weeklyKilometers,
        string $driverName = ''
    ): array {
        $normalizedName = $this->normalizeName($driverName);
        $limit = in_array($normalizedName, self::LIMIT_2200_DRIVERS, true) ? 2200.0 : 2000.0;
        $rate = in_array($normalizedName, self::RATE_005_DRIVERS, true) ? 0.05 : 0.10;
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

    private function normalizeName(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($name), 'UTF-8'));

        return preg_replace('/\s+/', ' ', $ascii ?: mb_strtolower(trim($name), 'UTF-8'));
    }
}
