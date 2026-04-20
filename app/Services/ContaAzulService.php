<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ExternalExpense;
use App\Services\ContaAzul\ContaAzulManagerDashboardService;
use Illuminate\Support\Carbon;

class ContaAzulService
{
    public function __construct(
        protected ContaAzulManagerDashboardService $managerDashboardService
    ) {
    }

    public function fetchExpenses(Company $company, array $query = []): array
    {
        return $this->managerDashboardService->expenses($company, $query);
    }

    public function mapToLocal(Company $company, array $expense): array
    {
        $externalId = (string) ($expense['id'] ?? '');

        if ($externalId === '') {
            return [];
        }

        return [
            'external_id' => $company->id . ':' . $externalId,
            'description' => (string) ($expense['description'] ?? 'Sem descricao'),
            'amount' => (float) ($expense['amount'] ?? 0),
            'date' => !empty($expense['date']) ? Carbon::parse($expense['date'])->toDateString() : null,
            'category' => !empty($expense['category']) ? (string) $expense['category'] : null,
        ];
    }

    public function syncExpenses(?Company $company = null, array $query = []): int
    {
        $synced = 0;

        $companies = $company
            ? collect([$company])
            : Company::query()
                ->whereHas('conta_azul_connection', function ($queryBuilder) {
                    $queryBuilder->whereNotNull('access_token');
                })
                ->get();

        foreach ($companies as $syncCompany) {
            $expenses = $this->fetchExpenses($syncCompany, $query);

            foreach (($expenses['items'] ?? []) as $expense) {
                $payload = $this->mapToLocal($syncCompany, $expense);

                if (empty($payload)) {
                    continue;
                }

                ExternalExpense::updateOrCreate(
                    ['external_id' => $payload['external_id']],
                    $payload
                );

                $synced++;
            }
        }

        return $synced;
    }
}
