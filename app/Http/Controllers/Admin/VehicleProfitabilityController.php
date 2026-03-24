<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
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

            if (!$vehicleExists || !$weekExists) {
                $message = 'Selecione uma viatura e uma semana válidas.';
            } else {
                $result = VehicleProfitabilityService::make($vehicleId, $weekId);
            }
        } elseif ($request->query()) {
            $message = 'Selecione uma viatura e uma semana para ver o relatório.';
        }

        return view('admin.vehicleProfitability.index', [
            'vehicles' => $vehicles,
            'weeks' => $weeks,
            'vehicleId' => $vehicleId,
            'weekId' => $weekId,
            'result' => $result,
            'message' => $message,
        ]);
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
            if (!$weekExists) {
                $message = 'Selecione uma semana válida.';
            } else {
                $result = VehicleProfitabilityService::makeWeek($weekId, $companyId);
            }
        } elseif ($request->query()) {
            $message = 'Selecione uma semana para ver o relatório.';
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
        $companyId = $this->selectedCompanyId();

        if (!$companyId) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', 'Selecione uma empresa antes de exportar receitas para a Conta Azul.');
        }

        $company = Company::find($companyId);
        $week = TvdeWeek::find($weekId);

        if (!$company || !$week) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', 'Empresa ou semana inválida para exportação.');
        }

        try {
            $result = $this->vehicleRevenueExporter->exportWeek($company, $week, (int) auth()->id());
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
                ->with('error_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId])
            ->with('message', sprintf(
                'Conta Azul: %d viaturas exportadas e %d ignoradas.',
                $result['exported'],
                $result['skipped']
            ));
    }

    public function setVehicleItemId($vehicle_item_id)
    {
        session()->put('vehicle_item_id', $vehicle_item_id);

        return redirect()->back();
    }

    public function pdf(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleId = (int) $request->input('vehicle_id');
        $weekId = (int) $request->input('tvde_week_id');

        if (!$vehicleId || !$weekId) {
            abort(422, 'Selecione uma viatura e uma semana para exportar o PDF.');
        }

        $vehicleExists = VehicleItem::whereKey($vehicleId)->exists();
        $weekExists = TvdeWeek::whereKey($weekId)->exists();

        if (!$vehicleExists || !$weekExists) {
            abort(422, 'Selecione uma viatura e uma semana válidas para exportar o PDF.');
        }

        $result = VehicleProfitabilityService::make($vehicleId, $weekId);

        return Pdf::loadView('admin.vehicleProfitability.pdf', [
            'result' => $result,
        ])->setOption([
            'isRemoteEnabled' => true,
        ])->stream('vehicle-profitability.pdf');
    }

    protected function selectedCompanyId(): ?int
    {
        $companyId = session('company_id');

        if (!$companyId || $companyId === '0' || $companyId === 0) {
            return null;
        }

        return (int) $companyId;
    }
}
