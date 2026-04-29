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
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana {{ $tvde_week->display_number ?? $tvde_week->number }}/{{ $tvde_week->display_year ?? '-' }} · {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m') }}</a>
    @endforeach
    </div>
    @include('admin.partials.weekQuickSelect', ['tvde_weeks' => $tvde_weeks, 'tvde_week_id' => $tvde_week_id])
    <div class="report-toolbar">
        <input type="text" id="driverStatementSearch" class="form-control" placeholder="Filtrar motorista por nome">
        @if($driver_id && $driver_email)
        <span class="label label-info">Email configurado</span>
        @elseif($driver_id)
        <span class="label label-warning">Sem email configurado</span>
        @endif
    </div>
    <a href="/admin/financial-statements/driver/0" class="btn btn-default {{ $driver_id == null ? 'disabled selected' : '' }}" style="margin-top: 5px;">Todos</a>
    @foreach ($drivers as $d)
    <a href="/admin/financial-statements/driver/{{ $d->id }}" class="btn btn-default statement-driver-link {{ $driver_id == $d->id ? 'disabled selected' : '' }}" data-driver-name="{{ mb_strtolower($d->name) }}" style="margin-top: 5px;">{{
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
                    Retenções
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Taxa 6%</th>
                            <td style="color: red;">- {{ number_format($iva_value ?? 0, 2) }}€</td>
                        </tr>
                        <tr>
                            <th>Percentagem</th>
                            <td style="color: red;">- {{ number_format($percent_value ?? 0, 2) }}€</td>
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
                                <td>- {{ number_format($car_hire_base, 2) }}€</td>
                                <td>- {{ number_format($car_hire_base, 2) }}€</td>
                            </tr>
                            @if (($rent_discount ?? 0) > 0)
                            <tr>
                                <th>Abatimento de aluguer</th>
                                <td>{{ number_format($rent_discount, 2) }}€</td>
                                <td></td>
                                <td>{{ number_format($rent_discount, 2) }}€</td>
                            </tr>
                            @endif
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
                                <td>{{ $general_adjustments > 0 ? number_format($general_adjustments, 2) . '€' : '' }}</td>
                                <td>{{ $general_adjustments < 0 ? number_format($general_adjustments, 2) . '€' : '' }}</td>
                                <td>{{ number_format($general_adjustments, 2) }}€</td>
                            </tr>
                            @if (($minimum_billing_difference ?? 0) != 0)
                            <tr>
                                <th>Diferença de faturação mínima</th>
                                <td>{{ $minimum_billing_difference > 0 ? number_format($minimum_billing_difference, 2) . '€' : '' }}</td>
                                <td>{{ $minimum_billing_difference < 0 ? number_format($minimum_billing_difference, 2) . '€' : '' }}</td>
                                <td>{{ number_format($minimum_billing_difference, 2) }}€</td>
                            </tr>
                            @endif
                            @if (($caution_received ?? 0) != 0)
                            <tr>
                                <th>Caução recebida</th>
                                <td>{{ number_format($caution_received, 2) }}€</td>
                                <td></td>
                                <td>Informativo</td>
                            </tr>
                            @endif
                            @if (($caution_returned ?? 0) != 0)
                            <tr>
                                <th>Caução devolvida</th>
                                <td>{{ number_format($caution_returned, 2) }}€</td>
                                <td></td>
                                <td>Informativo</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Taxa 6%</th>
                                <td></td>
                                <td>- {{ number_format($iva_value ?? 0, 2) }}€</td>
                                <td>- {{ number_format($iva_value ?? 0, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Percentagem</th>
                                <td></td>
                                <td>- {{ number_format($percent_value ?? 0, 2) }}€</td>
                                <td>- {{ number_format($percent_value ?? 0, 2) }}€</td>
                            </tr>
                            @php
                            if ($general_adjustments && $general_adjustments > 0) {
                                $total_net = $total_net + $general_adjustments;
                            }
                            if ($minimum_billing_difference && $minimum_billing_difference > 0) {
                                $total_net = $total_net + $minimum_billing_difference;
                            }
                            if ($rent_discount && $rent_discount > 0) {
                                $total_net = $total_net + $rent_discount;
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
                    <p><small>Saldo transitado: {{ $driver_balance->last_balance ?? 0.00 }} €</small></p>
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
                @if(session('status'))
                <div class="alert alert-success" style="margin: 0 15px 15px 15px;">
                    {{ session('status') }}
                </div>
                @endif
                @if($errors->has('statement_email'))
                <div class="alert alert-danger" style="margin: 0 15px 15px 15px;">
                    {{ $errors->first('statement_email') }}
                </div>
                @endif
                @if($driver_id && $driver_email)
                <div class="panel-body" style="padding-top: 0;">
                    <form action="{{ route('admin.financial-statements.send-email') }}" method="post" class="form-inline">
                        @csrf
                        <input type="hidden" name="driver_id" value="{{ $driver_id }}">
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <button type="submit" class="btn btn-info">Enviar extrato por email</button>
                        <span style="margin-left: 10px;">
                            Destino: <strong>{{ $driver_email }}</strong>
                            @if($statement_sent_at)
                            <small style="display:block;">Ultimo envio: {{ \Carbon\Carbon::parse($statement_sent_at)->format('d/m/Y H:i') }} para {{ $statement_sent_to }}</small>
                            @endif
                        </span>
                    </form>
                </div>
                @elseif($driver_id)
                <div class="alert alert-warning" style="margin: 0 15px 15px 15px;">
                    O motorista selecionado nao tem email configurado.
                </div>
                @endif
                @if (auth()->user()->hasRole('Admin'))
                <div class="panel-footer">
                    <form action="/admin/financial-statements/update-balance" method="post" id="update-balance">
                        @csrf
                        <input type="hidden" name="driver_balance_id" value="{{ $driver_balance->id ?? 0 }}">
                        <div class="form-inline">
                            <div class="input-group">
                                <div class="input-group-addon">Saldo (€)</div>
                                <input type="text" class="form-control" value="{{ number_format(($driver_balance->new_balance ?? 0), 2) }}" name="new_balance">
                            </div>
                            <button type="submit" class="btn btn-success">Atualizar saldo</button>
                    </form>
                </div>
                @endif
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Passagens Via Verde da semana
                </div>
                <div class="panel-body">
                    @if(($car_track_details ?? collect())->count())
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th style="text-align: left;">Data</th>
                                <th style="text-align: right;">Valor</th>
                            </tr>
                            @foreach($car_track_details as $item)
                            <tr>
                                <td style="text-align: left;">{{ \Carbon\Carbon::parse($item['date'])->format('d-m-Y H:i') }}</td>
                                <td>{{ number_format((float) $item['value'], 2) }} EUR</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p>Sem passagens Via Verde para a semana filtrada.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
</div>
@endsection
@section('styles')
<style>
    .report-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin: 10px 0 15px 0;
    }

    .report-toolbar .form-control {
        max-width: 320px;
    }

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
        const driverSearch = document.getElementById('driverStatementSearch');
        const driverLinks = Array.from(document.querySelectorAll('.statement-driver-link'));

        driverSearch?.addEventListener('input', (event) => {
            const query = (event.target.value || '').toLowerCase().trim();

            driverLinks.forEach((link) => {
                const name = link.dataset.driverName || '';
                link.style.display = !query || name.includes(query) ? '' : 'none';
            });
        });

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
