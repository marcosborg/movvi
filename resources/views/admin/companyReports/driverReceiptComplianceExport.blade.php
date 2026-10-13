<table>
    <thead><tr><th>Motorista</th><th>Valor transferido</th><th>IVA</th><th>Recibos validados</th><th>Semanas aplicáveis</th><th>Acumulado</th><th>Estado</th></tr></thead>
    <tbody>
        @foreach($report['drivers'] as $row)
            <tr><td>{{ $row['driver']->name }}</td><td>{{ $row['amount_transferred'] }}</td><td>{{ $row['vat'] }}</td><td>{{ $row['verified_weeks'] }}</td><td>{{ $row['applicable_weeks'] }}</td><td>{{ $row['accumulated'] }}</td><td>{{ $row['status'] }}</td></tr>
        @endforeach
    </tbody>
</table>
