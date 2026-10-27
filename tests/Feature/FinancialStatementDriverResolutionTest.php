<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FinancialStatementController;
use App\Models\Driver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialStatementDriverResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_authenticated_driver_overrides_a_stale_session_selection(): void
    {
        DB::table('drivers')->insert([
            ['id' => 10, 'user_id' => 100, 'company_id' => 1, 'name' => 'Motorista certo'],
            ['id' => 20, 'user_id' => 200, 'company_id' => 2, 'name' => 'Outro motorista'],
        ]);
        session()->put('driver_id', 20);

        $request = Request::create('/admin/financial-statements/pdf');
        $request->setUserResolver(fn () => (object) ['id' => 100]);

        $driver = $this->controller()->resolveForTest($request);

        $this->assertSame(10, $driver->id);
    }

    public function test_admin_uses_the_driver_selected_in_session(): void
    {
        DB::table('drivers')->insert([
            'id' => 20,
            'user_id' => 200,
            'company_id' => 2,
            'name' => 'Motorista selecionado',
        ]);
        session()->put('driver_id', 20);

        $request = Request::create('/admin/financial-statements/pdf');
        $request->setUserResolver(fn () => (object) ['id' => 999]);

        $driver = $this->controller()->resolveForTest($request);

        $this->assertSame(20, $driver->id);
    }

    private function controller(): object
    {
        return new class extends FinancialStatementController {
            public function resolveForTest(Request $request): Driver
            {
                return $this->resolveStatementDriver($request);
            }
        };
    }
}
