<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\CurrentAccount;
use App\Models\TvdeWeek;
use App\Models\TvdeActivityEntry;
use App\Models\VehicleItem;
use App\Models\VehicleRevenueAllocationOverride;
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
     * - `car_hire` (weekly rental charged to the driver) => "Cedência"
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
            ->whereHas('vehicle_item', fn ($query) => $query->where('is_service_vehicle', false))
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
        $driverWeekUsageSeconds = self::buildDriverWeekUsageSeconds($weekStart, $weekEnd, $driverIds);
        $revenueRatios = self::buildDriverVehicleRevenueRatios($week->id, $driverIds);

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
            $driverTotalUsageSeconds = (int) ($driverWeekUsageSeconds[$driverId] ?? 0);
            $usageRatio = $driverTotalUsageSeconds > 0
                ? ($usageSeconds / $driverTotalUsageSeconds)
                : 0.0;
            $allocationRatio = self::allocationRatio($revenueRatios, $driverId, (int) $vehicle->id, $usageRatio);
            $account = $accounts->get($driverId);
            $profitabilityAdjustments = self::calculateDriverProfitabilityAdjustmentBreakdownForWeek(
                $driverId,
                $week,
                (int) $vehicle->company_id
            );

            if (!$account) {
                $missingAccounts[] = $driverId;
                $allocatedGeneralAdjustments = $profitabilityAdjustments['general_adjustments_total'] * $allocationRatio;
                $allocatedMinimumBillingDifference = $profitabilityAdjustments['minimum_billing_difference_total'] * $allocationRatio;
                $allocatedProfitabilityAdjustments = $profitabilityAdjustments['total'] * $allocationRatio;
                $drivers[] = [
                    'id' => $driverId,
                    'name' => $driverNames[$driverId] ?? null,
                    'usage_seconds' => $usageSeconds,
                    'allocation_ratio' => $allocationRatio,
                    'type' => 'unknown',
                    'rental' => 0.0,
                    'commission' => 0.0,
                    'adjustments' => $allocatedProfitabilityAdjustments,
                    'general_adjustments' => $allocatedGeneralAdjustments,
                    'minimum_billing_difference' => $allocatedMinimumBillingDifference,
                    'has_current_account' => false,
                ];
                continue;
            }

            $earnings = json_decode($account->data, true) ?? [];
            $rental = (float) ($earnings['car_hire'] ?? 0);
            $commission = (float) ($earnings['percent_value'] ?? 0);
            $allocatedRental = $rental * $allocationRatio;
            $allocatedCommission = $commission * $allocationRatio;
            $allocatedGeneralAdjustments = $profitabilityAdjustments['general_adjustments_total'] * $allocationRatio;
            $allocatedMinimumBillingDifference = $profitabilityAdjustments['minimum_billing_difference_total'] * $allocationRatio;
            $adjustments = $profitabilityAdjustments['total'] * $allocationRatio;

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

            $totalRental += $allocatedRental;
            $totalCommission += $allocatedCommission;
            $totalGeneralAdjustments += $allocatedGeneralAdjustments;
            $totalMinimumBillingDifference += $allocatedMinimumBillingDifference;

            $drivers[] = [
                'id' => (int) $account->driver->id,
                'name' => $account->driver->name ?? null,
                'usage_seconds' => $usageSeconds,
                'allocation_ratio' => $allocationRatio,
                'type' => $type,
                'rental' => $allocatedRental,
                'commission' => $allocatedCommission,
                'adjustments' => $adjustments,
                'general_adjustments' => $allocatedGeneralAdjustments,
                'minimum_billing_difference' => $allocatedMinimumBillingDifference,
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
                'exclusions' => self::profitabilityExclusions(),
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

        $usages = VehicleUsage::query()
            ->whereNotNull('vehicle_item_id')
            ->whereNotNull('driver_id')
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->whereHas('vehicle_item', fn ($query) => $query->where('is_service_vehicle', false))
            ->get(['vehicle_item_id', 'driver_id', 'start_date', 'end_date']);

        $usedVehicleIds = $usages
            ->pluck('vehicle_item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $vehicles = VehicleItem::with('vehicle_model')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where(function ($query) use ($usedVehicleIds) {
                $query->where(function ($query) {
                    $query->where('suspended', false)->orWhereNull('suspended');
                });

                if (! empty($usedVehicleIds)) {
                    $query->orWhereIn('id', $usedVehicleIds);
                }
            })
            ->where(function ($query) use ($weekEnd, $usedVehicleIds) {
                $query->whereNull('acquisition_date')
                    ->orWhere('acquisition_date', '<=', $weekEnd->toDateString());

                if (! empty($usedVehicleIds)) {
                    $query->orWhereIn('id', $usedVehicleIds);
                }
            })
            ->where(function ($query) use ($weekStart, $usedVehicleIds) {
                $query->whereNull('sale_date')
                    ->orWhere('sale_date', '>=', $weekStart->toDateString());

                if (! empty($usedVehicleIds)) {
                    $query->orWhereIn('id', $usedVehicleIds);
                }
            })
            ->orderBy('license_plate')
            ->get();

        if ($vehicles->isEmpty()) {
            return self::emptyWeekResult($tvdeWeekId, $week);
        }

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
        $driverWeekUsageSeconds = self::buildDriverWeekUsageSeconds($weekStart, $weekEnd, $driverIds);
        $revenueRatios = self::buildDriverVehicleRevenueRatios($week->id, $driverIds);

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
                $driverTotalUsageSeconds = (int) ($driverWeekUsageSeconds[(int) $driverId] ?? 0);
                $usageRatio = $driverTotalUsageSeconds > 0
                    ? ($seconds / $driverTotalUsageSeconds)
                    : 0.0;
                $allocationRatio = self::allocationRatio($revenueRatios, (int) $driverId, $vehicleId, $usageRatio);
                $earnings = $decoded[(int) $driverId] ?? null;
                $profitabilityAdjustments = self::calculateDriverProfitabilityAdjustmentBreakdownForWeek(
                    (int) $driverId,
                    $week,
                    (int) $vehicle->company_id
                );

                if ($earnings === null) {
                    $generalAdjustmentsTotal += $profitabilityAdjustments['general_adjustments_total'] * $allocationRatio;
                    $minimumBillingDifferenceTotal += $profitabilityAdjustments['minimum_billing_difference_total'] * $allocationRatio;
                    $missingAccountsCount++;
                    continue;
                }

                $rentalTotal += (float) ($earnings['car_hire'] ?? 0) * $allocationRatio;
                $commissionTotal += (float) ($earnings['percent_value'] ?? 0) * $allocationRatio;
                $generalAdjustmentsTotal += $profitabilityAdjustments['general_adjustments_total'] * $allocationRatio;
                $minimumBillingDifferenceTotal += $profitabilityAdjustments['minimum_billing_difference_total'] * $allocationRatio;
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
            'meta' => [
                'exclusions' => self::profitabilityExclusions(),
            ],
        ];
    }

    private static function calculateDriverProfitabilityAdjustmentBreakdownForWeek(
        int $driverId,
        TvdeWeek $week,
        ?int $companyId = null
    ): array {
        if (! self::hasVehicleProfitabilityAdjustmentsColumn()) {
            return [
                'general_adjustments_total' => 0.0,
                'minimum_billing_difference_total' => 0.0,
                'total' => 0.0,
            ];
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

        $query->where('affects_vehicle_profitability', true);

        $breakdown = [
            'general_adjustments_total' => 0.0,
            'minimum_billing_difference_total' => 0.0,
        ];

        $query->get()->each(function (Adjustment $adjustment) use (&$breakdown) {
            $amount = (float) ($adjustment->amount ?? 0);
            $category = $adjustment->category ?? Adjustment::CATEGORY_GENERAL;

            if (in_array($category, [
                Adjustment::CATEGORY_CAUTION_RECEIVED,
                Adjustment::CATEGORY_CAUTION_RETURNED,
            ], true)) {
                return;
            }

            if ($category === Adjustment::CATEGORY_RENT_DISCOUNT) {
                return;
            }

            $signedAmount = $adjustment->type === 'deduct' ? $amount : -$amount;

            if ($category === Adjustment::CATEGORY_MINIMUM_BILLING_DIFFERENCE) {
                $breakdown['minimum_billing_difference_total'] += $signedAmount;
                return;
            }

            $breakdown['general_adjustments_total'] += $signedAmount;
        });

        $breakdown['total'] = $breakdown['general_adjustments_total'] + $breakdown['minimum_billing_difference_total'];

        return $breakdown;
    }

    private static function hasVehicleProfitabilityAdjustmentsColumn(): bool
    {
        if (self::$hasVehicleProfitabilityAdjustmentsColumn === null) {
            self::$hasVehicleProfitabilityAdjustmentsColumn = Schema::hasColumn('adjustments', 'affects_vehicle_profitability');
        }

        return self::$hasVehicleProfitabilityAdjustmentsColumn;
    }

    private static function buildDriverWeekUsageSeconds(Carbon $weekStart, Carbon $weekEnd, array $driverIds): array
    {
        if (empty($driverIds)) {
            return [];
        }

        $secondsByDriver = [];
        $usages = VehicleUsage::query()
            ->whereIn('driver_id', $driverIds)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $weekStart);
            })
            ->whereHas('vehicle_item', fn ($query) => $query->where('is_service_vehicle', false))
            ->get(['driver_id', 'start_date', 'end_date']);

        foreach ($usages as $usage) {
            $usageStart = Carbon::parse($usage->start_date);
            $usageEnd = $usage->end_date ? Carbon::parse($usage->end_date) : $weekEnd;

            $intervalStart = $usageStart->greaterThan($weekStart) ? $usageStart : $weekStart;
            $intervalEnd = $usageEnd->lessThan($weekEnd) ? $usageEnd : $weekEnd;

            if ($intervalEnd->lessThan($intervalStart)) {
                continue;
            }

            $driverId = (int) $usage->driver_id;
            $secondsByDriver[$driverId] = ($secondsByDriver[$driverId] ?? 0)
                + $intervalEnd->diffInSeconds($intervalStart) + 1;
        }

        return $secondsByDriver;
    }

    private static function buildDriverVehicleRevenueRatios(int $weekId, array $driverIds): array
    {
        if (empty($driverIds)) {
            return [];
        }

        $ratios = [];
        $overrides = VehicleRevenueAllocationOverride::query()
            ->where('tvde_week_id', $weekId)
            ->whereIn('driver_id', $driverIds)
            ->get();

        foreach ($overrides as $override) {
            $ratios[(int) $override->driver_id] = [(int) $override->vehicle_item_id => 1.0];
        }

        $entries = TvdeActivityEntry::query()
            ->where('tvde_week_id', $weekId)
            ->whereIn('driver_id', $driverIds)
            ->get(['driver_id', 'vehicle_item_id', 'allocation_status', 'net']);

        foreach ($entries->groupBy('driver_id') as $driverId => $driverEntries) {
            if (isset($ratios[(int) $driverId])) {
                continue;
            }

            $allocatedEntries = $driverEntries
                ->whereIn('allocation_status', ['assigned', 'manual'])
                ->whereNotNull('vehicle_item_id');

            // Pending/unallocated entries must not disable the usage-time fallback.
            if ($allocatedEntries->isEmpty()) {
                continue;
            }

            $total = $allocatedEntries->sum(fn ($entry) => abs((float) $entry->net));
            if ($total <= 0) {
                $total = $allocatedEntries->count();
            }

            $ratios[(int) $driverId] = [];
            foreach ($allocatedEntries->groupBy('vehicle_item_id') as $vehicleId => $vehicleEntries) {
                $value = $vehicleEntries->sum(fn ($entry) => abs((float) $entry->net));
                if ($value <= 0) {
                    $value = $vehicleEntries->count();
                }
                $ratios[(int) $driverId][(int) $vehicleId] = $total > 0 ? $value / $total : 0.0;
            }
        }

        return $ratios;
    }

    private static function allocationRatio(array $ratios, int $driverId, int $vehicleId, float $usageRatio): float
    {
        if (! array_key_exists($driverId, $ratios)) {
            return $usageRatio;
        }

        return (float) ($ratios[$driverId][$vehicleId] ?? 0.0);
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
                'exclusions' => self::profitabilityExclusions(),
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
            'meta' => [
                'exclusions' => self::profitabilityExclusions(),
            ],
        ];
    }

    private static function profitabilityExclusions(): array
    {
        return [
            'receipts' => 'Recibos, pagamentos ao motorista e amount_transferred nao entram na rentabilidade da viatura.',
            'reimbursements' => 'Devolucoes, reforcos e outros movimentos de saldo do motorista nao contam como receita operacional da viatura.',
            'balances' => 'DriversBalance e estados semanais sao fluxo financeiro do motorista, nao receita de exploracao.',
        ];
    }
}
