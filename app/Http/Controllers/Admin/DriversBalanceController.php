<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyDriversBalanceRequest;
use App\Http\Requests\StoreDriversBalanceRequest;
use App\Http\Requests\UpdateDriversBalanceRequest;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriversBalanceController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('drivers_balance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (session()->has('company_id')) {
            if (session()->get('company_id' == '0')) {
                $driversBalances = DriversBalance::with(['driver.company', 'tvde_week'])->get();
            } else {
                $driversBalances = DriversBalance::whereHas('driver', function ($driver) {
                    $driver->where('company_id', session()->get('company_id'));
                })->with(['driver.company', 'tvde_week'])->get();
            }
        } else {
            $driversBalances = DriversBalance::with(['driver.company', 'tvde_week'])->get();
        }

        return view('admin.driversBalances.index', compact('driversBalances'));
    }

    public function create()
    {
        abort_if(Gate::denies('drivers_balance_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.driversBalances.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreDriversBalanceRequest $request)
    {
        $driversBalance = DriversBalance::create($request->all());

        return redirect()->route('admin.drivers-balances.index');
    }

    public function edit(DriversBalance $driversBalance)
    {
        abort_if(Gate::denies('drivers_balance_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $driversBalance->load('driver', 'tvde_week');

        return view('admin.driversBalances.edit', compact('drivers', 'driversBalance', 'tvde_weeks'));
    }

    public function update(UpdateDriversBalanceRequest $request, DriversBalance $driversBalance)
    {
        $driversBalance->update($request->all());

        return redirect()->route('admin.drivers-balances.index');
    }

    public function show(DriversBalance $driversBalance)
    {
        abort_if(Gate::denies('drivers_balance_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driversBalance->load('driver', 'tvde_week');

        return view('admin.driversBalances.show', compact('driversBalance'));
    }

    public function destroy(DriversBalance $driversBalance)
    {
        abort_if(Gate::denies('drivers_balance_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driversBalance->delete();

        return back();
    }

    public function massDestroy(MassDestroyDriversBalanceRequest $request)
    {
        $driversBalances = DriversBalance::find(request('ids'));

        foreach ($driversBalances as $driversBalance) {
            $driversBalance->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
