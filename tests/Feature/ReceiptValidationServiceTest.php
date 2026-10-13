<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\User;
use App\Services\ReceiptValidationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptValidationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->softDeletes(); });
        Schema::create('drivers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('email')->nullable(); $table->softDeletes();
        });
        Schema::create('tvde_weeks', function (Blueprint $table) {
            $table->id(); $table->date('start_date'); $table->date('end_date'); $table->softDeletes();
        });
        Schema::create('drivers_balances', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('driver_id'); $table->unsignedBigInteger('tvde_week_id');
            $table->decimal('last_balance', 10, 2)->default(0); $table->decimal('new_balance', 10, 2)->default(0);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('receipts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('driver_id'); $table->unsignedBigInteger('tvde_week_id');
            $table->decimal('value', 10, 2)->nullable(); $table->decimal('balance', 10, 2)->nullable();
            $table->decimal('verified_value', 10, 2)->nullable(); $table->decimal('amount_transferred', 10, 2)->nullable();
            $table->boolean('verified')->default(false); $table->boolean('paid')->default(false);
            $table->timestamp('verified_at')->nullable(); $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('balance_applied_at')->nullable(); $table->string('processed_as')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('media', function (Blueprint $table) {
            $table->id(); $table->string('model_type'); $table->unsignedBigInteger('model_id'); $table->string('collection_name'); $table->unsignedInteger('order_column')->nullable();
        });
    }

    public function test_validation_applies_balance_once_and_updates_future_carry(): void
    {
        DB::table('users')->insert(['id' => 9]);
        DB::table('drivers')->insert(['id' => 1, 'user_id' => null, 'email' => null]);
        DB::table('tvde_weeks')->insert([
            ['id' => 1, 'start_date' => '2026-10-05', 'end_date' => '2026-10-11'],
            ['id' => 2, 'start_date' => '2026-10-12', 'end_date' => '2026-10-18'],
        ]);
        DB::table('drivers_balances')->insert([
            ['id' => 1, 'driver_id' => 1, 'tvde_week_id' => 1, 'last_balance' => 0, 'new_balance' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'driver_id' => 1, 'tvde_week_id' => 2, 'last_balance' => 100, 'new_balance' => 150, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('receipts')->insert(['id' => 1, 'driver_id' => 1, 'tvde_week_id' => 1, 'value' => 40, 'balance' => 100, 'verified' => false, 'paid' => false, 'created_at' => now(), 'updated_at' => now()]);
        $actor = User::findOrFail(9);
        $service = app(ReceiptValidationService::class);

        $service->validate(Receipt::findOrFail(1), 40, 40, $actor);
        $service->validate(Receipt::findOrFail(1), 40, 40, $actor);

        $this->assertDatabaseHas('drivers_balances', ['id' => 1, 'new_balance' => 60]);
        $this->assertDatabaseHas('drivers_balances', ['id' => 2, 'last_balance' => 60, 'new_balance' => 110]);
        $this->assertDatabaseHas('receipts', ['id' => 1, 'verified' => true, 'verified_by' => 9, 'verified_value' => 40]);
    }
}
