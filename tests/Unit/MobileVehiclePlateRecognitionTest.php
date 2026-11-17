<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\MobileVehiclePlateController;
use App\Models\VehicleItem;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MobileVehiclePlateRecognitionTest extends TestCase
{
    public function test_it_matches_a_plate_with_separators_in_recognized_text(): void
    {
        $match = $this->match("PORTUGAL\nAA 01 AA", $this->vehicles([
            1 => 'AA-01-AA',
            2 => '23-XZ-45',
        ]));

        $this->assertSame(1, $match?->id);
    }

    public function test_it_accepts_one_common_ocr_confusion(): void
    {
        $match = $this->match('AA-O1-AA', $this->vehicles([1 => 'AA-01-AA']));

        $this->assertSame(1, $match?->id);
    }

    public function test_it_does_not_guess_when_more_than_one_vehicle_matches(): void
    {
        $match = $this->match('AA Q1 AA', $this->vehicles([
            1 => 'AA-01-AA',
            2 => 'AA-O1-AA',
        ]));

        $this->assertNull($match);
    }

    private function match(string $text, Collection $vehicles): ?VehicleItem
    {
        $method = new ReflectionMethod(MobileVehiclePlateController::class, 'matchVehicle');
        $method->setAccessible(true);

        return $method->invoke(new MobileVehiclePlateController(), $text, $vehicles);
    }

    private function vehicles(array $plates): Collection
    {
        return collect($plates)->map(function (string $plate, int $id) {
            $vehicle = new VehicleItem(['license_plate' => $plate]);
            $vehicle->id = $id;
            return $vehicle;
        })->values();
    }
}
