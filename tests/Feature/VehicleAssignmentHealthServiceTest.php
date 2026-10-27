<?php

namespace Tests\Feature;

use App\Services\VehicleAssignmentHealthService;
use Carbon\Carbon;
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
        Schema::create('drivers', function (Blueprint $table) { $table->id(); $table->string('name'); $table->unsignedBigInteger('company_id')->nullable(); $table->softDeletes(); });
        Schema::create('vehicle_items', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->string('license_plate'); $table->boolean('suspended')->default(false); $table->softDeletes(); });
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

    public function test_it_limits_assignment_health_to_the_selected_company(): void
    {
        DB::table('drivers')->insert([
            ['id' => 1, 'name' => 'Ana', 'company_id' => 10],
            ['id' => 2, 'name' => 'Bruno', 'company_id' => 20],
        ]);
        DB::table('vehicle_items')->insert([
            ['id' => 1, 'company_id' => 10, 'license_plate' => 'AA-00-AA'],
            ['id' => 2, 'company_id' => 20, 'license_plate' => 'BB-00-BB'],
        ]);
        DB::table('vehicle_usages')->insert([
            'driver_id' => 1,
            'vehicle_item_id' => 1,
            'start_date' => now()->subDay(),
            'end_date' => null,
            'usage_exceptions' => 'usage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $health = app(VehicleAssignmentHealthService::class)->current(10);

        $this->assertSame(0, $health['counts']['unassigned_vehicles']);
        $this->assertSame([], $health['unassigned_vehicles']->pluck('license_plate')->all());
    }

    public function test_a_date_only_end_remains_active_until_the_end_of_that_day(): void
    {
        Carbon::setTestNow('2026-10-27 16:30:00');
        DB::table('drivers')->insert(['id' => 1, 'name' => 'Ana', 'company_id' => 10]);
        DB::table('vehicle_items')->insert(['id' => 1, 'company_id' => 10, 'license_plate' => 'AA-00-AA']);
        DB::table('vehicle_usages')->insert([
            'driver_id' => 1,
            'vehicle_item_id' => 1,
            'start_date' => '2026-10-20 00:00:00',
            'end_date' => '2026-10-27 00:00:00',
            'usage_exceptions' => 'usage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $health = app(VehicleAssignmentHealthService::class)->current(10);

        $this->assertSame(0, $health['counts']['unassigned_vehicles']);
        Carbon::setTestNow();
    }
}
