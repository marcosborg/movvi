<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\Reports;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WeeklyReportRosterTest extends TestCase
{
    public function test_inactive_drivers_are_counted_for_the_right_week_and_platform_totals_are_not_duplicated(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('company_id');
            $table->integer('state_id');
            $table->string('uber_uuid')->nullable();
            $table->string('bolt_name')->nullable();
            $table->string('bolt_individual_id')->nullable();
            $table->softDeletes();
        });
        foreach (['current_accounts', 'receipts', 'drivers_balances'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('driver_id');
                $table->integer('tvde_week_id');
                $table->softDeletes();
            });
        }
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('usage_exceptions')->nullable();
            $table->softDeletes();
        });
        Schema::create('tvde_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('tvde_week_id');
            $table->integer('tvde_operator_id');
            $table->string('driver_code');
            $table->decimal('net', 12, 2);
            $table->softDeletes();
        });
        DB::table('drivers')->insert([
            ['id' => 1, 'name' => 'Ativo', 'company_id' => 1, 'state_id' => 1, 'uber_uuid' => 'shared', 'bolt_name' => null, 'deleted_at' => null],
            ['id' => 2, 'name' => 'Histórico', 'company_id' => 1, 'state_id' => 2, 'uber_uuid' => 'shared', 'bolt_name' => null, 'deleted_at' => '2026-01-15'],
            ['id' => 3, 'name' => 'Inativo com Bolt', 'company_id' => 1, 'state_id' => 2, 'uber_uuid' => null, 'bolt_name' => 'bolt-3', 'deleted_at' => null],
            ['id' => 4, 'name' => 'Inativo sem dados', 'company_id' => 1, 'state_id' => 2, 'uber_uuid' => null, 'bolt_name' => null, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Outra empresa', 'company_id' => 2, 'state_id' => 1, 'uber_uuid' => 'shared', 'bolt_name' => null, 'deleted_at' => null],
        ]);
        DB::table('current_accounts')->insert(['tvde_week_id' => 52, 'driver_id' => 2]);
        DB::table('tvde_activities')->insert([
            ['company_id' => 1, 'tvde_week_id' => 52, 'tvde_operator_id' => 1, 'driver_code' => 'shared', 'net' => 100],
            ['company_id' => 1, 'tvde_week_id' => 52, 'tvde_operator_id' => 2, 'driver_code' => 'bolt-3', 'net' => 75],
            ['company_id' => 2, 'tvde_week_id' => 52, 'tvde_operator_id' => 1, 'driver_code' => 'shared', 'net' => 1000],
        ]);
        $report = new class {
            use Reports { weeklyReportRoster as public; }
        };
        [$drivers, $activities] = $report->weeklyReportRoster(1, 52, Carbon::parse('2025-12-22'), Carbon::parse('2025-12-28')->endOfDay());
        $this->assertEqualsCanonicalizing([1, 2, 3], $drivers->pluck('id')->all());
        $this->assertFalse($activities->has('1:1'));
        $this->assertEquals(100, $activities->get('2:1')->sum('net'));
        $this->assertEquals(75, $activities->get('3:2')->sum('net'));
        [$nextWeekDrivers] = $report->weeklyReportRoster(1, 53, Carbon::parse('2025-12-29'), Carbon::parse('2026-01-04')->endOfDay());
        $this->assertSame([1], $nextWeekDrivers->pluck('id')->all());
    }
}
