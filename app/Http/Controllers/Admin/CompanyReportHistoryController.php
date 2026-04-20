<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CompanyReportHistoryExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\Reports;
use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class CompanyReportHistoryController extends Controller
{
    use Reports;

    public function index(Request $request)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.companyReports.history')->with($this->buildHistoryReportData($request));
    }

    public function exportExcel(Request $request)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return Excel::download(
            new CompanyReportHistoryExport($this->buildHistoryReportData($request)),
            'company-report-history-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return Pdf::loadView('admin.companyReports.historyPdf', $this->buildHistoryReportData($request))
            ->setPaper('a4', 'landscape')
            ->download('company-report-history-' . now()->format('Ymd-His') . '.pdf');
    }

    protected function buildHistoryReportData(Request $request): array
    {
        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');

        $tvde_week = TvdeWeek::find($tvde_week_id);
        $week_start = $tvde_week ? Carbon::parse($tvde_week->getRawOriginal('start_date'))->startOfDay() : null;
        $week_end = $tvde_week ? Carbon::parse($tvde_week->getRawOriginal('end_date'))->endOfDay() : null;

        $accounts = CurrentAccount::with('driver')
            ->where('tvde_week_id', $tvde_week_id)
            ->when($company_id !== 0, function ($query) use ($company_id) {
                $query->whereHas('driver', function ($driver) use ($company_id) {
                    $driver->where('company_id', $company_id);
                });
            })
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                $query->whereBetween('created_at', [
                    Carbon::parse($from_date)->startOfDay(),
                    Carbon::parse($to_date)->endOfDay(),
                ]);
            })
            ->when($from_date && !$to_date, function ($query) use ($from_date) {
                $query->where('created_at', '>=', Carbon::parse($from_date)->startOfDay());
            })
            ->when(!$from_date && $to_date, function ($query) use ($to_date) {
                $query->where('created_at', '<=', Carbon::parse($to_date)->endOfDay());
            })
            ->get();

        $drivers = $accounts->map(function ($account) use ($week_start, $week_end) {
            $driver = $account->driver;
            $earnings = json_decode($account->data, true) ?? [];

            $active_usage = VehicleUsage::with('vehicle_item')
                ->where('driver_id', $driver->id)
                ->when($week_end, function ($query) use ($week_end) {
                    $query->where('start_date', '<=', $week_end);
                })
                ->where(function ($query) use ($week_start) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $week_start);
                })
                ->where(function ($query) {
                    $query->whereNull('usage_exceptions')
                        ->orWhere('usage_exceptions', 'usage');
                })
                ->orderBy('start_date', 'desc')
                ->first();

            $driver->license_plate = $active_usage?->vehicle_item?->license_plate;
            $driver->earnings = $earnings;
            $driver->fuel = (float) ($earnings['fuel_transactions'] ?? 0);
            $driver->adjustments = (float) ($earnings['adjustments'] ?? 0);
            $driver->current_account = true;

            $balance = DriversBalance::where([
                'tvde_week_id' => $account->tvde_week_id,
                'driver_id' => $driver->id,
            ])->first();

            $driver->total = $balance ? (float) $balance->value : 0.0;
            $driver->last_balance = $balance ? (float) $balance->last_balance : 0.0;
            $driver->new_balance = $balance ? (float) $balance->new_balance : 0.0;

            return $driver;
        })->sortByDesc(fn ($driver) => (float) ($driver->total ?? 0))->values();

        $totals = collect([
            'gross_uber' => $drivers->sum(fn ($d) => (float) ($d->earnings['uber']['uber_gross'] ?? 0)),
            'gross_bolt' => $drivers->sum(fn ($d) => (float) ($d->earnings['bolt']['bolt_gross'] ?? 0)),
            'total_operators' => $drivers->sum(fn ($d) => (float) ($d->earnings['total_gross'] ?? 0)),
            'net_uber' => $drivers->sum(fn ($d) => (float) ($d->earnings['uber']['uber_net'] ?? 0)),
            'net_bolt' => $drivers->sum(fn ($d) => (float) ($d->earnings['bolt']['bolt_net'] ?? 0)),
            'total_net_operators' => $drivers->sum(fn ($d) => (float) ($d->earnings['total_net'] ?? 0)),
            'tips_total' => $drivers->sum(fn ($d) => (float) ($d->earnings['tips_total'] ?? 0)),
            'total_iva_value' => $drivers->sum(fn ($d) => (float) ($d->earnings['iva_value'] ?? 0)),
            'total_earnings_after_vat' => $drivers->sum(fn ($d) => (float) ($d->earnings['total_after_vat'] ?? 0)),
            'total_fuel_transactions' => $drivers->sum(fn ($d) => (float) ($d->earnings['fuel_transactions'] ?? 0)),
            'total_adjustments' => $drivers->sum(fn ($d) => (float) ($d->earnings['adjustments'] ?? 0)),
            'total_car_track' => $drivers->sum(fn ($d) => (float) ($d->earnings['car_track'] ?? 0)),
            'total_percent_value' => $drivers->sum(fn ($d) => (float) ($d->earnings['percent_value'] ?? 0)),
            'total_car_hire' => $drivers->sum(fn ($d) => (float) ($d->earnings['car_hire'] ?? 0)),
            'total_drivers' => $drivers->sum(fn ($d) => (float) ($d->total ?? 0)),
        ]);

        return [
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'drivers' => $drivers,
            'totals' => $totals,
        ];
    }
}
