<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\VehicleProfitabilityController;
use App\Services\ContaAzul\ContaAzulVehicleRevenueExporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class VehicleProfitabilityCompanySelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('conta_azul_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->text('access_token')->nullable();
        });
    }

    public function test_it_selects_the_only_company_connected_to_conta_azul(): void
    {
        DB::table('companies')->insert([
            ['id' => 1, 'name' => 'Sem ligação'],
            ['id' => 2, 'name' => 'Adelmo Top, Uni. Lda.'],
        ]);
        DB::table('conta_azul_connections')->insert([
            'company_id' => 2,
            'access_token' => 'encrypted-token',
        ]);

        $this->assertSame(2, $this->selectedCompanyId());
        $this->assertSame(2, session('company_id'));
    }

    public function test_it_requires_an_explicit_choice_when_multiple_companies_are_connected(): void
    {
        DB::table('companies')->insert([
            ['id' => 1, 'name' => 'Empresa A'],
            ['id' => 2, 'name' => 'Empresa B'],
        ]);
        DB::table('conta_azul_connections')->insert([
            ['company_id' => 1, 'access_token' => 'token-a'],
            ['company_id' => 2, 'access_token' => 'token-b'],
        ]);

        $this->assertNull($this->selectedCompanyId());
        $this->assertNull(session('company_id'));
    }

    private function selectedCompanyId(): ?int
    {
        session()->forget('company_id');

        $controller = new VehicleProfitabilityController(
            $this->mock(ContaAzulVehicleRevenueExporter::class)
        );
        $method = new ReflectionMethod($controller, 'selectedCompanyId');
        $method->setAccessible(true);

        return $method->invoke($controller);
    }
}
