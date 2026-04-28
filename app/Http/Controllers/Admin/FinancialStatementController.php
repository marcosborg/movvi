<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DriverFinancialStatementMail;
use App\Models\Adjustment;
use App\Models\Card;
use App\Models\CombustionTransaction;
use App\Models\Company;
use App\Models\ContractTypeRank;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\Electric;
use App\Models\ElectricTransaction;
use App\Models\TvdeActivity;
use App\Models\TvdeMonth;
use App\Models\TvdeWeek;
use App\Models\TvdeYear;
use App\Models\CurrentAccount;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Traits\Reports;

class FinancialStatementController extends Controller
{

    use Reports;

    public function index()
    {

        abort_if(Gate::denies('financial_statement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $drivers = $filter['drivers'];

        $driver_id = session()->get('driver_id') ? session()->get('driver_id') : $driver_id = 0;

        if (!session()->has('company_id')) {
            $company_id = 1;
            session()->put('company_id', $company_id);
        }

        if ($driver_id != 0) {

            $results = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver_id
            ])->first();

            if ($results) {
                $results = json_decode($results->data);
            }

        } else {
            session()->put('driver_id', 540);
            return redirect()->back();
        }

        $driver_balance = DriversBalance::where([
            'driver_id' => $driver_id,
            'tvde_week_id' => $tvde_week_id
        ])->first();

        $car_track_details = $this->driverCarTrackDetails((int) $driver_id, (int) $tvde_week_id);

        //return $results;

        // Prefer the new commission total when available to avoid re-applying expenses.
        $total = $results->driver_total
            ?? $results->total
            ?? (($results->subtotal_after_tips ?? 0)
                - ($results->car_hire ?? 0)
                - ($results->car_track ?? 0)
                + ($results->adjustments ?? 0));

        return view('admin.financialStatements.index')->with([
            'company_id' => $company_id,
            'tvde_year_id' => $tvde_year_id,
            'tvde_years' => $tvde_years,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $drivers,
            'driver_id' => $driver_id,
            'uber_gross' => isset($results) ? $results->uber->uber_gross : 0,
            'bolt_gross' => isset($results) ? $results->bolt->bolt_gross : 0,
            'uber_net' => isset($results) ? $results->uber->uber_net : 0,
            'bolt_net' => isset($results) ? $results->bolt->bolt_net : 0,
            'total_gross' => isset($results) ? $results->total_gross : 0,
            'total_net' => isset($results) ? $results->total_net : 0,
            'adjustments' => isset($results) ? $results->adjustments : 0,
            'general_adjustments' => isset($results) ? ($results->general_adjustments ?? $results->adjustments ?? 0) : 0,
            'rent_discount' => isset($results) ? ($results->abatimento_aluguer ?? 0) : 0,
            'minimum_billing_difference' => isset($results) ? ($results->diferenca_faturacao_minima ?? 0) : 0,
            'caution_received' => isset($results) ? ($results->caucao_recebida ?? 0) : 0,
            'caution_returned' => isset($results) ? ($results->caucao_devolvida ?? 0) : 0,
            'car_hire_base' => isset($results) ? ($results->car_hire_base ?? $results->car_hire ?? 0) : 0,
            'total' => $total ?? 0,
            'vat_value' => isset($results) ? $results->vat_value : 0,
            'iva_value' => isset($results) ? ($results->iva_value ?? 0) : 0,
            'percent_value' => isset($results) ? ($results->percent_value ?? 0) : 0,
            'car_track' => isset($results) ? $results->car_track : 0,
            'car_track_details' => $car_track_details,
            'car_hire' => isset($results) ? $results->car_hire : 0,
            'fuel_transactions' => isset($results) ? $results->fuel_transactions : 0,
            'driver_balance' => $driver_balance ?? null,
            'statement_sent_at' => $results && isset($currentAccount?->statement_sent_at) ? $currentAccount->statement_sent_at : null,
            'statement_sent_to' => $results && isset($currentAccount?->statement_sent_to) ? $currentAccount->statement_sent_to : null,
            'driver_email' => $drivers->firstWhere('id', $driver_id)?->email,
        ]);
    }

    public function year($tvde_year_id)
    {
        session()->put('tvde_year_id', $tvde_year_id);
        session()->put('tvde_month_id', TvdeMonth::where('year_id', session()->get('tvde_year_id'))
            ->withMax('weeks', 'start_date')
            ->orderByDesc('weeks_max_start_date')
            ->first()->id);
        session()->put('tvde_week_id', TvdeWeek::where('tvde_month_id', session()->get('tvde_month_id'))
            ->orderByDesc('start_date')
            ->first()->id);
        return back();
    }

    public function month($tvde_month_id)
    {
        session()->put('tvde_month_id', $tvde_month_id);
        session()->put('tvde_week_id', TvdeWeek::where('tvde_month_id', $tvde_month_id)
            ->orderByDesc('start_date')
            ->first()->id);
        return back();
    }

    public function week($tvde_week_id)
    {
        session()->put('tvde_week_id', $tvde_week_id);
        return back();
    }

    public function driver($driver_id)
    {
        session()->put('driver_id', $driver_id);
        return back();
    }

    public function pdf(Request $request)
    {
        abort_if(Gate::denies('financial-pdf'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_week_id = session()->get('tvde_week_id');
        $driver_id = session()->get('driver_id');
        $company_id = session()->get('company_id');
        $statement = $this->buildStatementPdfData($tvde_week_id, $driver_id, $company_id);
        $pdf = $this->buildStatementPdf($statement);


        if ($request->download) {
            $filename = $this->statementFilename($statement['driver'], $statement['tvde_week']);
            return $pdf->download($filename);
        } else {
            return $pdf->stream();
        }

    }

    public function sendEmail(Request $request)
    {
        abort_if(Gate::denies('financial_statement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'driver_id' => 'required|integer|exists:drivers,id',
            'tvde_week_id' => 'required|integer|exists:tvde_weeks,id',
        ]);

        $driver = Driver::findOrFail((int) $request->driver_id);
        $tvdeWeekId = (int) $request->tvde_week_id;
        $companyId = (int) (session()->get('company_id') ?: $driver->company_id);

        if (blank($driver->email)) {
            return back()->withErrors([
                'statement_email' => 'O motorista selecionado nao tem email configurado.',
            ]);
        }

        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $tvdeWeekId,
            'driver_id' => $driver->id,
        ])->first();

        if (!$currentAccount) {
            return back()->withErrors([
                'statement_email' => 'Nao existe extrato validado para esse motorista nessa semana.',
            ]);
        }

        $statement = $this->buildStatementPdfData($tvdeWeekId, $driver->id, $companyId);
        $pdf = $this->buildStatementPdf($statement);
        $filename = $this->statementFilename($statement['driver'], $statement['tvde_week']);

        Mail::to($driver->email)->send(new DriverFinancialStatementMail(
            $statement,
            $filename,
            $pdf->output()
        ));

        $currentAccount->statement_sent_at = now();
        $currentAccount->statement_sent_to = $driver->email;
        $currentAccount->save();

        return back()->with('status', 'Extrato enviado por email para ' . $driver->email . '.');
    }

    public function updateBalance(Request $request)
    {
        $request->validate([
            'new_balance' => 'required|numeric'
        ], [], [
            'new_balance' => 'Saldo'
        ]);

        $drivers_balance = DriversBalance::find($request->driver_balance_id);
        $drivers_balance->new_balance = $request->new_balance;
        $drivers_balance->save();
    }

    private function buildStatementPdfData(int $tvde_week_id, int $driver_id, int $company_id): array
    {
        $driver = Driver::find($driver_id);
        $company = Company::find($company_id);
        $tvde_week = TvdeWeek::find($tvde_week_id);
        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id,
        ])->first();
        $statementResults = $currentAccount ? json_decode($currentAccount->data) : null;

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
        ])->get();

        $adjustments = Adjustment::whereHas('drivers', function ($query) use ($driver_id) {
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
        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === 'refund') {
                $refund += $adjustment->amount;
            }
            if ($adjustment->type === 'deduct') {
                $deduct += $adjustment->amount;
            }
        }

        $electric_expenses = null;
        if ($driver && $driver->electric_id) {
            $electric = Electric::find($driver->electric_id);
            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id,
                ])->get();
                $electric_expenses = collect([
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', '') . ' EUR',
                    'value' => $electric_transactions->sum('total'),
                ]);
            }
        }

        $combustion_expenses = null;
        if ($driver && $driver->card_id) {
            $card = Card::find($driver->card_id);
            $code = $card?->code ?? 0;
            $combustion_transactions = $this->uniqueCombustionTransactions($tvde_week_id, [$code]);
            $combustion_total = $combustion_transactions->sum(function ($t) {
                return (float) $t->total;
            });
            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_total, 2, '.', '') . ' EUR',
                'value' => $combustion_total,
            ]);
        }

        $total_earnings_bolt = number_format($bolt_activities->sum('net') - $bolt_activities->sum('gross'), 2, '.', '');
        $total_tips_bolt = number_format($bolt_activities->sum('gross'), 2);
        $total_earnings_uber = number_format($uber_activities->sum('net') - $uber_activities->sum('gross'), 2, '.', '');
        $total_tips_uber = number_format($uber_activities->sum('gross'), 2);
        $total_tips = $total_tips_uber + $total_tips_bolt;
        $total_earnings = $bolt_activities->sum('net') + $uber_activities->sum('net');
        $total_earnings_no_tip = ($bolt_activities->sum('net') - $bolt_activities->sum('gross')) + ($uber_activities->sum('net') - $uber_activities->sum('gross'));

        $contract_type_ranks = [];
        if ($driver && !$statementResults && Schema::hasTable('contract_type_ranks')) {
            $contract_type_ranks = ContractTypeRank::where('contract_type_id', $driver->contract_type_id)->get();
        }
        $contract_type_rank = count($contract_type_ranks) > 0 ? $contract_type_ranks[0] : null;
        foreach ($contract_type_ranks as $value) {
            if ($value->from <= $total_earnings && $value->to >= $total_earnings) {
                $contract_type_rank = $value;
            }
        }

        $total_bolt = number_format(($bolt_activities->sum('net') - $bolt_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2, '.', '');
        $total_uber = number_format(($uber_activities->sum('net') - $uber_activities->sum('gross')) * ($contract_type_rank ? $contract_type_rank->percent / 100 : 0), 2, '.', '');
        $total_earnings_after_vat = $total_bolt + $total_uber;

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

        if ($statementResults) {
            $final_total = (float) ($statementResults->driver_total ?? $statementResults->total ?? $final_total);
        }

        $electric_racio = null;
        $combustion_racio = null;

        if ($electric_expenses && $total_earnings > 0) {
            $final_total -= $electric_expenses['value'];
            $gross_debts += $electric_expenses['value'];
            $electric_racio = $electric_expenses['value'] > 0 ? ($electric_expenses['value'] / $total_earnings) * 100 : 0;
        }

        if ($combustion_expenses && $total_earnings > 0) {
            $final_total -= $combustion_expenses['value'];
            $gross_debts += $combustion_expenses['value'];
            $combustion_racio = $combustion_expenses['value'] > 0 ? ($combustion_expenses['value'] / $total_earnings) * 100 : 0;
        }

        if ($driver->contract_vat->percent && $driver->contract_vat->percent > 0) {
            $txt_admin = ($final_total * $driver->contract_vat->percent) / 100;
            $gross_debts += $txt_admin;
            $final_total -= $txt_admin;
        } else {
            $txt_admin = 0;
        }

        $weekReport = $this->getWeekReport($company_id, $tvde_week_id);
        $drivers = collect($weekReport['drivers'] ?? [])
            ->filter(fn ($d) => !empty($d->earnings))
            ->sortByDesc(fn ($d) => (float) ($d->earnings_per_km ?? 0))
            ->values();
        $team_earnings = collect();
        $labels = [];
        $earnings = [];

        foreach ($drivers as $key => $d) {
            if ($driver) {
                $entry = collect([
                    'driver' => $driver->id === $d->id ? $driver->name : 'Motorista ' . ($key + 1),
                    'earnings' => number_format((float) ($d->earnings_per_km ?? 0), 3, '.', ''),
                    'own' => $driver->id === $d->id,
                ]);
                $team_earnings->add($entry);
            }
        }

        foreach ($team_earnings as $entry) {
            $labels[] = $entry['driver'];
            $earnings[] = $entry['earnings'];
        }

        $statementTotalNet = $statementResults ? (float) ($statementResults->total_net ?? 0) : null;
        $statementTotal = $statementResults ? (float) ($statementResults->driver_total ?? $statementResults->total ?? 0) : null;
        $statementGeneralAdjustments = $statementResults ? (float) ($statementResults->general_adjustments ?? $statementResults->adjustments ?? 0) : 0;
        $statementRentDiscount = $statementResults ? (float) ($statementResults->abatimento_aluguer ?? 0) : 0;
        $statementMinimumBillingDifference = $statementResults ? (float) ($statementResults->diferenca_faturacao_minima ?? 0) : 0;
        $statementCredits = $statementTotalNet ?? 0;

        if ($statementGeneralAdjustments > 0) {
            $statementCredits += $statementGeneralAdjustments;
        }
        if ($statementMinimumBillingDifference > 0) {
            $statementCredits += $statementMinimumBillingDifference;
        }
        if ($statementRentDiscount > 0) {
            $statementCredits += $statementRentDiscount;
        }

        return [
            'company_id' => $company_id,
            'company' => $company,
            'tvde_week_id' => $tvde_week_id,
            'tvde_week' => $tvde_week,
            'driver_id' => $driver_id,
            'bolt_activities' => $bolt_activities,
            'uber_activities' => $uber_activities,
            'total_earnings_uber' => $total_earnings_uber,
            'contract_type_rank' => $contract_type_rank,
            'total_uber' => $total_uber,
            'total_earnings_bolt' => $total_earnings_bolt,
            'total_bolt' => $total_bolt,
            'total_tips_uber' => $total_tips_uber,
            'uber_tip_percent' => $uber_tip_percent,
            'uber_tip_after_vat' => $uber_tip_after_vat,
            'total_tips_bolt' => $total_tips_bolt,
            'bolt_tip_percent' => $bolt_tip_percent,
            'bolt_tip_after_vat' => $bolt_tip_after_vat,
            'total_tips' => $total_tips,
            'total_tip_after_vat' => $total_tip_after_vat,
            'adjustments' => $adjustments,
            'statement_results' => $statementResults,
            'statement_uber_gross' => $statementResults ? (float) ($statementResults->uber->uber_gross ?? 0) : null,
            'statement_uber_net' => $statementResults ? (float) ($statementResults->uber->uber_net ?? 0) : null,
            'statement_bolt_gross' => $statementResults ? (float) ($statementResults->bolt->bolt_gross ?? 0) : null,
            'statement_bolt_net' => $statementResults ? (float) ($statementResults->bolt->bolt_net ?? 0) : null,
            'statement_total_gross' => $statementResults ? (float) ($statementResults->total_gross ?? 0) : null,
            'statement_total_net' => $statementTotalNet,
            'statement_total' => $statementTotal,
            'statement_credits' => $statementResults ? $statementCredits : null,
            'statement_debits' => $statementResults ? ($statementTotal - $statementCredits) : null,
            'general_adjustments' => $statementResults ? ($statementResults->general_adjustments ?? $statementResults->adjustments ?? 0) : 0,
            'rent_discount' => $statementResults ? ($statementResults->abatimento_aluguer ?? 0) : 0,
            'minimum_billing_difference' => $statementResults ? ($statementResults->diferenca_faturacao_minima ?? 0) : 0,
            'caution_received' => $statementResults ? ($statementResults->caucao_recebida ?? 0) : 0,
            'caution_returned' => $statementResults ? ($statementResults->caucao_devolvida ?? 0) : 0,
            'car_hire_base' => $statementResults ? ($statementResults->car_hire_base ?? $statementResults->car_hire ?? 0) : 0,
            'car_track' => $statementResults ? ($statementResults->car_track ?? 0) : 0,
            'fuel_transactions' => $statementResults ? ($statementResults->fuel_transactions ?? 0) : 0,
            'total_earnings' => $total_earnings,
            'total_earnings_no_tip' => $total_earnings_no_tip,
            'total' => $total,
            'total_after_vat' => $total_after_vat,
            'gross_credits' => $gross_credits,
            'gross_debts' => $gross_debts,
            'final_total' => $final_total,
            'driver' => $driver,
            'electric_expenses' => $electric_expenses,
            'combustion_expenses' => $combustion_expenses,
            'combustion_racio' => $combustion_racio,
            'electric_racio' => $electric_racio,
            'total_earnings_after_vat' => $total_earnings_after_vat,
            'iva_value' => $statementResults ? ($statementResults->iva_value ?? 0) : 0,
            'percent_value' => $statementResults ? ($statementResults->percent_value ?? 0) : 0,
            'txt_admin' => $txt_admin,
            'team_earnings' => $team_earnings,
            'chart1' => "https://quickchart.io/chart?c={type:'bar',data:{labels:" . json_encode($labels) . ",datasets:[{borderWidth: 1, label:'EUR/km',data:" . json_encode($earnings) . "}]}}",
            'chart2' => "https://quickchart.io/chart?c={type:'doughnut',data:{labels:['UBER', 'BOLT', 'GORJETAS'],datasets:[{label: 'Valor faturado', data: [" . $total_earnings_uber . ", " . $total_earnings_bolt . ", " . $total_tips . "]}]}}",
        ];
    }

    private function buildStatementPdf(array $statement)
    {
        return Pdf::loadView('admin.financialStatements.pdf', $statement)->setOption([
            'isRemoteEnabled' => true,
        ]);
    }

    private function statementFilename(Driver $driver, TvdeWeek $tvde_week): string
    {
        return strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9\-]/', '', $driver->name . '-' . $tvde_week->start_date))) . '.pdf';
    }

}
