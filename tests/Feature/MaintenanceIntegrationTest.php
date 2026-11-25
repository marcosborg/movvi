<?php

namespace Tests\Feature;

use App\Services\Muv\MuvFinancialReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MaintenanceIntegrationTest extends TestCase
{
    private const BASE = '/api/v1/integrations/maintenance';
    private const TOKEN = 'fixture-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'maintenance_integration.enabled' => true,
            'maintenance_integration.company_id' => 1,
            'maintenance_integration.token_hash' => hash('sha256', self::TOKEN),
            'maintenance_integration.expires_at' => now()->addDay()->toIso8601String(),
        ]);
        DB::purge('sqlite');
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token');
        });
        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('license_plate');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_item_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('usage_exceptions');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_item_id');
            $table->string('expense_type');
            $table->string('supplier')->nullable();
            $table->date('date');
            $table->text('description');
            $table->decimal('value', 12, 2);
            $table->decimal('vat', 5, 2);
            $table->timestamps();
            $table->softDeletes();
        });
        (require database_path('migrations/2026_11_25_100000_add_maintenance_integration_records.php'))->up();

        DB::table('companies')->insert([['id' => 1], ['id' => 2]]);
        DB::table('vehicle_items')->insert([
            ['id' => 10, 'company_id' => 1, 'driver_id' => null, 'license_plate' => 'AA-01-AA'],
            ['id' => 11, 'company_id' => 1, 'driver_id' => 5, 'license_plate' => 'BB-02-BB'],
            ['id' => 20, 'company_id' => 2, 'driver_id' => null, 'license_plate' => 'CC-03-CC'],
        ]);
        DB::table('vehicle_usages')->insert([
            'id' => 50, 'vehicle_item_id' => 11, 'driver_id' => 5,
            'usage_exceptions' => 'usage', 'start_date' => '2026-11-01 10:00:00',
        ]);
    }

    public function test_sandbox_authentication_expiry_and_company_scope(): void
    {
        $this->getJson(self::BASE . '/vehicles')->assertUnauthorized();
        $this->withToken('wrong-token')->getJson(self::BASE . '/vehicles')->assertUnauthorized();
        $this->withToken(self::TOKEN)->getJson(self::BASE . '/vehicles?company_id=2&per_page=1')
            ->assertOk()->assertJsonPath('data.0.id', 10)->assertJsonPath('meta.next_after_id', 10);
        $this->getJson(self::BASE . '/vehicles?after_id=10')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 11);
        $this->getJson(self::BASE . '/vehicle-usages')->assertOk()->assertJsonPath('data.0.id', 50);
        $this->getJson('/api/v1/vehicle-usages')->assertUnauthorized();
        config(['maintenance_integration.allowed_ip' => '192.0.2.1']);
        $this->getJson(self::BASE . '/vehicles')->assertForbidden();
        config(['maintenance_integration.allowed_ip' => null, 'maintenance_integration.expires_at' => now()->subDay()->toIso8601String()]);
        $this->getJson(self::BASE . '/vehicles')->assertUnauthorized();
        app()->instance('env', 'production');
        $this->postJson(self::BASE . '/maintenance-periods', [])->assertNotFound();
    }

    public function test_maintenance_preserves_assignments_and_retries_the_same_reference(): void
    {
        $this->withToken(self::TOKEN);
        $path = self::BASE . '/maintenance-periods';
        $data = ['external_reference' => 'job-001', 'started_at' => '2026-11-25T10:15:00+00:00'];
        $this->postJson($path, $data + ['vehicle_id' => 20])->assertNotFound();
        $this->postJson($path, $data + ['vehicle_id' => 11])->assertConflict();
        $this->assertDatabaseHas('vehicle_items', ['id' => 11, 'driver_id' => 5]);
        $this->assertDatabaseHas('vehicle_usages', ['id' => 50, 'end_date' => null, 'driver_id' => 5]);
        $this->postJson($path, $data + ['vehicle_id' => 10, 'license_plate' => 'AA01AA'])->assertUnprocessable();
        $created = $this->postJson($path, $data + ['license_plate' => 'aa 01 aa'])
            ->assertCreated()->assertJsonPath('data.start_date', '2026-11-25 10:15:00')->json('data.id');
        $this->postJson($path, $data + ['vehicle_id' => 10])->assertOk()->assertJsonPath('data.id', $created);
        $this->postJson($path, ['started_at' => '2026-11-25T10:16:00+00:00'] + $data + ['vehicle_id' => 10])
            ->assertConflict();
        $this->postJson($path, ['external_reference' => 'job-002'] + $data + ['vehicle_id' => 10])->assertConflict();
        $this->patchJson($path . '/job-001', ['ended_at' => '2026-11-25T10:14:00+00:00'])->assertUnprocessable();
        $end = ['ended_at' => '2026-11-25T11:45:00+00:00'];
        $this->patchJson($path . '/job-001', $end)->assertOk()->assertJsonPath('data.end_date', '2026-11-25 11:45:00');
        $this->patchJson($path . '/job-001', $end)->assertOk();
        $this->assertDatabaseCount('vehicle_usages', 2);
        $this->assertDatabaseCount('maintenance_integration_records', 1);
    }

    public function test_expense_replay_correction_and_cancellation_reach_the_cost_report(): void
    {
        $this->withToken(self::TOKEN);
        $path = self::BASE . '/vehicle-expenses';
        $fields = [
            'category' => 'Penus', 'net_amount' => '123.45', 'vat_rate' => '23.00',
            'date' => '2026-11-25', 'document_number' => 'FT 12/2026',
            'workshop' => 'Oficina de Teste', 'description' => 'Dois pneus',
        ];
        $payload = $fields + ['vehicle_id' => 10, 'external_reference' => 'expense-001'];
        $id = $this->postJson($path, $payload)->assertCreated()->assertJsonPath('data.document_number', 'FT 12/2026')->json('data.id');
        $this->postJson($path, $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->putJson($path . '/expense-001', ['net_amount' => '100.54'] + $fields)->assertOk();
        $report = new class(app(\App\Services\ContaAzul\ContaAzulManagerDashboardService::class)) extends MuvFinancialReportService {
            public function costs(): array
            {
                return $this->vehicleExpenses(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), 1)->all();
            }
        };
        $this->assertSame(100.54, $report->costs()[0]['amount']);
        $this->assertStringContainsString('AA-01-AA', $report->costs()[0]['description']);
        $this->deleteJson($path . '/expense-001')->assertNoContent();
        $this->deleteJson($path . '/expense-001')->assertNoContent();
        $this->assertSame([], $report->costs());
        $this->postJson($path, $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson($path)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['document_number', 'deleted_at']]]);
        $this->assertDatabaseCount('vehicle_expenses', 1);
    }
}
