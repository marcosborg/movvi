@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Novo link de menu
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('admin.menu-links.store') }}">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">Nome</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name') }}" required>
                            @error('name')<span class="help-block">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group {{ $errors->has('url') ? 'has-error' : '' }}">
                            <label class="required" for="url">URL</label>
                            <input class="form-control" type="text" name="url" id="url" value="{{ old('url') }}" required>
                            @error('url')<span class="help-block">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group {{ $errors->has('target') ? 'has-error' : '' }}">
                            <label for="target">Target (opcional)</label>
                            <input class="form-control" type="text" name="target" id="target" value="{{ old('target') }}" placeholder="_blank">
                            @error('target')<span class="help-block">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <button class="btn btn-success" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Ordenar / editar links (arraste para reordenar)
                </div>
                <div class="panel-body">
                    <ul id="menu-sortable" class="list-group">
                        @foreach($links as $link)
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $link->id }}">
                                <div>
                                    <strong>{{ $link->name }}</strong>
                                    <div class="text-muted small">{{ $link->url }}</div>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-xs btn-info" data-toggle="collapse" data-target="#edit-{{ $link->id }}">Editar</button>
                                    <form action="{{ route('admin.menu-links.destroy', $link->id) }}" method="POST" onsubmit="return confirm('Eliminar este link?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-xs btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </li>
                            <li class="list-group-item collapse" id="edit-{{ $link->id }}">
                                <form method="POST" action="{{ route('admin.menu-links.update', $link->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label>Nome</label>
                                        <input class="form-control" type="text" name="name" value="{{ $link->name }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>URL</label>
                                        <input class="form-control" type="text" name="url" value="{{ $link->url }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Target</label>
                                        <input class="form-control" type="text" name="target" value="{{ $link->target }}">
                                    </div>
                                    <button class="btn btn-primary btn-xs" type="submit">Guardar alterações</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
    $(function () {
        $('#menu-sortable').sortable({
            update: function () {
                const order = $(this).children('li[data-id]').map(function() {
                    return $(this).data('id');
                }).get();
                $.ajax({
                    method: 'POST',
                    url: '{{ route('admin.menu-links.order') }}',
                    data: { order: order, _token: '{{ csrf_token() }}' }
                });
            }
        });
    });
</script>
@endsection
