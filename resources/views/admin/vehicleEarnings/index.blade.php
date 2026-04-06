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
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana {{ $tvde_week->display_number ?? $tvde_week->number }}/{{ $tvde_week->display_year ?? '-' }} · {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m') }}</a>
        @endforeach
    </div>
    @include('admin.partials.weekQuickSelect', ['tvde_weeks' => $tvde_weeks, 'tvde_week_id' => $tvde_week_id])
    <div class="row" style="margin-top: 15px;">
        <div class="col-md-6">
            <div class="alert alert-warning" style="margin-bottom: 10px;">
                Viaturas sem motorista atribuido: <strong>{{ $vehicles_without_driver->count() }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-info" style="margin-bottom: 10px;">
                Condutores com issues: <strong>{{ $drivers_with_issues->count() }}</strong>
            </div>
        </div>
    </div>
    <div class="report-toolbar">
        <input type="text" id="vehicleIssuesSearch" class="form-control" placeholder="Filtrar por matricula ou condutor">
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
                            <tr class="vehicle-issue-row" data-plate="{{ mb_strtolower($vehicle->license_plate) }}" data-brand="{{ mb_strtolower($vehicle->vehicle_brand->name ?? '') }}" data-model="{{ mb_strtolower($vehicle->vehicle_model->name ?? '') }}">
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
                            <tr class="driver-issue-row" data-driver="{{ mb_strtolower($driver->name) }}" data-vat="{{ mb_strtolower($driver->nif ?? '') }}">
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
@section('styles')
<style>
    .report-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 15px;
    }

    .report-toolbar .form-control {
        max-width: 360px;
    }
</style>
@endsection
@section('scripts')
@parent
<script>
    (() => {
        const search = document.getElementById('vehicleIssuesSearch');
        const vehicleRows = Array.from(document.querySelectorAll('.vehicle-issue-row'));
        const driverRows = Array.from(document.querySelectorAll('.driver-issue-row'));

        search?.addEventListener('input', (event) => {
            const query = (event.target.value || '').toLowerCase().trim();

            vehicleRows.forEach((row) => {
                const haystack = [row.dataset.plate, row.dataset.brand, row.dataset.model].join(' ');
                row.style.display = !query || haystack.includes(query) ? '' : 'none';
            });

            driverRows.forEach((row) => {
                const haystack = [row.dataset.driver, row.dataset.vat].join(' ');
                row.style.display = !query || haystack.includes(query) ? '' : 'none';
            });
        });
    })();
</script>
@endsection
