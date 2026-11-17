<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\Inspections\InspectionVehicleUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileVehiclePlateController extends Controller
{
    private const OCR_EQUIVALENT_CHARACTERS = [
        ['0', 'O', 'Q', 'D'],
        ['1', 'I', 'L'],
        ['2', 'Z'],
        ['5', 'S'],
        ['6', 'G'],
        ['8', 'B'],
    ];

    public function recognize(Request $request)
    {
        $driver = $this->driver($request);
        $validated = $request->validate([
            'recognized_text' => ['required', 'string', 'max:2000'],
        ]);

        $vehicles = VehicleItem::query()
            ->with('driver:id,name')
            ->where('company_id', $driver->company_id)
            ->where(fn ($query) => $query->whereNull('suspended')->orWhere('suspended', false)->orWhere('suspended', 0))
            ->get();
        $vehicle = $this->matchVehicle((string) $validated['recognized_text'], $vehicles);

        if (!$vehicle) {
            throw ValidationException::withMessages([
                'recognized_text' => 'Nao foi possivel associar a matricula a uma unica viatura. Tente novamente mais perto ou com melhor luz.',
            ]);
        }

        return response()->json($this->preview($vehicle, $driver));
    }

    public function confirm(Request $request, VehicleItem $vehicle, InspectionVehicleUsageService $service)
    {
        $driver = $this->driver($request);
        abort_unless((int) $vehicle->company_id === (int) $driver->company_id, 404);

        $vehicle = DB::transaction(function () use ($driver, $vehicle, $service) {
            $vehicle = VehicleItem::query()->lockForUpdate()->findOrFail($vehicle->id);
            $state = $this->activeState($vehicle);
            if (in_array($state, ['maintenance', 'accident'], true)) {
                throw ValidationException::withMessages(['vehicle' => 'Esta viatura esta marcada como manutencao ou sinistrada.']);
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

    private function preview(VehicleItem $vehicle, Driver $driver): array
    {
        $state = $this->activeState($vehicle);

        return [
            'vehicle' => ['id' => (int) $vehicle->id, 'license_plate' => (string) $vehicle->license_plate],
            'current_driver' => $vehicle->driver ? ['id' => (int) $vehicle->driver->id, 'name' => (string) $vehicle->driver->name] : null,
            'state' => $state,
            'can_confirm' => !in_array($state, ['maintenance', 'accident'], true),
            'driver' => ['id' => (int) $driver->id, 'name' => (string) $driver->name],
        ];
    }

    private function matchVehicle(string $text, $vehicles): ?VehicleItem
    {
        $lines = collect(preg_split('/\R/u', $text) ?: [])
            ->map(fn ($line) => $this->normalizePlate((string) $line))
            ->filter()
            ->values();
        $allText = $this->normalizePlate($text);
        $normalized = $vehicles->map(fn (VehicleItem $vehicle) => [
            'vehicle' => $vehicle,
            'plate' => $this->normalizePlate((string) $vehicle->license_plate),
        ])->filter(fn (array $item) => strlen($item['plate']) >= 5);

        $exact = $normalized->filter(fn (array $item) => $lines->contains(
            fn (string $line) => str_contains($line, $item['plate'])
        ) || str_contains($allText, $item['plate']));
        if ($exact->count() === 1) {
            return $exact->first()['vehicle'];
        }
        if ($exact->isNotEmpty()) {
            return null;
        }

        $probable = $normalized->filter(function (array $item) use ($lines) {
            $length = strlen($item['plate']);
            return $lines->contains(function (string $line) use ($item, $length) {
                for ($start = 0; $start <= strlen($line) - $length; $start++) {
                    if ($this->ocrDistance(substr($line, $start, $length), $item['plate']) === 1) {
                        return true;
                    }
                }
                return false;
            });
        });

        return $probable->count() === 1 ? $probable->first()['vehicle'] : null;
    }

    private function normalizePlate(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($value)) ?: '';
    }

    private function ocrDistance(string $left, string $right): int
    {
        if (strlen($left) !== strlen($right)) {
            return PHP_INT_MAX;
        }

        $distance = 0;
        for ($index = 0; $index < strlen($left); $index++) {
            if ($left[$index] === $right[$index]) {
                continue;
            }
            if (!$this->charactersAreEquivalent($left[$index], $right[$index])) {
                return PHP_INT_MAX;
            }
            $distance++;
        }

        return $distance;
    }

    private function charactersAreEquivalent(string $left, string $right): bool
    {
        foreach (self::OCR_EQUIVALENT_CHARACTERS as $group) {
            if (in_array($left, $group, true) && in_array($right, $group, true)) {
                return true;
            }
        }
        return false;
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
