<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyExpenseReimbursementRequest;
use App\Http\Requests\StoreExpenseReimbursementRequest;
use App\Http\Requests\UpdateExpenseReimbursementRequest;
use App\Models\ExpenseReimbursement;
use App\Models\VehicleItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class ExpenseReimbursementController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('expense_reimbursement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = ExpenseReimbursement::with(['vehicle_item'])->select(sprintf('%s.*', (new ExpenseReimbursement)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'expense_reimbursement_show';
                $editGate      = 'expense_reimbursement_edit';
                $deleteGate    = 'expense_reimbursement_delete';
                $crudRoutePart = 'expense-reimbursements';

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

            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'vehicle_item']);

            return $table->make(true);
        }

        return view('admin.expenseReimbursements.index');
    }

    public function create()
    {
        abort_if(Gate::denies('expense_reimbursement_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.expenseReimbursements.create', compact('vehicle_items'));
    }

    public function store(StoreExpenseReimbursementRequest $request)
    {
        $expenseReimbursement = ExpenseReimbursement::create($request->all());

        return redirect()->route('admin.expense-reimbursements.index');
    }

    public function edit(ExpenseReimbursement $expenseReimbursement)
    {
        abort_if(Gate::denies('expense_reimbursement_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $expenseReimbursement->load('vehicle_item');

        return view('admin.expenseReimbursements.edit', compact('expenseReimbursement', 'vehicle_items'));
    }

    public function update(UpdateExpenseReimbursementRequest $request, ExpenseReimbursement $expenseReimbursement)
    {
        $expenseReimbursement->update($request->all());

        return redirect()->route('admin.expense-reimbursements.index');
    }

    public function show(ExpenseReimbursement $expenseReimbursement)
    {
        abort_if(Gate::denies('expense_reimbursement_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expenseReimbursement->load('vehicle_item');

        return view('admin.expenseReimbursements.show', compact('expenseReimbursement'));
    }

    public function destroy(ExpenseReimbursement $expenseReimbursement)
    {
        abort_if(Gate::denies('expense_reimbursement_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expenseReimbursement->delete();

        return back();
    }

    public function massDestroy(MassDestroyExpenseReimbursementRequest $request)
    {
        $expenseReimbursements = ExpenseReimbursement::find(request('ids'));

        foreach ($expenseReimbursements as $expenseReimbursement) {
            $expenseReimbursement->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
