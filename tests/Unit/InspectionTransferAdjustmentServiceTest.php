<?php

namespace Tests\Unit;

use App\Models\Adjustment;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Services\Inspections\InspectionTransferAdjustmentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InspectionTransferAdjustmentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'panel.date_format' => 'd-m-Y',
        ]);
        DB::purge('sqlite');

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('percent', 10, 2)->nullable();
            $table->string('category');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->boolean('company_expense')->default(false);
            $table->boolean('fleet_management')->default(false);
            $table->boolean('affects_vehicle_profitability')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('adjustment_driver', function (Blueprint $table) {
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedBigInteger('driver_id');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_creates_manual_adjustment_for_target_driver_in_current_week(): void
    {
        Carbon::setTestNow('2026-11-03 12:00:00');
        $driver = Driver::create(['company_id' => 7, 'name' => 'Motorista destino']);
        $vehicle = new VehicleItem(['company_id' => 7]);
        $vehicle->id = 10;

        $adjustment = app(InspectionTransferAdjustmentService::class)->createForTransfer(
            $vehicle,
            ['mode' => 'passagem', 'source_driver_id' => 99, 'target_driver_id' => $driver->id],
            ['name' => 'Acerto combinado', 'type' => 'refund', 'amount' => 12.345]
        );

        $this->assertSame(Adjustment::CATEGORY_MANUAL, $adjustment->category);
        $this->assertSame('refund', $adjustment->type);
        $this->assertSame('02-11-2026', $adjustment->start_date);
        $this->assertSame('08-11-2026', $adjustment->end_date);
        $this->assertEquals(12.35, $adjustment->amount);
        $this->assertSame([$driver->id], $adjustment->drivers()->pluck('drivers.id')->all());
    }

    public function test_recollection_uses_source_driver_and_rejects_other_company(): void
    {
        $driver = Driver::create(['company_id' => 8, 'name' => 'Motorista origem']);
        $vehicle = new VehicleItem(['company_id' => 7]);
        $vehicle->id = 10;

        $this->expectException(ValidationException::class);

        app(InspectionTransferAdjustmentService::class)->buildPlan(
            $vehicle,
            ['mode' => 'recolha', 'source_driver_id' => $driver->id, 'target_driver_id' => null],
            ['name' => 'Acerto', 'type' => 'deduct', 'amount' => 5]
        );
    }
}
