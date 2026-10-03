@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} Utilização da viatura
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-usages.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        ID
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Motorista
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Viatura
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->vehicle_item->license_plate ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Data de início
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->start_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Data de fim
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->end_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Exceções
                                    </th>
                                    <td>
                                        {{ App\Models\VehicleUsage::USAGE_EXCEPTIONS_RADIO[$vehicleUsage->usage_exceptions] ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <h4>Histórico de alterações</h4>
                        <p class="text-muted">Regista quem criou, alterou ou eliminou a utilização e os valores modificados.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Ação</th>
                                        <th>Utilizador</th>
                                        <th>Antes</th>
                                        <th>Depois</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vehicleUsage->audits as $audit)
                                        <tr>
                                            <td>{{ $audit->created_at?->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ ['created' => 'Criado', 'updated' => 'Alterado', 'deleted' => 'Eliminado'][$audit->action] ?? $audit->action }}</td>
                                            <td>{{ $audit->user->name ?? 'Sistema' }}</td>
                                            <td><small>{{ $audit->old_values ? json_encode($audit->old_values, JSON_UNESCAPED_UNICODE) : '—' }}</small></td>
                                            <td><small>{{ $audit->new_values ? json_encode($audit->new_values, JSON_UNESCAPED_UNICODE) : '—' }}</small></td>
                                            <td>{{ $audit->ip_address ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Sem alterações registadas. A auditoria começa após esta atualização.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-usages.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
