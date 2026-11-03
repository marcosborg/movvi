<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Inspections\InspectionTransferAdjustmentService;
use App\Services\Inspections\InspectionVehicleUsageService;
use App\Services\Inspections\InspectionWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MobileTransferAdjustmentApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('license_plate');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('roles')->insert(['id' => 1, 'title' => 'Gestor']);
        DB::table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        DB::table('drivers')->insert([
            'id' => 20,
            'company_id' => 7,
            'state_id' => 1,
            'name' => 'Motorista destino',
        ]);
        DB::table('vehicle_items')->insert([
            'id' => 10,
            'company_id' => 7,
            'driver_id' => null,
            'license_plate' => 'AA-00-AA',
        ]);

        $user = new User();
        $user->id = 1;
        Sanctum::actingAs($user);
    }

    public function test_transfer_does_not_require_an_adjustment(): void
    {
        $usage = Mockery::mock(InspectionVehicleUsageService::class);
        $usage->shouldReceive('applyDirectTransfer')->once()->andReturn([
            'mode' => 'entrega',
            'source_driver_id' => null,
            'target_driver_id' => 20,
        ]);
        $adjustments = Mockery::mock(InspectionTransferAdjustmentService::class);
        $adjustments->shouldNotReceive('createForTransfer');

        $this->app->instance(InspectionWorkflowService::class, Mockery::mock(InspectionWorkflowService::class));
        $this->app->instance(InspectionVehicleUsageService::class, $usage);
        $this->app->instance(InspectionTransferAdjustmentService::class, $adjustments);

        $this->postJson('/api/v1/mobile/inspections/transfers', [
            'vehicle_id' => 10,
            'driver_id' => 20,
            'source_driver_id' => null,
            'transfer_mode' => 'entrega',
        ])
            ->assertCreated()
            ->assertJsonPath('transfer.mode', 'entrega')
            ->assertJsonPath('adjustment', null);
    }

    public function test_partial_adjustment_is_rejected_only_when_option_is_used(): void
    {
        $this->app->instance(InspectionWorkflowService::class, Mockery::mock(InspectionWorkflowService::class));
        $this->app->instance(InspectionVehicleUsageService::class, Mockery::mock(InspectionVehicleUsageService::class));
        $this->app->instance(InspectionTransferAdjustmentService::class, Mockery::mock(InspectionTransferAdjustmentService::class));

        $this->postJson('/api/v1/mobile/inspections/transfers', [
            'vehicle_id' => 10,
            'driver_id' => 20,
            'transfer_mode' => 'entrega',
            'adjustment' => ['type' => 'refund'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['adjustment.name', 'adjustment.amount']);
    }
}
