@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Alertas de recibos em falta
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.driver-alerts.index') }}" class="form-inline" style="margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="driver_id" style="margin-right: 8px;">Motorista</label>
                            <select name="driver_id" id="driver_id" class="form-control select2" style="min-width: 260px;">
                                @foreach($drivers as $id => $name)
                                    <option value="{{ $id }}" {{ (string) $selectedDriverId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-left: 8px;">Filtrar</button>
                        @if($selectedDriverId)
                            <a href="{{ route('admin.driver-alerts.index') }}" class="btn btn-default" style="margin-left: 4px;">Limpar</a>
                        @endif
                    </form>

                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Condutor</th>
                                <th>Empresa</th>
                                <th>Tipo</th>
                                <th>Mensagem</th>
                                <th>Resolvido em</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->id }}</td>
                                    <td>{{ $alert->driver->name ?? '-' }}</td>
                                    <td>{{ $alert->driver->company->name ?? '-' }}</td>
                                    <td>{{ $alert->type }}</td>
                                    <td>{{ $alert->message }}</td>
                                    <td>{{ $alert->resolved_at ?? 'Pendente' }}</td>
                                    <td>{{ $alert->created_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">Sem alertas de recibos em falta.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
