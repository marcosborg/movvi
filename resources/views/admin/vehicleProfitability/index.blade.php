@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <form method="GET" class="form-inline" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label for="vehicle_id">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @if($vehicleId === $vehicle->id) selected @endif>
                                {{ $vehicle->license_plate }} @if($vehicle->vehicle_model) ({{ $vehicle->vehicle_model->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label for="tvde_week_id">Week</label>
                    <select name="tvde_week_id" id="tvde_week_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($weeks as $week)
                            <option value="{{ $week->id }}" @if($weekId === $week->id) selected @endif>
                                {{ $week->start_date }} → {{ $week->end_date }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-left: 10px;">Load</button>
                @php($canExport = $vehicleId && $weekId)
                <a class="btn btn-default"
                   style="margin-left: 10px; {{ $canExport ? '' : 'pointer-events: none; opacity: 0.6;' }}"
                   href="{{ $canExport ? url('admin/vehicle-profitability/pdf') . '?vehicle_id=' . $vehicleId . '&tvde_week_id=' . $weekId : '#' }}">
                    Exportar PDF
                </a>
                @php($canWeek = (bool) $weekId)
                <a class="btn btn-default"
                   style="margin-left: 10px; {{ $canWeek ? '' : 'pointer-events: none; opacity: 0.6;' }}"
                   href="{{ $canWeek ? route('admin.vehicle-profitabilities.week', ['tvde_week_id' => $weekId]) : '#' }}">
                    Todas as viaturas (semana)
                </a>
            </form>
        </div>
    </div>

    @if(!empty($message))
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info" role="alert">{{ $message }}</div>
            </div>
        </div>
    @endif

    @if($result)
        @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="box box-warning">
                    <div class="box-header with-border"><h3 class="box-title">Atribuição semanal de faturação</h3></div>
                    <div class="box-body">
                        <form method="POST" action="{{ route('admin.vehicle-profitabilities.allocation-override') }}" class="form-inline">
                            @csrf
                            <input type="hidden" name="tvde_week_id" value="{{ $weekId }}">
                            <select name="driver_id" class="form-control" required>
                                <option value="">Motorista</option>
                                @foreach($weekDrivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach
                            </select>
                            <select name="vehicle_item_id" class="form-control" required>
                                <option value="">Viatura operacional</option>
                                @foreach($operationalVehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->license_plate }}</option>@endforeach
                            </select>
                            <input name="reason" class="form-control" placeholder="Motivo (opcional)">
                            <button class="btn btn-warning" type="submit">Atribuir semana</button>
                        </form>
                        <small>Utilize apenas para semanas históricas sem detalhe temporal. A atribuição substitui a divisão automática do motorista nessa semana.</small>
                    </div>
                </div>
            </div>
        </div>

        @if($pendingEntries->isNotEmpty())
            <div class="row"><div class="col-lg-12"><div class="box box-danger">
                <div class="box-header with-border"><h3 class="box-title">Faturação pendente de revisão</h3></div>
                <div class="box-body table-responsive"><table class="table table-bordered">
                    <thead><tr><th>Motorista</th><th>Operador</th><th>Data/hora</th><th>Valor líquido</th><th>Motivo</th><th>Viatura</th></tr></thead>
                    <tbody>@foreach($pendingEntries as $entry)<tr>
                        <td>{{ optional($entry->driver)->name ?? $entry->driver_code }}</td>
                        <td>{{ optional($entry->tvde_operator)->name }}</td>
                        <td>{{ optional($entry->occurred_at)->format('d/m/Y H:i') ?? 'Sem data/hora' }}</td>
                        <td>{{ number_format($entry->net, 2, ',', '.') }} €</td>
                        <td>{{ $entry->allocation_reason }}</td>
                        <td><form method="POST" action="{{ route('admin.vehicle-profitabilities.allocate-entry', $entry) }}" class="form-inline">@csrf
                            <select name="vehicle_item_id" class="form-control" required><option value="">Selecionar</option>@foreach($operationalVehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->license_plate }}</option>@endforeach</select>
                            <button class="btn btn-primary" type="submit">Atribuir</button>
                        </form></td>
                    </tr>@endforeach</tbody>
                </table></div>
            </div></div></div>
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Identificação</h3>
                    </div>
                    <div class="box-body">
                        <p><strong>Viatura:</strong> {{ $result['vehicle']['license_plate'] }}</p>
                        <p><strong>Modelo:</strong> {{ $result['vehicle']['model'] }}</p>
                        <p><strong>Semana:</strong> {{ $result['week']['start_date'] }} → {{ $result['week']['end_date'] }}</p>
                        @if(!empty($result['meta']['missing_current_accounts']))
                            <div class="alert alert-warning" role="alert" style="margin-top: 10px;">
                                Existem motoristas que conduziram esta viatura nesta semana mas ainda não têm dados validados em <code>/admin/company-reports</code>.
                            </div>
                        @endif
                        <div class="alert alert-info" role="alert" style="margin-top: 10px; margin-bottom: 0;">
                            <strong>Leitura operacional:</strong>
                            {{ $result['meta']['exclusions']['receipts'] ?? '' }}
                            {{ $result['meta']['exclusions']['reimbursements'] ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="box box-info">
                    <div class="box-body">
                        <h4>Cedência (€)</h4>
                        <p>{{ number_format($result['revenues']['rental_total'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-warning">
                    <div class="box-body">
                        <h4>Percentagem (€)</h4>
                        <p>{{ number_format($result['revenues']['commission_total'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-danger">
                    <div class="box-body">
                        <h4>Ajustes operacionais (€)</h4>
                        <p>{{ number_format($result['revenues']['adjustments_total'] ?? 0, 2, ',', '.') }}</p>
                        <small>
                            Gerais: {{ number_format($result['revenues']['general_adjustments_total'] ?? 0, 2, ',', '.') }}
                            <br>
                            Fat. mínima: {{ number_format($result['revenues']['minimum_billing_difference_total'] ?? 0, 2, ',', '.') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-success">
                    <div class="box-body">
                        <h4>Total (€)</h4>
                        <p>{{ number_format($result['revenues']['total_revenue'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Motoristas</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Motorista</th>
                                    <th>Tipo</th>
                                    <th style="text-align:right;">Cedência</th>
                                    <th style="text-align:right;">Percentagem</th>
                                    <th style="text-align:right;">Ajustes</th>
                                    <th style="text-align:right;">Fat. mínima</th>
                                    <th style="text-align:right;">Uso (segundos)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($result['meta']['drivers'] ?? []) as $d)
                                    <tr>
                                        <td>{{ $d['name'] ?? ('#' . $d['id']) }}</td>
                                        <td>{{ $d['type'] }}</td>
                                        <td style="text-align:right;">{{ number_format($d['rental'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($d['commission'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($d['adjustments'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($d['minimum_billing_difference'] ?? 0, 2, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format($d['usage_seconds'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
