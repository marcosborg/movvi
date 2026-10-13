@extends('layouts.admin')
@section('content')
<div class="content">
    <form method="GET" class="form-inline" style="margin-bottom:20px;">
        <select name="vehicle_id" class="form-control select2">
            <option value="">Viatura</option>
            @foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" {{ $vehicleId === $vehicle->id ? 'selected' : '' }}>{{ $vehicle->license_plate }}</option>@endforeach
        </select>
        <select name="tvde_week_id" class="form-control select2">
            <option value="">Semana</option>
            @foreach($weeks as $week)<option value="{{ $week->id }}" {{ $weekId === $week->id ? 'selected' : '' }}>{{ $week->start_date }} a {{ $week->end_date }}</option>@endforeach
        </select>
        <button class="btn btn-primary">Consultar</button>
        <a class="btn btn-default" href="{{ route('admin.vehicle-profitabilities.week') }}">Relatório da frota</a>
        @if($result)<a class="btn btn-default" target="_blank" href="{{ route('admin.vehicle-profitability.pdf', ['vehicle_id' => $vehicleId, 'tvde_week_id' => $weekId]) }}">PDF</a>@endif
    </form>
    @if($message)<div class="alert alert-info">{{ $message }}</div>@endif
    @if($result)
    <div class="panel panel-default"><div class="panel-heading">{{ $result['vehicle']['license_plate'] }} — {{ $result['week']['start_date'] }} a {{ $result['week']['end_date'] }}</div><div class="panel-body table-responsive">
        <table class="table table-bordered"><thead><tr><th>Cedência</th><th>Percentagem</th><th>Ajustes</th><th>KM excedidos</th><th>Energia empresa</th><th>Total</th></tr></thead><tbody><tr>
            <td>{{ number_format($result['revenues']['rental_total'] ?? 0, 2, ',', '.') }} €</td>
            <td>{{ number_format($result['revenues']['commission_total'] ?? 0, 2, ',', '.') }} €</td>
            <td>{{ number_format($result['revenues']['adjustments_total'] ?? 0, 2, ',', '.') }} €</td>
            <td>{{ number_format($result['revenues']['excess_kilometers_total'] ?? 0, 2, ',', '.') }} €</td>
            <td>-{{ number_format($result['revenues']['company_paid_charging_total'] ?? 0, 2, ',', '.') }} €</td>
            <td><strong>{{ number_format($result['revenues']['total_revenue'] ?? 0, 2, ',', '.') }} €</strong></td>
        </tr></tbody></table>
        <p class="help-block">IVA é o imposto calculado no relatório do motorista. Via Verde (CarTrack) corresponde às portagens importadas.</p>
    </div></div>
    @endif
</div>
@endsection
