<?php

namespace App\Services\ContaAzul;

use App\Models\Company;
use App\Models\ContaAzulConnection;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ContaAzulClient
{
    public function __construct(
        protected ContaAzulOAuthService $oauthService
    ) {
    }

    public function connectionForCompany(Company $company): ?ContaAzulConnection
    {
        return $company->conta_azul_connection;
    }

    public function isEnabled(): bool
    {
        return (bool) config('conta_azul.enabled', true);
    }

    public function disabledMessage(): string
    {
        return (string) config('conta_azul.disabled_message', 'Integracao Conta Azul desativada neste ambiente.');
    }

    public function requireConnectionForCompany(Company $company): ContaAzulConnection
    {
        $this->ensureEnabled();

        $connection = $this->connectionForCompany($company);

        if (! $connection || ! $connection->access_token) {
            throw new \RuntimeException('A empresa ainda nao tem uma ligacao Conta Azul ativa.');
        }

        if ($this->shouldRefresh($connection)) {
            $connection = $this->oauthService->refresh($connection);
        }

        return $connection;
    }

    public function statusForCompany(Company $company): array
    {
        $connection = $this->connectionForCompany($company);

        return [
            'enabled' => $this->isEnabled(),
            'configured' => $this->oauthService->isConfigured(),
            'connected' => (bool) ($connection && $connection->status === ContaAzulConnection::STATUS_CONNECTED && $connection->access_token),
            'status' => $connection?->status ?? ContaAzulConnection::STATUS_PENDING,
            'expires_at' => optional($connection?->expires_at)->toIso8601String(),
            'connected_at' => optional($connection?->connected_at)->toIso8601String(),
            'last_refreshed_at' => optional($connection?->last_refreshed_at)->toIso8601String(),
            'last_synced_at' => optional($connection?->last_synced_at)->toIso8601String(),
            'scope' => $connection?->scope,
            'last_error' => $connection?->last_error,
            'disabled_message' => $this->isEnabled() ? null : $this->disabledMessage(),
        ];
    }

    public function listFinancialAccounts(Company $company, array $query = []): array
    {
        $response = $this->request($company, 'GET', '/v1/conta-financeira', $query);
        $payload = $response->json();
        $this->touchSync($company);

        return [
            'raw' => $payload,
            'items_totais' => $payload['itens_totais'] ?? null,
            'items' => $payload['itens'] ?? [],
            'totais' => $payload['totais'] ?? null,
        ];
    }

    public function listFinancialAccountsWithBalances(Company $company, array $query = []): array
    {
        $accounts = $this->listFinancialAccounts($company, $query);

        $accounts['items'] = collect($accounts['items'])
            ->map(function (array $account) use ($company) {
                $accountId = $account['id'] ?? null;
                if (! $accountId) {
                    return $account;
                }

                $balance = $this->getFinancialAccountBalance($company, (string) $accountId);
                $account['saldo_atual'] = $balance['saldo_atual'] ?? null;

                return $account;
            })
            ->values()
            ->all();

        return $accounts;
    }

    public function getFinancialAccountBalance(Company $company, string $accountId): array
    {
        $response = $this->request($company, 'GET', "/v1/conta-financeira/{$accountId}/saldo-atual");
        $this->touchSync($company);

        return $response->json();
    }

    public function listCategories(Company $company, array $query = []): array
    {
        $response = $this->request($company, 'GET', '/v1/categorias', $query);
        $this->touchSync($company);

        return $response->json();
    }

    public function listReceivables(Company $company, array $query = []): array
    {
        $response = $this->request($company, 'GET', '/v1/financeiro/eventos-financeiros/contas-a-receber/buscar', $query);
        $this->touchSync($company);

        return $response->json();
    }

    public function listPayables(Company $company, array $query = []): array
    {
        $response = $this->request($company, 'GET', '/v1/financeiro/eventos-financeiros/contas-a-pagar/buscar', $query);
        $this->touchSync($company);

        return $response->json();
    }

    public function createReceivableEvent(Company $company, array $payload): array
    {
        $response = $this->requestJson($company, 'POST', '/v1/financeiro/eventos-financeiros/contas-a-receber', $payload);
        $this->touchSync($company);

        return $response->json();
    }

    public function listEventInstallments(Company $company, string $eventId): array
    {
        $response = $this->request($company, 'GET', "/v1/financeiro/eventos-financeiros/{$eventId}/parcelas");
        $this->touchSync($company);

        return $response->json();
    }

    public function createAcquittance(Company $company, string $installmentId, array $payload): array
    {
        $response = $this->requestJson($company, 'POST', "/v1/financeiro/eventos-financeiros/parcelas/{$installmentId}/baixa", $payload);
        $this->touchSync($company);

        return $response->json();
    }

    public function resolveCompanyForUser(User $user, ?int $requestedCompanyId = null): ?Company
    {
        if ($requestedCompanyId && $user->hasRole('Admin')) {
            return Company::find($requestedCompanyId);
        }

        if ($user->company) {
            return $user->company;
        }

        $driver = $user->driver()->with('company')->first();

        if ($driver?->company) {
            return $driver->company;
        }

        if ($user->hasRole('Admin') || $user->hasRole('Gestor')) {
            return Company::query()
                ->whereHas('conta_azul_connection', function ($query) {
                    $query->whereNotNull('access_token');
                })
                ->orderByDesc('main')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    protected function request(Company $company, string $method, string $path, array $query = []): Response
    {
        $this->ensureEnabled();

        $connection = $this->requireConnectionForCompany($company);
        $url = rtrim(config('conta_azul.api_base_url'), '/') . $path;

        $response = Http::acceptJson()
            ->withToken((string) $connection->access_token)
            ->send($method, $url, [
                'query' => $query,
            ]);

        if ($response->status() === 401) {
            $connection = $this->oauthService->refresh($connection);

            $response = Http::acceptJson()
                ->withToken((string) $connection->access_token)
                ->send($method, $url, [
                    'query' => $query,
                ]);
        }

        if ($response->failed()) {
            $connection->update([
                'last_error' => 'Conta Azul API: ' . $response->status() . ' ' . $response->body(),
                'status' => $response->status() === 401
                    ? ContaAzulConnection::STATUS_ERROR
                    : $connection->status,
            ]);

            throw new \RuntimeException(
                'Falha ao ler dados da Conta Azul: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response;
    }

    protected function requestJson(Company $company, string $method, string $path, array $payload = []): Response
    {
        $this->ensureEnabled();

        $connection = $this->requireConnectionForCompany($company);
        $url = rtrim(config('conta_azul.api_base_url'), '/') . $path;

        $response = Http::acceptJson()
            ->withToken((string) $connection->access_token)
            ->send($method, $url, [
                'json' => $payload,
            ]);

        if ($response->status() === 401) {
            $connection = $this->oauthService->refresh($connection);

            $response = Http::acceptJson()
                ->withToken((string) $connection->access_token)
                ->send($method, $url, [
                    'json' => $payload,
                ]);
        }

        if ($response->failed()) {
            $connection->update([
                'last_error' => 'Conta Azul API: ' . $response->status() . ' ' . $response->body(),
                'status' => $response->status() === 401
                    ? ContaAzulConnection::STATUS_ERROR
                    : $connection->status,
            ]);

            throw new \RuntimeException(
                'Falha ao enviar dados para a Conta Azul: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response;
    }

    protected function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException($this->disabledMessage());
        }
    }

    protected function shouldRefresh(ContaAzulConnection $connection): bool
    {
        if (! $connection->expires_at) {
            return false;
        }

        return $connection->expires_at->lessThanOrEqualTo(
            Carbon::now()->addMinutes(config('conta_azul.refresh_leeway_minutes', 5))
        );
    }

    protected function touchSync(Company $company): void
    {
        $connection = $company->conta_azul_connection;

        if (! $connection) {
            return;
        }

        $connection->update([
            'last_synced_at' => now(),
            'last_error' => null,
            'status' => ContaAzulConnection::STATUS_CONNECTED,
        ]);
    }
}
