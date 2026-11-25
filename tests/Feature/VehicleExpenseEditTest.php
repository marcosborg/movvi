<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\VehicleExpensesController;
use App\Models\User;
use App\Models\VehicleExpense;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleExpenseEditTest extends TestCase
{
    public function test_edit_keeps_the_existing_vehicle_and_scopes_other_choices_to_the_company(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');

        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('license_plate');
            $table->boolean('suspended')->nullable();
            $table->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_item_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('usage_exceptions');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->softDeletes();
        });
        DB::table('vehicle_items')->insert([
            ['id' => 1, 'company_id' => 20, 'license_plate' => 'AA-01-AA', 'suspended' => true],
            ['id' => 2, 'company_id' => 20, 'license_plate' => 'AA-02-AA', 'suspended' => false],
            ['id' => 3, 'company_id' => 30, 'license_plate' => 'AA-03-AA', 'suspended' => false],
            ['id' => 4, 'company_id' => 20, 'license_plate' => 'AA-04-AA', 'suspended' => false],
        ]);
        DB::table('vehicle_usages')->insert([
            ['vehicle_item_id' => 2, 'usage_exceptions' => 'usage', 'start_date' => now()->subDay()],
            ['vehicle_item_id' => 3, 'usage_exceptions' => 'usage', 'start_date' => now()->subDay()],
        ]);

        session()->put('company_id', 20);
        Gate::before(static fn ($user, $ability) => $ability === 'vehicle_expense_edit' ? true : null);
        $this->actingAs(new User());
        $controller = app(VehicleExpensesController::class);

        $linked = $controller->edit(new VehicleExpense(['vehicle_item_id' => 1]))->getData();
        $this->assertSame('AA-01-AA', $linked['vehicleExpense']->vehicle_item->license_plate);
        $this->assertSame(['', 1, 2], $linked['vehicle_items']->keys()->all());

        $general = $controller->edit(new VehicleExpense())->getData();
        $this->assertNull($general['vehicleExpense']->vehicle_item);
        $this->assertSame(['', 2], $general['vehicle_items']->keys()->all());
    }
}
