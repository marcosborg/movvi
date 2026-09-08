<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyReportBatchValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('drivers', function (Blueprint $t) { $t->id(); });
        Schema::create('tvde_weeks', function (Blueprint $t) { $t->id(); $t->date('start_date'); $t->softDeletes(); });
        Schema::create('current_accounts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('driver_id'); $t->unsignedBigInteger('tvde_week_id');
            $t->text('data'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('drivers_balances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('driver_id'); $t->unsignedBigInteger('tvde_week_id');
            $t->double('value'); $t->double('last_balance'); $t->double('new_balance');
            $t->timestamps(); $t->softDeletes();
        });
        DB::table('tvde_weeks')->insert(['id' => 1, 'start_date' => '2026-09-01']);
        for ($id = 1; $id <= 40; $id++) DB::table('drivers')->insert(['id' => $id]);
        $this->withoutMiddleware();
    }

    private function rows(): array
    {
        return array_map(fn ($id) => ['tvde_week_id' => 1, 'driver' => [
            'id' => $id, 'total' => 42.5,
            'earnings' => array_fill_keys(array_map(fn ($i) => 'field_'.$i, range(1, 100)), 1),
        ]], range(1, 40));
    }

    public function test_large_json_batch_validates_every_selected_driver(): void
    {
        $this->postJson('/admin/company-reports/validate-data', ['data' => $this->rows(), 'expected_count' => 40])
            ->assertOk()->assertJson(['validated_count' => 40]);
        $this->assertSame(40, DB::table('current_accounts')->count());
        $this->assertSame(40, DB::table('drivers_balances')->where('value', 42.5)->count());
    }

    public function test_truncated_batch_is_rejected_before_any_write(): void
    {
        $this->postJson('/admin/company-reports/validate-data', ['data' => array_slice($this->rows(), 0, 10), 'expected_count' => 40])
            ->assertStatus(422);
        $this->assertSame(0, DB::table('current_accounts')->count());
    }

    public function test_invalid_last_driver_rejects_entire_batch(): void
    {
        $rows = $this->rows(); $rows[39]['driver']['id'] = 9999;
        $this->postJson('/admin/company-reports/validate-data', ['data' => $rows, 'expected_count' => 40])->assertStatus(422);
        $this->assertSame(0, DB::table('current_accounts')->count());
    }

    public function test_database_failure_rolls_back_earlier_drivers(): void
    {
        DB::statement("CREATE TRIGGER reject_last BEFORE INSERT ON drivers_balances WHEN NEW.driver_id = 40 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->postJson('/admin/company-reports/validate-data', ['data' => $this->rows(), 'expected_count' => 40])->assertStatus(500);
        $this->assertSame(0, DB::table('current_accounts')->count());
        $this->assertSame(0, DB::table('drivers_balances')->count());
    }
}
