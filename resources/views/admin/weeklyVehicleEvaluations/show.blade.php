@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">
        Avaliação semanal #{{ $evaluation->id }}
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>Semana</th><td>Semana {{ $evaluation->tvdeWeek->number ?? '-' }} · {{ $evaluation->tvdeWeek->start_date ?? '-' }} a {{ $evaluation->tvdeWeek->end_date ?? '-' }}</td></tr>
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
                    <tr><th>Combustível</th><td>{{ $fuelLevels[$evaluation->fuel_level] ?? '-' }}</td></tr>
                    <tr><th>Pneus dianteiros</th><td>{{ $tireStatuses[$evaluation->front_tire_status] ?? '-' }}</td></tr>
                    <tr><th>Pneus traseiros</th><td>{{ $tireStatuses[$evaluation->rear_tire_status] ?? '-' }}</td></tr>
                    <tr><th>Nível do óleo</th><td>{{ $oilLevels[$evaluation->oil_level] ?? '-' }}</td></tr>
                    <tr><th>Existe problema</th><td>{{ $evaluation->has_vehicle_issue ? 'Sim' : 'Não' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Descrição do problema</div>
            <div class="card-body">
                {{ $evaluation->issue_notes ?: 'Sem observações.' }}
            </div>
        </div>

        <a class="btn btn-default" href="{{ route('admin.weekly-vehicle-evaluations.index') }}">Voltar</a>
    </div>
</div>
@endsection
