@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Abrir pedido de suporte</h3></div>
            <form method="POST" action="{{ route('admin.support-tickets.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="box-body">
                    <div class="form-group {{ $errors->has('subject') ? 'has-error' : '' }}">
                        <label for="subject">Assunto</label>
                        <input id="subject" name="subject" class="form-control" maxlength="160" required value="{{ old('subject') }}" placeholder="Resuma o problema numa frase">
                        @if($errors->has('subject'))<span class="help-block">{{ $errors->first('subject') }}</span>@endif
                    </div>
                    <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                        <label for="message">Descrição</label>
                        <textarea id="message" name="message" class="form-control" rows="7" maxlength="10000" required placeholder="Explique o que aconteceu e, se possível, indique a página e a semana em causa.">{{ old('message') }}</textarea>
                        @if($errors->has('message'))<span class="help-block">{{ $errors->first('message') }}</span>@endif
                    </div>
                    <div class="form-group {{ $errors->has('images.*') ? 'has-error' : '' }}">
                        <label for="images">Imagens <small class="text-muted">(opcional, até 5 imagens)</small></label>
                        <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                        <p class="help-block">JPG, PNG ou WebP; máximo 8 MB por imagem.</p>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-default">Cancelar</a>
                    <button class="btn btn-primary pull-right"><i class="fa fa-paper-plane"></i> Enviar ticket</button>
                </div>
            </form>
        </div>
    </div></div>
</div>
@endsection
