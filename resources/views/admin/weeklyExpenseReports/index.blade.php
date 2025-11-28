@extends('layouts.admin')
@section('styles')
<style>
    tr {
        line-height: 25px;
    }

    tr:nth-child(even) {
        background-color: #eeeeee;
    }

    tr:nth-child(odd) {
        background-color: #ffffff;
    }
</style>
@endsection
@section('content')
<div class="content">

    @if ($company_id == 0 || $tvde_week_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os extratos.
    </div>
    @else
    <div class="btn-group btn-group-justified" role="group">
        @foreach ($tvde_years as $tvde_year)
        <a href="/admin/financial-statements/year/{{ $tvde_year->id }}"
            class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_months as $tvde_month)
        <a href="/admin/financial-statements/month/{{ $tvde_month->id }}"
            class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{
            $tvde_month->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_weeks as $tvde_week)
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}"
            class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
        @endforeach
    </div>
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Custos operacionais
                </div>
                <div class="panel-body">
                    <table style="width: 100%">
                        <thead>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Qtd.</th>
                                <th style="text-align: right;">Unitário</th>
                                <th style="text-align: right;">Semanal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Despesas fixas</th>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            @foreach ($company_expenses as $company_expense)
                            <tr>
                                <td>{{ $company_expense->name }}</td>
                                <td style="text-align: right;">{{ $company_expense->qty }}</td>
                                <td style="text-align: right;">{{ $company_expense->weekly_value }} <small>€</small>
                                </td>
                                <td style="text-align: right;">{{ number_format($company_expense->total, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            @endforeach
                            <tr>
                                <th>Despesas variáveis</th>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Prevenção de frota</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format(isset($totals->total_fleet_management) ?
                                    $totals->total_fleet_management : $totals['total_fleet_management'], 2)
                                    }} <small>€</small></td>
                            </tr>
                            <tr>
                                <td>Park</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ $company_park }} <small>€</small></td>
                            </tr>
                            <tr>
                                <td>Consultoria</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($total_consultancy, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <td>Pagamentos a motoristas</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right">{{ number_format(isset($totals->total_drivers) ?
                                    $totals->total_drivers : $totals['total_drivers'], 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Total de despesas</th>
                                <th></th>
                                <th></th>
                                <th style="text-align: right;">{{ number_format($final_total, 2) }} <small>€</small>
                                </th>
                            </tr>
                            @if (isset($fleet_adjusments) && $fleet_adjusments)
                            <tr>
                                <td>Prevenção de frota</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($fleet_adjusments, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            @endif
                            @if (isset($fleet_consultancies) && $fleet_consultancies)
                            <tr>
                                <td>Consultadorias</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($fleet_consultancies, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            @endif
                            @if (isset($fleet_company_parks) && $fleet_company_parks)
                            <tr>
                                <td>Pagamento de park</td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($fleet_company_parks, 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            @endif
                            @if (isset($fleet_earnings) && $fleet_earnings)
                            <tr>
                                <th>Totais de ganhos provenientes de outras empresas</th>
                                <td></td>
                                <td></td>
                                <th style="text-align: right;">{{ number_format($fleet_earnings, 2) }}
                                    <small>€</small>
                                </th>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="5">
                                    <canvas id="chart2" style="margin-top: 20px; height: 200px;"></canvas>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Rentabilidade semanal
                </div>
                <div class="panel-body">
                    <table style="width: 100%">
                        <tbody>
                            <tr>
                                <th>Ganhos</th>
                                <td style="text-align: right">{{ number_format(isset($totals->total_operators) ?
                                    $totals->total_operators : $totals['total_operators'], 2) }}
                                    <small>€</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Total de despesas</th>
                                <td style="text-align: right">{{ number_format($final_total, 2) }} <small>€</small></td>
                            </tr>
                            @if (isset($fleet_earnings) && $fleet_earnings)
                            <tr>
                                <th>Ganhos provenientes de outra empresas</th>
                                <td style="text-align: right">{{ number_format($fleet_earnings, 2) }} <small>€</small>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <th>Rentabilidade</th>
                                <td style="text-align: right">{{ number_format($profit, 2) }} <small>€</small></td>
                            </tr>
                            <tr>
                                <th>ROI (Return of investment)</th>
                                <td style="text-align: right">
                                    <h1>{{ round($roi) }}<small>%</small></h1>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <canvas id="chart1" style="margin-top: 20px;"></canvas>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align: right;">
                                    <div class="btn-group" style="margin-top: 20px;" role="group">
                                        <a href="/admin/weekly-expense-reports/pdf" target="_new"
                                            class="btn btn-primary"><i class="fa fa-file-pdf-o"></i></a>
                                        <a href="/admin/weekly-expense-reports/pdf/download" class="btn btn-primary"><i
                                                class="fa fa-cloud-download"></i></a>
                                        @if (auth()->user()->hasRole('Admin'))
                                        <a href="/admin/weekly-expense-reports/update" class="btn btn-primary" onclick="return confirm('Tem a certeza que deseja atualizar o relatório com informações mais recentes?')">Atualizar relatório</a>    
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx1 = document.getElementById('chart1');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: ['Ganhos', 'Total de despesas', 'Rentabilidade'],
            datasets: [{
                label: 'Ganhos',
                data: [
                    {{ round(isset($totals->total_operators) ? $totals->total_operators : $totals['total_operators']) }}, 
                    {{ round($final_total) }}, 
                    {{ round($profit) }}],
                borderWidth: 1,
                backgroundColor: [
                    'rgba(0, 0, 255, 0.2)',
                    'rgba(255, 0, 0, 0.2)',
                    'rgba(0, 128, 0, 0.2)',
                ],
                borderColor: [
                    'rgba(0, 0, 255)',
                    'rgba(255, 0, 0)',
                    'rgba(0, 128, 0)',
                ]
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false,
                }
            }
        }
    });
</script>
<script>
    const ctx2 = document.getElementById('chart2');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: [
                'Despesas fixas', 
                'Prevenção de frota', 
                'Park',
                'Consultoria',
                'Pagamentos a motoristas'
            ],
            datasets: [{
                label: 'Ganhos',
                data: [
                    {{ round($total_company_expenses) }}, 
                    {{ round(-$total_company_adjustments) }}, 
                    {{ round($company_park) }},
                    {{ round($total_consultancy) }},
                    {{ round(isset($totals->total_drivers) ? $totals->total_drivers : $totals['total_drivers']) }}
                ],
                borderWidth: 1,
                backgroundColor: [
                    'rgba(0, 0, 255, 0.2)',
                    'rgba(255, 0, 0, 0.2)',
                    'rgba(0, 128, 0, 0.2)',
                    'rgba(255, 255, 0, 0.2)',
                    'rgba(255, 165, 0, 0.2)',
                ],
                borderColor: [
                    'rgba(0, 0, 255)',
                    'rgba(255, 0, 0)',
                    'rgba(0, 128, 0)',
                    'rgba(255, 255, 0)',
                    'rgba(255, 165, 0)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                y: {
                    beginAtZero: true,
                }
            },
            plugins: {
                legend: {
                    display: false,
                }
            }
        }
    });
</script>
@endsection