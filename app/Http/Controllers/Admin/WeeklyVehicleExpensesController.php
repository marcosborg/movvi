<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyWeeklyVehicleExpenseRequest;
use App\Http\Requests\StoreWeeklyVehicleExpenseRequest;
use App\Http\Requests\UpdateWeeklyVehicleExpenseRequest;
use App\Models\Driver;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Models\WeeklyVehicleExpense;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class WeeklyVehicleExpensesController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('weekly_vehicle_expense_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = WeeklyVehicleExpense::with(['vehicle_item', 'driver', 'tvde_week'])->select(sprintf('%s.*', (new WeeklyVehicleExpense)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'weekly_vehicle_expense_show';
                $editGate      = 'weekly_vehicle_expense_edit';
                $deleteGate    = 'weekly_vehicle_expense_delete';
                $crudRoutePart = 'weekly-vehicle-expenses';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('vehicle_item_license_plate', function ($row) {
                return $row->vehicle_item ? $row->vehicle_item->license_plate : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->editColumn('total_km', function ($row) {
                return $row->total_km ? $row->total_km : '';
            });
            $table->editColumn('weekly_km', function ($row) {
                return $row->weekly_km ? $row->weekly_km : '';
            });
            $table->editColumn('extra_km', function ($row) {
                return $row->extra_km ? $row->extra_km : '';
            });
            $table->editColumn('transfers', function ($row) {
                return $row->transfers ? $row->transfers : '';
            });
            $table->editColumn('deposit', function ($row) {
                return $row->deposit ? $row->deposit : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle_item', 'driver', 'tvde_week']);

            return $table->make(true);
        }

        return view('admin.weeklyVehicleExpenses.index');
    }

    public function create()
    {
        abort_if(Gate::denies('weekly_vehicle_expense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.weeklyVehicleExpenses.create', compact('drivers', 'tvde_weeks', 'vehicle_items'));
    }

    public function store(StoreWeeklyVehicleExpenseRequest $request)
    {
        $weeklyVehicleExpense = WeeklyVehicleExpense::create($request->all());

        return redirect()->route('admin.weekly-vehicle-expenses.index');
    }

    public function edit(WeeklyVehicleExpense $weeklyVehicleExpense)
    {
        abort_if(Gate::denies('weekly_vehicle_expense_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $weeklyVehicleExpense->load('vehicle_item', 'driver', 'tvde_week');

        return view('admin.weeklyVehicleExpenses.edit', compact('drivers', 'tvde_weeks', 'vehicle_items', 'weeklyVehicleExpense'));
    }

    public function update(UpdateWeeklyVehicleExpenseRequest $request, WeeklyVehicleExpense $weeklyVehicleExpense)
    {
        $weeklyVehicleExpense->update($request->all());

        return redirect()->route('admin.weekly-vehicle-expenses.index');
    }

    public function show(WeeklyVehicleExpense $weeklyVehicleExpense)
    {
        abort_if(Gate::denies('weekly_vehicle_expense_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weeklyVehicleExpense->load('vehicle_item', 'driver', 'tvde_week');

        return view('admin.weeklyVehicleExpenses.show', compact('weeklyVehicleExpense'));
    }

    public function destroy(WeeklyVehicleExpense $weeklyVehicleExpense)
    {
        abort_if(Gate::denies('weekly_vehicle_expense_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weeklyVehicleExpense->delete();

        return back();
    }

    public function massDestroy(MassDestroyWeeklyVehicleExpenseRequest $request)
    {
        $weeklyVehicleExpenses = WeeklyVehicleExpense::find(request('ids'));

        foreach ($weeklyVehicleExpenses as $weeklyVehicleExpense) {
            $weeklyVehicleExpense->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
