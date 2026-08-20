<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\ContaAzul\ContaAzulClient;
use App\Services\ContaAzul\ContaAzulManagerDashboardService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ContaAzulManagerDashboardServiceTest extends TestCase
{
    public function test_expenses_aggregate_all_remote_pages_and_paginate_the_response(): void
    {
        Cache::flush();

        $company = new Company();
        $company->id = 321;

        $client = Mockery::mock(ContaAzulClient::class);
        $client->shouldReceive('listPayables')
            ->times(2)
            ->withArgs(function (Company $receivedCompany, array $query) use ($company) {
                return $receivedCompany === $company
                    && $query['tamanho_pagina'] === 200
                    && in_array($query['pagina'], [1, 2], true);
            })
            ->andReturnUsing(function (Company $receivedCompany, array $query) {
                $start = ($query['pagina'] - 1) * 200 + 1;
                $end = min($start + 199, 205);

                return [
                    'itens_totais' => 205,
                    'itens' => collect(range($start, $end))->map(fn (int $id) => [
                        'id' => (string) $id,
                        'descricao' => "Despesa {$id}",
                        'valor' => 10,
                        'situacao' => 'pago',
                        'data_vencimento' => sprintf('2026-08-%02d', (($id - 1) % 28) + 1),
                    ])->all(),
                ];
            });
        $client->shouldReceive('listCategories')->once()->andReturn(['itens' => []]);

        $response = (new ContaAzulManagerDashboardService($client))->expenses($company, [
            'data_vencimento_de' => '2026-08-01',
            'data_vencimento_ate' => '2026-08-31',
            'page' => 2,
            'per_page' => 20,
        ]);

        $this->assertSame(205, $response['summary']['items_count']);
        $this->assertSame(2050.0, $response['summary']['total_expenses']);
        $this->assertCount(20, $response['items']);
        $this->assertSame([
            'current_page' => 2,
            'per_page' => 20,
            'last_page' => 11,
            'total' => 205,
        ], $response['pagination']);
    }
}
