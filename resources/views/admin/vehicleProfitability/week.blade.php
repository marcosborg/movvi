@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <form method="GET" class="form-inline" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label for="tvde_week_id">Week</label>
                    <select name="tvde_week_id" id="tvde_week_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($weeks as $week)
                            <option value="{{ $week->id }}" @if($weekId === $week->id) selected @endif>
                                {{ $week->start_date }} → {{ $week->end_date }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-left: 10px;">Load</button>
                <a class="btn btn-default" style="margin-left: 10px;" href="{{ route('admin.vehicle-profitabilities.index') }}">
                    Voltar
                </a>
            </form>
            @if($weekId)
                <form action="{{ route('admin.vehicle-profitabilities.export-conta-azul') }}" method="POST" style="display:inline-block; margin-bottom: 20px;">
                    @csrf
                    <input type="hidden" name="tvde_week_id" value="{{ $weekId }}">
                    <button class="btn btn-success" type="submit" {{ $companyId ? '' : 'disabled' }}>
                        Lançar recebimentos na Conta Azul
                    </button>
                </form>
            @endif
            @if(!$companyId)
                <div class="alert alert-warning" role="alert">
                    Selecione uma empresa para lançar as receitas da semana na Conta Azul.
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
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Receitas por viatura (semana)</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Modelo</th>
                                    <th style="text-align:right;">Aluguer (€)</th>
                                    <th style="text-align:right;">Percentagem (€)</th>
                                    <th style="text-align:right;">Ajustes (€)</th>
                                    <th style="text-align:right;">Total (€)</th>
                                    <th style="text-align:right;">Motoristas</th>
                                    <th style="text-align:right;">Sem validação</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($result['vehicles'] ?? []) as $v)
                                    <tr>
                                        <td>{{ $v['license_plate'] }}</td>
                                        <td>{{ $v['model'] }}</td>
                                        <td style="text-align:right;">{{ number_format($v['rental_total'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($v['commission_total'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($v['adjustments_total'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;"><strong>{{ number_format($v['total_revenue'] ?? 0, 2, ',', '.') }}</strong></td>
                                        <td style="text-align:right;">{{ $v['drivers_count'] ?? 0 }}</td>
                                        <td style="text-align:right;">{{ $v['missing_accounts_count'] ?? 0 }}</td>
                                        <td style="white-space: nowrap;">
                                            <a class="btn btn-xs btn-primary"
                                               href="{{ route('admin.vehicle-profitabilities.index', ['vehicle_id' => $v['id'], 'tvde_week_id' => $result['week']['tvde_week_id']]) }}">
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" style="text-align:right;">Totais:</th>
                                    <th style="text-align:right;">{{ number_format($result['totals']['rental_total'] ?? 0, 2, ',', '.') }}</th>
                                    <th style="text-align:right;">{{ number_format($result['totals']['commission_total'] ?? 0, 2, ',', '.') }}</th>
                                    <th style="text-align:right;">{{ number_format($result['totals']['adjustments_total'] ?? 0, 2, ',', '.') }}</th>
                                    <th style="text-align:right;"><strong>{{ number_format($result['totals']['total_revenue'] ?? 0, 2, ',', '.') }}</strong></th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
