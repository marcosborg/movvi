<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Traits\Reports;
use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use Illuminate\Http\Request;
use App\Models\CompanyInvoice;
use App\Models\CompanyData;
use App\Models\Driver;
use App\Models\ExpenseReceipt;
use App\Models\TvdeWeek;

class HomeController
{

    use Reports;
    use MediaUploadingTrait;

    public function index()
    {
        $filter = $this->filter();
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $drivers = $filter['drivers'];

        $driver = Driver::where('user_id', auth()->user()->id)->first();

        if (!$driver) {
            return redirect('/admin/financial-statements');
        } else {
            $driver->load('contract_vat');
        }

        $driver_id = $driver->id;
        $company_id = $driver->company_id;

        $results = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();

        if ($results) {
            $results = json_decode($results->data);
        }

        $driver_balance = DriversBalance::where([
            'driver_id' => $driver_id,
            'tvde_week_id' => $tvde_week_id
        ])->first();

        if ($driver_balance) {

            $factor = $driver->contract_vat->iva / 100;
            $iva = number_format($driver_balance->value * $factor, 2);
            $driver_balance->iva = $iva;

            $factor = $driver->contract_vat->rf / 100;
            $rf = number_format(- ($driver_balance->value * $factor), 2);
            $driver_balance ? $driver_balance->rf = $rf ?? 0 : 0;

            $final = number_format($driver_balance->balance + $iva + $rf, 2);
            $driver_balance->final = $final;

            //VERIFICAR RECIBOS DE DESPESAS

            $expenseReceipt = ExpenseReceipt::where([
                'driver_id' => $driver_id,
                'tvde_week_id' => $tvde_week_id
            ])->first();

            if ($expenseReceipt && $expenseReceipt->verified) {
                $driver_balance->final = $driver_balance->final - $expenseReceipt->approved_value;
            }
        }

        //BALANCE LAST WEEK

        $current_week = TvdeWeek::findOrFail($tvde_week_id);

        $previous_week = TvdeWeek::where('end_date', '<', $current_week->start_date)
            ->orderByDesc('end_date')
            ->first();

        if ($previous_week) {
            $driver_balance_last_week = DriversBalance::where([
                'driver_id' => $driver_id,
                'tvde_week_id' => $previous_week->id
            ])->first();
        } else {
            $driver_balance_last_week = null; // ou valor default
        }

        return view('home')->with([
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
            'adjustments_array' => isset($results) && isset($results->adjustments_array) ? $results->adjustments_array : 0,
            'total' => isset($results) ? $results->total : 0,
            'vat_value' => isset($results) ? $results->vat_value : 0,
            'car_track' => isset($results) ? $results->car_track : 0,
            'car_hire' => isset($results) ? $results->car_hire : 0,
            'fuel_transactions' => isset($results) ? $results->fuel_transactions : 0,
            'driver_balance' => $driver_balance ?? null,
            'expenseReceipt' => $expenseReceipt ?? null,
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

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_week = $filter['tvde_week'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        //COMPANY EXPENSES

        $company_data = CompanyData::where([
            'company_id' => $company_id,
            'tvde_week_id' => $tvde_week_id
        ])->first();

        if ($company_data) {
            $data = json_decode($company_data->data);
        } else {
            $this->saveCompanyExpenses($company_id, $tvde_week_id);
            return redirect(url()->current());
        }

        return view('admin.weeklyExpenseReports.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'company_expenses' => $data->company_expenses,
            'total_company_expenses' => $data->total_company_expenses,
            'totals' => $data->totals,
            'company_park' => $data->company_park,
            'final_total' => $data->final_total,
            'final_company_expenses' => $data->final_company_expenses,
            'profit' => $data->profit,
            'roi' => $data->roi,
            'total_consultancy' => $data->total_consultancy,
            'fleet_adjusments' => $data->fleet_adjusments,
            'fleet_consultancies' => $data->fleet_consultancies,
            'fleet_company_parks' => $data->fleet_company_parks,
            'fleet_earnings' => $data->fleet_earnings,
            'total_company_adjustments' => $data->totals->total_company_adjustments,
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
            return redirect('/login')->with('message', 'A sua conta está suspensa. Entre em contacto com a ' . env('APP_NAME'));
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
}
