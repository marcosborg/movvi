<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthGates;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileVehiclePlateApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AuthGates::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
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
            $table->boolean('suspended')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_item_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('usage_exceptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('drivers')->insert([
            'id' => 10,
            'user_id' => 1,
            'company_id' => 7,
            'state_id' => 1,
            'name' => 'Motorista autenticado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vehicle_items')->insert([
            ['id' => 20, 'company_id' => 7, 'driver_id' => null, 'license_plate' => 'AA-01-AA', 'suspended' => false],
            ['id' => 21, 'company_id' => 8, 'driver_id' => null, 'license_plate' => 'BB-02-BB', 'suspended' => false],
        ]);

        $user = new User();
        $user->id = 1;
        Sanctum::actingAs($user);
    }

    public function test_active_driver_can_recognize_a_vehicle_from_the_same_company(): void
    {
        $this->postJson('/api/v1/mobile/driver/vehicle-plate/recognize', [
            'recognized_text' => "PORTUGAL\nAA 01 AA",
        ])
            ->assertOk()
            ->assertJsonPath('vehicle.id', 20)
            ->assertJsonPath('vehicle.license_plate', 'AA-01-AA')
            ->assertJsonPath('can_confirm', true);
    }

    public function test_driver_cannot_resolve_a_vehicle_from_another_company(): void
    {
        $this->postJson('/api/v1/mobile/driver/vehicle-plate/recognize', [
            'recognized_text' => 'BB 02 BB',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recognized_text']);
    }
}
