@extends('layouts.admin')
@section('content')
<div class="card">
    <div class="card-header">
        Avaliações semanais da viatura
    </div>

    <div class="card-body">
        <form method="GET" class="row" style="margin-bottom:16px;">
            <div class="col-md-3">
                <label>Semana</label>
                <select name="tvde_week_id" class="form-control">
                    <option value="">Todas</option>
                    @foreach($weeks as $week)
                        <option value="{{ $week->id }}" {{ (string) request('tvde_week_id') === (string) $week->id ? 'selected' : '' }}>
                            Semana {{ $week->number ?? '-' }} · {{ $week->start_date }} a {{ $week->end_date }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Motorista</label>
                <select name="driver_id" class="form-control">
                    <option value="">Todos</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ (string) request('driver_id') === (string) $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Viatura</label>
                <select name="vehicle_item_id" class="form-control">
                    <option value="">Todas</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" {{ (string) request('vehicle_item_id') === (string) $vehicle->id ? 'selected' : '' }}>
                            {{ $vehicle->license_plate }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Problema</label>
                <select name="has_vehicle_issue" class="form-control">
                    <option value="">Todos</option>
                    <option value="1" {{ request('has_vehicle_issue') === '1' ? 'selected' : '' }}>Com problema</option>
                    <option value="0" {{ request('has_vehicle_issue') === '0' ? 'selected' : '' }}>Sem problema</option>
                </select>
            </div>
            <div class="col-md-1" style="display:flex;align-items:flex-end;gap:8px;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Semana</th>
                        <th>Motorista</th>
                        <th>Viatura</th>
                        <th>KM final</th>
                        <th>Combustível</th>
                        <th>Pneus</th>
                        <th>Óleo</th>
                        <th>Problema</th>
                        <th>Submetido em</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $evaluation)
                        <tr>
                            <td>{{ $evaluation->id }}</td>
                            <td>Semana {{ $evaluation->tvdeWeek->number ?? '-' }}</td>
                            <td>{{ $evaluation->driver->name ?? '-' }}</td>
                            <td>{{ $evaluation->vehicle->license_plate ?? '-' }}</td>
                            <td>{{ $evaluation->final_mileage ?: '-' }}</td>
                            <td>{{ \App\Models\WeeklyVehicleEvaluation::FUEL_LEVELS[$evaluation->fuel_level] ?? '-' }}</td>
                            <td>
                                Frente: {{ \App\Models\WeeklyVehicleEvaluation::TIRE_STATUSES[$evaluation->front_tire_status] ?? '-' }}<br>
                                Trás: {{ \App\Models\WeeklyVehicleEvaluation::TIRE_STATUSES[$evaluation->rear_tire_status] ?? '-' }}
                            </td>
                            <td>{{ \App\Models\WeeklyVehicleEvaluation::OIL_LEVELS[$evaluation->oil_level] ?? '-' }}</td>
                            <td>{{ $evaluation->has_vehicle_issue ? 'Sim' : 'Não' }}</td>
                            <td>{{ $evaluation->submitted_at ?? '-' }}</td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.weekly-vehicle-evaluations.show', $evaluation->id) }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">Sem avaliações submetidas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $evaluations->links() }}
    </div>
</div>
@endsection
