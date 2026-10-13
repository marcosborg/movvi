@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <form method="GET" class="form-inline" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label for="tvde_week_id">Semana</label>
                    <select name="tvde_week_id" id="tvde_week_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($weeks as $week)
                            <option value="{{ $week->id }}" @if($weekId === $week->id) selected @endif>
                                {{ $week->start_date }} -> {{ $week->end_date }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-left:10px;">
                    <label for="month">ou mês</label>
                    <input type="month" name="month" id="month" class="form-control" value="{{ $month }}">
                </div>
                <button class="btn btn-primary" type="submit" style="margin-left: 10px;">Load</button>
                <a class="btn btn-default" style="margin-left: 10px;" href="{{ route('admin.vehicle-profitabilities.index') }}">
                    Voltar
                </a>
                @if($result)
                    <a class="btn btn-default" style="margin-left: 10px;" href="{{ route('admin.vehicle-profitabilities.week-pdf', $month ? ['month' => $month] : ['tvde_week_id' => $weekId]) }}" target="_blank">
                        Imprimir PDF
                    </a>
                @endif
            </form>

            @if(!$companyId)
                <div class="alert alert-warning" role="alert">
                    Selecione uma empresa para comunicar as receitas da semana a Conta Azul.
                </div>
            @endif
        </div>
    </div>

    @if(!empty($message))
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info" role="alert">{{ $message }}</div>
            </div>
        </div>
    @endif

    @if($result)
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info" role="alert">
                    <strong>Leitura operacional:</strong>
                    {{ $result['meta']['exclusions']['receipts'] ?? '' }}
                    {{ $result['meta']['exclusions']['reimbursements'] ?? '' }}
                    <br><strong>Valor comunicado</strong> é o valor enviado para a Conta Azul. <strong>Divergente</strong> significa que o cálculo da semana mudou depois desse envio; use “Corrigir movimento” para atualizar a parcela e a baixa existentes, sem criar outro movimento. A atribuição semanal respeita a viatura usada na data e qualquer correção manual guardada para essa semana.
                </div>
                @if(!empty($result['period']['estimated']))<div class="alert alert-warning">Relatório mensal estimado: semanas repartidas proporcionalmente pelos dias de cada mês.</div>@endif
                @if(!$month)
                    @foreach(($result['export_statuses'] ?? collect()) as $correctionExport)
                        <form id="correct-conta-azul-{{ $correctionExport->id }}" action="{{ route('admin.vehicle-profitabilities.correct-conta-azul') }}" method="POST" style="display:none;">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="export_id" value="{{ $correctionExport->id }}">
                        </form>
                    @endforeach
                @endif
                <form action="{{ route('admin.vehicle-profitabilities.export-conta-azul') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tvde_week_id" value="{{ $weekId }}">

                    <div class="box box-primary">
                        <div class="box-header with-border" style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:center;">
                            <h3 class="box-title">Receitas por viatura ({{ $month ? 'mês' : 'semana' }})</h3>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button class="btn btn-default btn-sm" type="button" id="select-exportable" {{ $companyId ? '' : 'disabled' }}>
                                    Selecionar exportaveis
                                </button>
                                <button class="btn btn-default btn-sm" type="button" id="clear-selection">
                                    Limpar selecao
                                </button>
                                <button class="btn btn-success btn-sm" type="submit" {{ $companyId && !$month ? '' : 'disabled' }}>
                                    Comunicar selecionadas a Conta Azul
                                </button>
                            </div>
                        </div>
                        <div class="box-body table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:52px;">Sel.</th>
                                        <th>Matricula</th>
                                        <th>Modelo</th>
                                        <th style="text-align:right;">Cedência (EUR)</th>
                                        <th style="text-align:right;">Percentagem (EUR)</th>
                                        <th style="text-align:right;">Ajustes (EUR)</th>
                                        <th style="text-align:right;">KM excedidos (EUR)</th>
                                        <th style="text-align:right;">Energia empresa (EUR)</th>
                                        <th style="text-align:right;">Total (EUR)</th>
                                        <th style="text-align:right;">Motoristas</th>
                                        <th style="text-align:right;">Sem validacao</th>
                                        <th>Estado Conta Azul</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($result['vehicles'] ?? []) as $v)
                                        @php
                                            $export = $result['export_statuses'][$v['id']] ?? null;
                                            $alreadyExported = $export && $export->status === \App\Models\ContaAzulVehicleRevenueExport::STATUS_EXPORTED;
                                            $isDivergent = $alreadyExported
                                                && abs((float) $export->amount - (float) ($v['total_revenue'] ?? 0)) >= 0.01;
                                            $isSelectable = ($v['total_revenue'] ?? 0) > 0 && ! $alreadyExported;
                                        @endphp
                                        <tr>
                                            <td style="text-align:center;">
                                                <input
                                                    type="checkbox"
                                                    name="vehicle_item_ids[]"
                                                    value="{{ $v['id'] }}"
                                                    class="vehicle-export-checkbox"
                                                    {{ $isSelectable ? '' : 'disabled' }}
                                                >
                                            </td>
                                            <td>{{ $v['license_plate'] }}</td>
                                            <td>{{ $v['model'] }}</td>
                                            <td style="text-align:right;">{{ number_format($v['rental_total'] ?? 0, 2, ',', '.') }}</td>
                                            <td style="text-align:right;">{{ number_format($v['commission_total'] ?? 0, 2, ',', '.') }}</td>
                                            <td style="text-align:right;">{{ number_format($v['adjustments_total'] ?? 0, 2, ',', '.') }}</td>
                                            <td style="text-align:right;">{{ number_format($v['excess_kilometers_total'] ?? 0, 2, ',', '.') }}</td>
                                            <td style="text-align:right;">-{{ number_format($v['company_paid_charging_total'] ?? 0, 2, ',', '.') }}</td>
                                            <td style="text-align:right;"><strong>{{ number_format($v['total_revenue'] ?? 0, 2, ',', '.') }}</strong></td>
                                            <td style="text-align:right;">{{ $v['drivers_count'] ?? 0 }}</td>
                                            <td style="text-align:right;">{{ $v['missing_accounts_count'] ?? 0 }}</td>
                                            <td style="white-space: nowrap;">
                                                @if($isDivergent)
                                                    <span class="label label-danger">Divergente</span>
                                                    <div style="margin-top:6px; font-size:12px; color:#a94442;">
                                                        Comunicado: {{ number_format($export->amount, 2, ',', '.') }} €<br>
                                                        Atual: {{ number_format($v['total_revenue'] ?? 0, 2, ',', '.') }} €
                                                    </div>
                                                    <button class="btn btn-xs btn-warning" style="margin-top:6px;"
                                                            type="submit" form="correct-conta-azul-{{ $export->id }}"
                                                            onclick="return confirm('Corrigir este movimento existente na Conta Azul?');">
                                                        Corrigir movimento
                                                    </button>
                                                @elseif($alreadyExported)
                                                    <span class="label label-success">Comunicada</span>
                                                @elseif($export && $export->status === \App\Models\ContaAzulVehicleRevenueExport::STATUS_ERROR)
                                                    <span class="label label-danger">Falhou</span>
                                                @elseif(($v['total_revenue'] ?? 0) <= 0)
                                                    <span class="label label-default">Sem valor positivo</span>
                                                @else
                                                    <span class="label label-warning">Por comunicar</span>
                                                @endif

                                                @if($export && $export->exported_at)
                                                    <div style="margin-top:6px; font-size:12px; color:#666;">
                                                        {{ $export->exported_at->format('Y-m-d H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="white-space: nowrap;">
                                                @if(!$month)
                                                    <a class="btn btn-xs btn-primary"
                                                       href="{{ route('admin.vehicle-profitabilities.index', ['vehicle_id' => $v['id'], 'tvde_week_id' => $result['week']['tvde_week_id']]) }}">
                                                        Ver
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" style="text-align:right;">Totais:</th>
                                        <th style="text-align:right;">{{ number_format($result['totals']['rental_total'] ?? 0, 2, ',', '.') }}</th>
                                        <th style="text-align:right;">{{ number_format($result['totals']['commission_total'] ?? 0, 2, ',', '.') }}</th>
                                        <th style="text-align:right;">{{ number_format($result['totals']['adjustments_total'] ?? 0, 2, ',', '.') }}</th>
                                        <th style="text-align:right;">{{ number_format($result['totals']['excess_kilometers_total'] ?? 0, 2, ',', '.') }}</th>
                                        <th style="text-align:right;">-{{ number_format($result['totals']['company_paid_charging_total'] ?? 0, 2, ',', '.') }}</th>
                                        <th style="text-align:right;"><strong>{{ number_format($result['totals']['total_revenue'] ?? 0, 2, ',', '.') }}</strong></th>
                                        <th colspan="4"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
@parent
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectExportableButton = document.getElementById('select-exportable');
    const clearSelectionButton = document.getElementById('clear-selection');
    const checkboxes = Array.from(document.querySelectorAll('.vehicle-export-checkbox'));

    if (selectExportableButton) {
        selectExportableButton.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                if (!checkbox.disabled) {
                    checkbox.checked = true;
                }
            });
        });
    }

    if (clearSelectionButton) {
        clearSelectionButton.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });
        });
    }
});
</script>
@endsection
