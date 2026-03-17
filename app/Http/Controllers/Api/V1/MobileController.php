<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use App\Services\VehicleProfitabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        $driver = Driver::with(['company', 'contract_vat', 'state'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('title')->values(),
            ],
            'driver' => $driver ? $this->serializeDriver($driver) : null,
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $driver = Driver::with(['company', 'contract_vat', 'state'])
            ->where('user_id', $user->id)
            ->first();

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista não encontrado para o utilizador autenticado.',
            ], 404);
        }

        [$week, $requestedDate] = $this->resolveWeek($request->query('date'));

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE não encontrada.',
                'received' => $requestedDate,
            ], 404);
        }

        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
        ])->first();

        $driverBalance = DriversBalance::where([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
        ])->first();

        $vehicle = $this->resolveVehicleForWeek($driver->id, $week);
        $profitability = $vehicle
            ? VehicleProfitabilityService::make((int) $vehicle->id, (int) $week->id)
            : null;

        return response()->json([
            'driver' => $this->serializeDriver($driver),
            'week' => [
                'id' => $week->id,
                'number' => $week->number,
                'start_date' => $week->start_date,
                'end_date' => $week->end_date,
                'requested_date' => $requestedDate,
            ],
            'account_summary' => $currentAccount ? (json_decode($currentAccount->data, true) ?? []) : null,
            'balance' => $this->serializeBalance($driverBalance, $driver),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'model' => optional($vehicle->vehicle_model)->name,
            ] : null,
            'vehicle_profitability' => $profitability,
        ]);
    }

    private function resolveWeek(?string $date): array
    {
        $requestedDate = trim((string) $date);

        if ($requestedDate !== '') {
            try {
                $dbDate = Carbon::createFromFormat('d-m-Y', $requestedDate)->format('Y-m-d');
            } catch (\Throwable $e) {
                return [null, $requestedDate];
            }

            return [TvdeWeek::where('start_date', $dbDate)->first(), $requestedDate];
        }

        $week = TvdeWeek::orderByDesc('start_date')->first();

        return [$week, $week?->start_date];
    }

    private function resolveVehicleForWeek(int $driverId, TvdeWeek $week)
    {
        $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();

        $usage = VehicleUsage::with('vehicle_item.vehicle_model')
            ->where('driver_id', $driverId)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->orderByDesc('start_date')
            ->first();

        return $usage?->vehicle_item;
    }

    private function serializeDriver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'code' => $driver->code,
            'name' => $driver->name,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'company' => $driver->company ? [
                'id' => $driver->company->id,
                'name' => $driver->company->name,
            ] : null,
            'state' => $driver->state ? [
                'id' => $driver->state->id,
                'name' => $driver->state->name,
            ] : null,
            'contract_vat' => $driver->contract_vat ? [
                'id' => $driver->contract_vat->id,
                'name' => $driver->contract_vat->name,
                'percent' => $driver->contract_vat->percent,
                'rf' => $driver->contract_vat->rf,
                'iva' => $driver->contract_vat->iva,
            ] : null,
        ];
    }

    private function serializeBalance(?DriversBalance $balance, Driver $driver): ?array
    {
        if (! $balance) {
            return null;
        }

        $vatFactor = (float) optional($driver->contract_vat)->iva / 100;
        $rfFactor = (float) optional($driver->contract_vat)->rf / 100;
        $vat = round(((float) $balance->value) * $vatFactor, 2);
        $rf = round(-((float) $balance->value) * $rfFactor, 2);

        return [
            'value' => (float) $balance->value,
            'last_balance' => (float) $balance->last_balance,
            'new_balance' => (float) $balance->new_balance,
            'vat' => $vat,
            'rf' => $rf,
            'final' => round(((float) $balance->new_balance) + $vat + $rf, 2),
        ];
    }
}
