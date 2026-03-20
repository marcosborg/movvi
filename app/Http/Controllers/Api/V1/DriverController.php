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
     * - q (string, optional) -> search by name, email or code.
     * - state_id (int, optional)
     * - created_from (date, optional)
     * - created_to (date, optional)
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'state_id' => ['nullable', 'integer'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
        ]);

        $driverId = (int) ($validated['driver_id'] ?? 0);

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

        $search = trim((string) ($validated['q'] ?? ''));
        $stateId = isset($validated['state_id']) ? (int) $validated['state_id'] : null;
        $createdFrom = $validated['created_from'] ?? null;
        $createdTo = $validated['created_to'] ?? null;

        return response()->json([
            'mode' => 'list',
            'filters' => [
                'q' => $search !== '' ? $search : null,
                'state_id' => $stateId,
                'created_from' => $createdFrom,
                'created_to' => $createdTo,
            ],
            'data' => Driver::with($relations)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
                })
                ->when($stateId, fn ($query) => $query->where('state_id', $stateId))
                ->when($createdFrom, fn ($query) => $query->whereDate('created_at', '>=', $createdFrom))
                ->when($createdTo, fn ($query) => $query->whereDate('created_at', '<=', $createdTo))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
