<?php

namespace Tests\Unit;

use App\Models\AdminContract;
use App\Models\Company;
use App\Models\Driver;
use App\Models\VehicleBrand;
use App\Models\VehicleItem;
use App\Models\VehicleModel;
use App\Services\Contracts\ContractTemplateRenderer;
use App\Services\Contracts\DefaultContractTemplate;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class ContractTemplateRendererTest extends TestCase
{
    public function test_default_template_contains_all_requested_corrections(): void
    {
        $content = DefaultContractTemplate::content();

        $this->assertStringContainsString('CEDÊNCIA DE VIATURA', $content);
        $this->assertStringNotContainsStringIgnoringCase('aluguer', $content);
        $this->assertStringContainsString('Avenida Vasco da Gama, N.º 774, Espaço 30, 4430-247 Mafamude, Vila Nova de Gaia', $content);
        $this->assertStringContainsString('365 dias, prorrogável por períodos sucessivos de 365 dias', $content);
        $this->assertStringContainsString('mensagens automáticas', $content);
        $this->assertStringContainsString('sinalética, dicas, sugestões e procedimentos da empresa', $content);
    }

    public function test_it_replaces_whitelisted_tags_and_returns_the_snapshot(): void
    {
        Carbon::setTestNow('2026-11-03 18:45:00');

        $company = new Company([
            'name' => 'Adelmo Top Unipessoal, Lda',
            'vat' => '515025712',
            'email' => 'geral@example.test',
        ]);
        $driver = new Driver([
            'name' => 'Motorista <Teste>',
            'driver_vat' => '123456789',
            'address' => 'Rua Um',
            'zip' => '4000-001',
            'city' => 'Porto',
            'email' => 'motorista@example.test',
            'license_plate' => 'AA-00-AA',
        ]);
        $driver->setRelation('company', $company);

        $vehicle = new VehicleItem(['license_plate' => 'BB-11-BB', 'year' => 2025]);
        $vehicle->setRelation('vehicle_brand', new VehicleBrand(['name' => 'MG']));
        $vehicle->setRelation('vehicle_model', new VehicleModel(['name' => 'ZS']));

        $contract = new AdminContract(['number' => 42, 'generated_at' => now()]);
        $result = (new ContractTemplateRenderer())->render(
            '{{contract.number}}|{{contract.date}}|{{driver.name}}|{{driver.full_address}}|{{vehicle.brand}}|{{vehicle.model}}|{{vehicle.license_plate}}',
            $driver,
            $contract,
            $vehicle
        );

        $this->assertSame('42|03/11/2026|Motorista &lt;Teste&gt;|Rua Um, 4000-001, Porto|MG|ZS|BB-11-BB', $result['content']);
        $this->assertSame('123456789', $result['data']['driver.vat']);
        $this->assertSame('Adelmo Top Unipessoal, Lda', $result['data']['company.name']);

        Carbon::setTestNow();
    }

    public function test_unknown_tags_do_not_leak_into_generated_contracts(): void
    {
        $driver = new Driver(['name' => 'Motorista', 'email' => 'motorista@example.test']);
        $driver->setRelation('company', null);
        $contract = new AdminContract(['number' => 1]);

        $result = (new ContractTemplateRenderer())->render('Antes {{driver.unknown}} Depois', $driver, $contract);

        $this->assertSame('Antes  Depois', $result['content']);
    }

    public function test_generated_snapshot_can_be_rendered_as_a_pdf(): void
    {
        $driver = new Driver(['name' => 'Motorista PDF', 'email' => 'pdf@example.test']);
        $contract = new AdminContract([
            'number' => 7,
            'template_version' => 2,
            'rendered_content' => '<h1>Contrato de teste</h1><p>Motorista PDF</p>',
        ]);
        $contract->setRelation('driver', $driver);

        $output = Pdf::loadView('admin.adminContracts.show', ['adminContract' => $contract])->output();

        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertGreaterThan(1000, strlen($output));
    }
}
