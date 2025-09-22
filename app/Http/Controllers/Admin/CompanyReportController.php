<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use Illuminate\Http\Request;
use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use App\Models\Driver;
use App\Models\Reimbursement;

class CompanyReportController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $results = $this->getWeekReport($company_id, $tvde_week_id);

        return view('admin.companyReports.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $results['drivers'],
            'totals' => $results['totals']
        ]);
    }

    public function validateData(Request $request)
    {
        foreach ($request->data as $data) {

            // 🔹 Função inline para normalizar valores vindos do front
            $normalize = function ($value): float {
                if (is_numeric($value)) {
                    return (float) $value; // já é número limpo
                }
                $v = str_replace(' ', '', (string) $value);   // remove espaços normais
                $v = str_replace("\xc2\xa0", '', $v);         // remove NBSP (utf-8)
                $v = str_replace('.', '', $v);                // tira separador de milhar
                $v = str_replace(',', '.', $v);               // vírgula → ponto decimal
                return (float) $v;
            };

            // 🔹 Normaliza o total do motorista
            $total = $normalize($data['driver']['total']);

            // 🔹 Registo da conta corrente
            $current_account = new CurrentAccount;
            $current_account->tvde_week_id = $data['tvde_week_id'];
            $current_account->driver_id    = $data['driver']['id'];
            $current_account->data         = json_encode($data['driver']['earnings']);
            $current_account->save();

            // 🔹 Último saldo
            $last_balance = DriversBalance::where('driver_id', $data['driver']['id'])
                ->orderBy('tvde_week_id', 'desc')
                ->first();

            $last = $last_balance ? (float) $last_balance->balance : 0.0;
            $balance = $last + $total;

            // 🔹 Novo saldo
            $driver_balance = new DriversBalance;
            $driver_balance->driver_id       = $data['driver']['id'];
            $driver_balance->tvde_week_id    = $data['tvde_week_id'];
            $driver_balance->value           = $total;
            $driver_balance->balance         = $balance;
            $driver_balance->drivers_balance = $balance;
            $driver_balance->save();

            /*
        $email = $data['driver']['email'];

        Notification::route('mail', $email)
            ->notify(new ActivityLaunchesSend());
        */
        }
    }


    public function revalidateData(Request $request)
    {
        $driver_id = $request->driver_id;
        $company_id = Driver::find($driver_id)->company_id;
        $tvde_week_id = $request->tvde_week_id;
        $data = $request->data;

        $current_account = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();
        $current_account->data = json_encode($data);
        $current_account->save();

        DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->delete();

        $last_balance = DriversBalance::where([
            'driver_id' => $data['driver']['id'],
        ])
            ->orderBy('tvde_week_id', 'desc')->first();

        $driver_balance = new DriversBalance;
        $driver_balance->driver_id = $data['driver']['id'];
        $driver_balance->tvde_week_id = $data['tvde_week_id'];
        $driver_balance->value = $data['driver']['total'];
        $driver_balance->balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
        $driver_balance->drivers_balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
        $driver_balance->save();
    }

    public function deleteData($tvde_week_id, $driver_id)
    {

        $current_account = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();

        if ($current_account) {
            $current_account->delete();
        }

        DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->delete();

        return redirect()->route('admin.company-reports.index')->with('message', 'Data deleted successfully.');
    }

    public function driverReportAllWeeks($driver_id = NULL, $state_id = 1)
    {

        $drivers = Driver::where('state_id', $state_id)->get();
        $driver_id = $driver_id ?? $drivers->first()->id;

        $weeks = \App\Models\TvdeWeek::orderBy('start_date', 'desc')->get();

        $results = [];

        foreach ($weeks as $week) {
            $account = \App\Models\CurrentAccount::where([
                'tvde_week_id' => $week->id,
                'driver_id' => $driver_id
            ])->first();

            $balance = \App\Models\DriversBalance::where([
                'tvde_week_id' => $week->id,
                'driver_id' => $driver_id
            ])->first();

            $receipt = \App\Models\Receipt::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $week->id,
            ])->latest()->first();

            // ➜ Devoluções validadas (motorista → empresa) nesta semana
            $reimbursed = Reimbursement::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $week->id,
                'verified'     => 1,                 // só as validadas
            ])->sum('value');

            $data = $account ? json_decode($account->data) : null;

            $amount_transferred = ($receipt->amount_transferred ?? 0) - $reimbursed;

            $results[] = [
                'week' => $week,
                'uber_gross' => $data->uber->uber_gross ?? 0,
                'bolt_gross' => $data->bolt->bolt_gross ?? 0,
                'uber_net' => $data->uber->uber_net ?? 0,
                'bolt_net' => $data->bolt->bolt_net ?? 0,
                'total_gross' => $data->total_gross ?? 0,
                'total_net' => $data->total_net ?? 0,
                'adjustments' => $data->adjustments ?? 0,
                'total' => $data->total ?? 0,
                'vat_value' => $data->vat_value ?? 0,
                'car_track' => $data->car_track ?? 0,
                'car_hire' => $data->car_hire ?? 0,
                'fuel_transactions' => $data->fuel_transactions ?? 0,
                'driver_balance' => $balance->balance ?? 0,
                'amount_transferred'   => $amount_transferred,
            ];
        }

        return view('admin.companyReports.driverReportAllWeeks')->with([
            'drivers' => $drivers,
            'driver_id' => $driver_id,
            'results' => $results,
            'state_id' => $state_id
        ]);
    }
}
