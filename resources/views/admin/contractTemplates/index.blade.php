@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Modelos de contrato</div>
        <div class="panel-body">
            <p>Cada alteração cria uma nova versão. Os contratos já gerados mantêm a versão e o texto originais.</p>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Modelo</th><th>Versão</th><th>Estado</th><th>Criado em</th><th></th></tr></thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->version }}</td>
                            <td>{{ $template->is_active ? 'Ativo' : 'Histórico' }}</td>
                            <td>{{ $template->created_at }}</td>
                            <td><a class="btn btn-xs btn-info" href="{{ route('admin.contract-templates.edit', $template) }}">{{ $template->is_active ? 'Editar' : 'Ver' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
