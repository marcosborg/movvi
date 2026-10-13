<?php

namespace App\Services;

use App\Models\TvdeWeek;
use Carbon\Carbon;

class VehicleProfitabilityPeriodService
{
    private const MONEY_FIELDS = [
        'rental_total', 'commission_total', 'general_adjustments_total',
        'minimum_billing_difference_total', 'adjustments_total',
        'excess_kilometers_total', 'company_paid_charging_total', 'total_revenue',
    ];

    public function month(string $month, ?int $companyId): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $weeks = TvdeWeek::query()
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get();
        $vehicles = [];
        $totals = array_fill_keys(self::MONEY_FIELDS, 0.0);

        foreach ($weeks as $week) {
            $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
            $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();
            $overlapStart = $weekStart->greaterThan($start) ? $weekStart : $start;
            $overlapEnd = $weekEnd->lessThan($end) ? $weekEnd : $end;
            $weekDays = max(1, $weekStart->diffInDays($weekEnd) + 1);
            $overlapDays = max(0, $overlapStart->copy()->startOfDay()->diffInDays($overlapEnd->copy()->startOfDay()) + 1);
            $ratio = $overlapDays / $weekDays;
            $snapshot = VehicleProfitabilityService::makeWeek($week->id, $companyId);

            foreach ($snapshot['vehicles'] ?? [] as $row) {
                $id = (int) $row['id'];
                $vehicles[$id] ??= array_merge($row, array_fill_keys(self::MONEY_FIELDS, 0.0), [
                    'drivers_count' => 0, 'missing_accounts_count' => 0,
                ]);
                foreach (self::MONEY_FIELDS as $field) {
                    $value = $this->apportionCents((float) ($row[$field] ?? 0), $ratio, $weekStart, $weekEnd, $start, $end);
                    $vehicles[$id][$field] += $value;
                    $totals[$field] += $value;
                }
                $vehicles[$id]['drivers_count'] = max($vehicles[$id]['drivers_count'], (int) ($row['drivers_count'] ?? 0));
                $vehicles[$id]['missing_accounts_count'] += (int) ($row['missing_accounts_count'] ?? 0);
            }
        }

        foreach ($vehicles as &$vehicle) {
            foreach (self::MONEY_FIELDS as $field) $vehicle[$field] = round($vehicle[$field], 2);
        }
        foreach ($totals as $field => $value) $totals[$field] = round($value, 2);

        return [
            'period' => ['type' => 'month', 'month' => $month, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'estimated' => true],
            'week' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'vehicles' => array_values($vehicles),
            'totals' => $totals,
            'meta' => ['exclusions' => ['receipts' => 'Valores mensais estimados pela proporção de dias de cada semana.', 'reimbursements' => 'A soma dos meses preserva o total semanal, salvo arredondamento de cêntimos.']],
        ];
    }

    private function apportionCents(float $amount, float $ratio, Carbon $weekStart, Carbon $weekEnd, Carbon $monthStart, Carbon $monthEnd): float
    {
        $share = round($amount * $ratio, 2);

        // Give the residual cent to the month containing the last day of the
        // week. This makes the two monthly reports reconcile exactly to the
        // original weekly amount instead of independently rounding twice.
        if ($weekEnd->betweenIncluded($monthStart, $monthEnd) && $weekStart->lt($monthStart)) {
            $previousMonthEnd = $monthStart->copy()->subDay()->endOfDay();
            $previousDays = $weekStart->copy()->startOfDay()->diffInDays($previousMonthEnd->copy()->startOfDay()) + 1;
            $weekDays = $weekStart->copy()->startOfDay()->diffInDays($weekEnd->copy()->startOfDay()) + 1;
            $previousShare = round($amount * ($previousDays / max(1, $weekDays)), 2);

            return round($amount - $previousShare, 2);
        }

        return $share;
    }
}
