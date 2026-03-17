<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ContaAzul\ContaAzulClient;
use App\Services\ContaAzul\ContaAzulManagerDashboardService;
use Illuminate\Http\Request;

class ContaAzulController extends Controller
{
    public function __construct(
        protected ContaAzulClient $client,
        protected ContaAzulManagerDashboardService $managerDashboard
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
            return $this->client->listReceivables($company, $this->forwardedQuery($request));
        });
    }

    public function payables(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->client->listPayables($company, $this->forwardedQuery($request));
        });
    }

    public function managerProfitLoss(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->profitLoss($company, $this->forwardedQuery($request));
        });
    }

    public function managerMovements(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->movements($company, $this->forwardedQuery($request));
        });
    }

    public function managerExpenses(Request $request)
    {
        return $this->respondWithContaAzulData($request, function ($company) use ($request) {
            return $this->managerDashboard->expenses($company, $this->forwardedQuery($request));
        });
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

    protected function canViewFinancialData(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole('Admin') || $user->hasRole('Gestor'));
    }
}
