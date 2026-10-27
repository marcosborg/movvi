<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('contract_vat_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Motorista Teste',
            'email' => 'motorista@example.test',
            'password' => Hash::make('segredo-seguro'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('roles')->insert([
            'id' => 3,
            'title' => 'Driver',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert(['user_id' => 10, 'role_id' => 3]);
        DB::table('companies')->insert([
            'id' => 4,
            'name' => 'Empresa Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('drivers')->insert([
            'id' => 20,
            'user_id' => 10,
            'company_id' => 4,
            'code' => 'DRV-20',
            'name' => 'Motorista Teste',
            'email' => 'motorista@example.test',
            'phone' => '910000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_login_returns_the_driver_profile_with_the_token(): void
    {
        $this->postJson('/api/login', [
            'email' => 'motorista@example.test',
            'password' => 'segredo-seguro',
        ])->assertOk()
            ->assertJsonPath('user.roles.0', 'Driver')
            ->assertJsonPath('driver.id', 20)
            ->assertJsonPath('driver.company.name', 'Empresa Teste')
            ->assertJsonStructure(['access_token', 'token_type', 'user', 'driver']);
    }

    public function test_invalid_credentials_do_not_create_a_token(): void
    {
        $this->postJson('/api/login', [
            'email' => 'motorista@example.test',
            'password' => 'incorreta',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
