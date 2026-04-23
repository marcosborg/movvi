<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use App\Services\AdminDriverImpersonationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDriverImpersonationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Route::has('admin._impersonation_test_write')) {
            Route::middleware(['web', 'auth', 'prevent-writes-while-impersonating'])
                ->post('/admin/_impersonation-test-write', function () {
                    return response('ok');
                })
                ->name('admin._impersonation_test_write');
        }

        if (!Route::has('admin._impersonation_test_user')) {
            Route::middleware(['web', 'auth'])
                ->get('/admin/_impersonation-test-user', function () {
                    return response((string) auth()->user()->email);
                })
                ->name('admin._impersonation_test_user');
        }
    }

    public function test_admin_can_start_impersonation_for_driver_with_user(): void
    {
        [$admin, $driverUser, $driver] = $this->createAdminAndDriver();

        $response = $this->actingAs($admin)->post(route('admin.impersonation.start'), [
            'driver_id' => $driver->id,
        ]);

        $response->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs(User::findOrFail($driverUser->id));
        $this->assertSame($admin->id, session(AdminDriverImpersonationService::SESSION_ADMIN_ID));
        $this->assertSame($driver->id, session(AdminDriverImpersonationService::SESSION_DRIVER_ID));
    }

    public function test_driver_without_user_cannot_be_impersonated(): void
    {
        [$admin] = $this->createAdminAndDriver();

        $driver = Driver::create([
            'code' => 'DRV-NOUSER-' . uniqid(),
            'name' => 'Driver without user',
        ]);

        $response = $this->from(route('admin.home'))
            ->actingAs($admin)
            ->post(route('admin.impersonation.start'), [
                'driver_id' => $driver->id,
            ]);

        $response->assertRedirect(route('admin.home'));
        $response->assertSessionHasErrors('driver_id');
        $this->assertAuthenticatedAs(User::findOrFail($admin->id));
    }

    public function test_stop_restores_original_admin(): void
    {
        [$admin, $driverUser, $driver] = $this->createAdminAndDriver();

        $this->actingAs($admin)->post(route('admin.impersonation.start'), [
            'driver_id' => $driver->id,
        ]);

        $response = $this->post(route('admin.impersonation.stop'));

        $response->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs(User::findOrFail($admin->id));
        $this->assertNull(session(AdminDriverImpersonationService::SESSION_ADMIN_ID));
        $this->assertNull(session(AdminDriverImpersonationService::SESSION_DRIVER_ID));
    }

    public function test_search_endpoint_is_available_for_admin_and_impersonating_session_only(): void
    {
        [$admin, $driverUser, $driver] = $this->createAdminAndDriver();

        $this->actingAs($admin)
            ->getJson(route('admin.impersonation.drivers', ['q' => $driver->name]))
            ->assertOk()
            ->assertJsonFragment(['id' => $driver->id]);

        $this->post(route('admin.impersonation.start'), [
            'driver_id' => $driver->id,
        ])->assertRedirect(route('admin.home'));

        $this->getJson(route('admin.impersonation.drivers', ['q' => $driver->name]))
            ->assertOk()
            ->assertJsonFragment(['id' => $driver->id]);

        $this->post(route('admin.impersonation.stop'))
            ->assertRedirect(route('admin.home'));

        $anotherDriverUser = $this->createUserDirect('plain-driver-' . uniqid() . '@example.com', 'Plain Driver User');
        $this->attachRole($anotherDriverUser->id, 'Driver');

        $this->actingAs($anotherDriverUser)
            ->getJson(route('admin.impersonation.drivers'))
            ->assertForbidden();
    }

    public function test_post_requests_are_blocked_while_impersonating(): void
    {
        [$admin, $driverUser, $driver] = $this->createAdminAndDriver();

        $this->actingAs($admin)->post(route('admin.impersonation.start'), [
            'driver_id' => $driver->id,
        ])->assertRedirect(route('admin.home'));

        $response = $this->from(route('admin.home'))
            ->post('/admin/_impersonation-test-write');

        $response->assertRedirect(route('admin.home'));
        $response->assertSessionHas('error_message', 'Modo motorista ativo em leitura. Saia deste modo para editar.');
    }

    public function test_get_requests_run_as_the_impersonated_driver_user(): void
    {
        [$admin, $driverUser, $driver] = $this->createAdminAndDriver();

        $this->actingAs($admin)->post(route('admin.impersonation.start'), [
            'driver_id' => $driver->id,
        ])->assertRedirect(route('admin.home'));

        $this->get('/admin/_impersonation-test-user')
            ->assertOk()
            ->assertSee($driverUser->email);
    }

    private function createAdminAndDriver(): array
    {
        $admin = $this->createUserDirect('admin-' . uniqid() . '@example.com', 'Admin User');
        $this->attachRole($admin->id, 'Admin');

        $driverUser = $this->createUserDirect('driver-' . uniqid() . '@example.com', 'Driver User');
        $this->attachRole($driverUser->id, 'Driver');

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'code' => 'DRV-' . uniqid(),
            'name' => 'Driver ' . uniqid(),
        ]);

        return [$admin, $driverUser, $driver];
    }

    private function createUserDirect(string $email, string $name): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'verified' => 1,
            'verified_at' => now(),
            'email_verified_at' => now(),
            'remember_token' => uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($userId);
    }

    private function attachRole(int $userId, string $title): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'title' => $title . ' ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($title === 'Admin') {
            DB::table('roles')->where('id', $roleId)->update(['title' => 'Admin']);
        }

        if ($title === 'Driver') {
            DB::table('roles')->where('id', $roleId)->update(['title' => 'Driver']);
        }

        DB::table('role_user')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }
}
