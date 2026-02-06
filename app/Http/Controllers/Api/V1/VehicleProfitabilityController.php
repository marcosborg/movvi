<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Services\VehicleProfitabilityService;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleProfitabilityController extends Controller
{
    /**
     * GET /api/v1/vehicle-profitabilities
     *
     * Query params:
     * - tvde_week_id (int) OR date (d-m-Y)
     * - vehicle_id (int, optional) -> when present returns per-vehicle breakdown; otherwise returns week totals for all vehicles.
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleId = (int) $request->query('vehicle_id', 0);
        $weekId = (int) $request->query('tvde_week_id', 0);
        $date = trim((string) $request->query('date', ''));

        if (!$weekId && $date !== '') {
            try {
                $carbonDate = Carbon::createFromFormat('d-m-Y', $date);
            } catch (\Throwable $e) {
                return response()->json([
                    'error' => 'Formato de data inválido. Usa d-m-Y, por exemplo 03-11-2025.',
                    'received' => $date,
                ], 422);
            }

            $dbDate = $carbonDate->format('Y-m-d');
            $week = TvdeWeek::where('start_date', $dbDate)->first();

            if (!$week) {
                return response()->json([
                    'error' => 'Semana TVDE não encontrada para a data indicada.',
                    'start_date' => $dbDate,
                ], 404);
            }

            $weekId = (int) $week->id;
        }

        if (!$weekId) {
            return response()->json([
                'error' => 'Parâmetro obrigatório em falta: tvde_week_id ou date (d-m-Y).',
            ], 422);
        }

        $week = TvdeWeek::find($weekId);
        if (!$week) {
            return response()->json([
                'error' => 'Semana TVDE não encontrada.',
                'tvde_week_id' => $weekId,
            ], 404);
        }

        if ($vehicleId > 0) {
            $vehicle = VehicleItem::find($vehicleId);
            if (!$vehicle) {
                return response()->json([
                    'error' => 'Viatura não encontrada.',
                    'vehicle_id' => $vehicleId,
                ], 404);
            }

            return response()->json([
                'mode' => 'vehicle',
                'params' => [
                    'vehicle_id' => $vehicleId,
                    'tvde_week_id' => $weekId,
                ],
                'data' => VehicleProfitabilityService::make($vehicleId, $weekId),
            ]);
        }

        return response()->json([
            'mode' => 'week',
            'params' => [
                'tvde_week_id' => $weekId,
            ],
            'data' => VehicleProfitabilityService::makeWeek($weekId),
        ]);
    }
}

