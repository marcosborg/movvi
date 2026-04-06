@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">
        Avaliacao semanal #{{ $evaluation->id }}
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>Semana</th><td>Semana {{ $evaluation->tvdeWeek->number ?? '-' }} - {{ $evaluation->tvdeWeek->start_date ?? '-' }} a {{ $evaluation->tvdeWeek->end_date ?? '-' }}</td></tr>
                    <tr><th>Motorista</th><td>{{ $evaluation->driver->name ?? '-' }}</td></tr>
                    <tr><th>Empresa</th><td>{{ $evaluation->driver->company->name ?? '-' }}</td></tr>
                    <tr><th>Viatura</th><td>{{ $evaluation->vehicle->license_plate ?? '-' }} | {{ $evaluation->vehicle->vehicle_brand->name ?? '-' }} {{ $evaluation->vehicle->vehicle_model->name ?? '' }}</td></tr>
                    <tr><th>Submetido por</th><td>{{ $evaluation->submittedBy->name ?? '-' }}</td></tr>
                    <tr><th>Submetido em</th><td>{{ $evaluation->submitted_at ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>KM final</th><td>{{ $evaluation->final_mileage ?: '-' }}</td></tr>
                    <tr><th>Combustivel</th><td>{{ $fuelLevels[$evaluation->fuel_level] ?? '-' }}</td></tr>
                    <tr><th>Pneus dianteiros</th><td>{{ $tireStatuses[$evaluation->front_tire_status] ?? '-' }}</td></tr>
                    <tr><th>Pneus traseiros</th><td>{{ $tireStatuses[$evaluation->rear_tire_status] ?? '-' }}</td></tr>
                    <tr><th>Nivel do oleo</th><td>{{ $oilLevels[$evaluation->oil_level] ?? '-' }}</td></tr>
                    <tr><th>Avisos no painel</th><td>{{ $evaluation->has_panel_warning ? 'Sim' : 'Nao' }}</td></tr>
                    <tr><th>Existe problema</th><td>{{ $evaluation->has_vehicle_issue ? 'Sim' : 'Nao' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">Painel</div>
            <div class="card-body">
                <p><strong>Observacoes:</strong> {{ $evaluation->panel_warning_notes ?: 'Sem observacoes.' }}</p>
                @if($evaluation->getFirstMedia('panel_photo'))
                    <p>
                        <a href="{{ $evaluation->getFirstMedia('panel_photo')->getUrl() }}" target="_blank" class="btn btn-sm btn-primary">
                            Ver foto do painel
                        </a>
                    </p>
                @else
                    <p>Sem foto do painel anexada.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">Descricao do problema</div>
            <div class="card-body">
                {{ $evaluation->issue_notes ?: 'Sem observacoes.' }}
            </div>
        </div>

        <a class="btn btn-default" href="{{ route('admin.weekly-vehicle-evaluations.index') }}">Voltar</a>
    </div>
</div>
@endsection
