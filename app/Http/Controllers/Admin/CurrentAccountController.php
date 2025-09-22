<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCurrentAccountRequest;
use App\Http\Requests\StoreCurrentAccountRequest;
use App\Http\Requests\UpdateCurrentAccountRequest;
use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\Company;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CurrentAccountController extends Controller
{
    public function index(Request $request)
    {        

        if ($request->ajax()) {
            
            if (session()->get('company_id') !== '0') {
                $query = CurrentAccount::with(['tvde_week', 'driver'])
                    ->whereHas('driver', function ($driver) {
                        $driver->where('company_id', session()->get('company_id'));
                    })
                    ->select(sprintf('%s.*', (new CurrentAccount)->table));
            } else {
                $query = CurrentAccount::with(['tvde_week', 'driver'])
                    ->select(sprintf('%s.*', (new CurrentAccount)->table));
            }

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'current_account_show';
                $editGate = 'current_account_edit';
                $deleteGate = 'current_account_delete';
                $crudRoutePart = 'current-accounts';

                return view(
                    'partials.datatablesActions',
                    compact(
                        'viewGate',
                        'editGate',
                        'deleteGate',
                        'crudRoutePart',
                        'row'
                    )
                );
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->addColumn('driver.company.name', function ($row) {
                return $row->driver->company ? $row->driver->company->name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->editColumn('driver.name', function ($row) {
                return $row->driver ? (is_string($row->driver) ? $row->driver : $row->driver->name) : '';
            });
            $table->editColumn('driver.email', function ($row) {
                return $row->driver ? (is_string($row->driver) ? $row->driver : $row->driver->email) : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'tvde_week', 'driver']);

            return $table->make(true);
        }

        $tvde_weeks = TvdeWeek::get();
        $drivers = Driver::get();

        return view('admin.currentAccounts.index', compact('tvde_weeks', 'drivers'));
    }

    public function create()
    {
        abort_if(Gate::denies('current_account_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.currentAccounts.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreCurrentAccountRequest $request)
    {
        $currentAccount = CurrentAccount::create($request->all());

        return redirect()->route('admin.current-accounts.index');
    }

    public function edit(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $currentAccount->load('tvde_week', 'driver');

        return view('admin.currentAccounts.edit', compact('currentAccount', 'drivers', 'tvde_weeks'));
    }

    public function update(UpdateCurrentAccountRequest $request, CurrentAccount $currentAccount)
    {
        $currentAccount->update($request->all());

        return redirect()->route('admin.current-accounts.index');
    }

    public function show(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currentAccount->load('tvde_week', 'driver');

        return view('admin.currentAccounts.show', compact('currentAccount'));
    }

    public function destroy(CurrentAccount $currentAccount)
    {
        abort_if(Gate::denies('current_account_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currentAccount->delete();

        return back();
    }

    public function massDestroy(MassDestroyCurrentAccountRequest $request)
    {
        $currentAccounts = CurrentAccount::find(request('ids'));

        foreach ($currentAccounts as $currentAccount) {
            $currentAccount->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
