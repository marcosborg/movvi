<?php

namespace App\Services;

use Illuminate\Support\Str;

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
        $limit = $this->matchesConfiguredName($normalizedName, self::LIMIT_2200_DRIVERS) ? 2200.0 : 2000.0;
        $rate = $this->matchesConfiguredName($normalizedName, self::RATE_005_DRIVERS) ? 0.05 : 0.10;
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
        $ascii = Str::ascii(mb_strtolower(trim($name), 'UTF-8'));

        return preg_replace('/\s+/', ' ', $ascii);
    }

    private function matchesConfiguredName(string $driverName, array $configuredNames): bool
    {
        $driverTokens = array_filter(explode(' ', $driverName));

        foreach ($configuredNames as $configuredName) {
            $configuredTokens = array_filter(explode(' ', $configuredName));

            if (array_diff($configuredTokens, $driverTokens) === []) {
                return true;
            }
        }

        return false;
    }
}
