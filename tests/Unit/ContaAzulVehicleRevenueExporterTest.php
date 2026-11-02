<?php

namespace Tests\Unit;

use App\Models\ContaAzulConnection;
use App\Models\TvdeWeek;
use App\Services\ContaAzul\ContaAzulClient;
use App\Services\ContaAzul\ContaAzulVehicleRevenueExporter;
use Mockery;
use Tests\TestCase;

class ContaAzulVehicleRevenueExporterTest extends TestCase
{
    public function test_receivable_payload_uses_the_current_conta_azul_customer_field(): void
    {
        $connection = new ContaAzulConnection([
            'receivable_contact_id' => 'customer-uuid',
            'receivable_financial_account_id' => 'account-uuid',
            'receivable_category_id' => 'category-uuid',
        ]);
        $week = new TvdeWeek();
        $week->id = 58;
        $week->start_date = '2026-10-19';
        $week->end_date = '2026-10-25';

        $exporter = new class(Mockery::mock(ContaAzulClient::class)) extends ContaAzulVehicleRevenueExporter
        {
            public function payload(ContaAzulConnection $connection, TvdeWeek $week, array $row): array
            {
                return $this->buildReceivablePayload($connection, $week, $row);
            }
        };

        $payload = $exporter->payload($connection, $week, [
            'license_plate' => 'BO-27-CG',
            'total_revenue' => 230,
        ]);

        $this->assertSame('customer-uuid', $payload['id_cliente']);
        $this->assertArrayNotHasKey('contato', $payload);
    }
}
