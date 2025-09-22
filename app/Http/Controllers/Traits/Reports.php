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

        $total_operators = [];
        $total_earnings_after_discount = [];
        $total_fuel_transactions = [];
        $total_adjustments = [];
        $total_fleet_management = [];
        $total_drivers = [];
        $total_company_adjustments = [];
        $total_vat_value = [];
        $total_earnings_after_vat = [];
        $total_car_track = [];
        $total_car_hire = [];
        $total_net_operators = [];

        foreach ($drivers as $driver) {

            $uber_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 1,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->uber_uuid
            ])
                ->get();

            $uber_gross = $uber_activities->sum('gross');
            $uber_net = $uber_activities->sum('net');

            $bolt_activities = TvdeActivity::where([
                'company_id' => $company_id,
                'tvde_operator_id' => 2,
                'tvde_week_id' => $tvde_week_id,
                'driver_code' => $driver->bolt_name
            ])
                ->get();

            $bolt_gross = $bolt_activities->sum('gross');
            $bolt_net = $bolt_activities->sum('net');

            //EARNINGS

            $uber = collect([
                'uber_gross' => $uber_gross,
                'uber_net' => $uber_net,
            ]);

            $bolt = collect([
                'bolt_gross' => $bolt_gross,
                'bolt_net' => $bolt_net,
            ]);

            $gross_total = $uber_gross + $bolt_gross;
            $net_total = $uber_net + $bolt_net;

            //CONTRACT VAT

            $vat = $driver->contract_vat->percent;
            $vat_factor = ($vat / 100) + 1;
            $earnings_after_discount = ($gross_total / $vat_factor);
            $vat_value = $gross_total - $earnings_after_discount;

            $total_after_vat = $net_total - $vat_value;

            //FUEL

            $fuel_transactions = 0;

            if ($driver->electric) {
                $electric_transactions = ElectricTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->electric->code
                ])
                    ->sum('total');

                if ($electric_transactions > 0) {
                    $fuel_transactions = $electric_transactions;
                }
            }

            if ($driver->cards) {
                $fuel_transactions = [];
                foreach ($driver->cards as $card) {
                    $combustion_transactions = CombustionTransaction::where([
                        'tvde_week_id' => $tvde_week_id,
                        'card' => $card->code
                    ])
                        ->sum('total');

                    if ($combustion_transactions > 0) {
                        $fuel_transactions[] = $combustion_transactions;
                    }
                }
                $fuel_transactions = array_sum($fuel_transactions);
            } elseif ($driver->card) {
                $combustion_transactions = CombustionTransaction::where([
                    'tvde_week_id' => $tvde_week_id,
                    'card' => $driver->card->code
                ])
                    ->sum('total');

                if ($combustion_transactions > 0) {
                    $fuel_transactions = $combustion_transactions;
                }
            }

            if($driver->half_tolls && $fuel_transactions > 0) {
                $fuel_transactions = $fuel_transactions / 2;
            }

            $driver->fuel = $fuel_transactions;

            //TESLA

            $tesla_total = 0;

            // Buscar todos carregamentos Tesla na semana
            $tesla_chargings = TeslaCharging::whereBetween('datetime', [$tvde_week->start_date, $tvde_week->end_date])->get();

            foreach ($tesla_chargings as $charging) {
                // Procurar VehicleUsage do carro do carregamento na data do charging
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

                // Se usage encontrado e pertence a este driver, soma valor
                if ($usage && $usage->driver_id === $driver->id) {
                    $tesla_total += $charging->value;
                }
            }

            // Soma TeslaCharging ao total de combustível do driver
            $driver->fuel += $tesla_total;

            $total_fuel_transactions[] = $driver->fuel;

            //CAR HIRE

            $car_hire = CarHire::where([
                'driver_id' => $driver->id,
            ])
                ->where(function ($query) use ($tvde_week) {
                    $query->where('start_date', '<=', $tvde_week->start_date)
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) use ($tvde_week) {
                    $query->where('end_date', '>=', $tvde_week->end_date)
                        ->orWhereNull('end_date');
                })
                ->first();

            //ADJUSTMENTS
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
                        $fleet_management[] = $adjustment->amount;
                    } else {
                        $deducts[] = $adjustment->amount;
                    }
                } else {
                    if ($adjustment->fleet_management) {
                        $fleet_management[] = (-$adjustment->amount);
                    } else {
                        $refunds[] = $adjustment->amount;
                    }
                }
                if ($adjustment->company_expense) {
                    if ($adjustment->type == 'deduct') {
                        $company_expense[] = -$adjustment->amount;
                    } else {
                        $company_expense[] = $adjustment->amount;
                    }
                }
            }

            $refunds = array_sum($refunds);
            $deducts = array_sum($deducts);
            $adjustments = $refunds - $deducts;

            $total_adjustments[] = $adjustments;

            $fleet_management = array_sum($fleet_management);

            $total_fleet_management[] = $fleet_management;

            $total_company_adjustments[] = array_sum($company_expense);

            // --- CAR TRACK (Via Verde) atribuído ao motorista ativo na data ---
            $car_track = 0.0;

            if ($tvde_week->id) {
                $car_track = \DB::table('car_tracks as ct')
                    ->join('vehicle_items as vi', 'vi.license_plate', '=', 'ct.license_plate')
                    ->join('vehicle_usages as vu', 'vu.vehicle_item_id', '=', 'vi.id')
                    ->where('ct.tvde_week_id', $tvde_week->id)          // registos CarTrack dessa semana
                    ->where('vu.driver_id', $driver->id)                 // atribuir ao teu motorista
                    // motorista ativo nessa viatura na data do CarTrack
                    ->whereColumn('vu.start_date', '<=', 'ct.date')
                    ->where(function ($q) {
                        $q->whereNull('vu.end_date')
                            ->orWhereColumn('vu.end_date', '>=', 'ct.date');
                    })
                    // (Opcional) Contar apenas utilização "real"
                    ->where(function ($q) {
                        $q->whereNull('vu.usage_exceptions')
                            ->orWhere('vu.usage_exceptions', 'usage');
                    })
                    ->sum('ct.value');

            }

            $earnings = collect([
                'uber' => $uber,
                'bolt' => $bolt,
                'total_gross' => $gross_total,
                'total_net' => $net_total,
                'car_track' => $car_track ?? 0,
                'vat_value' => $vat_value,
                'total_after_vat' => $total_after_vat,
                'adjustments' => $adjustments,
                'fuel_transactions' => $driver->fuel,
                'car_hire' => $car_hire ? $car_hire->amount : 0,
                'company_expense' => $total_company_adjustments,
                'adjustments_array' => $adjustments_array
            ]);

            $driver->earnings = $earnings;
            $driver->refunds = $refunds;
            $driver->adjustments = $adjustments;
            $driver->fleet_management = $fleet_management;

            //BALANCE
            $driver_balance = DriversBalance::where('driver_id', $driver->id)->orderBy('id', 'desc')->first();

            $driver->balance = $driver_balance ? $driver_balance->drivers_balance : 0;

            $driver->total = $total_after_vat - $driver->fuel + $adjustments - $fleet_management - $driver->earnings['car_track'] - ($car_hire ? $car_hire->amount : 0);

            $driver->final_total = $driver->total;

            $driver->final_total_balance = $driver->final_total + $driver->balance;

            $earnings['total'] = $driver->total;

            $gross_uber[] = $uber_gross;
            $gross_bolt[] = $bolt_gross;
            $total_operators[] = $gross_total;
            $total_net_operators[] = $net_total ?? 0;
            $net_uber[] = $uber_net;
            $net_bolt[] = $bolt_net;
            $total_earnings_after_discount[] = $earnings_after_discount;
            $total_drivers[] = $driver->total;
            $total_vat_value[] = $vat_value;
            $total_earnings_after_vat[] = $total_after_vat;
            $total_car_track[] = $driver->earnings['car_track'];
            $total_car_hire[] = $car_hire ? $car_hire->amount : 0;

            $current_account = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            if ($current_account) {
                $driver->current_account = true;
            } else {
                $driver->current_account = false;
            }
        }

        $totals = collect([
            'gross_uber' => isset($gross_uber) ? array_sum($gross_uber) : 0,
            'gross_bolt' => isset($gross_bolt) ? array_sum($gross_bolt) : 0,
            'net_uber' => isset($net_uber) ? array_sum($net_uber) : 0,
            'net_bolt' => isset($net_bolt) ? array_sum($net_bolt) : 0,
            'total_operators' => array_sum($total_operators),
            'total_earnings_after_discount' => array_sum($total_earnings_after_discount),
            'total_fuel_transactions' => array_sum($total_fuel_transactions),
            'total_adjustments' => array_sum($total_adjustments),
            'total_fleet_management' => array_sum($total_fleet_management),
            'total_drivers' => array_sum($total_drivers),
            'total_company_adjustments' => array_sum($total_company_adjustments),
            'total_vat_value' => array_sum($total_vat_value),
            'total_net_operators' => array_sum($total_net_operators),
            'total_earnings_after_vat' => array_sum($total_earnings_after_vat),
            'total_car_track' => array_sum($total_car_track),
            'total_car_hire' => array_sum($total_car_hire),
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
