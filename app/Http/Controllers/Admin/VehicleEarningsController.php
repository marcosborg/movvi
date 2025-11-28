<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use App\Models\VehicleItem;
use App\Models\TvdeWeek;
use App\Models\CurrentAccount;
use App\Models\VehicleUsage;

class VehicleEarningsController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('vehicle_earning_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $tvde_week = TvdeWeek::findOrFail($tvde_week_id);
        $start_date = $tvde_week->start_date;
        $end_date = $tvde_week->end_date;

        $vehicle_items = VehicleItem::with(['driver', 'vehicle_usage.driver'])
            ->where('company_id', $company_id)
            ->where('suspended', false)
            ->get();

        // 1. Viaturas sem motorista atribuído na semana
        $vehicles_without_driver = $vehicle_items->filter(function ($vehicle) use ($start_date, $end_date) {
            return $vehicle->vehicle_usage->filter(function ($usage) use ($start_date, $end_date) {
                return $usage->start_date <= $end_date && $usage->end_date >= $start_date && $usage->driver_id;
            })->isEmpty();
        });

        // 2. Condutores com uso mas sem conta corrente ou rendimento zero
        $drivers_with_usage_no_account_or_zero = collect();

        $usages = VehicleUsage::with('driver')
            ->whereBetween('start_date', [$start_date, $end_date])
            ->orWhereBetween('end_date', [$start_date, $end_date])
            ->get();

        foreach ($usages as $usage) {
            $driver = $usage->driver;
            if (!$driver) continue;

            $account = CurrentAccount::where('driver_id', $driver->id)
                ->where('tvde_week_id', $tvde_week_id)
                ->first();

            $account_data = json_decode($account->data ?? '[]', true);

            if (!$account || floatval($account_data['total_net']) == 0.0) {
                $drivers_with_usage_no_account_or_zero->push($driver);
            }
        }

        return view('admin.vehicleEarnings.index')->with([
            'company_id' => $company_id,
            'tvde_year_id' => $tvde_year_id,
            'tvde_years' => $tvde_years,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'vehicles_without_driver' => $vehicles_without_driver,
            'drivers_with_issues' => $drivers_with_usage_no_account_or_zero->unique('id'),
        ]);
    }
}
