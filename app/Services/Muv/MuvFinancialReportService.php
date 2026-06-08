<?php

namespace App\Services\Muv;

use App\Models\Company;
use App\Models\ContaAzulConnection;
use App\Models\ContaAzulVehicleRevenueExport;
use App\Models\TvdeWeek;
use App\Services\ContaAzul\ContaAzulManagerDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MuvFinancialReportService
{
    public function __construct(
        protected ContaAzulManagerDashboardService $contaAzulDashboard
    ) {
    }

    public function build(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date'])->startOfDay();
        $endDate = Carbon::parse($filters['end_date'])->endOfDay();
        $companyId = $filters['company_id'] ?? null;

        $weeks = $this->weeksForPeriod($startDate, $endDate);
        $weekIds = $weeks->pluck('id')->all();

        $activities = $this->activities($weekIds, $companyId);
        $currentAccounts = $this->currentAccounts($weekIds, $companyId);
        $vehicleExpenses = $this->vehicleExpenses($startDate, $endDate, $companyId);
        $companyExpenses = $this->companyExpenses($startDate, $endDate, $weeks, $companyId);
        $externalExpenses = $this->externalExpenses($startDate, $endDate);
        $driverReceipts = $this->driverReceipts($weekIds, $companyId);
        $driverReimbursements = $this->driverReimbursements($weekIds, $companyId);
        $driverExpenseReceipts = $this->driverExpenseReceipts($weekIds, $companyId);
        $vehicleExpenseReimbursements = $this->vehicleExpenseReimbursements($startDate, $endDate, $companyId);
        $contaAzulExports = $this->contaAzulRevenueExports($weekIds, $companyId);
        $contaAzulLive = $this->contaAzulLive($startDate, $endDate, $companyId);

        $activityGross = $activities->sum('gross');
        $activityNet = $activities->sum('net');
        $activityTips = $activities->sum('tips');
        $operatorFees = max(0, $activityGross - $activityNet);

        $driverPayouts = $currentAccounts->sum('driver_total');
        $taxes = $currentAccounts->sum('taxes');
        $fleetCharges = $currentAccounts->sum('fleet_charges');

        $incomeLines = collect([
            ['label' => 'Receita TVDE bruta', 'value' => $activityGross],
            ['label' => 'Gorjetas registadas', 'value' => $activityTips],
            ['label' => 'Recibos cobrados a motoristas', 'value' => $driverReceipts->sum('amount')],
            ['label' => 'Reembolsos de despesas/viaturas', 'value' => $vehicleExpenseReimbursements->sum('amount')],
        ])->map(fn ($item) => ['label' => $item['label'], 'value' => round((float) $item['value'], 2)]);

        $outcomeLines = collect([
            ['label' => 'Comissoes dos operadores', 'value' => $operatorFees],
            ['label' => 'Pagamentos/valores a motoristas', 'value' => $driverPayouts],
            ['label' => 'Impostos e retencoes estimadas', 'value' => $taxes],
            ['label' => 'Despesas de viaturas', 'value' => $vehicleExpenses->sum('amount')],
            ['label' => 'Despesas fixas da empresa', 'value' => $companyExpenses->sum('amount')],
            ['label' => 'Reembolsos a motoristas', 'value' => $driverReimbursements->sum('amount')],
            ['label' => 'Recibos de despesas aprovados', 'value' => $driverExpenseReceipts->sum('amount')],
            ['label' => 'Conta Azul - despesas sincronizadas', 'value' => $externalExpenses->sum('amount')],
        ])->map(fn ($item) => ['label' => $item['label'], 'value' => round((float) $item['value'], 2)]);

        $incomeTotal = round($incomeLines->sum('value'), 2);
        $outcomeTotal = round($outcomeLines->sum('value'), 2);
        $estimatedResult = round($incomeTotal - $outcomeTotal, 2);

        $periodSeries = $this->periodSeries($startDate, $endDate);
        $monthlyIncome = $this->monthlySeries($periodSeries, [
            ...$activities->map(fn ($row) => ['date' => $row['date'], 'amount' => $row['gross'] + $row['tips']])->all(),
            ...$driverReceipts->all(),
            ...$vehicleExpenseReimbursements->all(),
        ]);
        $monthlyOutcome = $this->monthlySeries($periodSeries, [
            ...$activities->map(fn ($row) => ['date' => $row['date'], 'amount' => max(0, $row['gross'] - $row['net'])])->all(),
            ...$currentAccounts->map(fn ($row) => ['date' => $row['date'], 'amount' => $row['driver_total'] + $row['taxes']])->all(),
            ...$vehicleExpenses->all(),
            ...$companyExpenses->all(),
            ...$driverReimbursements->all(),
            ...$driverExpenseReceipts->all(),
            ...$externalExpenses->all(),
        ]);

        $expenseBreakdown = $this->breakdown($outcomeLines->all(), 'label');
        $incomeBreakdown = $this->breakdown($incomeLines->all(), 'label');

        $vehicleExpenseBreakdown = $this->breakdown($vehicleExpenses->all(), 'category');
        $contaAzulExpenseBreakdown = $this->breakdown($externalExpenses->all(), 'category');

        return [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'company_id' => $companyId,
                'company' => $companyId ? Company::find($companyId) : null,
            ],
            'companies' => Company::orderByDesc('main')->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'income_total' => $incomeTotal,
                'outcome_total' => $outcomeTotal,
                'estimated_result' => $estimatedResult,
                'margin' => $incomeTotal > 0 ? round(($estimatedResult / $incomeTotal) * 100, 1) : 0,
                'tvde_gross' => round($activityGross, 2),
                'tvde_net' => round($activityNet, 2),
                'operator_fees' => round($operatorFees, 2),
                'driver_payouts' => round($driverPayouts, 2),
                'taxes' => round($taxes, 2),
                'fleet_charges' => round($fleetCharges, 2),
                'vehicle_expenses' => round($vehicleExpenses->sum('amount'), 2),
                'company_expenses' => round($companyExpenses->sum('amount'), 2),
                'conta_azul_synced_expenses' => round($externalExpenses->sum('amount'), 2),
                'conta_azul_exported_revenue' => round($contaAzulExports->sum('amount'), 2),
                'conta_azul_live_revenue' => $contaAzulLive['summary']['revenue'] ?? null,
                'conta_azul_live_expenses' => $contaAzulLive['summary']['expenses'] ?? null,
                'conta_azul_live_result' => $contaAzulLive['summary']['net_result'] ?? null,
                'weeks_count' => count($weekIds),
                'drivers_count' => $currentAccounts->pluck('driver_id')->filter()->unique()->count(),
            ],
            'income_lines' => $incomeLines->sortByDesc('value')->values()->all(),
            'outcome_lines' => $outcomeLines->sortByDesc('value')->values()->all(),
            'charts' => [
                'labels' => $periodSeries->pluck('label')->values()->all(),
                'income' => $monthlyIncome,
                'outcome' => $monthlyOutcome,
                'result' => collect($monthlyIncome)->zip($monthlyOutcome)->map(fn ($pair) => round($pair[0] - $pair[1], 2))->values()->all(),
                'expense_breakdown' => $expenseBreakdown,
                'income_breakdown' => $incomeBreakdown,
                'vehicle_expense_breakdown' => $vehicleExpenseBreakdown,
                'conta_azul_expense_breakdown' => $contaAzulExpenseBreakdown,
            ],
            'tables' => [
                'top_expenses' => collect([
                    ...$vehicleExpenses->all(),
                    ...$companyExpenses->all(),
                    ...$externalExpenses->all(),
                ])->sortByDesc('amount')->take(12)->values()->all(),
                'conta_azul_exports' => $contaAzulExports->sortByDesc('amount')->take(12)->values()->all(),
                'driver_settlements' => $currentAccounts->sortByDesc('driver_total')->take(12)->values()->all(),
            ],
            'conta_azul' => [
                'live' => $contaAzulLive,
                'exports_count' => $contaAzulExports->count(),
                'exports_errors' => $contaAzulExports->where('status', ContaAzulVehicleRevenueExport::STATUS_ERROR)->count(),
                'connections' => ContaAzulConnection::with('company')
                    ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                    ->get(),
            ],
            'generated_at' => now(),
        ];
    }

    protected function weeksForPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return TvdeWeek::query()
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->orderBy('start_date')
            ->get(['id', 'number', 'start_date', 'end_date']);
    }

    protected function activities(array $weekIds, ?int $companyId): Collection
    {
        if (empty($weekIds)) {
            return collect();
        }

        return DB::table('tvde_activities')
            ->leftJoin('tvde_weeks', 'tvde_activities.tvde_week_id', '=', 'tvde_weeks.id')
            ->leftJoin('tvde_operators', 'tvde_activities.tvde_operator_id', '=', 'tvde_operators.id')
            ->whereNull('tvde_activities.deleted_at')
            ->whereIn('tvde_activities.tvde_week_id', $weekIds)
            ->when($companyId, fn ($query) => $query->where('tvde_activities.company_id', $companyId))
            ->selectRaw('tvde_activities.tvde_week_id, tvde_weeks.start_date as date, COALESCE(tvde_operators.name, "Operador") as category, SUM(COALESCE(tvde_activities.gross, 0)) as gross, SUM(COALESCE(tvde_activities.net, 0)) as net, SUM(COALESCE(tvde_activities.tips, 0)) as tips')
            ->groupBy('tvde_activities.tvde_week_id', 'tvde_weeks.start_date', 'category')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'category' => $row->category,
                'gross' => (float) $row->gross,
                'net' => (float) $row->net,
                'tips' => (float) $row->tips,
                'amount' => (float) $row->gross,
            ]);
    }

    protected function currentAccounts(array $weekIds, ?int $companyId): Collection
    {
        if (empty($weekIds)) {
            return collect();
        }

        return DB::table('current_accounts')
            ->leftJoin('drivers', 'current_accounts.driver_id', '=', 'drivers.id')
            ->leftJoin('tvde_weeks', 'current_accounts.tvde_week_id', '=', 'tvde_weeks.id')
            ->whereNull('current_accounts.deleted_at')
            ->whereIn('current_accounts.tvde_week_id', $weekIds)
            ->when($companyId, fn ($query) => $query->where('drivers.company_id', $companyId))
            ->select('current_accounts.*', 'drivers.name as driver_name', 'tvde_weeks.start_date as date')
            ->get()
            ->map(function ($row) {
                $data = json_decode($row->data ?? '{}', true) ?: [];
                $driverTotal = $this->money($data['driver_total'] ?? $data['total'] ?? 0);

                return [
                    'date' => $row->date,
                    'driver_id' => $row->driver_id,
                    'driver' => $row->driver_name ?: 'Motorista',
                    'driver_total' => $driverTotal,
                    'taxes' => $this->money($data['vat_value'] ?? (($data['iva_value'] ?? 0) + ($data['percent_value'] ?? 0))),
                    'fleet_charges' => $this->money($data['car_hire'] ?? 0) + $this->money($data['car_track'] ?? 0) + $this->money($data['fuel_transactions'] ?? 0),
                    'gross' => $this->money($data['total_gross'] ?? 0),
                    'net' => $this->money($data['total_net'] ?? 0),
                    'amount' => $driverTotal,
                ];
            });
    }

    protected function vehicleExpenses(Carbon $startDate, Carbon $endDate, ?int $companyId): Collection
    {
        return DB::table('vehicle_expenses')
            ->leftJoin('vehicle_items', 'vehicle_expenses.vehicle_item_id', '=', 'vehicle_items.id')
            ->whereNull('vehicle_expenses.deleted_at')
            ->whereBetween('vehicle_expenses.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($companyId, fn ($query) => $query->where('vehicle_items.company_id', $companyId))
            ->select('vehicle_expenses.date', 'vehicle_expenses.expense_type as category', 'vehicle_expenses.description', 'vehicle_expenses.value as amount', 'vehicle_items.license_plate')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'category' => $row->category ?: 'Despesa de viatura',
                'description' => trim(($row->license_plate ? $row->license_plate . ' - ' : '') . ($row->description ?: $row->category)),
                'amount' => $this->money($row->amount),
                'source' => 'Despesa de viatura',
            ]);
    }

    protected function companyExpenses(Carbon $startDate, Carbon $endDate, Collection $weeks, ?int $companyId): Collection
    {
        $expenses = DB::table('company_expenses')
            ->whereNull('deleted_at')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get();

        return $expenses->map(function ($expense) use ($weeks) {
            $activeWeeks = $weeks->filter(function ($week) use ($expense) {
                return $week->start_date <= $expense->end_date && $week->end_date >= $expense->start_date;
            })->count();

            return [
                'date' => $expense->start_date,
                'category' => 'Despesa fixa',
                'description' => $expense->name,
                'amount' => $this->money($expense->weekly_value) * (int) $expense->qty * $activeWeeks,
                'source' => 'Despesa fixa da empresa',
            ];
        })->filter(fn ($row) => $row['amount'] > 0)->values();
    }

    protected function externalExpenses(Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('external_expenses')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'category' => $row->category ?: 'Conta Azul',
                'description' => $row->description,
                'amount' => $this->money($row->amount),
                'source' => 'Conta Azul sincronizada',
            ]);
    }

    protected function driverReceipts(array $weekIds, ?int $companyId): Collection
    {
        return $this->driverWeekMoney('receipts', 'COALESCE(receipts.verified_value, receipts.value, 0)', $weekIds, $companyId, 'Recibo motorista');
    }

    protected function driverReimbursements(array $weekIds, ?int $companyId): Collection
    {
        return $this->driverWeekMoney('reimbursements', 'COALESCE(reimbursements.value, 0)', $weekIds, $companyId, 'Reembolso motorista', true);
    }

    protected function driverExpenseReceipts(array $weekIds, ?int $companyId): Collection
    {
        return $this->driverWeekMoney('expense_receipts', 'COALESCE(expense_receipts.approved_value, 0)', $weekIds, $companyId, 'Recibo despesa', true);
    }

    protected function driverWeekMoney(string $table, string $amountExpression, array $weekIds, ?int $companyId, string $source, bool $verifiedOnly = false): Collection
    {
        if (empty($weekIds)) {
            return collect();
        }

        return DB::table($table)
            ->leftJoin('drivers', "{$table}.driver_id", '=', 'drivers.id')
            ->leftJoin('tvde_weeks', "{$table}.tvde_week_id", '=', 'tvde_weeks.id')
            ->whereNull("{$table}.deleted_at")
            ->whereIn("{$table}.tvde_week_id", $weekIds)
            ->when($companyId, fn ($query) => $query->where('drivers.company_id', $companyId))
            ->when($verifiedOnly, fn ($query) => $query->where("{$table}.verified", 1))
            ->selectRaw("tvde_weeks.start_date as date, drivers.name as driver_name, SUM({$amountExpression}) as amount")
            ->groupBy('tvde_weeks.start_date', 'drivers.name')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'category' => $source,
                'description' => $row->driver_name ?: $source,
                'amount' => $this->money($row->amount),
                'source' => $source,
            ]);
    }

    protected function vehicleExpenseReimbursements(Carbon $startDate, Carbon $endDate, ?int $companyId): Collection
    {
        return DB::table('expense_reimbursements')
            ->leftJoin('vehicle_items', 'expense_reimbursements.vehicle_item_id', '=', 'vehicle_items.id')
            ->whereNull('expense_reimbursements.deleted_at')
            ->whereBetween('expense_reimbursements.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($companyId, fn ($query) => $query->where('vehicle_items.company_id', $companyId))
            ->select('expense_reimbursements.date', 'expense_reimbursements.value as amount', 'vehicle_items.license_plate')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'category' => 'Reembolso viatura',
                'description' => $row->license_plate ?: 'Reembolso de despesa',
                'amount' => $this->money($row->amount),
                'source' => 'Reembolso de despesa',
            ]);
    }

    protected function contaAzulRevenueExports(array $weekIds, ?int $companyId): Collection
    {
        if (empty($weekIds)) {
            return collect();
        }

        return ContaAzulVehicleRevenueExport::query()
            ->with('week')
            ->whereIn('tvde_week_id', $weekIds)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get()
            ->map(fn (ContaAzulVehicleRevenueExport $row) => [
                'date' => optional($row->week)->getRawOriginal('start_date') ?: optional($row->exported_at)->toDateString(),
                'category' => $row->status,
                'description' => trim(($row->license_plate ?: 'Viatura') . ' - ' . ($row->description ?: 'Receita exportada')),
                'amount' => $this->money($row->amount),
                'status' => $row->status,
                'source' => 'Conta Azul - receita exportada',
            ]);
    }

    protected function contaAzulLive(Carbon $startDate, Carbon $endDate, ?int $companyId): array
    {
        $company = $companyId
            ? Company::with('conta_azul_connection')->find($companyId)
            : Company::with('conta_azul_connection')
                ->whereHas('conta_azul_connection', fn ($query) => $query->whereNotNull('access_token'))
                ->orderByDesc('main')
                ->orderBy('id')
                ->first();

        if (! $company || ! $company->conta_azul_connection?->access_token) {
            return [
                'available' => false,
                'message' => 'Sem ligação Conta Azul ativa para o filtro selecionado.',
            ];
        }

        try {
            $data = $this->contaAzulDashboard->profitLoss($company, [
                'data_vencimento_de' => $startDate->toDateString(),
                'data_vencimento_ate' => $endDate->toDateString(),
            ]);

            return [
                'available' => true,
                'company' => $company->name,
                'summary' => $data['summary'] ?? [],
                'revenue_categories' => $data['revenue_categories'] ?? [],
                'expense_categories' => $data['expense_categories'] ?? [],
            ];
        } catch (\Throwable $exception) {
            return [
                'available' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function periodSeries(Carbon $startDate, Carbon $endDate): Collection
    {
        $cursor = $startDate->copy()->startOfMonth();
        $items = collect();

        while ($cursor <= $endDate) {
            $items->push([
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('M Y'),
            ]);
            $cursor->addMonth();
        }

        return $items;
    }

    protected function monthlySeries(Collection $periodSeries, array $rows): array
    {
        $grouped = collect($rows)->groupBy(fn ($row) => Carbon::parse($row['date'] ?? now())->format('Y-m'));

        return $periodSeries
            ->map(fn ($period) => round((float) ($grouped->get($period['key'], collect())->sum('amount')), 2))
            ->values()
            ->all();
    }

    protected function breakdown(array $rows, string $labelKey): array
    {
        return collect($rows)
            ->groupBy(fn ($row) => $row[$labelKey] ?? 'Sem categoria')
            ->map(fn ($items, $label) => [
                'label' => $label,
                'value' => round((float) collect($items)->sum(fn ($row) => $row['value'] ?? $row['amount'] ?? 0), 2),
            ])
            ->filter(fn ($row) => $row['value'] > 0)
            ->sortByDesc('value')
            ->take(8)
            ->values()
            ->all();
    }

    protected function money($value): float
    {
        return round((float) ($value ?: 0), 2);
    }
}
