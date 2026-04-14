<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\Reports;
use App\Models\CompanyData;
use App\Models\CompanyInvoice;
use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\ExpenseReceipt;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    use Reports;
    use MediaUploadingTrait;

    public function index()
    {
        $filter        = $this->filter();
        $tvde_week_id  = $filter['tvde_week_id'];
        $tvde_years    = $filter['tvde_years'];
        $tvde_year_id  = $filter['tvde_year_id'];
        $tvde_months   = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks    = $filter['tvde_weeks'];
        $drivers       = $filter['drivers'];

        $driver = Driver::where('user_id', auth()->user()->id)->first();

        if (!$driver) {
            return redirect('/admin/financial-statements');
        } else {
            $driver->load('contract_vat');
        }

        $driver_id  = $driver->id;
        $company_id = $driver->company_id;

        $results = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id'    => $driver_id,
        ])->first();

        if ($results) {
            $results = json_decode($results->data);
        }

        $driver_balance = DriversBalance::where([
            'driver_id'    => $driver_id,
            'tvde_week_id' => $tvde_week_id,
        ])->first();

        if ($driver_balance) {
            $factor = $driver->contract_vat ? $driver->contract_vat->iva / 100 : 0;
            $iva = number_format($driver_balance->value * $factor, 2);
            $driver_balance->iva = $iva;

            $factor = $driver->contract_vat ? $driver->contract_vat->rf / 100 : 0;
            $rf = number_format(-($driver_balance->value * $factor), 2);
            $driver_balance->rf = $rf ?? 0;

            $final = number_format($driver_balance->balance + $iva + $rf, 2);
            $driver_balance->final = $final;

            // Verificar recibos de despesas
            $expenseReceipt = ExpenseReceipt::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $tvde_week_id,
            ])->first();

            if ($expenseReceipt && $expenseReceipt->verified) {
                $driver_balance->final = $driver_balance->final - $expenseReceipt->approved_value;
            }
        }

        // Balance last week
        $current_week = TvdeWeek::findOrFail($tvde_week_id);

        $previous_week = TvdeWeek::where('end_date', '<', $current_week->start_date)
            ->orderByDesc('end_date')
            ->first();

        if ($previous_week) {
            $driver_balance_last_week = DriversBalance::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $previous_week->id,
            ])->first();
        } else {
            $driver_balance_last_week = null;
        }

        // Prefer the new commission total when available to avoid re-applying expenses.
        $total = $results->driver_total
            ?? $results->total
            ?? (($results->subtotal_after_tips ?? 0)
                - ($results->car_hire ?? 0)
                - ($results->car_track ?? 0)
                + ($results->adjustments ?? 0));

        // === Abastecimentos: cartoes do driver e unidades ===
        $driver->load(['cards:id,code,type']);

        // mapear unidades por cartao (kWh para tipos "eletric/electric"; senao L)
        $cardUnits = [];
        foreach ($driver->cards as $c) {
            $type = strtolower($c->type ?? '');
            $isElectric = str_contains($type, 'eletric') || str_contains($type, 'electric') || str_contains($type, 'ev');
            $cardUnits[$c->code] = $isElectric ? 'kWh' : 'L';
        }
        $cardCodes = collect($cardUnits)->keys();

        // Carrega abastecimentos sem writes no request. A normalizacao e feita
        // apenas em memoria para evitar timeouts no dashboard.
        $combustion_transactions = $this->uniqueCombustionTransactions(
            $tvde_week_id,
            $cardCodes->isNotEmpty() ? $cardCodes->all() : []
        )->map(function ($transaction) {
            $amount = (float) $transaction->amount;
            $total = (float) $transaction->total;

            if ($amount > 200 && $total > 0) {
                $pricePerUnit = $total / $amount;

                if ($pricePerUnit < 0.10) {
                    $transaction->amount = $amount / 1000;
                }
            }

            return $transaction;
        });

        // Totais por unidade
        $total_liters = $combustion_transactions
            ->filter(fn ($t) => ($cardUnits[$t->card] ?? 'L') === 'L')
            ->sum('amount');

        $total_kwh = $combustion_transactions
            ->filter(fn ($t) => ($cardUnits[$t->card] ?? 'L') === 'kWh')
            ->sum('amount');

        $car_track_details = $this->driverCarTrackDetails((int) $driver_id, (int) $tvde_week_id);

        return view('home')->with([
            'company_id'                => $company_id,
            'tvde_year_id'              => $tvde_year_id,
            'tvde_years'                => $tvde_years,
            'tvde_months'               => $tvde_months,
            'tvde_month_id'             => $tvde_month_id,
            'tvde_weeks'                => $tvde_weeks,
            'tvde_week_id'              => $tvde_week_id,
            'drivers'                   => $drivers,
            'driver_id'                 => $driver_id,
            'uber_gross'                => isset($results) ? $results->uber->uber_gross : 0,
            'bolt_gross'                => isset($results) ? $results->bolt->bolt_gross : 0,
            'uber_net'                  => isset($results) ? $results->uber->uber_net : 0,
            'bolt_net'                  => isset($results) ? $results->bolt->bolt_net : 0,
            'total_gross'               => isset($results) ? $results->total_gross : 0,
            'total_net'                 => isset($results) ? $results->total_net : 0,
            'adjustments'               => isset($results) ? $results->adjustments : 0,
            'adjustments_array'         => isset($results) && isset($results->adjustments_array) ? $results->adjustments_array : 0,
            'total'                     => $total ?? 0,
            'vat_value'                 => isset($results) ? $results->vat_value : 0,
            'car_track'                 => isset($results) ? $results->car_track : 0,
            'car_hire'                  => isset($results) ? $results->car_hire : 0,
            'fuel_transactions'         => isset($results) ? $results->fuel_transactions : 0,
            'car_track_details'         => $car_track_details,
            'driver_balance'            => $driver_balance ?? null,
            'expenseReceipt'            => $expenseReceipt ?? null,

            // dados novos/ajustados
            'driver_balance_last_week'  => $driver_balance_last_week ?? null,
            'combustion_transactions'   => $combustion_transactions,
            'driver_card_codes'         => $cardCodes,
            'card_units'                => $cardUnits,
            'total_liters'              => $total_liters,
            'total_kwh'                 => $total_kwh,
        ]);
    }

    public function selectCompany($company_id)
    {
        session()->forget('driver_id');
        session()->put('company_id', $company_id);
    }

    public function companyDashboard()
    {
        abort_if(Gate::denies('weekly_expense_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (auth()->user()->hasRole('Empresas Associadas')) {
            $user = auth()->user()->load('company');
            $company_id = $user->company->id;
            session()->put('company_id', $company_id);
        }

        $filter        = $this->filter();
        $company_id    = $filter['company_id'];
        $tvde_week_id  = $filter['tvde_week_id'];
        $tvde_week     = $filter['tvde_week'];
        $tvde_years    = $filter['tvde_years'];
        $tvde_year_id  = $filter['tvde_year_id'];
        $tvde_months   = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks    = $filter['tvde_weeks'];

        // COMPANY EXPENSES
        $company_data = CompanyData::where([
            'company_id'   => $company_id,
            'tvde_week_id' => $tvde_week_id,
        ])->first();

        if ($company_data) {
            $data = json_decode($company_data->data);
        } else {
            $this->saveCompanyExpenses($company_id, $tvde_week_id);
            return redirect(url()->current());
        }

        return view('admin.weeklyExpenseReports.index')->with([
            'company_id'                  => $company_id,
            'tvde_years'                  => $tvde_years,
            'tvde_year_id'                => $tvde_year_id,
            'tvde_months'                 => $tvde_months,
            'tvde_month_id'               => $tvde_month_id,
            'tvde_weeks'                  => $tvde_weeks,
            'tvde_week_id'                => $tvde_week_id,
            'company_expenses'            => $data->company_expenses,
            'total_company_expenses'      => $data->total_company_expenses,
            'totals'                      => $data->totals,
            'company_park'                => $data->company_ark ?? $data->company_park ?? null,
            'final_total'                 => $data->final_total,
            'final_company_expenses'      => $data->final_company_expenses,
            'profit'                      => $data->profit,
            'roi'                         => $data->roi,
            'total_consultancy'           => $data->total_consultancy,
            'fleet_adjusments'            => $data->fleet_adjusments,
            'fleet_consultancies'         => $data->fleet_consultancies,
            'fleet_company_parks'         => $data->fleet_company_parks,
            'fleet_earnings'              => $data->fleet_earnings,
            'total_company_adjustments'   => $data->totals->total_company_adjustments,
        ]);
    }

    public function companyInvoiceDashboard()
    {
        $company_id = auth()->user()->company->id;

        if (!session()->get('company_id')) {
            session()->put('company_id', $company_id);
        }

        $company = auth()->user()->company->load('company_invoices');

        if ($company->suspended) {
            session()->flush();
            return redirect('/login')->with('message', 'A sua conta esta suspensa. Entre em contacto com a ' . env('APP_NAME'));
        }

        return view('admin.companyInvoiceDashboard.index', compact('company'));
    }

    public function companyInvoiceUploadMedia(Request $request)
    {
        $file = $this->storeMedia($request);
        $fileData = json_decode($file->content());
        $fileName = $fileData->name;
        $company_invoice = CompanyInvoice::find($request->company_invoice_id);
        $company_invoice->addMedia(storage_path('tmp/uploads/' . $fileName))->toMediaCollection('payment_receipt');
        return redirect()->back();
    }

    // Util se receberes strings com virgulas/nbspace
    function parsePtNumber(string $s): float
    {
        $s = str_replace(["\xC2\xA0", ' '], '', $s); // remove espacos e NBSP
        $s = str_replace(',', '.', $s);               // virgula -> ponto
        return (float) $s;
    }
}
