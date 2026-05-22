@php
    $weekStart = optional($tvde_week)->start_date ? \Carbon\Carbon::parse($tvde_week->start_date) : null;
    $weekNumber = optional($tvde_week)->display_number ?? optional($tvde_week)->number ?? ($weekStart ? $weekStart->isoWeek() : '-');
    $weekYear = optional($tvde_week)->display_year ?? ($weekStart ? $weekStart->isoWeekYear() : '-');
@endphp

<table>
    <tr>
        <th colspan="19">Extrato de Condutores</th>
    </tr>
    <tr>
        <th>Empresa</th>
        <td colspan="18">{{ $company->name ?? 'Empresa' }}</td>
    </tr>
    <tr>
        <th>Semana</th>
        <td colspan="18">{{ optional($tvde_week)->start_date }} a {{ optional($tvde_week)->end_date }} (Semana {{ $weekNumber }}/{{ $weekYear }})</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>Condutor</th>
            <th>Viatura(s)</th>
            <th>Liquido Uber</th>
            <th>Liquido Bolt</th>
            <th>KM</th>
            <th>EUR/km</th>
            <th>Gorjetas</th>
            <th>Taxa 6%</th>
            <th>Combustivel</th>
            <th>Ajustes</th>
            <th>Via Verde</th>
            <th>Percentagem</th>
            <th>Cedência</th>
            <th>Abatimento cedência</th>
            <th>Caucao</th>
            <th>Total semana</th>
            <th>Ultimo saldo</th>
            <th>Novo saldo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($drivers as $driver)
            <tr>
                <td>{{ $driver->name }}</td>
                <td>{{ $driver->license_plate ?? '-' }}</td>
                <td>{{ $driver->earnings['uber']['uber_net'] ?? 0 }}</td>
                <td>{{ $driver->earnings['bolt']['bolt_net'] ?? 0 }}</td>
                <td>{{ $driver->weekly_km ?? 0 }}</td>
                <td>{{ $driver->earnings_per_km ?? 0 }}</td>
                <td>{{ $driver->earnings['tips_total'] ?? 0 }}</td>
                <td>{{ $driver->earnings['iva_value'] ?? 0 }}</td>
                <td>{{ $driver->fuel ?? 0 }}</td>
                <td>{{ $driver->adjustments ?? 0 }}</td>
                <td>{{ $driver->earnings['car_track'] ?? 0 }}</td>
                <td>{{ $driver->earnings['percent_value'] ?? 0 }}</td>
                <td>{{ $driver->earnings['car_hire'] ?? 0 }}</td>
                <td>{{ $driver->earnings['abatimento_aluguer'] ?? 0 }}</td>
                <td>{{ ($driver->earnings['caucao_recebida'] ?? 0) + ($driver->earnings['caucao_devolvida'] ?? 0) }}</td>
                <td>{{ $driver->total ?? 0 }}</td>
                <td>{{ $driver->last_balance ?? 0 }}</td>
                <td>{{ $driver->new_balance ?? 0 }}</td>
                <td>{{ $driver->balance_manual_status_label ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th>Totais</th>
            <th></th>
            <th>{{ $totals['net_uber'] ?? 0 }}</th>
            <th>{{ $totals['net_bolt'] ?? 0 }}</th>
            <th>{{ $totals['total_weekly_km'] ?? 0 }}</th>
            <th>{{ $totals['total_earnings_per_km'] ?? 0 }}</th>
            <th>{{ $totals['tips_total'] ?? 0 }}</th>
            <th>{{ $totals['total_iva_value'] ?? 0 }}</th>
            <th>{{ $totals['total_fuel_transactions'] ?? 0 }}</th>
            <th>{{ $totals['total_adjustments'] ?? 0 }}</th>
            <th>{{ $totals['total_car_track'] ?? 0 }}</th>
            <th>{{ $totals['total_percent_value'] ?? 0 }}</th>
            <th>{{ $totals['total_car_hire'] ?? 0 }}</th>
            <th>{{ $totals['total_rent_discounts'] ?? 0 }}</th>
            <th>{{ ($totals['total_caution_received'] ?? 0) + ($totals['total_caution_returned'] ?? 0) }}</th>
            <th>{{ $totals['total_drivers'] ?? 0 }}</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </tfoot>
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
            <td>{{ $totals['total_general_adjustments'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Abatimento de cedência</td>
            <td>{{ $totals['total_rent_discounts'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Diferenca de faturacao minima</td>
            <td>{{ $totals['total_minimum_billing_difference'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Caucao recebida</td>
            <td>{{ $totals['total_caution_received'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Caucao devolvida</td>
            <td>{{ $totals['total_caution_returned'] ?? 0 }}</td>
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
            <td>Sem diferenca</td>
            <td>{{ $totals['receipt_check_match_count'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Divergente</td>
            <td>{{ $totals['receipt_check_mismatch_count'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Sem recibo validado</td>
            <td>{{ $totals['receipt_check_missing_count'] ?? 0 }}</td>
        </tr>
        <tr>
            <td>Diferenca agregada</td>
            <td>{{ $totals['receipt_check_difference_total'] ?? 0 }}</td>
        </tr>
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th>Total motoristas</th>
            <th>Cedências recebidos</th>
            <th>Percentuais recebidos</th>
            <th>Receita operacional</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $totals['total_drivers'] ?? 0 }}</td>
            <td>{{ $totals['total_car_hire'] ?? 0 }}</td>
            <td>{{ $totals['total_percent_value'] ?? 0 }}</td>
            <td>{{ ($totals['total_car_hire'] ?? 0) + ($totals['total_percent_value'] ?? 0) + ($totals['total_adjustments'] ?? 0) }}</td>
        </tr>
    </tbody>
</table>
