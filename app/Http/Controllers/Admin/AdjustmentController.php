<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyAdjustmentRequest;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use App\Models\Adjustment;
use App\Models\Company;
use App\Models\Driver;
use App\Models\TvdeWeek;
use Carbon\Carbon;
use Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AdjustmentController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
{
    abort_if(Gate::denies('adjustment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    if ($request->ajax()) {
        $query = Adjustment::with(['drivers', 'company']);

        if (session()->has('company_id') && session()->get('company_id') !== '0') {
            $query->where('company_id', session()->get('company_id'));
        }

        // 👇 Aplica o filtro do driver_id antes do select()
        if ($request->filled('driver_id')) {
            $query->whereHas('drivers', function ($q) use ($request) {
                $q->where('drivers.id', $request->driver_id);
            });
        }

        // 👇 Só agora adiciona o select, depois de todos os filtros
        $query->select(sprintf('%s.*', (new Adjustment)->table));

        $table = Datatables::of($query);
        $table->addColumn('placeholder', '&nbsp;');
        $table->addColumn('actions', '&nbsp;');

        $table->editColumn('actions', function ($row) {
            $viewGate = 'adjustment_show';
            $editGate = 'adjustment_edit';
            $deleteGate = 'adjustment_delete';
            $crudRoutePart = 'adjustments';

            return view('partials.datatablesActions', compact(
                'viewGate', 'editGate', 'deleteGate', 'crudRoutePart', 'row'
            ));
        });

        $table->editColumn('id', fn($row) => $row->id ?? '');
        $table->editColumn('name', fn($row) => $row->name ?? '');
        $table->editColumn('type', fn($row) => $row->type ? Adjustment::TYPE_RADIO[$row->type] : '');
        $table->editColumn('category', fn($row) => $row->category_label ?? '');
        $table->editColumn('amount', fn($row) => $row->amount ?? '');
        $table->editColumn('percent', fn($row) => $row->percent ?? '');

        $table->editColumn('drivers', function ($row) {
            $labels = [];
            foreach ($row->drivers as $driver) {
                $labels[] = sprintf('<span class="label label-info label-many">%s</span>', $driver->name);
            }
            return implode(' ', $labels);
        });

        $table->addColumn('company_name', fn($row) => $row->company->name ?? '');
        $table->editColumn('affects_vehicle_profitability', fn($row) => '<input type="checkbox" disabled ' . ($row->affects_vehicle_profitability ? 'checked' : null) . '>');

        $table->rawColumns(['actions', 'placeholder', 'drivers', 'company', 'affects_vehicle_profitability']);
        return $table->make(true);
    }

    $drivers = Driver::get();
    return view('admin.adjustments.index', compact('drivers'));
}


    public function create()
    {
        abort_if(Gate::denies('adjustment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.adjustments.create', compact('companies', 'drivers'));
    }

    public function store(StoreAdjustmentRequest $request)
    {
        $payload = $request->validated();
        $payload['affects_vehicle_profitability'] = $request->boolean('affects_vehicle_profitability');
        $payload['company_expense'] = false;
        $payload['fleet_management'] = false;
        $driverIds = $request->input('drivers', []);

        if (
            ($payload['category'] ?? null) === Adjustment::CATEGORY_CAUTION_RECEIVED
            && (int) ($payload['dilution_weeks'] ?? 1) > 1
        ) {
            $this->storeDilutedCautionAdjustments($payload, $driverIds);

            return redirect()->route('admin.adjustments.index')
                ->with('message', 'Caucao diluida criada com sucesso.');
        }

        unset($payload['dilution_weeks']);

        $adjustment = Adjustment::create($payload);
        $adjustment->drivers()->sync($driverIds);

        return redirect()->route('admin.adjustments.index');
    }

    public function edit(Adjustment $adjustment)
    {
        abort_if(Gate::denies('adjustment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (session()->has('company_id') && session()->get('company_id') !== '0') {
            $drivers = Driver::where('company_id', session()->get('company_id'))->pluck('name', 'id');
        } else {
            $drivers = Driver::pluck('name', 'id');
        }

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $adjustment->load('drivers', 'company');

        return view('admin.adjustments.edit', compact('adjustment', 'companies', 'drivers'));
    }

    public function update(UpdateAdjustmentRequest $request, Adjustment $adjustment)
    {
        $payload = $request->validated();
        $payload['affects_vehicle_profitability'] = $request->boolean('affects_vehicle_profitability');
        $payload['company_expense'] = false;
        $payload['fleet_management'] = false;

        $adjustment->update($payload);
        $adjustment->drivers()->sync($request->input('drivers', []));

        return redirect()->route('admin.adjustments.index');
    }

    public function show(Adjustment $adjustment)
    {
        abort_if(Gate::denies('adjustment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $adjustment->load('drivers', 'company');

        return view('admin.adjustments.show', compact('adjustment'));
    }

    public function destroy(Adjustment $adjustment)
    {
        abort_if(Gate::denies('adjustment_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $adjustment->delete();

        return back();
    }

    public function massDestroy(MassDestroyAdjustmentRequest $request)
    {
        $adjustments = Adjustment::find(request('ids'));

        foreach ($adjustments as $adjustment) {
            $adjustment->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    protected function storeDilutedCautionAdjustments(array $payload, array $driverIds): void
    {
        $weeksToDilute = (int) ($payload['dilution_weeks'] ?? 1);
        $startWeek = $this->resolveDilutionStartWeek((string) ($payload['start_date'] ?? ''));
        $weeks = TvdeWeek::query()
            ->where('start_date', '>=', $startWeek->getRawOriginal('start_date'))
            ->orderBy('start_date')
            ->limit($weeksToDilute)
            ->get();

        if ($weeks->count() < $weeksToDilute) {
            throw ValidationException::withMessages([
                'dilution_weeks' => 'Nao existem semanas TVDE suficientes para criar todas as parcelas da caucao.',
            ]);
        }

        $amounts = $this->splitAmountAcrossWeeks((float) ($payload['amount'] ?? 0), $weeksToDilute);
        $baseName = trim((string) ($payload['name'] ?? 'Caucao'));

        DB::transaction(function () use ($payload, $driverIds, $weeks, $weeksToDilute, $amounts, $baseName) {
            foreach ($weeks->values() as $index => $week) {
                $weekPayload = $payload;
                unset($weekPayload['dilution_weeks']);

                $weekPayload['name'] = sprintf('%s (%d/%d)', $baseName, $index + 1, $weeksToDilute);
                $weekPayload['amount'] = $amounts[$index];
                $weekPayload['start_date'] = Carbon::parse($week->getRawOriginal('start_date'))->format(config('panel.date_format'));
                $weekPayload['end_date'] = Carbon::parse($week->getRawOriginal('end_date'))->format(config('panel.date_format'));

                $adjustment = Adjustment::create($weekPayload);
                $adjustment->drivers()->sync($driverIds);
            }
        });
    }

    protected function resolveDilutionStartWeek(string $startDate): TvdeWeek
    {
        $normalizedDate = Carbon::createFromFormat(config('panel.date_format'), $startDate)->format('Y-m-d');

        $week = TvdeWeek::query()
            ->where('start_date', '<=', $normalizedDate)
            ->where('end_date', '>=', $normalizedDate)
            ->orderByDesc('start_date')
            ->first();

        if ($week) {
            return $week;
        }

        $nextWeek = TvdeWeek::query()
            ->where('start_date', '>=', $normalizedDate)
            ->orderBy('start_date')
            ->first();

        if ($nextWeek) {
            return $nextWeek;
        }

        throw ValidationException::withMessages([
            'start_date' => 'Nao foi encontrada nenhuma semana TVDE para iniciar a diluicao.',
        ]);
    }

    protected function splitAmountAcrossWeeks(float $amount, int $weeks): array
    {
        $amountInCents = (int) round($amount * 100);
        $baseSlice = intdiv($amountInCents, $weeks);
        $remainder = $amountInCents % $weeks;
        $parts = [];

        for ($index = 0; $index < $weeks; $index++) {
            $slice = $baseSlice;
            if ($remainder > 0) {
                $slice++;
                $remainder--;
            }

            $parts[] = $slice / 100;
        }

        return $parts;
    }
}
