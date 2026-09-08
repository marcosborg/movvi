<?php

namespace App\Services;

use App\Models\TvdeWeek;
use Illuminate\Support\Collection;

/** Read the existing profitability totals without duplicating their accounting rules. */
class VehicleUsageRevenueService
{
    public function weeks(Collection $vehicles, string $from, string $to): array
    {
        if ($vehicles->isEmpty()) return [];
        $ids = array_fill_keys($vehicles->modelKeys(), true);
        $companies = $vehicles->pluck('company_id')->unique();
        $weeks = TvdeWeek::where('start_date', '<=', $to)->where('end_date', '>=', $from)
            ->orderBy('start_date')->get();
        $result = [];
        foreach ($weeks as $week) {
            $values = [];
            foreach ($companies as $companyId) {
                $snapshot = VehicleProfitabilityService::makeWeek($week->id, $companyId ? (int) $companyId : null);
                foreach ($snapshot['vehicles'] as $row) {
                    if (isset($ids[$row['id']])) $values[$row['id']] = [
                        'revenue' => (float) $row['total_revenue'],
                        'missing_accounts' => (int) $row['missing_accounts_count'],
                    ];
                }
            }
            $result[] = ['id' => $week->id, 'from' => $week->getRawOriginal('start_date'),
                'to' => $week->getRawOriginal('end_date'), 'vehicles' => $values];
        }
        return $result;
    }
}
