<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Services\VehicleProfitabilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleProfitabilityController extends Controller
{
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
                $message = 'Selecione uma viatura e uma semana v\u00e1lidas.';
            } else {
                $result = VehicleProfitabilityService::make($vehicleId, $weekId);
            }
        } elseif ($request->query()) {
            $message = 'Selecione uma viatura e uma semana para ver o relat\u00f3rio.';
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
            abort(422, 'Selecione uma viatura e uma semana v\u00e1lidas para exportar o PDF.');
        }

        $result = VehicleProfitabilityService::make($vehicleId, $weekId);

        return Pdf::loadView('admin.vehicleProfitability.pdf', [
            'result' => $result,
        ])->setOption([
            'isRemoteEnabled' => true,
        ])->stream('vehicle-profitability.pdf');
    }
}
