@extends('layouts.website')

@section('content')
<section class="pt-5">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Aluguer</p>
                <h1 class="h3 mb-0">{{ $car->title }}</h1>
                @if($car->subtitle)
                    <small class="text-muted">{{ $car->subtitle }}</small>
                @endif
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('website.rentals') }}">Voltar</a>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <div class="ratio ratio-16x9 mb-3">
                    <img src="{{ $car->photo ? $car->photo->getUrl() : 'https://via.placeholder.com/800x600?text=Viatura' }}" class="w-100 h-100 object-fit-contain p-2 rounded shadow-sm" alt="{{ $car->title }}">
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="mb-3">Quer saber mais sobre esta viatura?</h4>
                        <form method="POST" action="{{ route('website.rentals.form') }}">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $car->id }}">
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
                                <div class="col-md-6">
                                    <label class="form-label" for="city">Cidade</label>
                                    <input class="form-control" type="text" name="city" id="city" value="{{ old('city') }}">
                                    @error('city')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tvde_card">Cartão TVDE</label>
                                    <input class="form-control" type="text" name="tvde_card" id="tvde_card" value="{{ old('tvde_card') }}">
                                    @error('tvde_card')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="tvde" id="tvde" value="1" {{ old('tvde') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="tvde">Tenho TVDE</label>
                                    </div>
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
                                <button class="btn btn-primary" type="submit">Enviar pedido</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        @if($car->price)
                            <div class="d-flex justify-content-end mb-3">
                                <span class="badge bg-primary fs-6">Desde {{ $car->price }} € / semana*</span>
                            </div>
                        @endif

                        @if($car->specifications)
                            <hr>
                            <div class="fs-6 lh-lg">
                                {!! $car->specifications !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
