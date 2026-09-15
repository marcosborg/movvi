<?php

namespace Tests\Feature;

use App\Services\DriverBalanceCorrectionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverBalanceCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('tvde_weeks', function (Blueprint $t) {
            $t->id(); $t->date('start_date'); $t->softDeletes();
        });
        Schema::create('drivers_balances', function (Blueprint $t) {
            $t->id(); $t->integer('driver_id'); $t->integer('tvde_week_id');
            $t->double('value'); $t->double('last_balance'); $t->double('new_balance');
            $t->string('manual_status')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        DB::table('tvde_weeks')->insert([
            ['id' => 30, 'start_date' => '2026-08-31'],
            ['id' => 10, 'start_date' => '2026-09-07'],
            ['id' => 20, 'start_date' => '2026-09-14'],
            ['id' => 5, 'start_date' => '2026-09-21'],
        ]);
        DB::table('drivers_balances')->insert([
            ['id' => 1, 'driver_id' => 1, 'tvde_week_id' => 30, 'value' => 80, 'last_balance' => 0, 'new_balance' => 80],
            ['id' => 2, 'driver_id' => 1, 'tvde_week_id' => 10, 'value' => 20, 'last_balance' => 80, 'new_balance' => 100],
            // Net weekly movement is 30 after a payment of 20; preserve it.
            ['id' => 3, 'driver_id' => 1, 'tvde_week_id' => 20, 'value' => 50, 'last_balance' => 100, 'new_balance' => 130],
            ['id' => 4, 'driver_id' => 1, 'tvde_week_id' => 5, 'value' => -40, 'last_balance' => 130, 'new_balance' => 90],
            ['id' => 5, 'driver_id' => 2, 'tvde_week_id' => 20, 'value' => 70, 'last_balance' => 10, 'new_balance' => 80],
        ]);
    }

    public function test_zero_carries_forward_in_date_order_preserving_weekly_movements(): void
    {
        DB::table('drivers_balances')->where('id', 3)->update(['manual_status' => 'paid']);
        app(DriverBalanceCorrectionService::class)->correct(2, 0);
        $this->assertDatabaseHas('drivers_balances', ['id' => 2, 'new_balance' => 0, 'last_balance' => 80]);
        $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => 0, 'new_balance' => 30, 'value' => 50, 'manual_status' => 'paid']);
        $this->assertDatabaseHas('drivers_balances', ['id' => 4, 'last_balance' => 30, 'new_balance' => -10]);
        $this->assertDatabaseHas('drivers_balances', ['id' => 1, 'new_balance' => 80]);
        $this->assertDatabaseHas('drivers_balances', ['id' => 5, 'new_balance' => 80]);
    }

    public function test_resaving_an_old_zero_repairs_stale_carries_and_is_idempotent(): void
    {
        DB::table('drivers_balances')->where('id', 2)->update(['new_balance' => 0]);
        $service = app(DriverBalanceCorrectionService::class);
        $service->correct(2, 0);
        $service->correct(2, 0);
        $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => 0, 'new_balance' => 30]);
        $this->assertDatabaseHas('drivers_balances', ['id' => 4, 'last_balance' => 30, 'new_balance' => -10]);
    }

    public function test_positive_and_negative_corrections_round_to_cents(): void
    {
        app(DriverBalanceCorrectionService::class)->correct(2, -25.126);
        $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => -25.13, 'new_balance' => 4.87]);
        app(DriverBalanceCorrectionService::class)->correct(2, 1000.25);
        $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => 1000.25, 'new_balance' => 1030.25]);
    }

    public function test_failure_rolls_back_the_whole_correction(): void
    {
        DB::statement("CREATE TRIGGER fail_carry BEFORE UPDATE ON drivers_balances WHEN NEW.id = 4 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        try {
            app(DriverBalanceCorrectionService::class)->correct(2, 0);
            $this->fail('Expected database failure');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertDatabaseHas('drivers_balances', ['id' => 2, 'new_balance' => 100]);
            $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => 100, 'new_balance' => 130]);
        }
    }

    private function signIn(bool $admin): void
    {
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('hasRole')->with('Admin')->andReturn($admin);
        $this->actingAs($user)->withoutMiddleware();
    }

    public function test_update_endpoint_propagates_an_admin_correction(): void
    {
        $this->signIn(true);
        $this->postJson('/admin/financial-statements/update-balance', [
            'driver_balance_id' => 2, 'new_balance' => 0,
        ])->assertOk();
        $this->assertDatabaseHas('drivers_balances', ['id' => 3, 'last_balance' => 0, 'new_balance' => 30]);
    }

    public function test_update_endpoint_rejects_non_admin_and_invalid_balance(): void
    {
        $this->signIn(false);
        $this->postJson('/admin/financial-statements/update-balance', [
            'driver_balance_id' => 2, 'new_balance' => 0,
        ])->assertForbidden();
        $this->assertDatabaseHas('drivers_balances', ['id' => 2, 'new_balance' => 100]);
        $this->signIn(true);
        $this->postJson('/admin/financial-statements/update-balance', [
            'driver_balance_id' => 999, 'new_balance' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('driver_balance_id');
    }
}
