<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\Inspections\InspectionVehicleUsageService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class MobileVehicleQrController extends Controller
{
    public function show(Request $request, string $token)
    {
        $driver = $this->driver($request);
        $vehicle = VehicleItem::with('driver:id,name')
            ->where('company_id', $driver->company_id)
            ->where('qr_token', $token)
            ->firstOrFail();
        $state = $this->activeState($vehicle);

        return response()->json([
            'vehicle' => ['id' => $vehicle->id, 'license_plate' => $vehicle->license_plate],
            'current_driver' => $vehicle->driver ? ['id' => $vehicle->driver->id, 'name' => $vehicle->driver->name] : null,
            'state' => $state,
            'can_confirm' => !in_array($state, ['maintenance', 'accident'], true),
            'driver' => ['id' => $driver->id, 'name' => $driver->name],
        ]);
    }

    public function confirm(Request $request, string $token, InspectionVehicleUsageService $service)
    {
        $driver = $this->driver($request);
        $vehicle = DB::transaction(function () use ($driver, $token, $service) {
            $vehicle = VehicleItem::query()
                ->where('company_id', $driver->company_id)
                ->where('qr_token', $token)
                ->lockForUpdate()
                ->firstOrFail();
            $state = $this->activeState($vehicle);
            if (in_array($state, ['maintenance', 'accident'], true)) {
                throw ValidationException::withMessages(['vehicle' => 'Esta viatura está marcada como manutenção ou sinistrada.']);
            }

            if ((int) $vehicle->driver_id === (int) $driver->id && $state === 'usage') {
                return $vehicle;
            }

            VehicleUsage::query()
                ->where('driver_id', $driver->id)
                ->where('vehicle_item_id', '!=', $vehicle->id)
                ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', now()))
                ->lockForUpdate()
                ->get()
                ->each(function (VehicleUsage $usage) {
                    $usage->update(['end_date' => now()->subSecond()]);
                    VehicleItem::whereKey($usage->vehicle_item_id)->where('driver_id', $usage->driver_id)->update(['driver_id' => null]);
                });

            $service->applyDirectTransfer($vehicle, [
                'mode' => $vehicle->driver_id ? 'passagem' : 'entrega',
                'source_driver_id' => $vehicle->driver_id,
                'target_driver_id' => $driver->id,
            ]);

            return $vehicle;
        });

        return response()->json(['message' => 'Viatura assumida com sucesso.', 'license_plate' => $vehicle->license_plate]);
    }

    private function driver(Request $request): Driver
    {
        $driver = Driver::where('user_id', $request->user()->id)->where('state_id', '!=', 2)->first();
        abort_unless($driver, 403, 'Apenas motoristas ativos podem assumir uma viatura.');
        return $driver;
    }

    private function activeState(VehicleItem $vehicle): ?string
    {
        return VehicleUsage::where('vehicle_item_id', $vehicle->id)
            ->where('start_date', '<=', now())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->latest('start_date')
            ->value('usage_exceptions');
    }
}
