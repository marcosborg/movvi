<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\CompanyReportApiController;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('title'); $t->softDeletes(); });
        Schema::create('permissions', function (Blueprint $t) { $t->id(); $t->string('title'); $t->softDeletes(); });
        Schema::create('permission_role', function (Blueprint $t) { $t->integer('permission_id'); $t->integer('role_id'); });
        Schema::create('role_user', function (Blueprint $t) { $t->integer('user_id'); $t->integer('role_id'); });
        Schema::create('companies', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('main')->default(false); $t->softDeletes();
        });
        Schema::create('drivers', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->integer('company_id'); $t->softDeletes();
        });
        Schema::create('tvde_weeks', function (Blueprint $t) {
            $t->id(); $t->integer('number')->nullable(); $t->date('start_date'); $t->date('end_date'); $t->softDeletes();
        });
        Schema::create('vehicle_items', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->softDeletes();
        });
        DB::table('companies')->insert([
            ['id' => 1, 'name' => 'A', 'main' => true], ['id' => 2, 'name' => 'B', 'main' => false],
        ]);
        for ($i = 1; $i <= 30; $i++) {
            $start = \Carbon\Carbon::parse('2025-12-29')->addWeeks($i - 1);
            DB::table('tvde_weeks')->insert(['id' => $i, 'start_date' => $start->toDateString(), 'end_date' => $start->addDays(6)->toDateString()]);
        }
        // Deterministic report fixture; HTTP routing, authentication, selection and serialization remain real.
        $this->app->bind(CompanyReportApiController::class, fn () => new class extends CompanyReportApiController {
            public function getWeekReport($company_id, $tvde_week_id)
            {
                $driver = new Driver(['name' => 'Fixture']);
                $driver->id = 1;
                $driver->earnings = ['uber' => ['uber_net' => 100], 'bolt' => ['bolt_net' => 50]];
                $driver->total = 75;
                return ['drivers' => collect([$driver]), 'totals' => collect([
                    'total_car_hire' => $company_id * 100, 'total_percent_value' => $tvde_week_id,
                    'total_adjustments' => -5,
                ])];
            }
        });
    }

    private function loginAs(bool $admin = true): void
    {
        $user = new User();
        $user->id = 1;
        DB::table('roles')->insert(['id' => 1, 'title' => $admin ? 'Admin' : 'Gestor']);
        DB::table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        Sanctum::actingAs($user);
    }

    public function test_authentication_and_admin_access(): void
    {
        $this->getJson('/api/v1/company-reports/operational-revenue?tvde_week_id=1')->assertUnauthorized();
        $this->loginAs(false);
        $this->getJson('/api/v1/company-reports/operational-revenue?tvde_week_id=1')->assertForbidden();
        $this->getJson('/api/v1/company-reports/weekly?tvde_week_id=1')->assertForbidden();
    }

    public function test_revenue_uses_requested_company_and_week_and_preserves_weekly_fields(): void
    {
        $this->loginAs();
        $this->getJson('/api/v1/company-reports/operational-revenue?company_id=2&tvde_week_id=1&date=01-01-2026')
            ->assertOk()->assertJsonPath('company.id', 2)->assertJsonPath('week.id', 1)
            ->assertJsonPath('week.year', 2026)->assertJsonPath('currency', 'EUR')
            ->assertJsonPath('data.operational_revenue', 196);
        $this->getJson('/api/v1/company-reports/weekly?company_id=2&tvde_week_id=1')
            ->assertOk()->assertJsonPath('data.totals.operational_revenue', 196)
            ->assertJsonPath('data.drivers.0.total_net', 150)->assertJsonPath('data.drivers.0.total', 75)
            ->assertJsonStructure(['data' => ['totals' => ['net_uber', 'total_car_hire', 'total_percent_value', 'total_adjustments']]]);
        $this->getJson('/api/v1/company-reports/weekly')->assertOk()->assertJsonPath('week.id', 30);
    }

    public function test_invalid_filters_never_fall_back(): void
    {
        $this->loginAs();
        foreach (['date=31-02-2026', 'date=garbage', 'date=', 'tvde_week_id=abc', 'tvde_week_id=1&date=05-01-2026'] as $query) {
            foreach (['weekly', 'operational-revenue'] as $endpoint) {
                $this->getJson('/api/v1/company-reports/'.$endpoint.'?'.$query)->assertUnprocessable();
            }
        }
        foreach (['tvde_week_id=999', 'date=01-01-2000', 'company_id=999&tvde_week_id=1'] as $query) {
            $this->getJson('/api/v1/company-reports/weekly?'.$query)->assertNotFound();
        }
        $this->getJson('/api/v1/company-reports/operational-revenue')->assertUnprocessable();
    }

    public function test_weeks_pagination_includes_history_and_iso_year(): void
    {
        $this->loginAs();
        $this->getJson('/api/v1/weeks')->assertOk()->assertJsonCount(24, 'weeks')->assertJsonPath('pagination.total', 30);
        $this->getJson('/api/v1/weeks?page=2')->assertOk()->assertJsonCount(6, 'weeks')->assertJsonPath('weeks.5.year', 2026);
        $this->getJson('/api/v1/weeks?per_page=100')->assertOk()->assertJsonCount(30, 'weeks');
        $this->getJson('/api/v1/weeks?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/weeks?date_from=2025-12-29&date_to=2025-12-29')->assertOk()->assertJsonCount(1, 'weeks');
    }

    public function test_vehicle_company_mismatch_and_permission_are_rejected(): void
    {
        $this->loginAs();
        Gate::define('vehicle_profitability_access', fn () => false);
        $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id=1')->assertForbidden();
        Gate::define('vehicle_profitability_access', fn () => true);
        DB::table('vehicle_items')->insert(['id' => 1, 'company_id' => 2]);
        $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id=1&vehicle_id=1&company_id=1')->assertNotFound();
        $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id=1&company_id=999')->assertNotFound();
    }
}
