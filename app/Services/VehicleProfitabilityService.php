<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\CurrentAccount;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class VehicleProfitabilityService
{
    private static ?bool $hasVehicleProfitabilityAdjustmentsColumn = null;

    /**
     * Build a revenue snapshot for a vehicle in a given TVDE week.
     * Revenue is based on what is saved/validated in Company Reports (CurrentAccount->data).
     *
     * We group the validated driver earnings for the week by the drivers that used the vehicle
     * (VehicleUsage overlap) and sum:
     * - `car_hire` (weekly rental charged to the driver) => "Aluguer"
     * - `percent_value` (company commission charged to the driver) => "Percentagem"
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

        $driverUsageSeconds = [];
        $driverNames = [];

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

            $driverUsageSeconds[$driverId] = ($driverUsageSeconds[$driverId] ?? 0) + $seconds;
            $driverNames[$driverId] = $usage->driver->name ?? null;
        }

        // If all intervals are invalid after clipping, return a safe empty payload.
        if (empty($driverUsageSeconds)) {
            return self::emptyResult($vehicleId, $tvdeWeekId, $vehicle, $week);
        }

        arsort($driverUsageSeconds);
        $driverIds = array_map('intval', array_keys($driverUsageSeconds));

        $accounts = CurrentAccount::with('driver')
            ->where('tvde_week_id', $week->id)
            ->whereIn('driver_id', $driverIds)
            ->get()
            ->keyBy('driver_id');

        $drivers = [];
        $totalRental = 0.0;
        $totalCommission = 0.0;
        $totalGeneralAdjustments = 0.0;
        $totalMinimumBillingDifference = 0.0;
        $missingAccounts = [];

        foreach ($driverIds as $driverId) {
            $usageSeconds = (int) ($driverUsageSeconds[$driverId] ?? 0);
            $account = $accounts->get($driverId);
            $fallbackAdjustments = self::calculateDriverAdjustmentsForWeek(
                $driverId,
                $week,
                (int) $vehicle->company_id,
                true
            );

            if (!$account) {
                $missingAccounts[] = $driverId;
                $drivers[] = [
                    'id' => $driverId,
                    'name' => $driverNames[$driverId] ?? null,
                    'usage_seconds' => $usageSeconds,
                    'type' => 'unknown',
                    'rental' => 0.0,
                    'commission' => 0.0,
                    'adjustments' => $fallbackAdjustments,
                    'general_adjustments' => $fallbackAdjustments,
                    'minimum_billing_difference' => 0.0,
                    'has_current_account' => false,
                ];
                continue;
            }

            $earnings = json_decode($account->data, true) ?? [];
            $rental = (float) ($earnings['car_hire'] ?? 0);
            $commission = (float) ($earnings['percent_value'] ?? 0);
            $generalAdjustments = array_key_exists('general_adjustments', $earnings)
                ? (float) ($earnings['general_adjustments'] ?? 0)
                : (float) ($earnings['adjustments'] ?? 0);
            $minimumBillingDifference = 0 - (float) ($earnings['diferenca_faturacao_minima'] ?? 0);
            $adjustments = $generalAdjustments + $minimumBillingDifference;

            $type = 'unknown';
            if ($rental > 0 && $commission <= 0) {
                $type = 'rental';
            } elseif ($commission > 0 && $rental <= 0) {
                $type = 'percentage';
            } elseif ($commission > 0 && $rental > 0) {
                $type = 'mixed';
            } elseif ($commission <= 0 && $rental <= 0) {
                $type = 'none';
            }

            $totalRental += $rental;
            $totalCommission += $commission;
            $totalGeneralAdjustments += $generalAdjustments;
            $totalMinimumBillingDifference += $minimumBillingDifference;

            $drivers[] = [
                'id' => (int) $account->driver->id,
                'name' => $account->driver->name ?? null,
                'usage_seconds' => $usageSeconds,
                'type' => $type,
                'rental' => $rental,
                'commission' => $commission,
                'adjustments' => $adjustments,
                'general_adjustments' => $generalAdjustments,
                'minimum_billing_difference' => $minimumBillingDifference,
                'has_current_account' => true,
            ];
        }

        $totalAdjustments = $totalGeneralAdjustments + $totalMinimumBillingDifference;
        $totalRevenue = $totalRental + $totalCommission + $totalAdjustments;

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
                'rental_total' => $totalRental,
                'commission_total' => $totalCommission,
                'general_adjustments_total' => $totalGeneralAdjustments,
                'minimum_billing_difference_total' => $totalMinimumBillingDifference,
                'adjustments_total' => $totalAdjustments,
                'total_revenue' => $totalRevenue,
            ],
            'meta' => [
                'drivers' => $drivers,
                'missing_current_accounts' => $missingAccounts,
            ],
        ];
    }

    /**
     * Build a week snapshot for all vehicles.
     *
     * Source of truth:
     * - Vehicle → drivers via VehicleUsage overlap for the week (assignment by plate)
     * - Driver revenues via validated Company Reports (CurrentAccount->data)
     */
    public static function makeWeek(int $tvdeWeekId, ?int $companyId = null): array
    {
        $week = TvdeWeek::find($tvdeWeekId);
        if (!$week) {
            return self::emptyWeekResult($tvdeWeekId);
        }

        $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();

        $vehicles = VehicleItem::with('vehicle_model')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where(function ($query) {
                $query->where('suspended', false)->orWhereNull('suspended');
            })
            ->where(function ($query) use ($weekEnd) {
                $query->whereNull('acquisition_date')
                    ->orWhere('acquisition_date', '<=', $weekEnd->toDateString());
            })
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('sale_date')
                    ->orWhere('sale_date', '>=', $weekStart->toDateString());
            })
            ->orderBy('license_plate')
            ->get();

        if ($vehicles->isEmpty()) {
            return self::emptyWeekResult($tvdeWeekId, $week);
        }

        $usages = VehicleUsage::query()
            ->whereNotNull('vehicle_item_id')
            ->whereNotNull('driver_id')
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->get(['vehicle_item_id', 'driver_id', 'start_date', 'end_date']);

        $usageSeconds = [];
        $driverIds = [];

        foreach ($usages as $usage) {
            $usageStart = Carbon::parse($usage->start_date);
            $usageEnd = $usage->end_date ? Carbon::parse($usage->end_date) : $weekEnd;

            $intervalStart = $usageStart->greaterThan($weekStart) ? $usageStart : $weekStart;
            $intervalEnd = $usageEnd->lessThan($weekEnd) ? $usageEnd : $weekEnd;

            if ($intervalEnd->lessThan($intervalStart)) {
                continue;
            }

            $vehicleId = (int) $usage->vehicle_item_id;
            $driverId = (int) $usage->driver_id;
            $seconds = $intervalEnd->diffInSeconds($intervalStart) + 1;

            $usageSeconds[$vehicleId] ??= [];
            $usageSeconds[$vehicleId][$driverId] = ($usageSeconds[$vehicleId][$driverId] ?? 0) + $seconds;
            $driverIds[$driverId] = true;
        }

        $driverIds = array_keys($driverIds);

        $accounts = CurrentAccount::query()
            ->where('tvde_week_id', $week->id)
            ->when(!empty($driverIds), fn ($q) => $q->whereIn('driver_id', $driverIds))
            ->get(['driver_id', 'data'])
            ->keyBy('driver_id');

        $decoded = [];
        foreach ($accounts as $driverId => $account) {
            $decoded[(int) $driverId] = json_decode($account->data, true) ?? [];
        }

        $rows = [];
        $totRental = 0.0;
        $totCommission = 0.0;
        $totGeneralAdjustments = 0.0;
        $totMinimumBillingDifference = 0.0;

        foreach ($vehicles as $vehicle) {
            $vehicleId = (int) $vehicle->id;
            $drivers = $usageSeconds[$vehicleId] ?? [];

            $rentalTotal = 0.0;
            $commissionTotal = 0.0;
            $generalAdjustmentsTotal = 0.0;
            $minimumBillingDifferenceTotal = 0.0;
            $missingAccountsCount = 0;

            foreach ($drivers as $driverId => $seconds) {
                $earnings = $decoded[(int) $driverId] ?? null;
                $fallbackAdjustments = self::calculateDriverAdjustmentsForWeek(
                    (int) $driverId,
                    $week,
                    (int) $vehicle->company_id,
                    true
                );

                if ($earnings === null) {
                    $generalAdjustmentsTotal += $fallbackAdjustments;
                    $missingAccountsCount++;
                    continue;
                }

                $rentalTotal += (float) ($earnings['car_hire'] ?? 0);
                $commissionTotal += (float) ($earnings['percent_value'] ?? 0);
                $generalAdjustmentsTotal += array_key_exists('general_adjustments', $earnings)
                    ? (float) ($earnings['general_adjustments'] ?? 0)
                    : (float) ($earnings['adjustments'] ?? 0);
                $minimumBillingDifferenceTotal += 0 - (float) ($earnings['diferenca_faturacao_minima'] ?? 0);
            }

            $adjustmentsTotal = $generalAdjustmentsTotal + $minimumBillingDifferenceTotal;
            $totRental += $rentalTotal;
            $totCommission += $commissionTotal;
            $totGeneralAdjustments += $generalAdjustmentsTotal;
            $totMinimumBillingDifference += $minimumBillingDifferenceTotal;

            $rows[] = [
                'id' => $vehicleId,
                'license_plate' => $vehicle->license_plate,
                'model' => optional($vehicle->vehicle_model)->name,
                'rental_total' => $rentalTotal,
                'commission_total' => $commissionTotal,
                'general_adjustments_total' => $generalAdjustmentsTotal,
                'minimum_billing_difference_total' => $minimumBillingDifferenceTotal,
                'adjustments_total' => $adjustmentsTotal,
                'total_revenue' => $rentalTotal + $commissionTotal + $adjustmentsTotal,
                'drivers_count' => count($drivers),
                'missing_accounts_count' => $missingAccountsCount,
            ];
        }

        $totAdjustments = $totGeneralAdjustments + $totMinimumBillingDifference;

        return [
            'week' => [
                'tvde_week_id' => $week->id,
                'start_date' => $week->start_date,
                'end_date' => $week->end_date,
            ],
            'vehicles' => $rows,
            'totals' => [
                'rental_total' => $totRental,
                'commission_total' => $totCommission,
                'general_adjustments_total' => $totGeneralAdjustments,
                'minimum_billing_difference_total' => $totMinimumBillingDifference,
                'adjustments_total' => $totAdjustments,
                'total_revenue' => $totRental + $totCommission + $totAdjustments,
            ],
        ];
    }

    private static function calculateDriverAdjustmentsForWeek(
        int $driverId,
        TvdeWeek $week,
        ?int $companyId = null,
        bool $onlyVehicleProfitability = false
    ): float {
        if ($onlyVehicleProfitability && ! self::hasVehicleProfitabilityAdjustmentsColumn()) {
            return 0.0;
        }

        $query = Adjustment::query()
            ->whereHas('drivers', function ($query) use ($driverId) {
                $query->where('id', $driverId);
            })
            ->where(function ($query) use ($week) {
                $query->where('start_date', '<=', $week->start_date)
                    ->orWhereNull('start_date');
            })
            ->where(function ($query) use ($week) {
                $query->where('end_date', '>=', $week->end_date)
                    ->orWhereNull('end_date');
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($onlyVehicleProfitability) {
            $query->where('affects_vehicle_profitability', true);
        }

        return (float) $query->get()->sum(function (Adjustment $adjustment) {
            $amount = (float) ($adjustment->amount ?? 0);
            $category = $adjustment->category ?? Adjustment::CATEGORY_GENERAL;

            if (in_array($category, [
                Adjustment::CATEGORY_CAUTION_RECEIVED,
                Adjustment::CATEGORY_CAUTION_RETURNED,
            ], true)) {
                return 0.0;
            }

            if ($category === Adjustment::CATEGORY_RENT_DISCOUNT) {
                return 0.0;
            }

            if ($category === Adjustment::CATEGORY_MINIMUM_BILLING_DIFFERENCE) {
                return $adjustment->type === 'deduct' ? $amount : -$amount;
            }

            return $adjustment->type === 'deduct' ? $amount : -$amount;
        });
    }

    private static function hasVehicleProfitabilityAdjustmentsColumn(): bool
    {
        if (self::$hasVehicleProfitabilityAdjustmentsColumn === null) {
            self::$hasVehicleProfitabilityAdjustmentsColumn = Schema::hasColumn('adjustments', 'affects_vehicle_profitability');
        }

        return self::$hasVehicleProfitabilityAdjustmentsColumn;
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
                'rental_total' => 0.0,
                'commission_total' => 0.0,
                'general_adjustments_total' => 0.0,
                'minimum_billing_difference_total' => 0.0,
                'adjustments_total' => 0.0,
                'total_revenue' => 0.0,
            ],
            'meta' => [
                'drivers' => [],
                'missing_current_accounts' => [],
            ],
        ];
    }

    private static function emptyWeekResult(int $tvdeWeekId, ?TvdeWeek $week = null): array
    {
        return [
            'week' => [
                'tvde_week_id' => $tvdeWeekId,
                'start_date' => $week?->start_date,
                'end_date' => $week?->end_date,
            ],
            'vehicles' => [],
            'totals' => [
                'rental_total' => 0.0,
                'commission_total' => 0.0,
                'general_adjustments_total' => 0.0,
                'minimum_billing_difference_total' => 0.0,
                'adjustments_total' => 0.0,
                'total_revenue' => 0.0,
            ],
        ];
    }

    //
}
