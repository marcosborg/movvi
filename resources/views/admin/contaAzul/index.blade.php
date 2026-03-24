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

                    <h4>Configuracao para receitas por matricula</h4>
                    <form action="{{ route('admin.conta-azul.receivable-settings', $company) }}" method="POST" style="max-width: 760px;">
                        @csrf
                        @if($optionsError)
                            <div class="alert alert-warning">
                                Nao foi possivel carregar automaticamente contactos e contas financeiras: {{ $optionsError }}
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="receivable_contact_id">Contacto Conta Azul</label>
                            <select
                                class="form-control"
                                id="receivable_contact_id"
                                name="receivable_contact_id"
                            >
                                <option value="">Selecionar contacto</option>
                                @foreach($contactOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ old('receivable_contact_id', $company->conta_azul_connection->receivable_contact_id ?? '') == $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($contactOptions))
                                <p class="help-block">Liga a empresa e carrega contactos validos a partir da API do Conta Azul.</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="receivable_financial_account_id">Conta financeira</label>
                            <select
                                class="form-control"
                                id="receivable_financial_account_id"
                                name="receivable_financial_account_id"
                            >
                                <option value="">Selecionar conta financeira</option>
                                @foreach($accountOptions as $option)
                                    <option value="{{ $option['id'] }}" {{ old('receivable_financial_account_id', $company->conta_azul_connection->receivable_financial_account_id ?? '') == $option['id'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($accountOptions))
                                <p class="help-block">As contas financeiras serao carregadas automaticamente quando a ligacao estiver operacional.</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="receivable_payment_method">Metodo de pagamento</label>
                            <select
                                class="form-control"
                                id="receivable_payment_method"
                                name="receivable_payment_method"
                            >
                                @foreach(['TRANSFERENCIA_BANCARIA', 'DINHEIRO', 'BOLETO_BANCARIO', 'CARTAO_DEBITO', 'CARTAO_CREDITO', 'PIX'] as $method)
                                    <option value="{{ $method }}" {{ old('receivable_payment_method', $company->conta_azul_connection->receivable_payment_method ?? 'TRANSFERENCIA_BANCARIA') == $method ? 'selected' : '' }}>
                                        {{ $method }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-success" type="submit">Guardar configuracao</button>
                    </form>

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
