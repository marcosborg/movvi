@extends('layouts.admin')
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os seus extratos.
    </div>
    @else
    <div class="btn-group btn-group-justified" role="group">
        @foreach ($tvde_years as $tvde_year)
        <a href="/admin/financial-statements/year/{{ $tvde_year->id }}" class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_months as $tvde_month)
        <a href="/admin/financial-statements/month/{{ $tvde_month->id }}" class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{
            $tvde_month->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_weeks as $tvde_week)
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
        @endforeach
    </div>
    @endif
    <div class="row" style="margin-top: 20px;">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">Viaturas sem motorista atribuído</div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vehicles_without_driver as $vehicle)
                            <tr>
                                <td>{{ $vehicle->license_plate }}</td>
                                <td>{{ $vehicle->vehicle_brand->name ?? '' }}</td>
                                <td>{{ $vehicle->vehicle_model->name ?? '' }}</td>
                            </tr>
                            @endforeach
                            @if ($vehicles_without_driver->isEmpty())
                            <tr>
                                <td colspan="3">Todas as viaturas tiveram motorista.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">Condutores sem conta corrente ou com rendimento 0 €</div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>NIF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drivers_with_issues as $driver)
                            <tr>
                                <td>{{ $driver->name }}</td>
                                <td>{{ $driver->nif ?? '—' }}</td>
                            </tr>
                            @endforeach
                            @if ($drivers_with_issues->isEmpty())
                            <tr>
                                <td colspan="2">Todos os condutores com viatura faturaram corretamente.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
