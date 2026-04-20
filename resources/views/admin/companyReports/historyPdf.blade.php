<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Historico de faturacao</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cccccc;
            padding: 6px;
        }

        th {
            background: #f3f3f3;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h2>Historico de faturacao</h2>
    <table>
        <thead>
            <tr>
                <th>Condutor</th>
                <th>Matricula</th>
                <th class="text-right">Liquido Uber</th>
                <th class="text-right">Liquido Bolt</th>
                <th class="text-right">Gorjetas</th>
                <th class="text-right">Taxa 6%</th>
                <th class="text-right">Abastecimento</th>
                <th class="text-right">Ajustes</th>
                <th class="text-right">Via verde</th>
                <th class="text-right">Percentagem</th>
                <th class="text-right">Aluguer</th>
                <th class="text-right">Valor da semana</th>
                <th class="text-right">Ultimo saldo</th>
                <th class="text-right">Novo saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($drivers as $driver)
            @if ($driver->earnings)
            <tr>
                <td>{{ $driver->name }}</td>
                <td>{{ $driver->license_plate ?? '-' }}</td>
                <td class="text-right">{{ number_format($driver->earnings['uber']['uber_net'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['bolt']['bolt_net'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['tips_total'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['iva_value'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->fuel ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->adjustments ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['car_track'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['percent_value'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->earnings['car_hire'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->total ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->last_balance ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($driver->new_balance ?? 0, 2) }}</td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Totais</th>
                <th></th>
                <th class="text-right">{{ number_format($totals['net_uber'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['net_bolt'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['tips_total'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_iva_value'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_fuel_transactions'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_adjustments'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_car_track'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_percent_value'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_car_hire'] ?? 0, 2) }}</th>
                <th class="text-right">{{ number_format($totals['total_drivers'] ?? 0, 2) }}</th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
