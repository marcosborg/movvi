<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\Driver;
use App\Models\VehicleBrand;
use App\Models\VehicleItem;
use App\Models\VehicleModel;
use App\Models\VehicleUsage;
use App\Services\Contracts\ContractGenerationService;
use App\Services\Contracts\DefaultContractTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractGenerationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        $this->app['db']->purge('sqlite');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vat')->nullable();
            $table->string('tvde_license')->nullable();
            $table->string('address')->nullable();
            $table->string('zip')->nullable();
            $table->string('location')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('driver_vat')->nullable();
            $table->string('citizen_card')->nullable();
            $table->string('driver_license')->nullable();
            $table->string('address')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('iban')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('vehicle_brand_id')->nullable();
            $table->foreignId('vehicle_model_id')->nullable();
            $table->string('license_plate');
            $table->unsignedInteger('year')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->foreignId('vehicle_item_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('usage_exceptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version');
            $table->longText('content');
            $table->boolean('is_active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admin_contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->foreignId('driver_id');
            $table->foreignId('contract_template_id')->nullable();
            $table->unsignedInteger('template_version')->nullable();
            $table->longText('source_content')->nullable();
            $table->longText('rendered_content')->nullable();
            $table->longText('data_snapshot')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->date('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_generates_an_immutable_versioned_snapshot_from_the_driver_page_data(): void
    {
        $company = Company::create([
            'name' => 'Empresa TVDE',
            'vat' => '515025712',
            'tvde_license' => 'TVDE-12345',
        ]);
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Motorista Um',
            'email' => 'motorista@example.test',
            'phone' => '910000000',
            'driver_vat' => '245123456',
            'citizen_card' => '12345678',
            'driver_license' => 'P-123456',
            'address' => 'Rua Um',
            'zip' => '4000-001',
            'city' => 'Porto',
            'iban' => 'PT50000000000000000000000',
        ]);
        $brand = VehicleBrand::create(['name' => 'MG']);
        $model = VehicleModel::create(['name' => 'ZS']);
        $vehicle = VehicleItem::create([
            'driver_id' => $driver->id,
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'license_plate' => 'AA-00-AA',
            'year' => 2026,
        ]);
        VehicleUsage::create([
            'driver_id' => $driver->id,
            'vehicle_item_id' => $vehicle->id,
            'start_date' => now()->subDay(),
            'usage_exceptions' => 'usage',
        ]);
        $template = ContractTemplate::create([
            'name' => 'Contrato TVDE',
            'version' => 3,
            'content' => 'Contrato {{contract.number}} — {{driver.name}} — {{vehicle.license_plate}}',
            'is_active' => true,
        ]);

        $contract = app(ContractGenerationService::class)->generate($driver, $template);

        $this->assertSame(1, $contract->number);
        $this->assertSame(3, $contract->template_version);
        $this->assertSame('Contrato 1 — Motorista Um — AA-00-AA', $contract->rendered_content);
        $this->assertSame('AA-00-AA', $contract->data_snapshot['vehicle.license_plate']);
        $this->assertSame('TVDE-12345', $contract->data_snapshot['company.tvde_license']);
        $this->assertSame($template->content, $contract->source_content);

        $template->update(['content' => 'Texto alterado']);
        $this->assertSame('Contrato 1 — Motorista Um — AA-00-AA', $contract->fresh()->rendered_content);
    }

    public function test_it_refuses_to_generate_without_the_operator_tvde_license(): void
    {
        $company = Company::create(['name' => 'Empresa TVDE', 'vat' => '515025712']);
        $driver = Driver::create(['company_id' => $company->id, 'name' => 'Motorista Sem Licença']);

        $this->expectException(ValidationException::class);

        app(ContractGenerationService::class)->generate($driver);
    }

    public function test_it_lists_missing_contract_data_before_generation(): void
    {
        $company = Company::create([
            'name' => 'Empresa TVDE',
            'vat' => '515025712',
            'tvde_license' => 'TVDE-12345',
        ]);
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Motorista Incompleto',
            'email' => 'motorista@example.test',
        ]);

        try {
            app(ContractGenerationService::class)->generate($driver);
            $this->fail('Expected incomplete contract data to be rejected.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['contract_data'][0];
            $this->assertStringContainsString('telefone', $message);
            $this->assertStringContainsString('NIF', $message);
            $this->assertStringContainsString('morada', $message);
            $this->assertStringContainsString('IBAN', $message);
        }
    }

    public function test_it_activates_the_new_default_template_without_changing_the_old_version(): void
    {
        $old = ContractTemplate::create([
            'name' => DefaultContractTemplate::NAME,
            'version' => 1,
            'content' => 'Modelo antigo',
            'is_active' => true,
        ]);

        $active = app(ContractGenerationService::class)->activeTemplate();

        $this->assertSame(DefaultContractTemplate::VERSION, $active->version);
        $this->assertSame(DefaultContractTemplate::content(), $active->content);
        $this->assertTrue($active->is_active);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame('Modelo antigo', $old->fresh()->content);
    }
}
