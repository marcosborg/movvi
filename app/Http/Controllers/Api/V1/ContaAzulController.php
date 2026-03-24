<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContaAzulVehicleRevenueExport;
use App\Models\TvdeWeek;
use App\Services\ContaAzul\ContaAzulClient;
use App\Services\ContaAzul\ContaAzulManagerDashboardService;
use App\Services\ContaAzul\ContaAzulVehicleRevenueExporter;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class ContaAzulController extends Controller
{
    public function __construct(
        protected ContaAzulClient $client,
        protected ContaAzulManagerDashboardService $managerDashboard,
        protected ContaAzulVehicleRevenueExporter $vehicleRevenueExporter
    ) {
    }

    public function status(Request $request)
    {
        if (! $this->canViewFinancialData($request)) {
            return response()->json(['error' => '403 Forbidden'], 403);
        }

        $company = $this->resolveCompany($request);

        if (! $company) {
            return response()->json([
                'error' => 'Empresa nao encontrada para o utilizador autenticado.',
            ], 404);
        }

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'connection' => $this->client->statusForCompany($company),
        ]);
    }

    public function accounts(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listFinancialAccounts($company, $this->forwardedQuery($request));
        });
    }

    public function balances(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listFinancialAccountsWithBalances($company, $this->forwardedQuery($request));
        });
    }

    public function categories(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listCategories($company, $this->forwardedQuery($request));
        });
    }

    public function receivables(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listReceivables($company, $this->financialQuery($request));
        });
    }

    public function payables(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listPayables($company, $this->financialQuery($request));
        });
    }

    public function managerProfitLoss(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->profitLoss($company, $this->financialQuery($request));
        });
    }

    public function managerMovements(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->movements($company, $this->financialQuery($request));
        });
    }

    public function managerExpenses(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->expenses($company, $this->financialQuery($request));
        });
    }

    public function vehicleRevenueExports(Request $request)
    {
        if (! $this->canViewFinancialData($request)) {
            return response()->json(['error' => '403 Forbidden'], 403);
        }

        $validated = $request->validate([
            'tvde_week_id' => ['nullable', 'integer', 'exists:tvde_weeks,id'],
        ]);

        $company = $this->resolveCompany($request);

        if (! $company) {
            return response()->json([
                'error' => 'Empresa nao encontrada para o utilizador autenticado.',
            ], 404);
        }

        $selectedWeek = ! empty($validated['tvde_week_id'])
            ? TvdeWeek::find((int) $validated['tvde_week_id'])
            : null;

        $selectedWeekExports = collect();

        if ($selectedWeek) {
            $selectedWeekExports = ContaAzulVehicleRevenueExport::query()
                ->where('company_id', $company->id)
                ->where('tvde_week_id', $selectedWeek->id)
                ->orderByDesc('exported_at')
                ->get();
        }

        $recentWeeks = ContaAzulVehicleRevenueExport::query()
            ->select('tvde_week_id')
            ->where('company_id', $company->id)
            ->groupBy('tvde_week_id')
            ->orderByDesc('tvde_week_id')
            ->limit(8)
            ->pluck('tvde_week_id');

        $recentExports = $recentWeeks->isEmpty()
            ? collect()
            : ContaAzulVehicleRevenueExport::query()
                ->with('week')
                ->where('company_id', $company->id)
                ->whereIn('tvde_week_id', $recentWeeks)
                ->orderByDesc('tvde_week_id')
                ->orderByDesc('exported_at')
                ->get()
                ->groupBy('tvde_week_id');

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'selected_week' => $selectedWeek ? [
                'id' => $selectedWeek->id,
                'number' => $selectedWeek->number,
                'start_date' => $selectedWeek->start_date,
                'end_date' => $selectedWeek->end_date,
                'summary' => $this->summarizeVehicleRevenueExports($selectedWeekExports),
                'items' => $selectedWeekExports->map(fn (ContaAzulVehicleRevenueExport $item) => $this->serializeVehicleRevenueExport($item))->values(),
            ] : null,
            'recent_weeks' => $recentExports
                ->map(function ($items, $weekId) {
                    $week = $items->first()?->week;

                    return [
                        'week' => $week ? [
                            'id' => $week->id,
                            'number' => $week->number,
                            'start_date' => $week->start_date,
                            'end_date' => $week->end_date,
                        ] : [
                            'id' => (int) $weekId,
                            'number' => null,
                            'start_date' => null,
                            'end_date' => null,
                        ],
                        'summary' => $this->summarizeVehicleRevenueExports($items),
                    ];
                })
                ->values(),
        ]);
    }

    public function exportVehicleRevenues(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Admin')) {
            return response()->json(['error' => '403 Forbidden'], 403);
        }

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
        ]);

        $company = $this->resolveCompany($request);

        if (! $company) {
            return response()->json([
                'error' => 'Empresa nao encontrada para o utilizador autenticado.',
            ], 404);
        }

        $week = TvdeWeek::find($validated['tvde_week_id']);

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
            ], 404);
        }

        try {
            $result = $this->vehicleRevenueExporter->exportWeek($company, $week, (int) $user->id);
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => sprintf(
                'Conta Azul: %d viaturas exportadas e %d ignoradas.',
                $result['exported'],
                $result['skipped']
            ),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'week' => [
                'id' => $week->id,
                'number' => $week->number,
                'start_date' => $week->start_date,
                'end_date' => $week->end_date,
            ],
            'data' => $result,
        ]);
    }

    protected function summarizeVehicleRevenueExports($exports): array
    {
        $items = collect($exports);

        return [
            'total' => $items->count(),
            'exported' => $items->where('status', ContaAzulVehicleRevenueExport::STATUS_EXPORTED)->count(),
            'errors' => $items->where('status', ContaAzulVehicleRevenueExport::STATUS_ERROR)->count(),
            'amount' => round((float) $items->sum('amount'), 2),
            'last_exported_at' => $items->max('exported_at')
                ? Carbon::parse($items->max('exported_at'))->format('Y-m-d H:i:s')
                : null,
        ];
    }

    protected function serializeVehicleRevenueExport(ContaAzulVehicleRevenueExport $item): array
    {
        return [
            'id' => $item->id,
            'vehicle_item_id' => $item->vehicle_item_id,
            'license_plate' => $item->license_plate,
            'amount' => (float) ($item->amount ?? 0),
            'description' => $item->description,
            'status' => $item->status,
            'error_message' => $item->error_message,
            'conta_azul_event_id' => $item->conta_azul_event_id,
            'conta_azul_installment_id' => $item->conta_azul_installment_id,
            'conta_azul_acquittance_id' => $item->conta_azul_acquittance_id,
            'exported_at' => optional($item->exported_at)->format('Y-m-d H:i:s'),
        ];
    }

    protected function resolveCompany(Request $request)
    {
        $user = $request->user();
        $requestedCompanyId = $request->query('company_id');

        return $this->client->resolveCompanyForUser($user, $requestedCompanyId ? (int) $requestedCompanyId : null);
    }

    protected function respondWithContaAzulData(Request $request, callable $callback)
    {
        if (! $this->canViewFinancialData($request)) {
            return response()->json(['error' => '403 Forbidden'], 403);
        }

        $company = $this->resolveCompany($request);

        if (! $company) {
            return response()->json([
                'error' => 'Empresa nao encontrada para o utilizador autenticado.',
            ], 404);
        }

        try {
            $data = $callback($company);
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'data' => $data,
        ]);
    }

    protected function forwardedQuery(Request $request): array
    {
        return collect($request->query())
            ->except(['company_id'])
            ->toArray();
    }

    protected function financialQuery(Request $request): array
    {
        $query = $this->forwardedQuery($request);

        if (!isset($query['data_vencimento_de']) || !isset($query['data_vencimento_ate'])) {
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end = Carbon::now()->endOfMonth()->toDateString();

            $query['data_vencimento_de'] = $query['data_vencimento_de'] ?? $start;
            $query['data_vencimento_ate'] = $query['data_vencimento_ate'] ?? $end;
        }

        return $query;
    }

    protected function canViewFinancialData(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole('Admin') || $user->hasRole('Gestor'));
    }
}
