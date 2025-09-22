<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCombustionTransactionRequest;
use App\Http\Requests\StoreCombustionTransactionRequest;
use App\Http\Requests\UpdateCombustionTransactionRequest;
use App\Models\CombustionTransaction;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CombustionTransactionController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('combustion_transaction_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = CombustionTransaction::with(['tvde_week'])->select(sprintf('%s.*', (new CombustionTransaction)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'combustion_transaction_show';
                $editGate      = 'combustion_transaction_edit';
                $deleteGate    = 'combustion_transaction_delete';
                $crudRoutePart = 'combustion-transactions';

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
            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->editColumn('card', function ($row) {
                return $row->card ? $row->card : '';
            });

            $table->editColumn('exist', function ($row) {
                if (!$row->card) {
                    return '<span class="badge badge-secondary">Sem cartão</span>';
                }

                // Procurar driver com este cartão
                $driver = \App\Models\Driver::where('card_id', function ($query) use ($row) {
                    $query->select('id')
                        ->from('cards')
                        ->where('code', $row->card)
                        ->limit(1);
                })
                    ->orWhereHas('cards', function ($query) use ($row) {
                        $query->where('code', $row->card);
                    })
                    ->first();

                if ($driver) {
                    return '';
                }

                return '<span class="badge badge-danger">Não existe</span>';
            });


            $table->editColumn('amount', function ($row) {
                return $row->amount ? $row->amount : '';
            });
            $table->editColumn('total', function ($row) {
                return $row->total ? $row->total : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'tvde_week', 'exist']);

            return $table->make(true);
        }

        return view('admin.combustionTransactions.index');
    }

    public function create()
    {
        abort_if(Gate::denies('combustion_transaction_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.combustionTransactions.create', compact('tvde_weeks'));
    }

    public function store(StoreCombustionTransactionRequest $request)
    {
        $combustionTransaction = CombustionTransaction::create($request->all());

        return redirect()->route('admin.combustion-transactions.index');
    }

    public function edit(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $combustionTransaction->load('tvde_week');

        return view('admin.combustionTransactions.edit', compact('combustionTransaction', 'tvde_weeks'));
    }

    public function update(UpdateCombustionTransactionRequest $request, CombustionTransaction $combustionTransaction)
    {
        $combustionTransaction->update($request->all());

        return redirect()->route('admin.combustion-transactions.index');
    }

    public function show(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $combustionTransaction->load('tvde_week');

        return view('admin.combustionTransactions.show', compact('combustionTransaction'));
    }

    public function destroy(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $combustionTransaction->delete();

        return back();
    }

    public function massDestroy(MassDestroyCombustionTransactionRequest $request)
    {
        $combustionTransactions = CombustionTransaction::find(request('ids'));

        foreach ($combustionTransactions as $combustionTransaction) {
            $combustionTransaction->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
