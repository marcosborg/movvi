@extends('layouts.admin')
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os seus extratos.
    </div>
    @else
    <div class="btn-group btn-group-justified" role="group">
        @foreach ($tvde_years as $tvde_year)
        <a href="/admin/financial-statements/year/{{ $tvde_year->id }}" class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_months as $tvde_month)
        <a href="/admin/financial-statements/month/{{ $tvde_month->id }}" class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{
            $tvde_month->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_weeks as $tvde_week)
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
        @endforeach
    </div>
    <a href="/admin/financial-statements/driver/0" class="btn btn-default {{ $driver_id == null ? 'disabled selected' : '' }}" style="margin-top: 5px;">Todos</a>
    @foreach ($drivers as $d)
    <a href="/admin/financial-statements/driver/{{ $d->id }}" class="btn btn-default {{ $driver_id == $d->id ? 'disabled selected' : '' }}" style="margin-top: 5px;">{{
        $d->name }} {{ $d->team->count() > 0 ? '(Team)' : '' }}</a>
    @endforeach
    <div class="row" style="margin-top: 5px;">
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Atividades por operador
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Bruto</th>
                                <th style="text-align: right;">Líquido</th>
                            </tr>
                            <tr>
                                <th>UBER</th>
                                <td>{{ number_format($uber_gross, 2) }}€</td>
                                <td>{{ number_format($uber_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>BOLT</th>
                                <td>{{ number_format($bolt_gross, 2) }}€</td>
                                <td>{{ number_format($bolt_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Totais</th>
                                <td>{{ number_format($total_gross, 2) }}€</td>
                                <td>{{ number_format($total_net, 2) }}€</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    IVA
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>IVA</th>
                            <td style="color: red;">- {{ number_format($vat_value, 2) }}€</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Totais
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Créditos</th>
                                <th style="text-align: right;">Débitos</th>
                                <th style="text-align: right;">Totais</th>
                            </tr>
                            <tr>
                                <th>Ganhos</th>
                                <td>{{ number_format($total_net, 2) }}€</td>
                                <td></td>
                                <td>{{ number_format($total_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Aluguer</th>
                                <td></td>
                                <td>- {{ number_format($car_hire, 2) }}€</td>
                                <td>- {{ number_format($car_hire, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Via Verde</th>
                                <td></td>
                                <td>- {{ number_format($car_track, 2) }}€</td>
                                <td>- {{ number_format($car_track, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Abastecimento</th>
                                <td></td>
                                <td>- {{ number_format($fuel_transactions, 2) }}€</td>
                                <td>- {{ number_format($fuel_transactions, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Acertos</th>
                                <td>{{ $adjustments > 0 ? number_format($adjustments, 2) . '€' : '' }}</td>
                                <td>{{ $adjustments < 0 ? number_format($adjustments, 2) . '€' : '' }}</td>
                                <td>{{ number_format($adjustments, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>IVA</th>
                                <td></td>
                                <td>- {{ number_format($vat_value, 2) }}€</td>
                                <td>- {{ number_format($vat_value, 2) }}€</td>
                            </tr>
                            @php
                            if ($adjustments && $adjustments > 0) {
                            $total_net = $total_net + $adjustments;
                            }
                            @endphp
                            <tr>
                                <th>Totais</th>
                                <th style="text-align: right;">{{ number_format($total_net, 2) }}€</th>
                                <th style="text-align: right;">{{ number_format(($total - $total_net), 2) }}€</th>
                                <th style="text-align: right;">{{ number_format($total, 2) }}€</th>
                            </tr>
                        </tbody>
                    </table>
                    @if ($driver_balance && $driver_balance->drivers_balance > 0)
                        <p><small>Saldo transitado: {{ number_format(($total - ($driver_balance->drivers_balance ?? 0)) * -1, 2) }}€</small></p>
                    @else
                        <p><small>Saldo transitado: {{ number_format(($total + ($driver_balance->drivers_balance ?? 0)) * -1, 2) }}€</small></p>
                    @endif
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body">
                    <h3 class="pull-left">Valor semanal sem impostos: <span style="font-weight: 800;">{{ number_format($total, 2) }}</span>€</h3>
                    <div class="pull-right">
                        <a target="_new" href="/admin/financial-statements/pdf" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i></a>
                        <a href="/admin/financial-statements/pdf/1" class="btn btn-primary"><i class="fa fa-cloud-download"></i></a>
                    </div>
                </div>
                @if (auth()->user()->hasRole('Admin'))
                <div class="panel-footer">
                    <form action="/admin/financial-statements/update-balance" method="post" id="update-balance">
                        @csrf
                        <input type="hidden" name="driver_balance_id" value="{{ $driver_balance->id ?? 0 }}">
                        <div class="form-inline">
                            <div class="input-group">
                                <div class="input-group-addon">Saldo (€)</div>
                                <input type="text" class="form-control" value="{{ $driver_balance->drivers_balance ?? 0 }}" name="balance">
                            </div>
                            <button type="submit" class="btn btn-success">Atualizar saldo</button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
</div>
@endsection
@section('styles')
<style>
    td {
        text-align: right;
    }

    table {
        font-size: 13px;
    }

    canvas#electric_racio {
        pointer-events: none;
    }

</style>
@endsection
@section('scripts')
@parent
<script src="https://malsup.github.io/jquery.form.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js">
</script>
<script>
    $(() => {
        $('#update-balance').ajaxForm({
            beforeSubmit: () => {
                $('#update-balance').LoadingOverlay('show');
            }
            , success: () => {
                $('#update-balance').LoadingOverlay('hide');
                Swal.fire({
                    title: 'Atualizado com sucesso'
                    , icon: 'success'
                , }).then(() => {
                    location.reload();
                });
            }
            , error: (error) => {
                $('#update-balance').LoadingOverlay('hide');
                var html = '';
                $.each(error.responseJSON.errors, (i, v) => {
                    $.each(v, (index, value) => {
                        html += value + '<br>'
                    });
                });
                Swal.fire({
                    title: 'Erro de validação'
                    , html: html
                    , icon: 'error'
                , }).then(() => {
                    location.reload();
                });
            }
        });
    });

</script>
@endsection
<script>
    console.log({
        !!$driver_balance!!
    })

</script>
