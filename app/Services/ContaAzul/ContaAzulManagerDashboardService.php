<?php

namespace App\Services\ContaAzul;

use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContaAzulManagerDashboardService
{
    public function __construct(
        protected ContaAzulClient $client
    ) {
    }

    public function profitLoss(Company $company, array $query = []): array
    {
        $receivables = $this->allReceivables($company, $query);
        $payables = $this->allPayables($company, $query);
        $orderedReceivables = $this->sortItemsByDateDesc($receivables);
        $orderedPayables = $this->sortItemsByDateDesc($payables);

        $revenues = $this->sumAmounts($receivables);
        $expenses = $this->sumAmounts($payables);

        return [
            'summary' => [
                'revenue' => $revenues,
                'expenses' => $expenses,
                'gross_result' => round($revenues - $expenses, 2),
                'net_result' => round($revenues - $expenses, 2),
            ],
            'revenue_categories' => $this->summarizeByCategory($receivables),
            'expense_categories' => $this->summarizeByCategory($payables),
            'totals' => [
                'receivables_count' => $receivables->count(),
                'payables_count' => $payables->count(),
            ],
            'raw' => [
                'receivables' => $orderedReceivables->values()->all(),
                'payables' => $orderedPayables->values()->all(),
            ],
        ];
    }

    public function movements(Company $company, array $query = []): array
    {
        $accounts = $this->client->listFinancialAccountsWithBalances($company, $query);
        $receivables = $this->allReceivables($company, $query)
            ->map(fn (array $item) => $this->normalizeMovement($item, 'incoming'));
        $payables = $this->allPayables($company, $query)
            ->map(fn (array $item) => $this->normalizeMovement($item, 'outgoing'));

        $movements = $receivables
            ->merge($payables)
            ->sortByDesc(fn (array $item) => $item['date'] ?? '')
            ->values();

        return [
            'accounts' => [
                'items' => collect($accounts['items'] ?? [])
                    ->map(function (array $account) {
                        return [
                            'id' => $account['id'] ?? null,
                            'name' => $account['nome'] ?? $account['name'] ?? 'Conta sem nome',
                            'type' => $account['tipo'] ?? $account['type'] ?? null,
                            'active' => $account['ativo'] ?? $account['active'] ?? null,
                            'balance' => $this->asFloat($account['saldo_atual'] ?? null),
                        ];
                    })
                    ->sortByDesc('balance')
                    ->values()
                    ->all(),
                'totals' => [
                    'current_balance' => round(collect($accounts['items'] ?? [])->sum(function (array $account) {
                        return $this->asFloat($account['saldo_atual'] ?? 0);
                    }), 2),
                ],
            ],
            'summary' => [
                'incoming_total' => round($receivables->sum('amount'), 2),
                'outgoing_total' => round($payables->sum('amount'), 2),
                'net_cashflow' => round($receivables->sum('amount') - $payables->sum('amount'), 2),
                'movements_count' => $movements->count(),
            ],
            'movements' => $movements->all(),
        ];
    }

    public function expenses(Company $company, array $query = []): array
    {
        $page = max((int) ($query['page'] ?? 1), 1);
        $perPage = min(max((int) ($query['per_page'] ?? 20), 1), 100);
        $financialQuery = Arr::except($query, ['page', 'per_page']);
        $payables = $this->allPayables($company, $financialQuery);
        $orderedPayables = $this->sortItemsByDateDesc($payables);
        $categories = $this->client->listCategories($company, $financialQuery);
        $lastPage = max((int) ceil($payables->count() / $perPage), 1);
        $pageItems = $orderedPayables->forPage($page, $perPage);

        $verifiedExpenses = $payables->filter(fn (array $item) => $this->isSettled($item));
        $openExpenses = $payables->reject(fn (array $item) => $this->isSettled($item));
        $overdueExpenses = $openExpenses->filter(function (array $item) {
            $date = $item['date'] ?? null;
            return $date && $date < now()->toDateString();
        });

        return [
            'summary' => [
                'total_expenses' => $this->sumAmounts($payables),
                'open_expenses' => $this->sumAmounts($openExpenses),
                'paid_expenses' => $this->sumAmounts($verifiedExpenses),
                'overdue_expenses' => $this->sumAmounts($overdueExpenses),
                'items_count' => $payables->count(),
            ],
            'categories' => [
                'expense_breakdown' => $this->summarizeByCategory($payables),
                'catalog' => $this->normalizeCategoryCatalog($categories),
            ],
            'items' => $pageItems->map(fn (array $item) => [
                'id' => $item['id'],
                'description' => $item['description'],
                'counterparty' => $item['counterparty'],
                'category' => $item['category'],
                'status' => $item['status'],
                'date' => $item['date'],
                'amount' => $item['amount'],
            ])->values()->all(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'total' => $payables->count(),
            ],
        ];
    }

    protected function allReceivables(Company $company, array $query): Collection
    {
        return $this->allFinancialItems(
            $company,
            'receivables',
            $query,
            fn (array $pageQuery) => $this->client->listReceivables($company, $pageQuery)
        );
    }

    protected function allPayables(Company $company, array $query): Collection
    {
        return $this->allFinancialItems(
            $company,
            'payables',
            $query,
            fn (array $pageQuery) => $this->client->listPayables($company, $pageQuery)
        );
    }

    protected function allFinancialItems(Company $company, string $type, array $query, callable $fetchPage): Collection
    {
        $query = Arr::except($query, ['pagina', 'tamanho_pagina', 'page', 'per_page']);
        ksort($query);
        $cacheKey = 'conta-azul:manager:' . $company->getKey() . ':' . $type . ':' . sha1(json_encode($query));

        $items = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $fetchPage) {
            $page = 1;
            $perPage = 200;
            $items = collect();
            $total = null;

            do {
                $payload = $fetchPage(array_merge($query, [
                    'pagina' => $page,
                    'tamanho_pagina' => $perPage,
                ]));
                $batch = $this->normalizeItems($payload);
                $items = $items->concat($batch);
                $totalValue = Arr::get($payload, 'itens_totais')
                    ?? Arr::get($payload, 'items_totais')
                    ?? Arr::get($payload, 'total');
                $total = is_numeric($totalValue) ? (int) $totalValue : $total;
                $page++;
            } while ($batch->isNotEmpty()
                && ($total === null ? $batch->count() === $perPage : $items->count() < $total));

            return $items->values()->all();
        });

        return collect($items);
    }

    protected function normalizeItems(array $payload): Collection
    {
        $items = Arr::get($payload, 'itens')
            ?? Arr::get($payload, 'items')
            ?? Arr::get($payload, 'data')
            ?? Arr::get($payload, 'resultados')
            ?? $payload;

        return collect(is_array($items) ? $items : [])
            ->map(fn ($item) => is_array($item) ? $this->normalizeFinancialItem($item) : null)
            ->filter();
    }

    protected function normalizeFinancialItem(array $item): array
    {
        return [
            'id' => $item['id'] ?? $item['uuid'] ?? null,
            'description' => $item['descricao'] ?? $item['description'] ?? $item['historico'] ?? 'Sem descricao',
            'counterparty' => data_get($item, 'pessoa.nome')
                ?? data_get($item, 'cliente.nome')
                ?? data_get($item, 'fornecedor.nome')
                ?? $item['nome'] ?? null,
            'category' => data_get($item, 'categoria.nome')
                ?? data_get($item, 'categoria.descricao')
                ?? $item['categoria'] ?? $item['category'] ?? 'Sem categoria',
            'status' => $item['situacao'] ?? $item['status'] ?? $item['estado'] ?? null,
            'date' => $item['data_vencimento']
                ?? $item['data_competencia']
                ?? $item['data_emissao']
                ?? $item['data']
                ?? null,
            'amount' => $this->extractAmount($item),
            'raw' => $item,
        ];
    }

    protected function normalizeMovement(array $item, string $direction): array
    {
        return [
            'id' => $item['id'],
            'direction' => $direction,
            'description' => $item['description'],
            'counterparty' => $item['counterparty'],
            'category' => $item['category'],
            'status' => $item['status'],
            'date' => $item['date'],
            'amount' => $item['amount'],
        ];
    }

    protected function normalizeCategoryCatalog(array $payload): array
    {
        $items = Arr::get($payload, 'itens')
            ?? Arr::get($payload, 'items')
            ?? Arr::get($payload, 'data')
            ?? $payload;

        return collect(is_array($items) ? $items : [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['nome'] ?? $item['descricao'] ?? $item['name'] ?? 'Categoria',
                    'type' => $item['tipo'] ?? $item['type'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function sortItemsByDateDesc(Collection $items): Collection
    {
        return $items
            ->sortByDesc(fn (array $item) => $item['date'] ?? '')
            ->values();
    }

    protected function summarizeByCategory(Collection $items): array
    {
        return $items
            ->groupBy(fn (array $item) => $item['category'] ?: 'Sem categoria')
            ->map(function (Collection $group, string $category) {
                return [
                    'category' => $category,
                    'amount' => round($group->sum('amount'), 2),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    protected function sumAmounts(Collection $items): float
    {
        return round($items->sum('amount'), 2);
    }

    protected function extractAmount(array $item): float
    {
        foreach ([
            'valor_total',
            'valor',
            'valor_original',
            'valor_liquido',
            'valor_bruto',
            'total',
            'saldo',
            'amount',
        ] as $key) {
            if (array_key_exists($key, $item)) {
                return $this->asFloat($item[$key]);
            }
        }

        return 0.0;
    }

    protected function isSettled(array $item): bool
    {
        $status = strtolower((string) ($item['status'] ?? ''));

        return in_array($status, ['quitado', 'paid', 'pago', 'liquidado'], true);
    }

    protected function asFloat($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.-]/', '', $value) ?? '');
            return is_numeric($normalized) ? (float) $normalized : 0.0;
        }

        return 0.0;
    }
}
