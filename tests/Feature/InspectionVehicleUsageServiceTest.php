<?php

namespace Tests\Feature;

use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\Inspections\InspectionVehicleUsageService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InspectionVehicleUsageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('license_plate');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vehicle_item_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('usage_exceptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_new_assignment_stays_open_until_an_actual_change(): void
    {
        Carbon::setTestNow('2026-11-12 08:38:09');

        $vehicle = VehicleItem::create([
            'driver_id' => 10,
            'license_plate' => 'AR-69-VJ',
        ]);

        $previous = VehicleUsage::create([
            'driver_id' => 10,
            'vehicle_item_id' => $vehicle->id,
            'start_date' => '2025-12-19 18:14:45',
            'end_date' => null,
            'usage_exceptions' => 'usage',
        ]);

        app(InspectionVehicleUsageService::class)->applyDirectTransfer($vehicle, [
            'mode' => 'passagem',
            'source_driver_id' => 10,
            'target_driver_id' => 11,
        ]);

        $this->assertSame('2026-11-12 08:38:08', $previous->fresh()->end_date);
        $this->assertDatabaseHas('vehicle_usages', [
            'driver_id' => 11,
            'vehicle_item_id' => $vehicle->id,
            'start_date' => '2026-11-12 08:38:09',
            'end_date' => null,
            'usage_exceptions' => 'usage',
        ]);
        $this->assertSame(11, $vehicle->fresh()->driver_id);
    }
}
