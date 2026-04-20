@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Upload de documento
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.driver-documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if($canManageAll)
                            <div class="form-group">
                                <label for="driver_id">Condutor</label>
                                <select name="driver_id" id="driver_id" class="form-control select2" required>
                                    <option value="">Selecione</option>
                                    @foreach($drivers as $listDriver)
                                        <option value="{{ $listDriver->id }}" {{ (string) $selectedDriverId === (string) $listDriver->id ? 'selected' : '' }}>
                                            {{ $listDriver->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="name">Nome</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="type">Tipo</label>
                            <input type="text" name="type" id="type" class="form-control" value="{{ old('type') }}">
                        </div>
                        <div class="form-group">
                            <label for="file">Ficheiro</label>
                            <input type="file" name="file" id="file" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
            @if($canManageAll)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Filtrar
                    </div>
                    <div class="panel-body">
                        <form method="GET" action="{{ route('admin.driver-documents.index') }}">
                            <div class="form-group">
                                <label for="filter_driver_id">Condutor</label>
                                <select name="driver_id" id="filter_driver_id" class="form-control select2">
                                    <option value="">Todos</option>
                                    @foreach($drivers as $listDriver)
                                        <option value="{{ $listDriver->id }}" {{ (string) request('driver_id') === (string) $listDriver->id ? 'selected' : '' }}>
                                            {{ $listDriver->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-default">Filtrar</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Documentos do motorista
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    @if($canManageAll)
                                        <th>Condutor</th>
                                    @endif
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Criado em</th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr>
                                        <td>{{ $document->id }}</td>
                                        @if($canManageAll)
                                            <td>{{ $document->driver->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ $document->name }}</td>
                                        <td>{{ $document->type ?: '-' }}</td>
                                        <td>{{ $document->created_at }}</td>
                                        <td>
                                            <a href="{{ route('admin.driver-documents.download', $document) }}" class="btn btn-xs btn-primary">
                                                Download
                                            </a>
                                            <form action="{{ route('admin.driver-documents.destroy', $document) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ trans('global.areYouSure') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageAll ? 6 : 5 }}">Sem documentos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
