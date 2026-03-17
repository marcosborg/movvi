@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row" style="margin-bottom:12px;">
        <div class="col-md-3"><div class="small-box bg-blue"><div class="inner"><h3>{{ $summary['total'] }}</h3><p>Total inspeções</p></div><div class="icon"><i class="fa fa-clipboard"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $summary['in_progress'] }}</h3><p>Em curso</p></div><div class="icon"><i class="fa fa-tasks"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $summary['closed'] }}</h3><p>Fechadas</p></div><div class="icon"><i class="fa fa-check"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $summary['today'] }}</h3><p>Criadas hoje</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Inspeções de viaturas</div>
                <div class="panel-body">
                    <div style="margin-bottom: 16px;">
                        @if(auth()->user() && (auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Gestor')))
                            <a class="btn btn-success" href="{{ route('admin.inspections.create') }}">Nova inspeção</a>
                        @endif
                    </div>

                    <form method="GET" class="row" style="margin-bottom: 16px;">
                        <div class="col-md-2">
                            <label>Tipo</label>
                            <select name="type" class="form-control">
                                <option value="">Todos</option>
                                @foreach(config('inspections.types', []) as $typeKey)
                                    <option value="{{ $typeKey }}" {{ request('type') === $typeKey ? 'selected' : '' }}>
                                        {{ config('inspections.type_labels.' . $typeKey, $typeKey) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Estado</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                @foreach(config('inspections.status_labels', []) as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Viatura</label>
                            <select name="vehicle_id" class="form-control select2">
                                <option value="">Todas</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ (string) request('vehicle_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->license_plate }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Motorista</label>
                            <select name="driver_id" class="form-control select2">
                                <option value="">Todos</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}" {{ (string) request('driver_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Criado por</label>
                            <select name="created_by_user_id" class="form-control select2">
                                <option value="">Todos</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ (string) request('created_by_user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Matrícula</label>
                            <input type="text" name="plate" class="form-control" value="{{ request('plate') }}" placeholder="AA-00-AA">
                        </div>
                        <div class="col-md-2" style="margin-top:8px;">
                            <label>De</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2" style="margin-top:8px;">
                            <label>Até</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-8" style="margin-top:30px;">
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                            <a class="btn btn-default" href="{{ route('admin.inspections.index') }}">Limpar</a>
                        </div>
                    </form>

                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Viatura</th>
                                <th>Motorista</th>
                                <th>Estado</th>
                                <th>Etapa</th>
                                <th>Criado por</th>
                                <th>Data</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inspections as $inspection)
                                <tr>
                                    <td>{{ $inspection->id }}</td>
                                    <td>{{ config('inspections.type_labels.' . $inspection->type, $inspection->type) }}</td>
                                    <td>{{ $inspection->vehicle->license_plate ?? '-' }}</td>
                                    <td>{{ $inspection->driver->name ?? '-' }}</td>
                                    <td><span class="label label-info">{{ config('inspections.status_labels.' . $inspection->status, $inspection->status) }}</span></td>
                                    <td>{{ $inspection->current_step }}</td>
                                    <td>{{ $inspection->createdBy->name ?? '-' }}</td>
                                    <td>{{ $inspection->created_at }}</td>
                                    <td>
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.inspections.show', $inspection->id) }}">Ver</a>
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.inspections.edit', $inspection->id) }}">Wizard</a>
                                        @if((auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Gestor')) && !$inspection->locked_at)
                                            <form action="{{ route('admin.inspections.destroy', $inspection->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Eliminar esta inspeção aberta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-xs btn-danger" type="submit">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center">Sem resultados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $inspections->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
