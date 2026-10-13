<table class="table table-bordered table-striped">
    <thead><tr><th>Motorista</th><th class="text-right">Transferido</th><th class="text-right">IVA</th><th>Recibos</th><th class="text-right">Acumulado</th><th>Estado</th></tr></thead>
    <tbody>
        @foreach($report['drivers'] as $row)
            <tr><td>{{ $row['driver']->name }}</td><td class="text-right">{{ number_format($row['amount_transferred'], 2, ',', '.') }} €</td><td class="text-right">{{ number_format($row['vat'], 2, ',', '.') }} €</td><td>{{ $row['verified_weeks'] }}/{{ $row['applicable_weeks'] }} validados</td><td class="text-right">{{ number_format($row['accumulated'], 2, ',', '.') }} €</td><td><span class="label {{ $row['status'] === 'Em dia' ? 'label-success' : 'label-warning' }}">{{ $row['status'] }}</span></td></tr>
        @endforeach
        @if($report['drivers']->isEmpty())<tr><td colspan="6">Sem registos no período selecionado.</td></tr>@endif
    </tbody>
    <tfoot><tr><th>Totais</th><th class="text-right">{{ number_format($report['totals']['amount_transferred'], 2, ',', '.') }} €</th><th class="text-right">{{ number_format($report['totals']['vat'], 2, ',', '.') }} €</th><th colspan="3">{{ $report['totals']['in_compliance'] }} em dia · {{ $report['totals']['pending'] }} pendentes</th></tr></tfoot>
</table>
