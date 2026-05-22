@extends('layouts.admin')
@section('styles')
<style>
    table {
        width: 100%;
        font-size: 14px;
    }

    tr {
        line-height: 25px;
    }

    tr:nth-child(even) {
        background-color: #eeeeee;
    }

    tr:nth-child(odd) {
        background-color: #ffffff;
    }

    .report-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 15px;
    }

    .report-toolbar .form-control {
        max-width: 260px;
    }

    .report-toolbar form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        width: 100%;
    }
</style>
@endsection
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os extratos.
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

    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading">
            Faturacao (Historico)
        </div>
        <div class="panel-body" style="border-bottom: 1px solid #f4f4f4;">
            <div class="report-toolbar">
                <form method="GET" action="{{ route('admin.company-reports-history.index') }}">
                    <input type="date" name="from_date" class="form-control" value="{{ $from_date }}">
                    <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('admin.company-reports-history.index') }}" class="btn btn-default">Limpar</a>
                    <a href="{{ route('admin.company-reports-history.export-excel', request()->query()) }}" class="btn btn-success">Export Excel</a>
                    <a href="{{ route('admin.company-reports-history.export-pdf', request()->query()) }}" class="btn btn-danger">Export PDF</a>
                    <input type="text" id="historyReportSearch" class="form-control" placeholder="Filtrar por condutor ou matricula">
                </form>
            </div>
        </div>
        <div class="table-sticky-container">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>Condutor</th>
                        <th>Matricula</th>
                        <th style="text-align: right; background: #eeeeee; display: none;">Bruto Uber</th>
                        <th style="text-align: right; background: #eeeeee; display: none;">Bruto Bolt</th>
                        <th style="text-align: right; background: #eeeeee; display: none;">Bruto operadores</th>
                        <th style="text-align: right;">Liquido Uber</th>
                        <th style="text-align: right;">Liquido Bolt</th>
                        <th style="text-align: right; display: none;">Liquido operadores</th>
                        <th style="text-align: right;">Gorjetas</th>
                        <th style="text-align: right;">Taxa 6%</th>
                        <th style="text-align: right; display: none;">Depois da taxa 6%</th>
                        <th style="text-align: right;">Abastecimento</th>
                        <th style="text-align: right;">Ajustes</th>
                        <th style="text-align: right;">Via verde</th>
                        <th style="text-align: right;">Percentagem</th>
                        <th style="text-align: right;">Cedência</th>
                        <th style="text-align: right">Valor da semana</th>
                        <th style="text-align: right">Ultimo saldo</th>
                        <th style="text-align: right">Novo saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drivers as $driver)
                    @if ($driver->earnings)
                    <tr class="history-driver-row" data-driver="{{ mb_strtolower($driver->name ?? '') }}" data-plate="{{ mb_strtolower($driver->license_plate ?? '') }}">
                        <td>{{ $driver->name }}</td>
                        <td>{{ $driver->license_plate ?? '-' }}</td>
                        <td style="text-align: right; background: #eeeeee; display: none;">{{
                            number_format($driver->earnings['uber']['uber_gross'] ??
                            0, 2) }} <small>€</small></td>
                        <td style="text-align: right; background: #eeeeee; display: none;">{{
                            number_format($driver->earnings['bolt']['bolt_gross'] ??
                            0, 2) }} <small>€</small></td>
                        <td style="text-align: right; background: #eeeeee; display: none;">{{
                            number_format($driver->earnings['total_gross'] ?? 0, 2) }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right">{{ number_format($driver->earnings['uber']['uber_net'] ??
                                0, 2) }}<small> €</small>
                        </td>
                        <td style="text-align: right">{{ number_format($driver->earnings['bolt']['bolt_net'] ??
                            0, 2) }} <small>€</small>
                        </td>
                        <td style="text-align: right; display: none;">{{ number_format($driver->earnings['total_net'] ??
                            0, 2) }} <small>€</small>
                        </td>
                        <td style="text-align: right;">{{ number_format($driver->earnings['tips_total'], 2)
                            }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right; color: red;">{{ number_format($driver->earnings['iva_value'], 2)
                            }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right; display: none;">{{ number_format($driver->earnings['total_after_vat'], 2)
                            }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right;">-{{ number_format($driver->fuel, 2) }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right">{{ number_format($driver->adjustments, 2) }} <small>€</small></td>
                        <td style="text-align: right">{{ number_format($driver->earnings['car_track'], 2) }} <small>€</small></td>
                        <td style="text-align: right; color: red;">{{ number_format($driver->earnings['percent_value'], 2)
                            }}
                            <small>€</small>
                        </td>
                        <td style="text-align: right">-{{ number_format($driver->earnings['car_hire'], 2) }} <small>€</small></td>
                        <td style="text-align: right">{{ number_format($driver->total, 2) }} <small>€</small></td>
                        <td style="text-align: right">{{ number_format($driver->last_balance, 2) }} <small>€</small></td>
                        <td style="text-align: right">{{ number_format($driver->new_balance, 2) }} <small>€</small></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totais</th>
                        <th></th>
                        <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['gross_uber'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['gross_bolt'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right; background: #eeeeee; display: none;">{{ number_format($totals['total_operators'],
                            2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['net_uber'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['net_bolt'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right; display: none;">{{ number_format($totals['total_net_operators'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['tips_total'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right; color: red;">{{ number_format($totals['total_iva_value'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right; display: none;">{{ number_format($totals['total_earnings_after_vat'], 2)
                            }} <small>€</small>
                        </th>
                        <th style="text-align: right;">-{{ number_format($totals['total_fuel_transactions'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_adjustments'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_car_track'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right; color: red;">{{ number_format($totals['total_percent_value'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">-{{ number_format($totals['total_car_hire'], 2) }}
                            <small>€</small>
                        </th>
                        <th style="text-align: right;">{{ number_format($totals['total_drivers'], 2) }} <small>€</small>
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endif
</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('historyReportSearch');
        const rows = Array.from(document.querySelectorAll('.history-driver-row'));

        const applyHistoryFilter = () => {
            const search = (searchInput?.value || '').trim().toLowerCase();

            rows.forEach((row) => {
                const driver = row.dataset.driver || '';
                const plate = row.dataset.plate || '';
                row.style.display = search === '' || driver.includes(search) || plate.includes(search) ? '' : 'none';
            });
        };

        searchInput?.addEventListener('input', applyHistoryFilter);
    });
</script>
@endsection
