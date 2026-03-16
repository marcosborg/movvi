<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverController extends Controller
{
    /**
     * GET /api/v1/drivers
     *
     * Query params:
     * - driver_id (int, optional) -> when present returns only one driver.
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driverId = (int) $request->query('driver_id', 0);

        $relations = [
            'user',
            'card',
            'cards',
            'electric',
            'tool_card',
            'local',
            'contract_vat',
            'state',
            'company',
            'team',
            'vehicle',
            'vehicleUsages',
            'vehicleUsages.vehicle_item',
            'driverDocuments',
            'driverReceipts',
            'driverReceipts.tvde_week',
        ];

        if ($driverId > 0) {
            $driver = Driver::with($relations)->find($driverId);

            if (! $driver) {
                return response()->json([
                    'error' => 'Motorista nao encontrado.',
                    'driver_id' => $driverId,
                ], 404);
            }

            return response()->json([
                'mode' => 'single',
                'data' => $driver,
            ]);
        }

        return response()->json([
            'mode' => 'list',
            'data' => Driver::with($relations)->get(),
        ]);
    }
}
