<table>
    <thead>
        <tr>
            <th>Condutor</th>
            <th>Matricula</th>
            <th>Liquido Uber</th>
            <th>Liquido Bolt</th>
            <th>Gorjetas</th>
            <th>Taxa 6%</th>
            <th>Abastecimento</th>
            <th>Ajustes</th>
            <th>Via verde</th>
            <th>Percentagem</th>
            <th>Cedência</th>
            <th>Valor da semana</th>
            <th>Ultimo saldo</th>
            <th>Novo saldo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($drivers as $driver)
        @if ($driver->earnings)
        <tr>
            <td>{{ $driver->name }}</td>
            <td>{{ $driver->license_plate ?? '-' }}</td>
            <td>{{ number_format($driver->earnings['uber']['uber_net'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['bolt']['bolt_net'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['tips_total'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['iva_value'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->fuel ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->adjustments ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['car_track'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['percent_value'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->earnings['car_hire'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->total ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->last_balance ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($driver->new_balance ?? 0, 2, '.', '') }}</td>
        </tr>
        @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>Totais</th>
            <th></th>
            <th>{{ number_format($totals['net_uber'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['net_bolt'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['tips_total'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_iva_value'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_fuel_transactions'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_adjustments'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_car_track'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_percent_value'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_car_hire'] ?? 0, 2, '.', '') }}</th>
            <th>{{ number_format($totals['total_drivers'] ?? 0, 2, '.', '') }}</th>
            <th></th>
            <th></th>
        </tr>
    </tfoot>
</table>
