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

        // Totais (mantendo compatibilidade com a versão anterior)
        $total_operators = [];
        $total_earnings_after_discount = []; // legado (calculado como antes, a partir de gross_total e VAT)
        $total_fuel_transactions = [];
        $total_adjustments = [];
        $total_fleet_management = [];
        $total_drivers = [];
        $total_company_adjustments = [];
        $total_vat_value = [];
        $total_earnings_after_vat = []; // compat: soma de total_after_vat (alias do after_vat)
        $total_car_track = [];
        $total_car_hire = [];
        $total_net_operators = [];

        // Novos agregados úteis
        $gross_uber = [];
        $gross_bolt = [];
        $net_uber = [];
        $net_bolt = [];

        // Novos totais de tips e etapas do pipeline
        $uber_tips_total = [];
        $bolt_tips_total = [];
        $tips_total_all = [];
        $total_base_before_vat = [];
        $total_after_vat_arr = [];           // novo (after_vat)
        $total_after_vat_plus_tips = [];     // after_vat + tips

        foreach ($drivers as $driver) {

            // ---------- Atividades UBER ----------
            $uber_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 1,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->uber_uuid
            ])->get();

            $uber_gross = (float) $uber_activities->sum('gross');
            $uber_net   = (float) $uber_activities->sum('net');
            // tips podem vir null em alguns registos -> tratamos como 0
            $uber_tips  = (float) $uber_activities->sum(function ($a) {
                return $a->tips ?? 0;
            });

            // ---------- Atividades BOLT ----------
            $bolt_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 2,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->bolt_name
            ])->get();

            $bolt_gross = (float) $bolt_activities->sum('gross');
            $bolt_net   = (float) $bolt_activities->sum('net');
            $bolt_tips  = (float) $bolt_activities->sum(function ($a) {
                return $a->tips ?? 0;
            });

            // EARNINGS (por operador)
            $uber = collect([
                'uber_gross' => $uber_gross,
                'uber_net'   => $uber_net,
                'uber_tips'  => $uber_tips,
            ]);

            $bolt = collect([
                'bolt_gross' => $bolt_gross,
                'bolt_net'   => $bolt_net,
                'bolt_tips'  => $bolt_tips,
            ]);

            $gross_total = $uber_gross + $bolt_gross;
            $net_total   = $uber_net   + $bolt_net;

            // ---------- FUEL (combustão/eléctrico) ----------
            $fuel_transactions = 0;

            if ($driver->electric) {
                $electric_transactions = (float) ElectricTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->electric->code
                ])->sum('total');

                if ($electric_transactions > 0) {
                    $fuel_transactions = $electric_transactions;
                }
            }

            if ($driver->cards) {
                $fuel_sum_list = [];
                foreach ($driver->cards as $card) {
                    $combustion_transactions = (float) CombustionTransaction::where([
                        'tvde_week_id' => $tvde_week_id,
                        'card' => $card->code
                    ])->sum('total');

                    if ($combustion_transactions > 0) {
                        $fuel_sum_list[] = $combustion_transactions;
                    }
                }
                $fuel_transactions = array_sum($fuel_sum_list);
            } elseif ($driver->card) {
                $combustion_transactions = (float) CombustionTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->card->code
                ])->sum('total');

                if ($combustion_transactions > 0) {
                    $fuel_transactions = $combustion_transactions;
                }
            }

            if ($driver->half_tolls && $fuel_transactions > 0) {
                $fuel_transactions = $fuel_transactions / 2;
            }

            // ---------- TESLA ----------
            $tesla_total = 0.0;

            $tesla_chargings = TeslaCharging::whereBetween('datetime', [$tvde_week->start_date, $tvde_week->end_date])->get();

            foreach ($tesla_chargings as $charging) {
                $usage = VehicleUsage::whereHas('vehicle_item', function ($query) use ($charging) {
                    $query->whereRaw('REPLACE(UPPER(license_plate), "-", "") = ?', [
                        str_replace('-', '', strtoupper($charging->license))
                    ]);
                })
                    ->where('start_date', '<=', $charging->datetime)
                    ->where(function ($query) use ($charging) {
                        $query->where('end_date', '>=', $charging->datetime)
                            ->orWhereNull('end_date');
                    })
                    ->first();

                if ($usage && $usage->driver_id === $driver->id) {
                    $tesla_total += (float) $charging->value;
                }
            }

            // Garantir número em fuel
            $driver->fuel = (float) $fuel_transactions + (float) $tesla_total;
            $total_fuel_transactions[] = $driver->fuel;

            // ---------- CAR HIRE ----------
            $car_hire = CarHire::where(['driver_id' => $driver->id])
                ->where(function ($query) use ($tvde_week) {
                    $query->where('start_date', '<=', $tvde_week->start_date)
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($tvde_week) {
                    $query->where('end_date', '>=', $tvde_week->end_date)
                        ->orWhereNull('end_date');
                })
                ->first();

            $rent_value = $car_hire ? (float) $car_hire->amount : 0.0;

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

            $refunds = [];
            $deducts = [];
            $fleet_management = [];
            $company_expense = [];

            foreach ($adjustments_array as $adjustment) {
                if ($adjustment->type == 'deduct') {
                    if ($adjustment->fleet_management) {
                        $fleet_management[] = (float) $adjustment->amount;
                    } else {
                        $deducts[] = (float) $adjustment->amount;
                    }
                } else {
                    if ($adjustment->fleet_management) {
                        $fleet_management[] = (float) -$adjustment->amount;
                    } else {
                        $refunds[] = (float) $adjustment->amount;
                    }
                }

                if ($adjustment->company_expense) {
                    if ($adjustment->type == 'deduct') {
                        $company_expense[] = (float) -$adjustment->amount;
                    } else {
                        $company_expense[] = (float) $adjustment->amount;
                    }
                }
            }

            $refunds = array_sum($refunds);
            $deducts = array_sum($deducts);
            $adjustments = $refunds - $deducts;

            $fleet_management = array_sum($fleet_management);
            $total_adjustments[] = $adjustments;
            $total_fleet_management[] = $fleet_management;
            $total_company_adjustments[] = array_sum($company_expense);

            // ---------- CAR TRACK (Via Verde) ----------
            $car_track = 0.0;
            if ($tvde_week->id) {
                $car_track = (float) \DB::table('car_tracks as ct')
                    ->join('vehicle_items as vi', 'vi.license_plate', '=', 'ct.license_plate')
                    ->join('vehicle_usages as vu', 'vu.vehicle_item_id', '=', 'vi.id')
                    ->where('ct.tvde_week_id', $tvde_week->id)
                    ->where('vu.driver_id', $driver->id)
                    ->whereColumn('vu.start_date', '<=', 'ct.date')
                    ->where(function ($q) {
                        $q->whereNull('vu.end_date')
                            ->orWhereColumn('vu.end_date', '>=', 'ct.date');
                    })
                    ->where(function ($q) {
                        $q->whereNull('vu.usage_exceptions')
                            ->orWhere('vu.usage_exceptions', 'usage');
                    })
                    ->sum('ct.value');
            }

            // =======================
            // PIPELINE NOVO COM TIPS
            // =======================
            $tips_total = $uber_tips + $bolt_tips;

            // 1) Base: líquidos uber+bolt
            $base_liquida = $net_total;

            // 2) Retirar tips e abastecimento
            $base_before_vat = $base_liquida - $tips_total - $driver->fuel;

            // 3) Calcular e retirar IVA (usando percent do contract_vat; se faltar, 0%)
            $vat = $driver->contract_vat ? (float) $driver->contract_vat->percent : 0.0;
            $vat_factor = ($vat / 100) + 1;

            // Evitar divisão por zero
            $after_vat = ($vat_factor > 0) ? ($base_before_vat / $vat_factor) : $base_before_vat;
            $vat_value = $base_before_vat - $after_vat;

            // Alias para compatibilidade com código que ainda lê "total_after_vat"
            $total_after_vat_alias = $after_vat;

            // 4) Somar novamente as tips
            $subtotal_after_tips = $after_vat + $tips_total;

            // 5) Retirar rent e Via Verde
            // 6) Processar ajustes e subtrair fleet_management
            $final_total = $subtotal_after_tips + $adjustments - $fleet_management - $car_track - $rent_value;

            // ---------- LEGADO: earnings_after_discount (como dantes, a partir do gross_total e VAT) ----------
            // Isto mantém compatibilidade com o total_earnings_after_discount original.
            $legacy_vat_factor = ($vat / 100) + 1;
            $earnings_after_discount = ($legacy_vat_factor > 0) ? ($gross_total / $legacy_vat_factor) : $gross_total;

            // ---------- Guardar breakdown no driver ----------
            $earnings = collect([
                'uber' => $uber,
                'bolt' => $bolt,
                'total_gross' => $gross_total,
                'total_net' => $net_total,

                // Tips e etapas do pipeline
                'tips_total' => $tips_total,
                'base_before_vat' => $base_before_vat,
                'vat_value' => $vat_value,
                'after_vat' => $after_vat,                   // novo
                'total_after_vat' => $total_after_vat_alias, // alias compat
                'subtotal_after_tips' => $subtotal_after_tips,

                // Custos e ajustes
                'car_track' => $car_track,
                'fuel_transactions' => $driver->fuel,
                'car_hire' => $rent_value,
                'adjustments' => $adjustments,
                'fleet_management' => $fleet_management,
                'company_expense' => array_sum($company_expense),

                // Legado
                'earnings_after_discount' => $earnings_after_discount,
                'adjustments_array' => $adjustments_array,
            ]);

            $driver->earnings = $earnings;
            $driver->refunds = $refunds;
            $driver->adjustments = $adjustments;
            $driver->fleet_management = $fleet_management;

            // BALANCE
            $driver_balance = DriversBalance::where('driver_id', $driver->id)->orderBy('id', 'desc')->first();
            $driver->balance = $driver_balance ? (float) $driver_balance->drivers_balance : 0.0;

            // Totais finais do driver (pipeline novo)
            $driver->total = $final_total;
            $driver->final_total = $driver->total;
            $driver->final_total_balance = $driver->final_total + $driver->balance;

            // ---------- Alimentar arrays de totais ----------
            $gross_uber[] = $uber_gross;
            $gross_bolt[] = $bolt_gross;
            $net_uber[] = $uber_net;
            $net_bolt[] = $bolt_net;

            $total_operators[] = $gross_total;
            $total_net_operators[] = $net_total;
            $total_vat_value[] = $vat_value;
            $total_car_track[] = $car_track;
            $total_car_hire[] = $rent_value;
            $total_drivers[] = $driver->total;

            // Novos totais
            $uber_tips_total[] = $uber_tips;
            $bolt_tips_total[] = $bolt_tips;
            $tips_total_all[]  = $tips_total;
            $total_base_before_vat[] = $base_before_vat;
            $total_after_vat_arr[]   = $after_vat;
            $total_after_vat_plus_tips[] = $subtotal_after_tips;

            // Compat com chave antiga total_earnings_after_discount
            $total_earnings_after_discount[] = $earnings_after_discount;

            // Compat com chave antiga total_earnings_after_vat
            $total_earnings_after_vat[] = $total_after_vat_alias;

            // current_account flag
            $current_account = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            $driver->current_account = (bool) $current_account;
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
            'tips_total'      => array_sum($tips_total_all),

            // Pipeline
            'total_base_before_vat'     => array_sum($total_base_before_vat),
            'total_vat_value'           => array_sum($total_vat_value),
            'total_after_vat'           => array_sum($total_after_vat_arr),      // novo
            'total_earnings_after_vat'  => array_sum($total_earnings_after_vat), // compat (alias)
            'total_after_vat_plus_tips' => array_sum($total_after_vat_plus_tips),

            // Custos/Ajustes
            'total_fuel_transactions' => array_sum($total_fuel_transactions),
            'total_adjustments'       => array_sum($total_adjustments),
            'total_fleet_management'  => array_sum($total_fleet_management),
            'total_car_track'         => array_sum($total_car_track),
            'total_car_hire'          => array_sum($total_car_hire),

            // Total final (após tudo)
            'total_drivers' => array_sum($total_drivers),

            // Legado (compat)
            'total_earnings_after_discount' => array_sum($total_earnings_after_discount),
            'total_company_adjustments'     => array_sum($total_company_adjustments),
        ]);

        return [
            'drivers' => $drivers,
            'totals' => $totals,
        ];
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
            'driver_code' => $driver->bolt_name,
            'company_id' => $company_id,
        ])
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
            if (!$card) {
                $code = 0;
            } else {
                $code = $card->code;
            }
            $combustion_transactions = CombustionTransaction::where([
                'card' => $code,
                'tvde_week_id' => $tvde_week_id
            ])->get();
            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_transactions->sum('total'), 2, '.', '') . ' €',
                'value' => $combustion_transactions->sum('total')
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

    public function filter($state_id = 1)
    {

        $company_id = Company::where('main', true)->first()->id;

        $tvde_year_id = session()->get('tvde_year_id') ? session()->get('tvde_year_id') : $tvde_year_id = TvdeYear::orderBy('name', 'desc')->first()->id;
        if (session()->has('tvde_month_id')) {
            $tvde_month_id = session()->get('tvde_month_id');
        } else {
            $tvde_month = TvdeMonth::orderBy('number', 'desc')
                ->whereHas('weeks', function ($week) use ($company_id) {
                    $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                        $tvdeActivity->where('company_id', $company_id);
                    });
                })
                ->where('year_id', $tvde_year_id)
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
                ->orderBy('number', 'desc')
                ->where('tvde_month_id', $tvde_month_id)
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
        $tvde_months = TvdeMonth::orderBy('number', 'asc')
            ->whereHas('weeks', function ($week) use ($company_id) {
                $week->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                    $tvdeActivity->where('company_id', $company_id);
                });
            })
            ->where('year_id', $tvde_year_id)->get();

        $tvde_weeks = TvdeWeek::orderBy('number', 'asc')
            ->whereHas('tvdeActivities', function ($tvdeActivity) use ($company_id) {
                $tvdeActivity->where('company_id', $company_id);
            })
            ->where('tvde_month_id', $tvde_month_id)->get();

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
