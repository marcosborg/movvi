<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCarHireRequest;
use App\Http\Requests\StoreCarHireRequest;
use App\Http\Requests\UpdateCarHireRequest;
use App\Models\CarHire;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CarHireController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('car_hire_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = CarHire::with(['driver'])->select(sprintf('%s.*', (new CarHire)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'car_hire_show';
                $editGate      = 'car_hire_edit';
                $deleteGate    = 'car_hire_delete';
                $crudRoutePart = 'car-hires';

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
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->editColumn('amount', function ($row) {
                return $row->amount ? $row->amount : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'driver']);

            return $table->make(true);
        }

        return view('admin.carHires.index');
    }

    public function create()
    {
        abort_if(Gate::denies('car_hire_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.carHires.create', compact('drivers'));
    }

    public function store(StoreCarHireRequest $request)
    {
        $carHire = CarHire::create($request->all());

        return redirect()->route('admin.car-hires.index');
    }

    public function edit(CarHire $carHire)
    {
        abort_if(Gate::denies('car_hire_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carHire->load('driver');

        return view('admin.carHires.edit', compact('carHire', 'drivers'));
    }

    public function update(UpdateCarHireRequest $request, CarHire $carHire)
    {
        $carHire->update($request->all());

        return redirect()->route('admin.car-hires.index');
    }

    public function show(CarHire $carHire)
    {
        abort_if(Gate::denies('car_hire_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carHire->load('driver');

        return view('admin.carHires.show', compact('carHire'));
    }

    public function destroy(CarHire $carHire)
    {
        abort_if(Gate::denies('car_hire_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carHire->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarHireRequest $request)
    {
        $carHires = CarHire::find(request('ids'));

        foreach ($carHires as $carHire) {
            $carHire->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
