<?php

namespace App\Services;

use App\Models\CurrentAccount;
use App\Models\CarHire;
use App\Models\ExpenseReimbursement;
use App\Models\TvdeWeek;
use App\Models\VehicleExpense;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Carbon\Carbon;

class VehicleProfitabilityService
{
    /**
     * Build a profitability snapshot for a vehicle in a given TVDE week.
     * This consumes existing weekly driver report data (CurrentAccount).
     */
    public static function make(int $vehicleId, int $tvdeWeekId): array
    {
        $vehicle = VehicleItem::with(['vehicle_model'])->find($vehicleId);
        $week = TvdeWeek::find($tvdeWeekId);

        // Guard against invalid vehicle/week inputs to keep callers safe.
        if (!$vehicle || !$week) {
            return self::emptyResult($vehicleId, $tvdeWeekId);
        }

        $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();

        $usages = VehicleUsage::with('driver')
            ->where('vehicle_item_id', $vehicle->id)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->whereHas('driver')
            ->get();

        // If the vehicle has no usage in the week, return a safe empty payload.
        if ($usages->isEmpty()) {
            return self::emptyResult($vehicleId, $tvdeWeekId, $vehicle, $week);
        }

        /**
         * Driver selection rule:
         * Pick the driver with the largest total usage duration within the week.
         */
        $usageByDriver = [];

        foreach ($usages as $usage) {
            $usageStart = Carbon::parse($usage->start_date);
            $usageEnd = $usage->end_date
                ? Carbon::parse($usage->end_date)
                : $weekEnd;

            $intervalStart = $usageStart->greaterThan($weekStart) ? $usageStart : $weekStart;
            $intervalEnd = $usageEnd->lessThan($weekEnd) ? $usageEnd : $weekEnd;

            if ($intervalEnd->lessThan($intervalStart)) {
                continue;
            }

            $seconds = $intervalEnd->diffInSeconds($intervalStart) + 1;
            $driverId = $usage->driver->id;

            $usageByDriver[$driverId] = ($usageByDriver[$driverId] ?? 0) + $seconds;
        }

        // If all intervals are invalid after clipping, return a safe empty payload.
        if (empty($usageByDriver)) {
            return self::emptyResult($vehicleId, $tvdeWeekId, $vehicle, $week);
        }

        arsort($usageByDriver);
        $selectedDriverId = (int) array_key_first($usageByDriver);

        $driver = $usages
            ->first(fn ($u) => $u->driver->id === $selectedDriverId)
            ->driver;
        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
        ])->first();

        // Missing CurrentAccount should not break the dashboard/PDF.
        $results = $currentAccount ? json_decode($currentAccount->data) : (object) [];

        $totalRevenue = (float) ($results->total_net ?? 0);
        $carHire = 0.0;
        $viaVerde = (float) ($results->car_track ?? 0);
        $fuel = (float) ($results->fuel_transactions ?? 0);
        $otherDriverCosts = (float) ($results->fleet_management ?? 0);
        $taxes = (float) ($results->vat_value ?? 0);

        $vehicleExpenses = VehicleExpense::where('vehicle_item_id', $vehicle->id)
            ->whereDate('date', '>=', $week->start_date)
            ->whereDate('date', '<=', $week->end_date)
            ->sum('value');

        $reimbursements = ExpenseReimbursement::where('vehicle_item_id', $vehicle->id)
            ->whereDate('date', '>=', $week->start_date)
            ->whereDate('date', '<=', $week->end_date)
            ->sum('value');

        // Build a day-by-day car hire breakdown using contract dates only (no vehicle usage logic).
        $carHireBreakdown = self::buildCarHireBreakdownFromContracts($driver->id, $weekStart, $weekEnd);
        $carHire = (float) $carHireBreakdown['total_value'];

        $totalCosts = ($carHire + $viaVerde + $fuel + $otherDriverCosts)
            + $vehicleExpenses
            - $reimbursements;

        // Gross result is revenue minus driver and vehicle costs, plus reimbursements.
        $grossResult = $totalRevenue
            - ($carHire + $viaVerde + $fuel + $otherDriverCosts)
            - $vehicleExpenses
            + $reimbursements;

        $netResult = $grossResult - $taxes;
        $status = $netResult > 0 ? 'positive' : ($netResult < 0 ? 'negative' : 'neutral');
        $statusClass = $status === 'positive'
            ? 'label-success'
            : ($status === 'negative' ? 'label-danger' : 'label-warning');

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'model' => optional($vehicle->vehicle_model)->name,
            ],
            'week' => [
                'tvde_week_id' => $week->id,
                'start_date' => $week->start_date,
                'end_date' => $week->end_date,
            ],
            'revenues' => [
                'total_revenue' => $totalRevenue,
            ],
            'costs' => [
                'car_hire' => $carHire,
                'via_verde' => $viaVerde,
                'fuel' => $fuel,
                'other_driver_costs' => $otherDriverCosts,
            ],
            'car_hire_breakdown' => $carHireBreakdown,
            'vehicle_costs' => [
                'expenses' => (float) $vehicleExpenses,
                'reimbursements' => (float) $reimbursements,
            ],
            'totals' => [
                'total_costs' => $totalCosts,
                'gross_result' => $grossResult,
                'net_result' => $netResult,
                'final_result' => $netResult,
                'status' => $status,
                'status_class' => $statusClass,
            ],
            'meta' => [
                'driver_id' => $driver->id,
                'notes' => '',
            ],
        ];
    }

    private static function emptyResult(
        int $vehicleId,
        int $tvdeWeekId,
        ?VehicleItem $vehicle = null,
        ?TvdeWeek $week = null
    ): array {
        return [
            'vehicle' => [
                'id' => $vehicleId,
                'license_plate' => $vehicle?->license_plate,
                'model' => optional($vehicle?->vehicle_model)->name,
            ],
            'week' => [
                'tvde_week_id' => $tvdeWeekId,
                'start_date' => $week?->start_date,
                'end_date' => $week?->end_date,
            ],
            'revenues' => [
                'total_revenue' => 0.0,
            ],
            'costs' => [
                'car_hire' => 0.0,
                'via_verde' => 0.0,
                'fuel' => 0.0,
                'other_driver_costs' => 0.0,
            ],
            'car_hire_breakdown' => [
                'days' => [],
                'total_charged' => 0.0,
                'days_charged' => 0,
                'total_value' => 0.0,
            ],
            'vehicle_costs' => [
                'expenses' => 0.0,
                'reimbursements' => 0.0,
            ],
            'totals' => [
                'total_costs' => 0.0,
                'gross_result' => 0.0,
                'net_result' => 0.0,
                'final_result' => 0.0,
                'status' => 'neutral',
                'status_class' => 'label-warning',
            ],
            'meta' => [
                'driver_id' => null,
                'notes' => '',
            ],
        ];
    }

    /**
     * Build a daily car hire breakdown based exclusively on CarHire contracts.
     * Uses full-day pricing only, per contract dates, without vehicle usage logic.
     *
     * Example (comment-only):
     * 3 days on a 200/week contract + 4 days on a 300/week contract
     * => (200/7 * 3) + (300/7 * 4).
     */
    private static function buildCarHireBreakdownFromContracts(
        int $driverId,
        Carbon $weekStart,
        Carbon $weekEnd
    ): array {
        $days = [];
        $totalCharged = 0.0;
        $daysCharged = 0;
        $daysInWeek = 7;

        $contracts = CarHire::where('driver_id', $driverId)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->whereNull('deleted_at')
            ->orderBy('start_date')
            ->get();

        for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            $activeContract = null;
            foreach ($contracts as $contract) {
                $contractStart = Carbon::parse($contract->start_date)->startOfDay();
                $contractEnd = $contract->end_date
                    ? Carbon::parse($contract->end_date)->endOfDay()
                    : $weekEnd;

                if ($contractStart->gt($dayEnd) || $contractEnd->lt($dayStart)) {
                    continue;
                }

                // If multiple contracts intersect the day, the latest start_date wins.
                if (!$activeContract || $contractStart->gt(Carbon::parse($activeContract->start_date))) {
                    $activeContract = $contract;
                }
            }

            if ($activeContract) {
                $weeklyAmount = (float) $activeContract->amount;
                $dailyAmount = $daysInWeek > 0 ? ($weeklyAmount / $daysInWeek) : 0.0;
                $chargedValue = $dailyAmount;
                $totalCharged += $chargedValue;
                $daysCharged++;
                $days[] = [
                    'date' => $dayStart->toDateString(),
                    'car_hire_id' => $activeContract->id,
                    'weekly_amount' => $weeklyAmount,
                    'daily_amount' => $dailyAmount,
                    'charged_value' => $chargedValue,
                ];
            } else {
                $days[] = [
                    'date' => $dayStart->toDateString(),
                    'car_hire_id' => null,
                    'weekly_amount' => 0.0,
                    'daily_amount' => 0.0,
                    'charged_value' => 0.0,
                ];
            }
        }

        return [
            'days' => $days,
            'total_charged' => $totalCharged,
            'days_charged' => $daysCharged,
            'total_value' => $totalCharged,
        ];
    }
}
