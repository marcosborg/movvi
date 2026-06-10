<?php

namespace App\Http\Controllers\Traits;

use App\Models\Adjustment;
use App\Models\CarHire;
use App\Models\CombustionTransaction;
use App\Models\ContractTypeRank;
use App\Models\ContractVat;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\ElectricTransaction;
use App\Models\TollPayment;
use App\Models\TvdeActivity;
use App\Models\TvdeWeek;
use App\Models\CurrentAccount;
use App\Models\Electric;
use App\Models\Card;
use App\Models\Receipt;
use App\Models\TvdeMonth;
use App\Models\TvdeYear;
use App\Models\CompanyExpense;
use App\Models\CompanyPark;
use App\Models\Consultancy;
use App\Models\Company;
use App\Models\CompanyData;
use App\Models\CarTrack;
use App\Models\TeslaCharging;
use App\Models\VehicleUsage;
use App\Models\WeeklyVehicleMileage;
use Carbon\Carbon;

trait Reports
{
    public function getWeekReport($company_id, $tvde_week_id)
    {
        $tvde_week = TvdeWeek::find($tvde_week_id);

        $drivers = Driver::where('company_id', $company_id)
            ->where('state_id', 1)
            ->orderBy('name')
            ->get()
            ->load([
                'contract_vat',
                'card',
                'electric',
                'vehicle',
                'cards'
            ]);

        $weekStart = Carbon::parse($tvde_week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($tvde_week->getRawOriginal('end_date'))->endOfDay();
        $driverIds = $drivers->pluck('id')->all();

        $weekUsages = VehicleUsage::with('vehicle_item')
            ->whereIn('driver_id', $driverIds)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $weekStart);
            })
            ->where(function ($query) {
                $query->whereNull('usage_exceptions')
                    ->orWhere('usage_exceptions', 'usage');
            })
            ->get();

        $mileages = WeeklyVehicleMileage::where('tvde_week_id', $tvde_week_id)->get();
        $mileageAllocation = $this->buildWeeklyMileageAllocation($weekUsages, $mileages, $weekStart, $weekEnd);
        $weekReceipts = Receipt::whereIn('driver_id', $driverIds)
            ->where('tvde_week_id', $tvde_week_id)
            ->orderByDesc('verified')
            ->orderByDesc('id')
            ->get()
            ->groupBy('driver_id')
            ->map(function ($receipts) {
                return $receipts->firstWhere('verified', true) ?? $receipts->first();
            });

        // Totais (mantendo compatibilidade)
        $total_operators = [];
        $total_earnings_after_discount = []; // legado
        $total_fuel_transactions = [];
        $total_adjustments = [];
        $total_fleet_management = [];
        $total_drivers = [];
        $total_company_adjustments = [];
        $total_vat_value = [];              // compat: soma (iva + percent)
        $total_earnings_after_vat = [];     // compat (alias)
        $total_car_track = [];
        $total_car_hire = [];
        $total_net_operators = [];
        $total_weekly_km = [];

        // Novos agregados úteis
        $gross_uber = [];
        $gross_bolt = [];
        $net_uber = [];
        $net_bolt = [];

        // Novos totais de tips e pipeline
        $uber_tips_total = [];
        $bolt_tips_total = [];
        $tips_total_all = [];
        $total_base_before_vat = [];
        $total_after_vat_arr = [];
        $total_after_vat_plus_tips = [];

        // Novos totais separados para transparência
        $total_iva_value = [];
        $total_percent_value = [];
        $total_general_adjustments = [];
        $total_rent_discounts = [];
        $total_minimum_billing_difference = [];
        $total_caution_received = [];
        $total_caution_returned = [];
        $receipt_check_match_count = 0;
        $receipt_check_mismatch_count = 0;
        $receipt_check_missing_count = 0;
        $receipt_check_received_total = [];
        $receipt_check_difference_total = [];

        foreach ($drivers as $driver) {
            $driverPlates = $mileageAllocation['plates'][$driver->id] ?? [];
            $driver->license_plates = array_values($driverPlates);
            $driver->license_plate = !empty($driver->license_plates)
                ? implode(', ', $driver->license_plates)
                : null;
            $driver->weekly_km = (float) ($mileageAllocation['kilometers'][$driver->id] ?? 0);

            // ---------- Atividades UBER ----------
            $uber_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 1,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->uber_uuid
            ])->get();

            $uber_gross = (float) $uber_activities->sum('gross');
            $uber_net = (float) $uber_activities->sum('net');
            $uber_tips = (float) $uber_activities->sum(function ($a) {
                return $a->tips ?? 0;
            });

            // ---------- Atividades BOLT ----------
            $bolt_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 2,
                'tvde_week_id' => $tvde_week_id,
            ])->whereIn('driver_code', $driver->boltIdentifiers())->get();

            $bolt_gross = (float) $bolt_activities->sum('gross');
            $bolt_net = (float) $bolt_activities->sum('net');
            $bolt_tips = (float) $bolt_activities->sum(function ($a) {
                return $a->tips ?? 0;
            });

            // EARNINGS (por operador)
            $uber = collect([
                'uber_gross' => $uber_gross,
                'uber_net' => $uber_net,
                'uber_tips' => $uber_tips,
            ]);

            $bolt = collect([
                'bolt_gross' => $bolt_gross,
                'bolt_net' => $bolt_net,
                'bolt_tips' => $bolt_tips,
            ]);

            $gross_total = $uber_gross + $bolt_gross;
            $net_total = $uber_net + $bolt_net;

            // ---------- FUEL ----------
            $fuel_transactions = 0.0;

            if ($driver->electric) {
                $electric_transactions = $this->uniqueElectricTransactions($tvde_week_id, $driver->electric->code);
                $electric_total = (float) $electric_transactions->sum('total');

                if ($electric_total > 0) {
                    $fuel_transactions = $electric_total;
                }
            }

            $cardCodes = $driver->cards ? $driver->cards->pluck('code')->filter()->all() : [];
            if (empty($cardCodes) && $driver->card) {
                $cardCodes = [$driver->card->code];
            }

            $combustionTransactions = $this->uniqueCombustionTransactionsForDriver($tvde_week_id, $driver, $cardCodes);

            if ($combustionTransactions->isNotEmpty()) {
                $combustion_total = (float) $combustionTransactions->sum(function ($t) {
                    return (float) $t->total;
                });

                if ($combustion_total > 0) {
                    $fuel_transactions = $combustion_total;
                }
            }

            if ($driver->half_tolls && $fuel_transactions > 0) {
                $fuel_transactions = $fuel_transactions / 2;
            }

            // ---------- OUTROS ABASTECIMENTOS ----------
            $other_fuel_total = (float) $this->otherFuelTransactionsForDriver($tvde_week, $driver)
                ->sum('value');

            // Garantir número em fuel
            $driver->fuel = (float) $fuel_transactions + $other_fuel_total;
            $total_fuel_transactions[] = $driver->fuel;

            // ---------- CAR HIRE ----------
            $carHireResult = $this->calculateCarHireForWeek($driver, $tvde_week);
            $rent_base_value = (float) $carHireResult['total'];

            // ---------- ADJUSTMENTS ----------
            $adjustments_array = Adjustment::whereHas('drivers', function ($query) use ($driver) {
                $query->where('id', $driver->id);
            })
                ->where('company_id', $company_id)
                ->where(function ($query) use ($tvde_week) {
                    $query->where('start_date', '<=', $tvde_week->start_date)
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($tvde_week) {
                    $query->where('end_date', '>=', $tvde_week->end_date)
                        ->orWhereNull('end_date');
                })
                ->get();

            $adjustmentBreakdown = $this->buildDriverAdjustmentBreakdown($adjustments_array);
            $refunds = $adjustmentBreakdown['refunds_total'];
            $deducts = $adjustmentBreakdown['deducts_total'];
            $fleet_management = $adjustmentBreakdown['fleet_management_total'];
            $company_expense = $adjustmentBreakdown['company_expense_total'];
            $general_adjustments = $adjustmentBreakdown['general_total'];
            $rent_discount = $adjustmentBreakdown['rent_discount_total'];
            $minimum_billing_difference = $adjustmentBreakdown['minimum_billing_difference_total'];
            $caution_received = $adjustmentBreakdown['caution_received_total'];
            $caution_returned = $adjustmentBreakdown['caution_returned_total'];

            $adjustments = $general_adjustments + $minimum_billing_difference;
            $rent_value = max(0.0, $rent_base_value - $rent_discount);
            $total_adjustments[] = $adjustments;
            $total_fleet_management[] = $fleet_management;
            $total_company_adjustments[] = $company_expense;
            $total_general_adjustments[] = $general_adjustments;
            $total_rent_discounts[] = $rent_discount;
            $total_minimum_billing_difference[] = $minimum_billing_difference;
            $total_caution_received[] = $caution_received;
            $total_caution_returned[] = $caution_returned;
            // ---------- CAR TRACK (Via Verde) ----------
            $car_track = (float) CarTrack::query()
                ->where('tvde_week_id', $tvde_week->id)
                ->where('driver_id', $driver->id)
                ->where('assignment_status', CarTrack::STATUS_ASSIGNED)
                ->sum('value');

            // =======================
            // DRIVER PAYOUT (NET - TIPS - IVA 6% - COMPANY % - EXPENSES + ADJUSTMENTS + TIPS)
            // =======================
            $tips_total = $uber_tips + $bolt_tips;

            // Base from platforms excludes tips and fuel (tips are passed through in full at the end).
            // Fuel is removed only to compute IVA, then added back before company percentage.
            $base_before_taxes = $net_total - $tips_total - $driver->fuel;

            // IVA comes from the contract VAT model (default 6%) per payout rule.
            $iva_rate = (($driver->contract_vat ? (float) ($driver->contract_vat->iva ?? 6.0) : 6.0) / 100.0);
            $iva_value = max(0.0, $base_before_taxes) * $iva_rate;

            // Company percentage applies after IVA is removed (fuel is added back before percent).
            $percent_percent = $driver->contract_vat ? (float) ($driver->contract_vat->percent ?? 0) : 0.0;
            $percent_rate = $percent_percent / 100.0;
            $base_after_iva = $base_before_taxes - $iva_value + $driver->fuel;
            $percent_value = max(0.0, $base_after_iva) * $percent_rate;

            // Expenses (rent, fuel, Via Verde, fleet fees) are deducted after company percentage per payout rule.
            $base_after_company = $base_after_iva - $percent_value;
            $expenses_total = $rent_value + $driver->fuel + $car_track + $fleet_management;

            // Final driver total: base after taxes/percent - expenses + adjustments + caution movements + tips.
            $subtotal_after_tips = $base_after_company - $expenses_total;
            $final_total = $subtotal_after_tips + $adjustments + $caution_received + $caution_returned + $tips_total;
            $earnings_per_km = $driver->weekly_km > 0
                ? round($net_total / $driver->weekly_km, 6)
                : 0.0;

            // Legacy IVA/percent fields are kept for older reports.
            $iva_percent = $iva_rate * 100.0;
            $total_after_vat_alias = $base_after_iva - $percent_value; // alias compat
            $after_vat = $total_after_vat_alias;

            // ---------- LEGADO: earnings_after_discount ----------
            // Sequencial ao bruto (não precisa travão porque o bruto é >= 0):
            $earnings_after_discount = $gross_total;
            if ($iva_rate > 0) {
                $earnings_after_discount -= ($earnings_after_discount * $iva_rate);
            }
            if ($percent_rate > 0) {
                $earnings_after_discount -= ($earnings_after_discount * $percent_rate);
            }

            // ---------- Guardar breakdown no driver ----------
            $earnings = collect([
                'uber' => $uber,
                'bolt' => $bolt,
                'total_gross' => $gross_total,
                'total_net' => $net_total,

                // Tips e pipeline
                'tips_total' => $tips_total,
                'base_before_vat' => $base_before_taxes,
                'base_after_company' => $base_after_company,

                // Retenções (novos campos)
                'iva_percent' => $iva_percent,
                'percent_percent' => $percent_percent,
                'iva_value' => $iva_value,
                'percent_value' => $percent_value,

                // Compatibilidade (vat_value = iva + percent)
                'vat_value' => $iva_value + $percent_value,

                'after_vat' => $after_vat,                   // novo
                'total_after_vat' => $total_after_vat_alias, // alias compat
                'subtotal_after_tips' => $subtotal_after_tips,
                'driver_total' => $final_total,
                'weekly_km' => $driver->weekly_km,
                'earnings_per_km' => $earnings_per_km,

                // Custos e ajustes
                'car_track' => $car_track,
                'fuel_transactions' => $driver->fuel,
                'car_hire' => $rent_value,
                'car_hire_base' => $rent_base_value,
                'abatimento_aluguer' => $rent_discount,
                'adjustments' => $adjustments,
                'general_adjustments' => $general_adjustments,
                'diferenca_faturacao_minima' => $minimum_billing_difference,
                'caucao_recebida' => $caution_received,
                'caucao_devolvida' => $caution_returned,
                'fleet_management' => $fleet_management,
                'company_expense' => $company_expense,

                // Legado
                'earnings_after_discount' => $earnings_after_discount,
                'adjustments_array' => $adjustments_array,
            ]);

            $driver->earnings = $earnings;
            $driver->refunds = $refunds;
            $driver->adjustments = $adjustments;
            $driver->fleet_management = $fleet_management;
            $driver->caution_display = $this->formatCautionDisplay($caution_received, $caution_returned);
            $driver->caution_tooltip = $this->formatCautionTooltip($caution_received, $caution_returned);


            // BALANCE
            $current_balance = DriversBalance::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            if ($current_balance) {
                $driver->last_balance = (float) ($current_balance->last_balance ?? 0);
                $driver->new_balance = (float) ($current_balance->new_balance ?? 0);
            } else {
                $driver_balance = $this->previousDriverBalanceBeforeWeek((int) $driver->id, $tvde_week);
                $driver->last_balance = $driver_balance ? (float) $driver_balance->new_balance : 0.0;
                $driver->new_balance = $driver->last_balance + $final_total;
            }

            // Totais finais do driver (pipeline novo)
            $driver->total = $final_total;
            $driver->final_total = $driver->total;
            $driver->final_total_balance = $driver->final_total + $driver->new_balance;
            $driver->earnings_per_km = $earnings_per_km;

            $receipt = $weekReceipts->get($driver->id);
            $receiptIsVerified = $receipt && (bool) $receipt->verified;
            $receivedInAccount = null;

            if ($receiptIsVerified) {
                $receivedInAccount = $receipt->amount_transferred !== null
                    ? (float) $receipt->amount_transferred
                    : ($receipt->verified_value !== null ? (float) $receipt->verified_value : null);
            }
            $platformNetTotal = round($net_total, 2);
            $receiptCheckDifference = $receivedInAccount !== null
                ? round($receivedInAccount - $platformNetTotal, 2)
                : null;
            $receiptCheckStatus = 'missing';

            if ($receivedInAccount !== null) {
                $receiptCheckStatus = abs($receiptCheckDifference) <= 0.01 ? 'match' : 'mismatch';
            }

            if ($receiptCheckStatus === 'match') {
                $receipt_check_match_count++;
            } elseif ($receiptCheckStatus === 'mismatch') {
                $receipt_check_mismatch_count++;
            } else {
                $receipt_check_missing_count++;
            }

            if ($receiptCheckDifference !== null) {
                $receipt_check_received_total[] = $receivedInAccount;
                $receipt_check_difference_total[] = $receiptCheckDifference;
            }

            $driver->receipt_check = [
                'status' => $receiptCheckStatus,
                'platform_net_total' => $platformNetTotal,
                'received_in_account' => $receivedInAccount,
                'difference' => $receiptCheckDifference,
                'receipt_id' => $receipt?->id,
                'is_verified' => $receiptIsVerified,
                'amount_transferred' => $receipt && $receipt->amount_transferred !== null
                    ? (float) $receipt->amount_transferred
                    : null,
            ];

            // ---------- Alimentar arrays de totais ----------
            $gross_uber[] = $uber_gross;
            $gross_bolt[] = $bolt_gross;
            $net_uber[] = $uber_net;
            $net_bolt[] = $bolt_net;

            $total_operators[] = $gross_total;
            $total_net_operators[] = $net_total;

            // Compat: vat_value = iva + percent
            $total_vat_value[] = $iva_value + $percent_value;
            $total_iva_value[] = $iva_value;
            $total_percent_value[] = $percent_value;

            $total_car_track[] = $car_track;
            $total_car_hire[] = $rent_value;
            $total_drivers[] = $driver->total;
            $total_weekly_km[] = $driver->weekly_km;

            // Novos totais
            $uber_tips_total[] = $uber_tips;
            $bolt_tips_total[] = $bolt_tips;
            $tips_total_all[] = $tips_total;
            $total_base_before_vat[] = $base_before_taxes;
            $total_after_vat_arr[] = $after_vat;
            $total_after_vat_plus_tips[] = $subtotal_after_tips;

            // Legado
            $total_earnings_after_discount[] = $earnings_after_discount;
            $total_earnings_after_vat[] = $total_after_vat_alias;

            // current_account flag
            $current_account = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            $driver->current_account = (bool) $current_account;

            $driver->balance_manual_status = $current_balance?->manual_status;
            $driver->balance_manual_status_label = $current_balance?->manual_status_label;
            $driver->balance_record_id = $current_balance?->id;
        }

        $totals = collect([
            // Operadores (brutos e líquidos)
            'gross_uber' => array_sum($gross_uber),
            'gross_bolt' => array_sum($gross_bolt),
            'net_uber' => array_sum($net_uber),
            'net_bolt' => array_sum($net_bolt),

            'total_operators' => array_sum($total_operators),
            'total_net_operators' => array_sum($total_net_operators),

            // Tips
            'uber_tips_total' => array_sum($uber_tips_total),
            'bolt_tips_total' => array_sum($bolt_tips_total),
            'tips_total' => array_sum($tips_total_all),

            // Pipeline
            'total_base_before_vat' => array_sum($total_base_before_vat),
            'total_vat_value' => array_sum($total_vat_value),          // compat (iva + percent)
            'total_after_vat' => array_sum($total_after_vat_arr),      // novo
            'total_earnings_after_vat' => array_sum($total_earnings_after_vat), // compat (alias)
            'total_after_vat_plus_tips' => array_sum($total_after_vat_plus_tips),

            // Custos/Ajustes
            'total_fuel_transactions' => array_sum($total_fuel_transactions),
            'total_adjustments' => array_sum($total_adjustments),
            'total_general_adjustments' => array_sum($total_general_adjustments),
            'total_rent_discounts' => array_sum($total_rent_discounts),
            'total_minimum_billing_difference' => array_sum($total_minimum_billing_difference),
            'total_caution_received' => array_sum($total_caution_received),
            'total_caution_returned' => array_sum($total_caution_returned),
            'total_fleet_management' => array_sum($total_fleet_management),
            'total_car_track' => array_sum($total_car_track),
            'total_car_hire' => array_sum($total_car_hire),
            'total_weekly_km' => array_sum($total_weekly_km),

            // Total final (após tudo)
            'total_drivers' => array_sum($total_drivers),

            // Legado (compat)
            'total_earnings_after_discount' => array_sum($total_earnings_after_discount),
            'total_company_adjustments' => array_sum($total_company_adjustments),

            // Novos (transparência)
            'total_iva_value' => array_sum($total_iva_value),
            'total_percent_value' => array_sum($total_percent_value),
            'total_earnings_per_km' => array_sum($total_weekly_km) > 0
                ? (array_sum($total_net_operators) / array_sum($total_weekly_km))
                : 0,
            'receipt_check_match_count' => $receipt_check_match_count,
            'receipt_check_mismatch_count' => $receipt_check_mismatch_count,
            'receipt_check_missing_count' => $receipt_check_missing_count,
            'receipt_check_received_total' => array_sum($receipt_check_received_total),
            'receipt_check_difference_total' => array_sum($receipt_check_difference_total),
        ]);

        $totals['caution_display'] = $this->formatCautionDisplay(
            (float) ($totals['total_caution_received'] ?? 0),
            (float) ($totals['total_caution_returned'] ?? 0)
        );
        $totals['caution_tooltip'] = $this->formatCautionTooltip(
            (float) ($totals['total_caution_received'] ?? 0),
            (float) ($totals['total_caution_returned'] ?? 0)
        );

        return [
            'drivers' => $drivers,
            'totals' => $totals,
        ];
    }

    protected function buildDriverAdjustmentBreakdown($adjustments): array
    {
        $breakdown = [
            'refunds_total' => 0.0,
            'deducts_total' => 0.0,
            'fleet_management_total' => 0.0,
            'company_expense_total' => 0.0,
            'general_total' => 0.0,
            'rent_discount_total' => 0.0,
            'minimum_billing_difference_total' => 0.0,
            'caution_received_total' => 0.0,
            'caution_returned_total' => 0.0,
        ];

        foreach ($adjustments as $adjustment) {
            $amount = (float) ($adjustment->amount ?? 0);
            $signedAmount = $adjustment->type === 'deduct' ? -$amount : $amount;
            $costAmount = $adjustment->type === 'deduct' ? $amount : -$amount;
            $category = $adjustment->category ?? Adjustment::CATEGORY_GENERAL;

            if ($signedAmount >= 0) {
                $breakdown['refunds_total'] += $signedAmount;
            } else {
                $breakdown['deducts_total'] += abs($signedAmount);
            }

            if ($adjustment->fleet_management) {
                $breakdown['fleet_management_total'] += $costAmount;
                continue;
            }

            if ($adjustment->company_expense) {
                $breakdown['company_expense_total'] += $signedAmount;
            }

            switch ($category) {
                case Adjustment::CATEGORY_RENT_DISCOUNT:
                    $breakdown['rent_discount_total'] += $signedAmount;
                    break;

                case Adjustment::CATEGORY_MINIMUM_BILLING_DIFFERENCE:
                    $breakdown['minimum_billing_difference_total'] += $signedAmount;
                    break;

                case Adjustment::CATEGORY_CAUTION_RECEIVED:
                    $breakdown['caution_received_total'] += $signedAmount;
                    break;

                case Adjustment::CATEGORY_CAUTION_RETURNED:
                    $breakdown['caution_returned_total'] += $signedAmount;
                    break;

                case Adjustment::CATEGORY_MANUAL:
                case Adjustment::CATEGORY_GENERAL:
                default:
                    $breakdown['general_total'] += $signedAmount;
                    break;
            }
        }

        return $breakdown;
    }

    protected function formatCautionDisplay(float $received, float $returned): string
    {
        $parts = [];

        if (abs($received) > 0.00001) {
            $parts[] = $this->formatSignedCurrency($received);
        }

        if (abs($returned) > 0.00001) {
            $parts[] = $this->formatSignedCurrency($returned);
        }

        if (empty($parts)) {
            return $this->formatUnsignedCurrency(0.0);
        }

        return implode(' ', $parts);
    }

    protected function formatCautionTooltip(float $received, float $returned): string
    {
        return sprintf(
            'Caução recebida: %s | Caução devolvida: %s',
            $this->formatSignedCurrency($received),
            $this->formatSignedCurrency($returned)
        );
    }

    protected function formatSignedCurrency(float $value): string
    {
        if (abs($value) <= 0.00001) {
            return $this->formatUnsignedCurrency(0.0);
        }

        $sign = $value > 0 ? '+' : '-';

        return $sign . $this->formatUnsignedCurrency(abs($value));
    }

    protected function formatUnsignedCurrency(float $value): string
    {
        return number_format($value, 2, ',', '.') . '€';
    }

    protected function buildWeeklyMileageAllocation($weekUsages, $mileages, Carbon $weekStart, Carbon $weekEnd): array
    {
        $platesByDriver = [];
        $kilometersByDriver = [];

        foreach ($weekUsages as $usage) {
            $plate = optional($usage->vehicle_item)->license_plate;
            if (!$plate) {
                continue;
            }

            $platesByDriver[$usage->driver_id][$this->normalizeReportPlate($plate)] = $plate;
        }

        foreach ($mileages as $mileage) {
            $normalizedPlate = $this->normalizeReportPlate($mileage->license_plate);
            if (!$normalizedPlate) {
                continue;
            }

            $plateUsages = $weekUsages->filter(function ($usage) use ($normalizedPlate) {
                $plate = optional($usage->vehicle_item)->license_plate;

                return $this->normalizeReportPlate($plate) === $normalizedPlate;
            });

            if ($plateUsages->isEmpty()) {
                continue;
            }

            $secondsByDriver = [];

            foreach ($plateUsages as $usage) {
                $overlapSeconds = $this->calculateUsageOverlapSeconds($usage, $weekStart, $weekEnd);

                if ($overlapSeconds <= 0) {
                    continue;
                }

                $secondsByDriver[$usage->driver_id] = ($secondsByDriver[$usage->driver_id] ?? 0) + $overlapSeconds;
            }

            $totalSeconds = array_sum($secondsByDriver);
            if ($totalSeconds <= 0) {
                continue;
            }

            foreach ($secondsByDriver as $driverId => $seconds) {
                $kilometersByDriver[$driverId] = ($kilometersByDriver[$driverId] ?? 0)
                    + ((float) $mileage->distance_km * ($seconds / $totalSeconds));
            }
        }

        return [
            'plates' => $platesByDriver,
            'kilometers' => $kilometersByDriver,
        ];
    }

    protected function calculateUsageOverlapSeconds(VehicleUsage $usage, Carbon $weekStart, Carbon $weekEnd): int
    {
        $usageStart = $usage->getRawOriginal('start_date')
            ? Carbon::parse($usage->getRawOriginal('start_date'))
            : $weekStart->copy();
        $usageEnd = $usage->getRawOriginal('end_date')
            ? Carbon::parse($usage->getRawOriginal('end_date'))
            : $weekEnd->copy();

        if ($usageEnd->lessThan($usageStart)) {
            return 0;
        }

        $effectiveStart = $usageStart->greaterThan($weekStart) ? $usageStart : $weekStart->copy();
        $effectiveEnd = $usageEnd->lessThan($weekEnd) ? $usageEnd : $weekEnd->copy();

        if ($effectiveEnd->lessThan($effectiveStart)) {
            return 0;
        }

        return max(1, $effectiveStart->diffInSeconds($effectiveEnd));
    }

    protected function normalizeReportPlate(?string $plate): ?string
    {
        $normalized = strtoupper(str_replace(['-', ' '], '', trim((string) $plate)));

        return $normalized !== '' ? $normalized : null;
    }

    protected function otherFuelTransactionsForDriver(TvdeWeek $tvdeWeek, Driver $driver)
    {
        $weekStart = Carbon::parse($tvdeWeek->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($tvdeWeek->getRawOriginal('end_date'))->endOfDay();

        $usageIntervals = $driver->vehicleUsages()
            ->with('vehicle_item:id,license_plate')
            ->where('start_date', '<=', $weekEnd->toDateTimeString())
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $weekStart->toDateTimeString());
            })
            ->where(function ($query) {
                $query->whereNull('usage_exceptions')
                    ->orWhere('usage_exceptions', 'usage');
            })
            ->get();

        if ($usageIntervals->isEmpty()) {
            return collect();
        }

        $normalizedPlates = $usageIntervals
            ->map(function ($usage) {
                return $this->normalizeReportPlate(optional($usage->vehicle_item)->license_plate);
            })
            ->filter()
            ->unique()
            ->values();

        if ($normalizedPlates->isEmpty()) {
            return collect();
        }

        return TeslaCharging::whereBetween('datetime', [
            $weekStart->toDateTimeString(),
            $weekEnd->toDateTimeString(),
        ])
            ->get()
            ->filter(function ($charging) use ($normalizedPlates, $usageIntervals, $weekStart, $weekEnd) {
                $chargingPlate = $this->normalizeReportPlate((string) $charging->license);
                if (!$chargingPlate || !$normalizedPlates->contains($chargingPlate)) {
                    return false;
                }

                $chargingMoment = $charging->datetime
                    ? Carbon::parse($charging->datetime)
                    : null;

                if (!$chargingMoment) {
                    return false;
                }

                foreach ($usageIntervals as $usage) {
                    $usagePlate = $this->normalizeReportPlate(optional($usage->vehicle_item)->license_plate);
                    if ($usagePlate !== $chargingPlate) {
                        continue;
                    }

                    $usageStart = $usage->getRawOriginal('start_date')
                        ? Carbon::parse($usage->getRawOriginal('start_date'))
                        : $weekStart->copy();
                    $usageEnd = $usage->getRawOriginal('end_date')
                        ? Carbon::parse($usage->getRawOriginal('end_date'))
                        : $weekEnd->copy();

                    if ($chargingMoment->between($usageStart, $usageEnd, true)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function ($charging) {
                $charging->card_type = $charging->card_type ?: 'Tesla';
                return $charging;
            })
            ->sortBy('datetime')
            ->values();
    }

    public function getDriverWeekReport($driver_id, $company_id, $tvde_week_id)
    {

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $driver = Driver::find($driver_id)->load([
            'contract_vat'
        ]);

        $bolt_activities = TvdeActivity::where([
            'tvde_week_id' => $tvde_week_id,
            'tvde_operator_id' => 2,
            'company_id' => $company_id,
        ])
            ->whereIn('driver_code', $driver->boltIdentifiers())
            ->get();

        $uber_activities = TvdeActivity::where([
            'tvde_week_id' => $tvde_week_id,
            'tvde_operator_id' => 1,
            'driver_code' => $driver->uber_uuid,
            'company_id' => $company_id,
        ])
            ->get();

        $adjustments_array = Adjustment::whereHas('drivers', function ($query) use ($driver_id) {
            $query->where('id', $driver_id);
        })
            ->where('company_id', $company_id)
            ->where(function ($query) use ($tvde_week) {
                $query->where('start_date', '<=', $tvde_week->start_date)
                    ->orWhereNull('start_date');
            })
            ->where(function ($query) use ($tvde_week) {
                $query->where('end_date', '>=', $tvde_week->end_date)
                    ->orWhereNull('end_date');
            })
            ->get();

        $refund = 0;
        $deduct = 0;

        foreach ($adjustments_array as $adjustment) {
            switch ($adjustment->type) {
                case 'refund':
                    if ($adjustment->amount) {
                        $refund = $refund + $adjustment->amount;
                    }
                    if ($adjustment->percent) {
                        $total = $bolt_activities->sum('net') + $uber_activities->sum('net');
                        $percent = $adjustment->percent;
                        $amount = ($total * $percent) / 100;
                        $refund = $refund + $amount;
                        $adjustment->amount = $amount;
                    }
                    break;
                case 'deduct':
                    if ($adjustment->amount) {
                        $deduct = $deduct + $adjustment->amount;
                    }
                    if ($adjustment->percent) {
                        $total = $bolt_activities->sum('net') + $uber_activities->sum('net');
                        $percent = $adjustment->percent;
                        $amount = ($total * $percent) / 100;
                        $deduct = $deduct + $amount;
                        $adjustment->amount = $amount;
                    }
                    break;
            }
        }

        // FUEL EXPENSES

        $electric_expenses = null;
        if ($driver && $driver->electric_id) {
            $electric = Electric::find($driver->electric_id);
            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id
                ])->get();
                $electric_expenses = collect([
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', '') . ' €',
                    'value' => $electric_transactions->sum('total')
                ]);
            }
        }
        $combustion_expenses = null;
        if ($driver && $driver->card_id) {
            $card = Card::find($driver->card_id);
            $code = $card ? $card->code : 0;

            $combustion_transactions = $this->uniqueCombustionTransactions($tvde_week_id, [$code]);
            $combustion_total = $combustion_transactions->sum(function ($t) {
                return (float) $t->total;
            });

            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_total, 2, '.', '') . ' €',
                'value' => $combustion_total
            ]);
        }

        $total_earnings_bolt = number_format($bolt_activities->sum('net') - $bolt_activities->sum('gross'), 2, '.', '');
        $total_tips_bolt = number_format($bolt_activities->sum('gross'), 2);
        $total_earnings_uber = number_format($uber_activities->sum('net') - $uber_activities->sum('gross'), 2, '.', '');
        $total_tips_uber = number_format($uber_activities->sum('gross'), 2);
        $total_tips = $total_tips_uber + $total_tips_bolt;
        $total_earnings = $bolt_activities->sum('net') + $uber_activities->sum('net');
        $total_earnings_no_tip = ($bolt_activities->sum('net') - $bolt_activities->sum('gross')) + ($uber_activities->sum('net') - $uber_activities->sum('gross'));

        //CHECK PERCENT
        $contract_type_ranks = $driver ? ContractTypeRank::where('contract_type_id', $driver->contract_type_id)->get() : [];
        $contract_type_rank = count($contract_type_ranks) > 0 ? $contract_type_ranks[0] : null;
        foreach ($contract_type_ranks as $value) {
            if ($value->from <= $total_earnings && $value->to >= $total_earnings) {
                $contract_type_rank = $value;
            }
        }

        //

        $total_bolt = ($bolt_activities->sum('net') - $bolt_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);
        $total_uber = ($uber_activities->sum('net') - $uber_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0);

        $total_earnings_after_vat = $total_bolt + $total_uber;

        $total_bolt = number_format(($bolt_activities->sum('net') - $bolt_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);
        $total_uber = number_format(($uber_activities->sum('net') - $uber_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2);

        $bolt_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;
        $uber_tip_percent = $driver ? 100 - $driver->contract_vat->tips : 100;

        $bolt_tip_after_vat = number_format($total_tips_bolt * ($bolt_tip_percent / 100), 2);
        $uber_tip_after_vat = number_format($total_tips_uber * ($uber_tip_percent / 100), 2);

        $total_tip_after_vat = $bolt_tip_after_vat + $uber_tip_after_vat;

        $total = $total_earnings + $total_tips;
        $total_after_vat = $total_earnings_after_vat + $total_tip_after_vat;

        $gross_credits = $total_earnings_no_tip + $total_tips + $refund;
        $gross_debts = ($total_earnings_no_tip - $total_earnings_after_vat) + ($total_tips - $total_tip_after_vat) + $deduct;

        $final_total = $gross_credits - $gross_debts;

        $electric_racio = null;
        $combustion_racio = null;

        if ($electric_expenses && $total_earnings > 0) {
            $final_total = $final_total - $electric_expenses['value'];
            $gross_debts = $gross_debts + $electric_expenses['value'];
            if ($electric_expenses['value'] > 0) {
                $electric_racio = ($electric_expenses['value'] / $total_earnings) * 100;
            } else {
                $electric_racio = 0;
            }
        }
        if ($combustion_expenses && $total_earnings > 0) {
            $final_total = $final_total - $combustion_expenses['value'];
            $gross_debts = $gross_debts + $combustion_expenses['value'];
            if ($combustion_expenses['value'] > 0) {
                $combustion_racio = ($combustion_expenses['value'] / $total_earnings) * 100;
            } else {
                $combustion_racio = 0;
            }
        }

        if ($driver && $driver->contract_vat->percent && $driver->contract_vat->percent > 0) {
            $txt_admin = ($final_total * $driver->contract_vat->percent) / 100;
            $gross_debts = $gross_debts + $txt_admin;
            $final_total = $final_total - $txt_admin;
        } else {
            $txt_admin = 0;
        }

        $team_results = [];
        $team_gross_credits = [];
        $team_liquid_credits = [];
        $team_final_total = [];

        if ($driver_id != 0 && $driver->team->count() > 0) {
            foreach ($driver->team as $team) {
                foreach ($team->drivers as $team_driver) {
                    $r = CurrentAccount::where([
                        'tvde_week_id' => $tvde_week_id,
                        'driver_id' => $team_driver->id
                    ])->first();
                    if ($r) {
                        $d = json_decode($r->data);
                        $d->total_after_vat = round((($driver->contract_type->contract_type_ranks[0]->percent * $d->total_earnings) / 100), 2);
                        $team_results[] = $d;
                        $team_gross_credits[] = $d->gross_credits;
                        $team_liquid_credits[] = $d->total_after_vat;
                        $team_final_total[] = $d->final_total;
                    }
                }
            }
        }

        $team_gross_credits = array_sum($team_gross_credits);
        $team_liquid_credits = array_sum($team_liquid_credits);
        $team_final_total = array_sum($team_final_total);
        $team_final_result = 0;

        return compact([
            'company_id',
            'tvde_week_id',
            'driver_id',
            'total_earnings_uber',
            'contract_type_rank',
            'total_uber',
            'total_earnings_bolt',
            'total_bolt',
            'total_tips_uber',
            'uber_tip_percent',
            'uber_tip_after_vat',
            'total_tips_bolt',
            'bolt_tip_percent',
            'bolt_tip_after_vat',
            'total_tips',
            'total_tip_after_vat',
            'adjustments',
            'adjustments_array',
            'total_earnings',
            'total_earnings_no_tip',
            'total',
            'total_after_vat',
            'gross_credits',
            'gross_debts',
            'final_total',
            'driver',
            'electric_expenses',
            'combustion_expenses',
            'combustion_racio',
            'electric_racio',
            'total_earnings_after_vat',
            'txt_admin',
            'team_gross_credits',
            'team_liquid_credits',
            'team_final_total',
            'team_final_result',
            'team_results'
        ]);
    }

    /**
     * Return electric top-ups deduplicated by card+amount+total.
     */
    protected function uniqueElectricTransactions(int $tvde_week_id, string $cardCode)
    {
        return ElectricTransaction::where([
            'tvde_week_id' => $tvde_week_id,
            'card' => $cardCode,
        ])->get()
            ->unique(function ($transaction) {
                return sprintf('%s|%s|%s', $transaction->card, $transaction->amount, $transaction->total);
            })
            ->values();
    }

    /**
     * Return combustion refuels deduplicated by card+amount+total.
     */
    protected function uniqueCombustionTransactions(int $tvde_week_id, array $cardCodes = [])
    {
        $query = CombustionTransaction::where('tvde_week_id', $tvde_week_id);

        if (!empty($cardCodes)) {
            $query->whereIn('card', $cardCodes);
        }

        return $query->get()
            ->unique(function ($transaction) {
                $timestamp = $transaction->date ?? $transaction->created_at;
                return sprintf(
                    '%s|%s|%s|%s',
                    $transaction->card,
                    $transaction->amount,
                    $transaction->total,
                    (string) $timestamp
                );
            })
            ->values();
    }

    protected function uniqueCombustionTransactionsForDriver(int $tvde_week_id, Driver $driver, array $cardCodes = [])
    {
        return CombustionTransaction::where('tvde_week_id', $tvde_week_id)
            ->where(function ($query) use ($driver, $cardCodes) {
                $query->where('driver_id', $driver->id);

                if (!empty($cardCodes)) {
                    $query->orWhere(function ($legacyQuery) use ($cardCodes) {
                        $legacyQuery->whereNull('driver_id')
                            ->whereNull('vehicle_item_id')
                            ->whereIn('card', $cardCodes);
                    });
                }
            })
            ->get()
            ->unique(function ($transaction) {
                $timestamp = $transaction->date ?? $transaction->created_at;
                return sprintf(
                    '%s|%s|%s|%s',
                    $transaction->card,
                    $transaction->amount,
                    $transaction->total,
                    (string) $timestamp
                );
            })
            ->values();
    }

    protected function driverCarTrackDetails(int $driverId, int $tvdeWeekId)
    {
        $tvdeWeek = TvdeWeek::find($tvdeWeekId);

        if (!$tvdeWeek || !$driverId) {
            return collect();
        }

        return CarTrack::query()
            ->where('tvde_week_id', $tvdeWeek->id)
            ->where('driver_id', $driverId)
            ->where('assignment_status', CarTrack::STATUS_ASSIGNED)
            ->orderBy('date')
            ->get(['date', 'value', 'license_plate'])
            ->map(fn (CarTrack $carTrack) => [
                'date' => $carTrack->date,
                'value' => (float) $carTrack->value,
                'license_plate' => $carTrack->license_plate,
                'signature' => sprintf('%s|%s|%s', (string) $carTrack->date, (string) $carTrack->license_plate, (string) $carTrack->value),
            ])
            ->values();
    }

    protected function previousDriverBalanceBeforeWeek(int $driverId, ?TvdeWeek $week): ?DriversBalance
    {
        if (!$week) {
            return null;
        }

        $weekStart = Carbon::parse($week->getRawOriginal('start_date') ?: $week->start_date)->toDateString();

        return DriversBalance::query()
            ->select('drivers_balances.*')
            ->join('tvde_weeks', 'drivers_balances.tvde_week_id', '=', 'tvde_weeks.id')
            ->where('drivers_balances.driver_id', $driverId)
            ->where('tvde_weeks.start_date', '<', $weekStart)
            ->orderByDesc('tvde_weeks.start_date')
            ->orderByDesc('drivers_balances.id')
            ->first();
    }

    /**
     * CarHire is the single source of truth for rental proration (civil days).
     */
    protected function calculateCarHireForWeek(Driver $driver, TvdeWeek $week): array
    {
        $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();

        $contracts = CarHire::where('driver_id', $driver->id)
            ->where(function ($query) use ($weekEnd) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $weekEnd);
            })
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $weekStart);
            })
            ->orderBy('start_date')
            ->get();

        $days = [];
        $totalCharged = 0.0;
        $daysCharged = 0;

        foreach ($contracts as $contract) {
            $contractStart = $contract->start_date
                ? Carbon::parse($contract->start_date)->startOfDay()
                : $weekStart;
            $contractEnd = $contract->end_date
                ? Carbon::parse($contract->end_date)->endOfDay()
                : $weekEnd;

            $effectiveStart = $contractStart->greaterThan($weekStart) ? $contractStart : $weekStart;
            $effectiveEnd = $contractEnd->lessThan($weekEnd) ? $contractEnd : $weekEnd;

            if ($effectiveEnd->lt($effectiveStart)) {
                continue;
            }

            $daysInContract = $effectiveStart->diffInDays($effectiveEnd) + 1;
            $dailyValue = ((float) $contract->amount) / 7;
            $totalCharged += $dailyValue * $daysInContract;
            $daysCharged += $daysInContract;
        }

        for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $activeContract = null;
            $activeStart = null;

            foreach ($contracts as $contract) {
                $contractStart = $contract->start_date
                    ? Carbon::parse($contract->start_date)->startOfDay()
                    : $weekStart;
                $contractEnd = $contract->end_date
                    ? Carbon::parse($contract->end_date)->endOfDay()
                    : $weekEnd;

                if ($contractStart->gt($dayEnd) || $contractEnd->lt($dayStart)) {
                    continue;
                }

                if (!$activeContract || ($activeStart && $contractStart->gt($activeStart))) {
                    $activeContract = $contract;
                    $activeStart = $contractStart;
                }
            }

            if ($activeContract) {
                $dailyValue = ((float) $activeContract->amount) / 7;
                $days[] = [
                    'date' => $dayStart->toDateString(),
                    'car_hire_id' => $activeContract->id,
                    'weekly_amount' => (float) $activeContract->amount,
                    'daily_amount' => $dailyValue,
                    'charged_value' => $dailyValue,
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
            'total' => $totalCharged,
            'breakdown' => [
                'days' => $days,
                'total_charged' => $totalCharged,
                'days_charged' => $daysCharged,
                'total_value' => $totalCharged,
            ],
        ];
    }

    public function filter($state_id = 1)
    {
        $mainCompanyId = Company::where('main', true)->value('id');
        $sessionCompanyId = session()->get('company_id');
        $userCompanyId = auth()->check() ? optional(auth()->user()->company)->id : null;

        $company_id = $sessionCompanyId ?: $userCompanyId ?: $mainCompanyId;

        if (!$company_id || !Company::whereKey($company_id)->exists()) {
            $company_id = $mainCompanyId;
        }

        session()->put('company_id', $company_id);

        $tvde_year_id = session()->get('tvde_year_id') ? session()->get('tvde_year_id') : $tvde_year_id = TvdeYear::orderBy('name', 'desc')->first()->id;
        if (session()->has('tvde_month_id')) {
            $tvde_month_id = session()->get('tvde_month_id');
        } else {
            $tvde_month = TvdeMonth::where('year_id', $tvde_year_id)
                ->whereHas('weeks', function ($week) use ($company_id) {
                    $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                        $tvdeActivity->where('company_id', $company_id);
                    });
                })
                ->withMax('weeks', 'start_date')
                ->orderByDesc('weeks_max_start_date')
                ->first();
            if ($tvde_month) {
                $tvde_month_id = $tvde_month->id;
            } else {
                $tvde_month_id = 0;
            }
        }
        if (session()->has('tvde_week_id')) {
            $tvde_week_id = session()->get('tvde_week_id');
        } else {
            $tvde_week = TvdeWeek::has('tvdeActivities')
                ->where('tvde_month_id', $tvde_month_id)
                ->orderByDesc('start_date')
                ->first();
            if ($tvde_week) {
                $tvde_week_id = $tvde_week->id;
                session()->put('tvde_week_id', $tvde_week->id);
            } else {
                $tvde_week_id = 1;
            }
        }

        $tvde_years = TvdeYear::orderBy('name')
            ->whereHas('months', function ($month) use ($company_id) {
                $month->whereHas('weeks', function ($week) use ($company_id) {
                    $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                        $tvdeActivity->where('company_id', $company_id);
                    });
                });
            })
            ->get();
        $tvde_months = TvdeMonth::where('year_id', $tvde_year_id)
            ->whereHas('weeks', function ($week) use ($company_id) {
                $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                    $tvdeActivity->where('company_id', $company_id);
                });
            })
            ->withMin('weeks', 'start_date')
            ->orderBy('weeks_min_start_date', 'asc')
            ->get();

        $tvde_weeks = TvdeWeek::where('tvde_month_id', $tvde_month_id)
            ->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                $tvdeActivity->where('company_id', $company_id);
            })
            ->orderBy('start_date', 'asc')
            ->get();

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $drivers = Driver::where('company_id', $company_id)->where('state_id', $state_id)->orderBy('name')->get()->load('team');

        return [
            'company_id' => $company_id,
            'tvde_year_id' => $tvde_year_id,
            'tvde_years' => $tvde_years,
            'tvde_week_id' => $tvde_week_id,
            'tvde_week' => $tvde_week,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'drivers' => $drivers,
        ];
    }

    public function saveCompanyExpenses($company_id, $tvde_week_id)
    {
        $tvde_week = TvdeWeek::find($tvde_week_id);

        $company_expenses = CompanyExpense::where([
            'company_id' => $company_id,
        ])
            ->where('start_date', '<=', $tvde_week->start_date)
            ->where('end_date', '>=', $tvde_week->end_date)
            ->get();

        $company_expenses = $company_expenses->map(function ($expense) {
            $expense->total = $expense->qty * $expense->weekly_value;
            return $expense;
        });

        $total_company_expenses = [];

        foreach ($company_expenses as $company_expense) {
            $total_company_expenses[] = $company_expense->total;
        }

        $total_company_expenses = array_sum($total_company_expenses);

        $company_park = CompanyPark::where('tvde_week_id', $tvde_week_id)
            ->where('company_id', $company_id)
            ->sum('value');

        $tvde_week = TvdeWeek::find($tvde_week_id);

        $consultancy = Consultancy::where('company_id', $company_id)
            ->where('start_date', '<=', $tvde_week->start_date)
            ->where('end_date', '>=', $tvde_week->end_date)
            ->first();

        $totals = $this->getWeekReport($company_id, $tvde_week_id)['totals'];

        $company = Company::find($company_id);

        $total_consultancy = 0;

        if ($consultancy && !$company->main) {

            $total_consultancy = ($totals['total_operators'] * $consultancy->value) / 100;
        }

        //GET EARNINGS FROM OTHER COMPANIES

        $fleet_adjusments = 0;
        $fleet_consultancies = 0;
        $fleet_company_parks = 0;
        $fleet_earnings = 0;

        if ($company && $company->main) {

            $current_accounts = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id
            ])->get();

            $fleet_adjustments = [];

            foreach ($current_accounts as $current_account) {
                $data = json_decode($current_account->data);
                foreach ($data->adjustments as $fleet_adjustment) {
                    if ($fleet_adjustment->fleet_management == true) {
                        if ($fleet_adjustment->type == 'refund') {
                            $fleet_adjustments[] = (-$fleet_adjustment->amount);
                        } else {
                            $fleet_adjustments[] = $fleet_adjustment->amount;
                        }
                    }
                }
            }

            $fleet_adjusments = array_sum($fleet_adjustments);

            $companies = Company::whereHas('tvde_activities', function ($tvde_activity) use ($tvde_week_id) {
                $tvde_activity->where('tvde_week_id', $tvde_week_id);
            })
                ->get();

            $fleet_consultancies = [];

            foreach ($companies as $company) {
                $fleet_consultancy = Consultancy::where('company_id', $company->id)
                    ->where('start_date', '<=', $tvde_week->start_date)
                    ->where('end_date', '>=', $tvde_week->end_date)
                    ->first();
                $earnings = TvdeActivity::where([
                    'company_id' => $company->id,
                    'tvde_week_id' => $tvde_week_id,
                ])
                    ->sum('net');

                if ($fleet_consultancy && $fleet_consultancy->value && $earnings) {
                    $fleet_consultancies[] = ($earnings * $fleet_consultancy->value) / 100;
                }
            }

            $fleet_consultancies = array_sum($fleet_consultancies);

            $fleet_company_parks = CompanyPark::where([
                'tvde_week_id' => $tvde_week->id,
                'fleet_management' => true
            ])->sum('value');

            $fleet_earnings = $fleet_adjusments + $fleet_consultancies + $fleet_company_parks;
        }

        ////////////////////////////////

        $final_total = $total_company_expenses - $totals['total_company_adjustments'] + $company_park + $totals['total_drivers'] + $total_consultancy;

        //$final_total = $totals['total_company_adjustments'];

        $final_company_expenses = $total_company_expenses - $totals['total_company_adjustments'] + $company_park - $total_consultancy;

        $profit = $totals['total_operators'] - $final_total + $fleet_earnings;

        if ($totals['total_operators'] > 0) {
            $roi = ($profit / ($totals['total_operators'] + $fleet_earnings)) * 100;
        } else {
            $roi = 0;
        }

        $data = [
            'company_expenses' => $company_expenses,
            'total_company_expenses' => $total_company_expenses,
            'totals' => $totals,
            'company_park' => $company_park,
            'final_total' => $final_total,
            'final_company_expenses' => $final_company_expenses,
            'profit' => $profit,
            'roi' => $roi,
            'total_consultancy' => $total_consultancy,
            'fleet_adjusments' => $fleet_adjusments,
            'fleet_consultancies' => $fleet_consultancies,
            'fleet_company_parks' => $fleet_company_parks,
            'fleet_earnings' => $fleet_earnings
        ];

        $company_data = new CompanyData;
        $company_data->company_id = $company_id;
        $company_data->tvde_week_id = $tvde_week_id;
        $company_data->data = json_encode($data);
        $company_data->save();
    }
}


























