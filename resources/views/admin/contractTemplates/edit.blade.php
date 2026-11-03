@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-9">
            <div class="panel panel-default">
                <div class="panel-heading">Modelo de contrato — versão {{ $contractTemplate->version }}</div>
                <div class="panel-body">
                    @if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
                    <form method="POST" action="{{ route('admin.contract-templates.update', $contractTemplate) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Nome</label>
                            <input class="form-control" name="name" id="name" value="{{ old('name', $contractTemplate->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Texto do contrato em HTML</label>
                            <textarea class="form-control" style="min-height:650px;font-family:monospace" name="content" id="content" required>{{ old('content', $contractTemplate->content) }}</textarea>
                            <p class="help-block">Cole as tags exatamente como aparecem à direita. Ao guardar é criada uma nova versão.</p>
                        </div>
                        <button class="btn btn-danger" type="submit">Guardar como nova versão</button>
                        <a class="btn btn-default" href="{{ route('admin.contract-templates.index') }}">Voltar</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">Tags disponíveis</div>
                <div class="panel-body">
                    @foreach($tags as $tag => $label)
                        <p><code>{{ '{' . '{' . $tag . '}' . '}' }}</code><br><small>{{ $label }}</small></p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
