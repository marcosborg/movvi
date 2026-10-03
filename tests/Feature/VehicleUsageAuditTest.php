<?php

namespace Tests\Feature;

use App\Models\VehicleUsage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleUsageAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

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

        Schema::create('vehicle_usage_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_usage_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_vehicle_usage_changes_are_audited(): void
    {
        $usage = VehicleUsage::create([
            'driver_id' => 10,
            'vehicle_item_id' => 20,
            'start_date' => '2026-10-03 08:00:00',
            'usage_exceptions' => 'usage',
        ]);

        $this->assertDatabaseHas('vehicle_usage_audits', [
            'vehicle_usage_id' => $usage->id,
            'action' => 'created',
        ]);

        $usage->update(['driver_id' => 11]);

        $audit = DB::table('vehicle_usage_audits')->where('action', 'updated')->first();
        $this->assertSame(10, json_decode($audit->old_values, true)['driver_id']);
        $this->assertSame(11, json_decode($audit->new_values, true)['driver_id']);

        $usage->delete();

        $this->assertDatabaseHas('vehicle_usage_audits', [
            'vehicle_usage_id' => $usage->id,
            'action' => 'deleted',
        ]);
    }
}
