@extends('layouts.website')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Página</p>
                <h1 class="h3 mb-0">{{ $page->title }}</h1>
                @if($page->created_at)
                    <small class="text-muted">{{ $page->created_at->format('d/m/Y') }}</small>
                @endif
            </div>
            <a class="btn btn-outline-secondary" href="{{ url('/') }}">Voltar</a>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @php
            $image = $page->image ? $page->image->getUrl() : null;
        @endphp

        <div class="clearfix mb-5">
            @if($image)
                <img src="{{ $image }}" class="img-fluid rounded shadow-sm float-md-end ms-md-4 mb-3" alt="{{ $page->title }}" style="max-width: 420px;">
            @endif

            @if($page->description)
                <p class="lead">{{ $page->description }}</p>
            @endif

            <div class="fs-6 lh-lg">
                {!! $page->text !!}
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-3">Contacte-nos</h4>
                <form method="POST" action="{{ route('website.cms.form', [$page->id, $page->slug]) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Nome</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Telefone</label>
                            <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" name="email" id="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message">Mensagem</label>
                            <textarea class="form-control" name="message" id="message" rows="4">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rgpd" id="rgpd" value="1" {{ old('rgpd') ? 'checked' : '' }} required>
                                <label class="form-check-label" for="rgpd">Aceito o tratamento dos meus dados nos termos da política de privacidade.</label>
                            </div>
                            @error('rgpd')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
