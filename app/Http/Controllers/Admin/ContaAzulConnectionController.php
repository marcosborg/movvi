<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\ContaAzul\ContaAzulClient;
use App\Services\ContaAzul\ContaAzulOAuthService;
use Illuminate\Http\Request;

class ContaAzulConnectionController extends Controller
{
    public function __construct(
        protected ContaAzulOAuthService $oauthService,
        protected ContaAzulClient $client
    ) {
    }

    public function index(Company $company)
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403, '403 Forbidden');

        $company->load('conta_azul_connection');

        $contactOptions = [];
        $accountOptions = [];
        $optionsError = null;

        if ($company->conta_azul_connection?->access_token) {
            try {
                $contacts = $this->fetchAllContaAzulPeople($company);
                $accounts = $this->fetchAllContaAzulFinancialAccounts($company);

                $contactOptions = collect($contacts['items'] ?? [])
                    ->map(function (array $item) {
                        $name = $item['nome'] ?? $item['name'] ?? $item['razao_social'] ?? $item['fantasia'] ?? null;
                        $document = $item['cpf_cnpj'] ?? $item['documento'] ?? null;

                        return [
                            'id' => $item['id'] ?? null,
                            'label' => trim(implode(' · ', array_filter([$name, $document]))),
                        ];
                    })
                    ->filter(fn (array $item) => filled($item['id']) && filled($item['label']))
                    ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                $accountOptions = collect($accounts['items'] ?? [])
                    ->map(function (array $item) {
                        $name = $item['nome'] ?? $item['name'] ?? 'Conta';
                        $type = $item['tipo'] ?? $item['type'] ?? null;

                        return [
                            'id' => $item['id'] ?? null,
                            'label' => trim(implode(' · ', array_filter([$name, $type]))),
                        ];
                    })
                    ->filter(fn (array $item) => filled($item['id']) && filled($item['label']))
                    ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
            } catch (\Throwable $exception) {
                $optionsError = $exception->getMessage();
            }
        }

        return view('admin.contaAzul.index', [
            'company' => $company,
            'status' => $this->client->statusForCompany($company),
            'isConfigured' => $this->oauthService->isConfigured(),
            'redirectUri' => $this->oauthService->resolveRedirectUri(),
            'contactOptions' => $contactOptions,
            'accountOptions' => $accountOptions,
            'optionsError' => $optionsError,
        ]);
    }

    public function connect(Company $company)
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403, '403 Forbidden');

        if (! $this->oauthService->isConfigured()) {
            return redirect()
                ->route('admin.conta-azul.index', $company)
                ->with('error_message', 'Faltam configurar as credenciais Conta Azul no .env.');
        }

        return redirect()->away($this->oauthService->buildAuthorizationUrl($company, (int) auth()->id()));
    }

    public function callback(Request $request)
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403, '403 Forbidden');

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $connection = $this->oauthService->exchangeAuthorizationCode(
                (string) $request->query('code'),
                (string) $request->query('state')
            );
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.companies.index')
                ->with('error_message', $exception->getMessage());
        }

        return redirect()
            ->route('admin.conta-azul.index', $connection->company_id)
            ->with('message', 'Ligacao Conta Azul concluida com sucesso.');
    }

    public function disconnect(Company $company)
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403, '403 Forbidden');

        $connection = $company->conta_azul_connection;

        if ($connection) {
            $this->oauthService->disconnect($connection);
        }

        return redirect()
            ->route('admin.conta-azul.index', $company)
            ->with('message', 'Ligacao Conta Azul removida.');
    }

    public function updateReceivableSettings(Request $request, Company $company)
    {
        abort_if(! auth()->user()->hasRole('Admin'), 403, '403 Forbidden');

        $request->validate([
            'receivable_contact_id' => ['required', 'string', 'max:255'],
            'receivable_financial_account_id' => ['required', 'string', 'max:255'],
            'receivable_payment_method' => ['required', 'string', 'max:255'],
        ]);

        $connection = $company->conta_azul_connection;

        if (! $connection) {
            return redirect()
                ->route('admin.conta-azul.index', $company)
                ->with('error_message', 'Ligue primeiro a empresa à Conta Azul.');
        }

        $connection->update($request->only([
            'receivable_contact_id',
            'receivable_financial_account_id',
            'receivable_payment_method',
        ]));

        return redirect()
            ->route('admin.conta-azul.index', $company)
            ->with('message', 'Configuracao de recebimentos atualizada.');
    }

    protected function fetchAllContaAzulPeople(Company $company): array
    {
        $page = 1;
        $perPage = 200;
        $items = collect();
        $total = null;

        do {
            $response = $this->client->listPeople($company, [
                'pagina' => $page,
                'tamanho_pagina' => $perPage,
            ]);

            $batch = collect($response['items'] ?? []);
            $items = $items->concat($batch);
            $total = is_numeric($response['total'] ?? null) ? (int) $response['total'] : null;
            $page++;
        } while ($batch->isNotEmpty() && ($total === null ? $batch->count() === $perPage : $items->count() < $total));

        return [
            'items' => $items->values()->all(),
            'total' => $total ?? $items->count(),
        ];
    }

    protected function fetchAllContaAzulFinancialAccounts(Company $company): array
    {
        $page = 1;
        $perPage = 200;
        $items = collect();
        $total = null;

        do {
            $response = $this->client->listFinancialAccounts($company, [
                'pagina' => $page,
                'tamanho_pagina' => $perPage,
            ]);

            $batch = collect($response['items'] ?? []);
            $items = $items->concat($batch);
            $total = is_numeric($response['items_totais'] ?? null) ? (int) $response['items_totais'] : null;
            $page++;
        } while ($batch->isNotEmpty() && ($total === null ? $batch->count() === $perPage : $items->count() < $total));

        return [
            'items' => $items->values()->all(),
            'items_totais' => $total ?? $items->count(),
        ];
    }
}
