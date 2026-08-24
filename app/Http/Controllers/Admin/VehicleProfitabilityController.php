<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ContaAzulVehicleRevenueExport;
use App\Models\TvdeWeek;
use App\Models\TvdeActivityEntry;
use App\Models\VehicleItem;
use App\Models\VehicleRevenueAllocationOverride;
use App\Models\VehicleUsage;
use App\Services\ContaAzul\ContaAzulVehicleRevenueExporter;
use App\Services\VehicleProfitabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleProfitabilityController extends Controller
{
    public function __construct(
        protected ContaAzulVehicleRevenueExporter $vehicleRevenueExporter
    ) {
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleId = (int) $request->input('vehicle_id');
        $weekId = (int) $request->input('tvde_week_id');

        $vehicles = VehicleItem::with('vehicle_model')
            ->orderBy('license_plate')
            ->get();
        $weeks = TvdeWeek::orderBy('start_date', 'desc')->get();

        $result = null;
        $message = null;

        if ($vehicleId && $weekId) {
            $vehicleExists = VehicleItem::whereKey($vehicleId)->exists();
            $weekExists = TvdeWeek::whereKey($weekId)->exists();

            if (! $vehicleExists || ! $weekExists) {
                $message = 'Selecione uma viatura e uma semana validas.';
            } else {
                $result = VehicleProfitabilityService::make($vehicleId, $weekId);
            }
        } elseif ($request->query()) {
            $message = 'Selecione uma viatura e uma semana para ver o relatorio.';
        }

        $selectedWeek = $weekId ? TvdeWeek::find($weekId) : null;

        return view('admin.vehicleProfitability.index', [
            'vehicles' => $vehicles,
            'weeks' => $weeks,
            'vehicleId' => $vehicleId,
            'weekId' => $weekId,
            'result' => $result,
            'message' => $message,
            'operationalVehicles' => VehicleItem::query()->where('is_service_vehicle', false)->orderBy('license_plate')->get(),
            'pendingEntries' => $weekId ? TvdeActivityEntry::with(['driver', 'tvde_operator'])
                ->where('tvde_week_id', $weekId)->where('allocation_status', 'pending')->get() : collect(),
            'weekDrivers' => $selectedWeek ? VehicleUsage::with('driver')
                ->whereNotNull('driver_id')->whereHas('driver')
                ->whereHas('vehicle_item', fn ($query) => $query->where('is_service_vehicle', false))
                ->where('start_date', '<=', $selectedWeek->getRawOriginal('end_date'))
                ->where(function ($query) use ($selectedWeek) {
                    $query->whereNull('end_date')->orWhere('end_date', '>=', $selectedWeek->getRawOriginal('start_date'));
                })->get()->pluck('driver')->unique('id')->values() : collect(),
        ]);
    }

    public function allocateEntry(Request $request, TvdeActivityEntry $entry)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate(['vehicle_item_id' => ['required', 'integer', 'exists:vehicle_items,id']]);
        $vehicle = VehicleItem::findOrFail($data['vehicle_item_id']);
        abort_if($vehicle->is_service_vehicle, 422, 'Uma viatura de serviço não pode receber faturação operacional.');

        $entry->update([
            'vehicle_item_id' => $vehicle->id,
            'allocation_status' => 'manual',
            'allocation_reason' => 'Atribuicao manual por ' . (auth()->user()->name ?? ('#' . auth()->id())),
        ]);

        return back()->with('message', 'Movimento atribuído à viatura selecionada.');
    }

    public function storeAllocationOverride(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'vehicle_item_id' => ['required', 'integer', 'exists:vehicle_items,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $vehicle = VehicleItem::findOrFail($data['vehicle_item_id']);
        abort_if($vehicle->is_service_vehicle, 422, 'Uma viatura de serviço não pode receber faturação operacional.');

        $week = TvdeWeek::findOrFail($data['tvde_week_id']);
        $wasUsed = VehicleUsage::query()
            ->where('driver_id', $data['driver_id'])
            ->where('vehicle_item_id', $vehicle->id)
            ->where('start_date', '<=', $week->getRawOriginal('end_date'))
            ->where(function ($query) use ($week) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $week->getRawOriginal('start_date'));
            })
            ->exists();
        abort_unless($wasUsed, 422, 'A viatura selecionada não foi utilizada por este motorista na semana.');

        VehicleRevenueAllocationOverride::updateOrCreate(
            ['tvde_week_id' => $data['tvde_week_id'], 'driver_id' => $data['driver_id']],
            ['vehicle_item_id' => $vehicle->id, 'created_by' => auth()->id(), 'reason' => $data['reason'] ?? null]
        );

        return back()->with('message', 'Atribuição semanal guardada e rentabilidade recalculada.');
    }

    public function week(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weekId = (int) $request->input('tvde_week_id');
        $weeks = TvdeWeek::orderBy('start_date', 'desc')->get();
        $companyId = $this->selectedCompanyId();

        $result = null;
        $message = null;

        if ($weekId) {
            $weekExists = TvdeWeek::whereKey($weekId)->exists();

            if (! $weekExists) {
                $message = 'Selecione uma semana valida.';
            } else {
                $result = VehicleProfitabilityService::makeWeek($weekId, $companyId);
                $result['export_statuses'] = $companyId
                    ? ContaAzulVehicleRevenueExport::query()
                        ->where('company_id', $companyId)
                        ->where('tvde_week_id', $weekId)
                        ->get()
                        ->keyBy('vehicle_item_id')
                    : collect();
            }
        } elseif ($request->query()) {
            $message = 'Selecione uma semana para ver o relatorio.';
        }

        return view('admin.vehicleProfitability.week', [
            'weeks' => $weeks,
            'weekId' => $weekId,
            'result' => $result,
            'message' => $message,
            'companyId' => $companyId,
        ]);
    }

    public function exportContaAzul(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weekId = (int) $request->input('tvde_week_id');
        $selectedVehicleIds = collect($request->input('vehicle_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $companyId = $this->selectedCompanyId();

        if (! $companyId) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', 'Selecione uma empresa antes de exportar receitas para a Conta Azul.');
        }

        $company = Company::find($companyId);
        $week = TvdeWeek::find($weekId);

        if (! $company || ! $week) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', 'Empresa ou semana invalida para exportacao.');
        }

        if (empty($selectedVehicleIds)) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', 'Selecione pelo menos uma viatura para comunicar a Conta Azul.');
        }

        try {
            $result = $this->vehicleRevenueExporter->exportWeek($company, $week, (int) auth()->id(), $selectedVehicleIds);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
            ->with(
                ($result['errors'] ?? 0) > 0 && ($result['exported'] ?? 0) === 0 ? 'error_message' : 'message',
                sprintf(
                    'Conta Azul: %d viaturas exportadas, %d ignoradas e %d falharam.',
                    $result['exported'] ?? 0,
                    $result['skipped'] ?? 0,
                    $result['errors'] ?? 0
                )
            );
    }

    public function setVehicleItemId($vehicleItemId)
    {
        session()->put('vehicle_item_id', $vehicleItemId);

        return redirect()->back();
    }

    public function pdf(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleId = (int) $request->input('vehicle_id');
        $weekId = (int) $request->input('tvde_week_id');

        if (! $vehicleId || ! $weekId) {
            abort(422, 'Selecione uma viatura e uma semana para exportar o PDF.');
        }

        $vehicleExists = VehicleItem::whereKey($vehicleId)->exists();
        $weekExists = TvdeWeek::whereKey($weekId)->exists();

        if (! $vehicleExists || ! $weekExists) {
            abort(422, 'Selecione uma viatura e uma semana validas para exportar o PDF.');
        }

        $result = VehicleProfitabilityService::make($vehicleId, $weekId);

        return Pdf::loadView('admin.vehicleProfitability.pdf', [
            'result' => $result,
        ])->setOption([
            'isRemoteEnabled' => true,
        ])->stream('vehicle-profitability.pdf');
    }

    public function weekPdf(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weekId = (int) $request->input('tvde_week_id');
        $companyId = $this->selectedCompanyId();

        if (! $weekId) {
            abort(422, 'Selecione uma semana para exportar o PDF.');
        }

        $week = TvdeWeek::find($weekId);
        if (! $week) {
            abort(422, 'Selecione uma semana valida para exportar o PDF.');
        }

        $result = VehicleProfitabilityService::makeWeek($weekId, $companyId);
        $company = $companyId ? Company::find($companyId) : null;

        return Pdf::loadView('admin.vehicleProfitability.week-pdf', [
            'result' => $result,
            'company' => $company,
        ])->setOption([
            'isRemoteEnabled' => true,
        ])->setPaper('a4', 'landscape')->stream($this->weekPdfFilename($week));
    }

    protected function weekPdfFilename(TvdeWeek $week): string
    {
        $weekNumber = $week->display_number ?? $week->number ?? $week->id;
        $weekYear = $week->display_year;

        if ($weekYear) {
            return sprintf('vehicle-profitability-week-%s-%s.pdf', $weekNumber, $weekYear);
        }

        return sprintf('vehicle-profitability-week-%s.pdf', $weekNumber);
    }

    protected function selectedCompanyId(): ?int
    {
        $companyId = session('company_id');

        if (! $companyId || $companyId === '0' || $companyId === 0) {
            return null;
        }

        return (int) $companyId;
    }
}
