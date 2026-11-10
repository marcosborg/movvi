<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthGates;
use App\Models\DriverReferral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileDriverReferralApiTest extends TestCase
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
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('driver_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('referrer_driver_id');
            $table->unsignedBigInteger('referred_driver_id')->nullable();
            $table->string('referred_name');
            $table->string('referred_phone');
            $table->string('referred_phone_normalized');
            $table->string('status');
            $table->decimal('reward_amount', 10, 2);
            $table->timestamp('consent_confirmed_at');
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('drivers')->insert([
            'id' => 10,
            'user_id' => 1,
            'company_id' => 7,
            'name' => 'Motorista indicador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 1;
        Sanctum::actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_driver_can_create_and_list_a_referral_with_consent(): void
    {
        $this->postJson('/api/v1/mobile/driver/referrals', [
            'name' => 'João da Silva',
            'phone' => '+351 912 345 678',
            'consent' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('referral.status', DriverReferral::STATUS_SUBMITTED)
            ->assertJsonPath('referral.reward_amount', 50);

        $this->assertDatabaseHas('driver_referrals', [
            'company_id' => 7,
            'referrer_driver_id' => 10,
            'referred_phone_normalized' => '912345678',
        ]);

        $this->getJson('/api/v1/mobile/driver/referrals')
            ->assertOk()
            ->assertJsonCount(1, 'referrals')
            ->assertJsonPath('referrals.0.status_label', 'Recebida');
    }

    public function test_consent_and_duplicate_phone_are_enforced(): void
    {
        $payload = ['name' => 'João da Silva', 'phone' => '912345678'];
        $this->postJson('/api/v1/mobile/driver/referrals', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consent']);

        $this->postJson('/api/v1/mobile/driver/referrals', $payload + ['consent' => true])->assertCreated();
        $this->postJson('/api/v1/mobile/driver/referrals', [
            'name' => 'Outro nome',
            'phone' => '+351 912 345 678',
            'consent' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_linked_driver_becomes_eligible_after_thirty_days(): void
    {
        Carbon::setTestNow('2026-11-10 12:00:00');
        DB::table('drivers')->insert([
            'id' => 11,
            'user_id' => null,
            'company_id' => 7,
            'name' => 'Motorista indicado',
            'start_date' => '2026-10-01',
            'end_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('driver_referrals')->insert([
            'id' => 20,
            'company_id' => 7,
            'referrer_driver_id' => 10,
            'referred_driver_id' => 11,
            'referred_name' => 'Motorista indicado',
            'referred_phone' => '912345679',
            'referred_phone_normalized' => '912345679',
            'status' => DriverReferral::STATUS_JOINED,
            'reward_amount' => 50,
            'consent_confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/mobile/driver/referrals')
            ->assertOk()
            ->assertJsonPath('referrals.0.status', DriverReferral::STATUS_ELIGIBLE)
            ->assertJsonPath('referrals.0.qualified_at', '2026-10-31');
    }
}
