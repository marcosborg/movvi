<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\Reports;
use App\Models\Driver;
use App\Models\TvdeWeek;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OtherFuelTransactionAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate');
            $table->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('vehicle_item_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('usage_exceptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tesla_chargings', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 12, 2);
            $table->string('license');
            $table->dateTime('datetime');
            $table->string('card_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tvde_weeks', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_assigns_fuel_to_the_first_of_two_weekly_vehicles_with_a_date_only_end(): void
    {
        DB::table('drivers')->insert(['id' => 1, 'name' => 'Luisa']);
        DB::table('vehicle_items')->insert([
            ['id' => 1, 'license_plate' => 'BT-77-VD'],
            ['id' => 2, 'license_plate' => 'CJ-55-ZF'],
        ]);
        DB::table('vehicle_usages')->insert([
            [
                'driver_id' => 1,
                'vehicle_item_id' => 1,
                'start_date' => '2026-09-28 00:00:00',
                'end_date' => '2026-10-05 00:00:00',
                'usage_exceptions' => 'usage',
            ],
            [
                'driver_id' => 1,
                'vehicle_item_id' => 2,
                'start_date' => '2026-10-05 00:00:00',
                'end_date' => null,
                'usage_exceptions' => 'usage',
            ],
        ]);
        DB::table('tesla_chargings')->insert([
            'value' => 11.85,
            'license' => 'bt77vd',
            'datetime' => '2026-10-05 14:30:00',
            'card_type' => 'Continente',
        ]);
        DB::table('tvde_weeks')->insert([
            'id' => 40,
            'number' => 40,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
        ]);

        $transactions = $this->reportHarness()->forDriver(TvdeWeek::findOrFail(40), Driver::findOrFail(1));

        $this->assertCount(1, $transactions);
        $this->assertSame('bt77vd', $transactions->first()->license);
        $this->assertSame('Continente', $transactions->first()->card_type);
        $this->assertEqualsWithDelta(11.85, $transactions->sum('value'), 0.001);
    }

    public function test_it_keeps_precise_non_midnight_usage_boundaries(): void
    {
        DB::table('drivers')->insert(['id' => 1, 'name' => 'Luisa']);
        DB::table('vehicle_items')->insert(['id' => 1, 'license_plate' => 'BT-77-VD']);
        DB::table('vehicle_usages')->insert([
            'driver_id' => 1,
            'vehicle_item_id' => 1,
            'start_date' => '2026-10-05 08:00:00',
            'end_date' => '2026-10-05 12:00:00',
            'usage_exceptions' => 'usage',
        ]);
        DB::table('tesla_chargings')->insert([
            'value' => 11.85,
            'license' => 'BT-77-VD',
            'datetime' => '2026-10-05 14:30:00',
            'card_type' => 'Continente',
        ]);
        DB::table('tvde_weeks')->insert([
            'id' => 40,
            'number' => 40,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
        ]);

        $transactions = $this->reportHarness()->forDriver(TvdeWeek::findOrFail(40), Driver::findOrFail(1));

        $this->assertCount(0, $transactions);
    }

    private function reportHarness()
    {
        return new class {
            use Reports;

            public function forDriver(TvdeWeek $week, Driver $driver)
            {
                return $this->otherFuelTransactionsForDriver($week, $driver);
            }
        };
    }
}
