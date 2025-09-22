<table>
    <tr>
        <th>Company</th>
        <th>Driver</th>
        <th>Amount</th>
    </tr>
    @foreach ($fleet_adjusments as $fleet_adjusment)
        <tr>
            <td>{{ $fleet_adjusment['company'] }}</td>
            <td>{{ $fleet_adjusment['driver'] }}</td>
            <td>{{ $fleet_adjusment['amount'] }}</td>
        </tr>
    @endforeach
</table>