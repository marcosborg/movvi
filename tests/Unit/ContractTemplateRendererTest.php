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
        $this->assertStringContainsString('com sede em {{company.full_address}}', $content);
        $this->assertStringContainsString('365 dias, prorrogável por períodos sucessivos de 365 dias', $content);
        $this->assertStringContainsString('mensagens automáticas', $content);
        $this->assertStringContainsString('Lei n.º 59/2026, de 25 de agosto', $content);
        $this->assertStringContainsString('artigo 12.º do Código do Trabalho', $content);
        $this->assertStringContainsString('{{company.tvde_license}}', $content);
        $this->assertStringContainsString('Cartrack', $content);
        $this->assertStringContainsString('{{financial.commission_clause}}', $content);
        $this->assertStringContainsString('{{driver.identification_type}}', $content);
        $this->assertStringContainsString('{{vehicle.brand_model}}', $content);
        $this->assertStringNotContainsString('Passaporte (quando aplicável)', $content);
        $this->assertStringNotContainsString('<div class="page-break"></div>', $content);
        $this->assertStringContainsString('formação inicial de 50 horas', $content);
        $this->assertStringContainsString('dístico identificador inamovível', $content);
        $this->assertStringContainsString('ANEXO I - AVISO DE PRIVACIDADE DO MOTORISTA', $content);
        $this->assertStringContainsString('plataforma de partilha de dados do IMT', $content);
    }

    public function test_it_replaces_whitelisted_tags_and_returns_the_snapshot(): void
    {
        Carbon::setTestNow('2026-11-03 18:45:00');

        $company = new Company([
            'name' => 'Adelmo Top Unipessoal, Lda',
            'vat' => '515025712',
            'tvde_license' => 'TVDE-12345',
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
        $this->assertSame('TVDE-12345', $result['data']['company.tvde_license']);

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

    public function test_financial_and_document_overrides_are_rendered_for_commission_contracts(): void
    {
        $driver = new Driver(['name' => 'Jorge Cabral']);
        $driver->setRelation('company', null);
        $driver->setRelation('contract_vat', null);
        $contract = new AdminContract(['number' => 7, 'generated_at' => now()]);

        $result = (new ContractTemplateRenderer())->render(
            '{{driver.identification_type}}|{{driver.commission_percent}}|{{financial.weekly_clause}}|{{documents.citizen_card_expires_at}}|{{vehicle.year}}',
            $driver,
            $contract,
            null,
            [
                'commission_percent' => 15,
                'identification_type' => 'BI',
                'vehicle_year' => 2023,
                'document_expiries' => ['citizen_card_expires_at' => '31/12/2030'],
            ]
        );

        $this->assertStringContainsString('BI|15,00 %', $result['content']);
        $this->assertStringContainsString('Não é devido um valor semanal fixo', $result['content']);
        $this->assertStringContainsString('31/12/2030|2023', $result['content']);
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
