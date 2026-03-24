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

        return view('admin.contaAzul.index', [
            'company' => $company,
            'status' => $this->client->statusForCompany($company),
            'isConfigured' => $this->oauthService->isConfigured(),
            'redirectUri' => $this->oauthService->resolveRedirectUri(),
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
}
