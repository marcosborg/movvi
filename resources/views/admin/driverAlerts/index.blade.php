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
                                    <td colspan="7">Sem alertas.</td>
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
