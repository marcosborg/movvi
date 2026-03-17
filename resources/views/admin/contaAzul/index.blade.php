@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Conta Azul
                </div>
                <div class="panel-body">
                    <h4 style="margin-top:0;">{{ $company->name }}</h4>

                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th>Configuracao local</th>
                                <td>
                                    @if ($isConfigured)
                                        <span class="label label-success">Pronta</span>
                                    @else
                                        <span class="label label-danger">Incompleta</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Estado da ligacao</th>
                                <td>{{ $status['status'] }}</td>
                            </tr>
                            <tr>
                                <th>Ligada</th>
                                <td>{{ $status['connected'] ? 'Sim' : 'Nao' }}</td>
                            </tr>
                            <tr>
                                <th>Redirect URI</th>
                                <td><code>{{ $redirectUri }}</code></td>
                            </tr>
                            <tr>
                                <th>Expira em</th>
                                <td>{{ $status['expires_at'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Ultimo refresh</th>
                                <td>{{ $status['last_refreshed_at'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Ultima sincronizacao</th>
                                <td>{{ $status['last_synced_at'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Scope</th>
                                <td>{{ $status['scope'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Ultimo erro</th>
                                <td>{{ $status['last_error'] ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="{{ route('admin.conta-azul.connect', $company) }}" class="btn btn-primary {{ $isConfigured ? '' : 'disabled' }}">
                            Ligar Conta Azul
                        </a>
                        <form action="{{ route('admin.conta-azul.disconnect', $company) }}" method="POST" style="display:inline-block;">
                            @csrf
                            <button class="btn btn-danger" type="submit">Desligar</button>
                        </form>
                        <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-default">Voltar a empresa</a>
                    </div>

                    <hr>

                    <p><strong>Endpoints internos prontos</strong></p>
                    <ul>
                        <li><code>/api/v1/conta-azul/status?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/accounts?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/balances?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/categories?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/receivables?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/payables?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/manager/profit-loss?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/manager/movements?company_id={{ $company->id }}</code></li>
                        <li><code>/api/v1/conta-azul/manager/expenses?company_id={{ $company->id }}</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
