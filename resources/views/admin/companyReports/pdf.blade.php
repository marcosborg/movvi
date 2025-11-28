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
    <h2>Extrato de Condutores</h2>
    <h4>Empresa: {{ $company->name ?? 'Empresa' }}</h4>
    <h4>Semana: {{ optional($tvde_week)->start_date }} a {{ optional($tvde_week)->end_date }}</h4>

    <table>
        <colgroup>
            <col style="width: 13%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
        </colgroup>
        <thead>
            <tr>
                <th class="wrap">Condutor</th>
                <th>Líquido Uber</th>
                <th>Líquido Bolt</th>
                <th>Gorjetas</th>
                <th>IVA</th>
                <th>Percentagem</th>
                <th>Combustível</th>
                <th>Ajustes</th>
                <th>Via Verde</th>
                <th>Aluguer</th>
                <th>Total semana</th>
                <th>Último saldo</th>
                <th>Novo saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($drivers as $driver)
                <tr>
                    <td class="wrap">{{ $driver->name }}</td>
                    <td class="nowrap">{{ number_format($driver->earnings['uber']['uber_net'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['bolt']['bolt_net'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['tips_total'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['iva_value'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['percent_value'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->fuel ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->adjustments ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['car_track'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->earnings['car_hire'] ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->total ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->last_balance ?? 0, 2) }} &euro;</td>
                    <td class="nowrap">{{ number_format($driver->new_balance ?? 0, 2) }} &euro;</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Totais</th>
                <th>{{ number_format($totals['net_uber'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['net_bolt'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['tips_total'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_iva_value'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_percent_value'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_fuel_transactions'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_adjustments'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_car_track'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_car_hire'] ?? 0, 2) }} &euro;</th>
                <th>{{ number_format($totals['total_drivers'] ?? 0, 2) }} &euro;</th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
