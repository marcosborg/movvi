<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CompanyReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverReportAllWeeksTest extends TestCase
{
    public function test_weekly_history_displays_the_stored_negative_balance(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        session(['company_id' => 1]);

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('company_id');
            $table->integer('state_id');
            $table->softDeletes();
        });
        Schema::create('tvde_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->softDeletes();
        });
        foreach (['current_accounts', 'drivers_balances', 'receipts', 'reimbursements'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->integer('driver_id');
                $table->integer('tvde_week_id');
                $table->decimal('new_balance', 12, 2)->nullable();
                $table->decimal('value', 12, 2)->nullable();
                $table->boolean('verified')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        DB::table('drivers')->insert(['id' => 1, 'name' => 'Motorista', 'company_id' => 1, 'state_id' => 1]);
        DB::table('tvde_weeks')->insert(['id' => 1, 'start_date' => '2026-11-16']);
        DB::table('drivers_balances')->insert(['driver_id' => 1, 'tvde_week_id' => 1, 'new_balance' => -25.13]);

        $view = (new CompanyReportController)->driverReportAllWeeks(1);

        $this->assertSame(-25.13, $view->getData()['results'][0]['driver_balance']);
    }
}
