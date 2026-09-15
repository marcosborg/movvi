<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthGates;
use App\Models\User;
use App\Models\VehicleExpense;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleExpenseApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(AuthGates::class);
        Schema::create('companies', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->softDeletes();
        });
        Schema::create('vehicle_items', function (Blueprint $t) {
            $t->id(); $t->integer('company_id'); $t->string('license_plate'); $t->softDeletes();
        });
        Schema::create('vehicle_expenses', function (Blueprint $t) {
            $t->id(); $t->integer('vehicle_item_id'); $t->string('expense_type');
            $t->date('date'); $t->text('description'); $t->decimal('value', 12, 2); $t->decimal('vat', 5, 2);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('drivers', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->integer('company_id'); $t->softDeletes();
        });
        DB::table('companies')->insert([['id' => 1, 'user_id' => 10], ['id' => 2, 'user_id' => 20]]);
        DB::table('vehicle_items')->insert([
            ['id' => 1, 'company_id' => 1, 'license_plate' => 'AA-01-AA', 'deleted_at' => null],
            ['id' => 2, 'company_id' => 2, 'license_plate' => 'BB-02-BB', 'deleted_at' => null],
            ['id' => 3, 'company_id' => 1, 'license_plate' => 'CC-03-CC', 'deleted_at' => '2026-09-10 12:00:00'],
        ]);
        foreach ([1 => 1, 2 => 2, 3 => 3] as $id => $vehicle) {
            DB::table('vehicle_expenses')->insert([
                'id' => $id, 'vehicle_item_id' => $vehicle, 'expense_type' => 'Oficina especial',
                'date' => '2026-09-10', 'description' => '<p>Revisão &amp; limpeza</p>',
                'value' => 123.45, 'vat' => 23,
                'created_at' => '2026-09-10 10:00:00', 'updated_at' => '2026-09-10 10:00:00',
                'deleted_at' => $id === 3 ? '2026-09-11 10:00:00' : null,
            ]);
        }
    }

    private function signIn(bool $admin = false, bool $permission = true): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 10;
        $user->shouldReceive('getForeignKey')->andReturn('user_id');
        $user->shouldReceive('hasRole')->with('Admin')->andReturn($admin);
        $this->actingAs($user);
        Gate::define('vehicle_expense_access', fn () => $permission);
    }

    public function test_authentication_and_permission_are_required(): void
    {
        $this->getJson('/api/v1/vehicle-expenses?company_id=1')->assertUnauthorized();
        $this->signIn(false, false);
        $this->getJson('/api/v1/vehicle-expenses?company_id=1')->assertForbidden();
    }

    public function test_company_scope_and_admin_access(): void
    {
        $this->signIn();
        $this->getJson('/api/v1/vehicle-expenses?company_id=2')->assertForbidden();
        $this->getJson('/api/v1/vehicle-expenses?company_id=1')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.source_id', 'movvi:vehicle-expense:1')
            ->assertJsonPath('data.0.description', 'Revisão & limpeza')
            ->assertJsonPath('data.0.value', 123.45)
            ->assertJsonPath('data.1.deleted_at', '2026-09-11 10:00:00');
        $this->signIn(true);
        $this->getJson('/api/v1/vehicle-expenses?company_id=2')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pagination_replay_and_deleted_records(): void
    {
        $this->signIn();
        $first = $this->getJson('/api/v1/vehicle-expenses?company_id=1&per_page=1')->assertOk();
        $first->assertJsonPath('meta.next_after_id', 1)->assertJsonPath('meta.has_more', true);
        $this->getJson('/api/v1/vehicle-expenses?company_id=1&per_page=1&after_id=1')
            ->assertOk()->assertJsonPath('data.0.id', 3)->assertJsonPath('meta.has_more', false);
        $this->getJson('/api/v1/vehicle-expenses?company_id=1&per_page=1')
            ->assertJsonPath('data.0.source_id', $first->json('data.0.source_id'));
        $this->getJson('/api/v1/vehicle-expenses?company_id=1&updated_since=2026-09-11%2010:00:00')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 3);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->signIn();
        $this->getJson('/api/v1/vehicle-expenses')->assertUnprocessable();
        $this->getJson('/api/v1/vehicle-expenses?company_id=1&per_page=201&updated_since=oops')->assertUnprocessable();
    }

    public function test_custom_groups_are_reusable_and_legacy_labels_preserved(): void
    {
        $this->assertSame('Oficina especial', VehicleExpense::expenseTypes()['Oficina especial']);
        $this->assertSame('Pneus', (new VehicleExpense(['expense_type' => 'Penus']))->expense_type_label);
        $this->assertSame('Oficina especial', VehicleExpense::find(1)->expense_type_label);
    }
}
