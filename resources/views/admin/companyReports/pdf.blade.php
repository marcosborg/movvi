<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
<style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 0; }
        h2 { margin: 0 0 8px 0; }
        h4 { margin: 0 0 10px 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #d7d7d7; padding: 5px 6px; text-align: right; vertical-align: top; }
        th:first-child, td:first-child { text-align: left; }
        thead th { background: #f5f5f5; font-weight: 600; }
        tfoot th { background: #fafafa; }
        .nowrap { white-space: nowrap; }
        .wrap { word-wrap: break-word; }
    </style>
</head>
<body>
    @php
        $weekStart = optional($tvde_week)->start_date ? \Carbon\Carbon::parse($tvde_week->start_date) : null;
        $weekNumber = optional($tvde_week)->display_number ?? optional($tvde_week)->number ?? ($weekStart ? $weekStart->isoWeek() : '-');
        $weekYear = optional($tvde_week)->display_year ?? ($weekStart ? $weekStart->isoWeekYear() : '-');
    @endphp
    <h2>Extrato de Condutores</h2>
    <h4>Empresa: {{ $company->name ?? 'Empresa' }}</h4>
    <h4>Semana: {{ optional($tvde_week)->start_date }} a {{ optional($tvde_week)->end_date }} (Semana {{ $weekNumber }}/{{ $weekYear }})</h4>

    <table>
        <colgroup>
            <col style="width: 13%;">
            <col style="width: 10%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
        </colgroup>
        <thead>
            <tr>
                <th class="wrap">Condutor</th>
                <th class="wrap" style="text-align: left;">Viatura(s)</th>
                <th>Líquido Uber</th>
                <th>Líquido Bolt</th>
                <th>KM</th>
                <th>€/km</th>
                <th>Gorjetas</th>
                <th>Taxa 6%</th>
                <th>Combustível</th>
                <th>Ajustes</th>
                <th>Via Verde</th>
                <th>Percentagem</th>
                <th>Aluguer</th>
                <th>Caucao</th>
                <th>Total semana</th>
                <th>Último saldo</th>
                <th>Novo saldo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($drivers as $driver)
                <tr>
                    <td class="wrap">{{ $driver->name }}</td>
                    <td class="wrap" style="text-align: left;">{{ $driver->license_plate ?? '-' }}</td>
                    <td class="nowrap">{{ number_format($driver->earnings['uber']['uber_net'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['bolt']['bolt_net'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->weekly_km ?? 0, 1) }} km</td>
                    <td class="nowrap">{{ number_format($driver->earnings_per_km ?? 0, 3) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['tips_total'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['iva_value'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->fuel ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->adjustments ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['car_track'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['percent_value'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">
                        {{ number_format($driver->earnings['car_hire'] ?? 0, 2) }} &euro;
                        @if(($driver->earnings['abatimento_aluguer'] ?? 0) > 0)
                            <br><small>abatimento: {{ number_format($driver->earnings['abatimento_aluguer'], 2) }} &euro;</small>
                        @endif
                    </td>
                    <td class="nowrap">
                        @php
                            $driverCautionReceived = (float) ($driver->earnings['caucao_recebida'] ?? 0);
                            $driverCautionReturned = (float) ($driver->earnings['caucao_devolvida'] ?? 0);
                        @endphp
                        @if($driverCautionReceived == 0.0 && $driverCautionReturned == 0.0)
                            0.00 &euro;
                        @else
                            @if($driverCautionReceived != 0.0)
                                <div>+{{ number_format($driverCautionReceived, 2) }} &euro;</div>
                            @endif
                            @if($driverCautionReturned != 0.0)
                                <div>-{{ number_format(abs($driverCautionReturned), 2) }} &euro;</div>
                            @endif
                        @endif
                    </td>
                    <td class="nowrap">{{ number_format($driver->total ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->last_balance ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->new_balance ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ $driver->balance_manual_status_label ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Totais</th>
                <th></th>
                <th>{{ number_format($totals['net_uber'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['net_bolt'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_weekly_km'] ?? 0, 1) }} km</th>
                <th>{{ number_format($totals['total_earnings_per_km'] ?? 0, 3) }} &euro;</th>
                <th>{{ number_format($totals['tips_total'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_iva_value'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_fuel_transactions'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_adjustments'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_car_track'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_percent_value'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_car_hire'] ?? 0, 2) }} &euro;</th>
                <th>
                    @if(($totals['total_caution_received'] ?? 0) == 0 && ($totals['total_caution_returned'] ?? 0) == 0)
                        0.00 &euro;
                    @else
                        @if(($totals['total_caution_received'] ?? 0) != 0)
                            <div>+{{ number_format($totals['total_caution_received'], 2) }} &euro;</div>
                        @endif
                        @if(($totals['total_caution_returned'] ?? 0) != 0)
                            <div>-{{ number_format(abs($totals['total_caution_returned']), 2) }} &euro;</div>
                        @endif
                    @endif
                </th>
                <th>{{ number_format($totals['total_drivers'] ?? 0, 2) }} &euro;</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <table>
        <thead>
            <tr>
                <th>Total motoristas</th>
                <th>Alugueres recebidos</th>
                <th>Percentuais recebidos</th>
                <th>Receita operacional</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($totals['total_drivers'] ?? 0, 2) }} &euro;</td>
                <td>{{ number_format($totals['total_car_hire'] ?? 0, 2) }} &euro;</td>
                <td>{{ number_format($totals['total_percent_value'] ?? 0, 2) }} &euro;</td>
                <td>{{ number_format(($totals['total_car_hire'] ?? 0) + ($totals['total_percent_value'] ?? 0) + ($totals['total_adjustments'] ?? 0), 2) }} &euro;</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Resumo de categorias</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ajustes gerais / manuais</td>
                <td>{{ number_format($totals['total_general_adjustments'] ?? 0, 2) }} &euro;</td>
            </tr>
            <tr>
                <td>Abatimento de aluguer</td>
                <td>{{ number_format($totals['total_rent_discounts'] ?? 0, 2) }} &euro;</td>
            </tr>
            <tr>
                <td>Diferença de faturação mínima</td>
                <td>{{ number_format($totals['total_minimum_billing_difference'] ?? 0, 2) }} &euro;</td>
            </tr>
            <tr>
                <td>Caução recebida</td>
                <td>{{ number_format($totals['total_caution_received'] ?? 0, 2) }} &euro;</td>
            </tr>
            <tr>
                <td>Caução devolvida</td>
                <td>{{ number_format($totals['total_caution_returned'] ?? 0, 2) }} &euro;</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Duplo check Uber/Bolt vs conta</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sem diferenÃ§a</td>
                <td>{{ number_format($totals['receipt_check_match_count'] ?? 0, 0) }}</td>
            </tr>
            <tr>
                <td>Divergente</td>
                <td>{{ number_format($totals['receipt_check_mismatch_count'] ?? 0, 0) }}</td>
            </tr>
            <tr>
                <td>Sem recibo validado</td>
                <td>{{ number_format($totals['receipt_check_missing_count'] ?? 0, 0) }}</td>
            </tr>
            <tr>
                <td>Diferenca agregada</td>
                <td>{{ number_format($totals['receipt_check_difference_total'] ?? 0, 2) }} &euro;</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
