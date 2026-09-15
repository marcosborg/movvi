<?php

namespace Tests\Feature;

use App\Models\CarTrack;
use App\Services\CarTrackAssignmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CarTrackDuplicatePlateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('vehicle_items', function (Blueprint $t) {
            $t->id(); $t->string('license_plate'); $t->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $t) {
            $t->id(); $t->integer('vehicle_item_id'); $t->integer('driver_id')->nullable();
            $t->dateTime('start_date'); $t->dateTime('end_date')->nullable();
            $t->string('usage_exceptions')->nullable(); $t->softDeletes();
        });
        DB::table('vehicle_items')->insert([
            ['id' => 41, 'license_plate' => 'BX-53-OL', 'deleted_at' => '2026-02-03 22:44:28'],
            ['id' => 53, 'license_plate' => 'BX-53-OL', 'deleted_at' => null],
            ['id' => 62, 'license_plate' => 'BX 53 OL', 'deleted_at' => null],
        ]);
        DB::table('vehicle_usages')->insert([
            'id' => 619, 'vehicle_item_id' => 53, 'driver_id' => 81,
            'start_date' => '2026-03-31 19:58:10', 'end_date' => '2026-09-12 10:03:20',
            'usage_exceptions' => 'usage',
        ]);
    }

    private function decision(): array
    {
        return app(CarTrackAssignmentService::class)->assignWithDiagnostics(new CarTrack([
            'license_plate' => 'bx-53-ol', 'date' => '2026-09-11 11:15:28',
        ]), false, false);
    }

    public function test_duplicate_plate_uses_the_vehicle_with_usage_at_passage_time(): void
    {
        $result = $this->decision();
        $this->assertSame(CarTrack::STATUS_ASSIGNED, $result['status']);
        $this->assertSame(53, $result['vehicle_item_id']);
        $this->assertSame(81, $result['driver_id']);
        $this->assertSame(619, $result['vehicle_usage_id']);
    }

    public function test_conflicting_usages_across_duplicate_vehicles_remain_unassigned(): void
    {
        DB::table('vehicle_usages')->insert([
            'id' => 620, 'vehicle_item_id' => 62, 'driver_id' => 82,
            'start_date' => '2026-09-01', 'end_date' => null, 'usage_exceptions' => 'usage',
        ]);
        $result = $this->decision();
        $this->assertSame(CarTrack::STATUS_MULTIPLE_USAGE_MATCHES, $result['status']);
        $this->assertNull($result['driver_id']);
    }

    public function test_historical_vehicle_still_resolves_after_soft_deletion(): void
    {
        DB::table('vehicle_items')->where('id', 53)->update(['deleted_at' => '2026-09-14']);
        $this->assertSame(81, $this->decision()['driver_id']);
    }

    public function test_deleted_usage_does_not_assign_a_driver(): void
    {
        DB::table('vehicle_usages')->where('id', 619)->update(['deleted_at' => '2026-09-14']);
        $result = $this->decision();
        $this->assertSame(CarTrack::STATUS_NO_USAGE_MATCH, $result['status']);
        $this->assertNull($result['driver_id']);
    }
}
