@extends('layouts.website')

@section('content')
<section class="pt-5">
    <div class="container py-5">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Stand</p>
                <h1 class="h3 mb-0">{{ optional($standCar->brand)->name }} {{ optional($standCar->car_model)->name }}</h1>
                @if($standCar->catalogYear)
                    <small class="text-muted">{{ $standCar->catalogYear->name }}</small>
                @endif
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('website.stand') }}">Voltar ao stand</a>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <div class="swiper vehicleSwiper rounded shadow-sm">
                    <div class="swiper-wrapper">
                        @forelse($standCar->images as $media)
                            <div class="swiper-slide">
                                <div class="ratio ratio-4x3">
                                    <img src="{{ $media->getUrl() }}" class="w-100 h-100 object-fit-cover rounded" alt="{{ $standCar->slug }}">
                                </div>
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <div class="ratio ratio-4x3">
                                    <img src="https://via.placeholder.com/800x600?text=Sem+Imagem" class="w-100 h-100 object-fit-cover rounded" alt="Sem imagem">
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h4 mb-0">{{ optional($standCar->brand)->name }} {{ optional($standCar->car_model)->name }}</h2>
                            @if($standCar->price)
                                <span class="badge bg-primary fs-6">{{ $standCar->price }} €</span>
                            @endif
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 g-3">
                            @if($standCar->catalogYear)
                                <div class="col">
                                    <small class="text-muted d-block">Ano</small>
                                    <strong>{{ $standCar->catalogYear->name }}</strong>
                                </div>
                            @endif
                            @if($standCar->transmision)
                                <div class="col">
                                    <small class="text-muted d-block">Caixa</small>
                                    <strong>{{ $standCar->transmision }}</strong>
                                </div>
                            @endif
                            @if($standCar->fuel)
                                <div class="col">
                                    <small class="text-muted d-block">Combustível</small>
                                    <strong>{{ $standCar->fuel->name }}</strong>
                                </div>
                            @endif
                            @if($standCar->kilometers)
                                <div class="col">
                                    <small class="text-muted d-block">Quilómetros</small>
                                    <strong>{{ $standCar->kilometers }}</strong>
                                </div>
                            @endif
                            @if($standCar->power)
                                <div class="col">
                                    <small class="text-muted d-block">Potência</small>
                                    <strong>{{ $standCar->power }} cv</strong>
                                </div>
                            @endif
                            @if($standCar->origin)
                                <div class="col">
                                    <small class="text-muted d-block">Origem</small>
                                    <strong>{{ $standCar->origin->name }}</strong>
                                </div>
                            @endif
                            @if($standCar->distance)
                                <div class="col">
                                    <small class="text-muted d-block">Localização</small>
                                    <strong>{{ $standCar->distance }}</strong>
                                </div>
                            @endif
                            @if($standCar->cylinder_capacity)
                                <div class="col">
                                    <small class="text-muted d-block">Cilindrada</small>
                                    <strong>{{ $standCar->cylinder_capacity }}</strong>
                                </div>
                            @endif
                            @if($standCar->battery_capacity)
                                <div class="col">
                                    <small class="text-muted d-block">Bateria</small>
                                    <strong>{{ $standCar->battery_capacity }} kWh</strong>
                                </div>
                            @endif
                            @if($standCar->status)
                                <div class="col">
                                    <small class="text-muted d-block">Estado</small>
                                    <strong>{{ $standCar->status->name }}</strong>
                                </div>
                            @endif
                        </div>

                        @if($standCar->description ?? null)
                            <hr>
                            <p class="mb-0">{{ $standCar->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="mb-3">Quer saber mais sobre esta viatura?</h4>
                <form method="POST" action="{{ route('website.stand.form', [$standCar->id, $standCar->slug]) }}">
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
                        <div class="col-md-6">
                            <label class="form-label" for="city">Cidade</label>
                            <input class="form-control" type="text" name="city" id="city" value="{{ old('city') }}">
                            @error('city')
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
                        <button class="btn btn-primary" type="submit">Enviar pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    new Swiper('.vehicleSwiper', {
        loop: true,
        spaceBetween: 10,
        slidesPerView: 1,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
</script>
@endsection
