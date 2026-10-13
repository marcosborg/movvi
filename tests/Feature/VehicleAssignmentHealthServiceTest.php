<?php

namespace Tests\Feature;

use App\Services\VehicleAssignmentHealthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleAssignmentHealthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('drivers', function (Blueprint $table) { $table->id(); $table->string('name'); $table->softDeletes(); });
        Schema::create('vehicle_items', function (Blueprint $table) { $table->id(); $table->string('license_plate'); $table->boolean('suspended')->default(false); $table->softDeletes(); });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('driver_id')->nullable(); $table->unsignedBigInteger('vehicle_item_id');
            $table->dateTime('start_date'); $table->dateTime('end_date')->nullable(); $table->string('usage_exceptions')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
    }

    public function test_it_reports_all_three_assignment_anomalies(): void
    {
        DB::table('drivers')->insert([['id' => 1, 'name' => 'Ana'], ['id' => 2, 'name' => 'Bruno']]);
        DB::table('vehicle_items')->insert([['id' => 1, 'license_plate' => 'AA-00-AA'], ['id' => 2, 'license_plate' => 'BB-00-BB'], ['id' => 3, 'license_plate' => 'CC-00-CC']]);
        foreach ([[1, 1], [2, 1], [1, 2]] as [$driver, $vehicle]) {
            DB::table('vehicle_usages')->insert(['driver_id' => $driver, 'vehicle_item_id' => $vehicle, 'start_date' => now()->subDay(), 'end_date' => null, 'usage_exceptions' => 'usage', 'created_at' => now(), 'updated_at' => now()]);
        }

        $health = app(VehicleAssignmentHealthService::class)->current();

        $this->assertSame(1, $health['counts']['unassigned_vehicles']);
        $this->assertSame(1, $health['counts']['vehicles_with_multiple_drivers']);
        $this->assertSame(1, $health['counts']['drivers_with_multiple_vehicles']);
    }
}
