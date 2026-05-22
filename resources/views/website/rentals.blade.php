@extends('layouts.website')

@section('content')
<section class="pt-5">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Cedência de Viaturas</h1>
            <p class="lead text-muted">Encontre a viatura ideal para o seu negócio TVDE ou para as suas deslocações.</p>
        </div>

        <div class="row g-4">
            @forelse($cars as $car)
                @php
                    $image = $car->photo ? $car->photo->getUrl() : 'https://via.placeholder.com/600x400?text=Viatura';
                @endphp
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="ratio ratio-16x9">
                            <img src="{{ $image }}" class="card-img-top object-fit-contain p-2" alt="{{ $car->title }}">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-2">{{ $car->title }}</h5>
                            @if($car->subtitle)
                                <p class="card-text text-muted mb-2">{{ $car->subtitle }}</p>
                            @endif
                            @if($car->price)
                                <p class="fw-bold text-primary mb-2">Desde {{ $car->price }} € / semana*</p>
                            @endif
                            <div class="d-flex gap-2 mt-auto">
                                <a href="{{ route('website.rentals.show', [$car->id, $car->slug]) }}" class="btn btn-outline-secondary w-100">Ver detalhes</a>
                                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#rentalModal" data-car-id="{{ $car->id }}" data-car-title="{{ $car->title }}">Pedir contacto</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">Sem viaturas para cedência neste momento.</div>
                </div>
            @endforelse
        </div>
        <p class="text-muted small mt-3">* Valores indicativos por semana. Contacte-nos para obter uma proposta personalizada.</p>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="rentalModal" tabindex="-1" aria-labelledby="rentalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rentalModalLabel">Pedir contacto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('website.rentals.form') }}">
                @csrf
                <input type="hidden" name="car_id" id="car_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="modal_name">Nome</label>
                        <input class="form-control" type="text" name="name" id="modal_name" value="{{ old('name') }}" required>
                        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modal_phone">Telefone</label>
                        <input class="form-control" type="text" name="phone" id="modal_phone" value="{{ old('phone') }}" required>
                        @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modal_email">Email</label>
                        <input class="form-control" type="email" name="email" id="modal_email" value="{{ old('email') }}" required>
                        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modal_city">Cidade</label>
                        <input class="form-control" type="text" name="city" id="modal_city" value="{{ old('city') }}">
                        @error('city')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modal_tvde_card">Cartão TVDE</label>
                        <input class="form-control" type="text" name="tvde_card" id="modal_tvde_card" value="{{ old('tvde_card') }}">
                        @error('tvde_card')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="tvde" id="modal_tvde" value="1" {{ old('tvde') ? 'checked' : '' }}>
                        <label class="form-check-label" for="modal_tvde">Tenho TVDE</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="modal_message">Mensagem</label>
                        <textarea class="form-control" name="message" id="modal_message" rows="3">{{ old('message') }}</textarea>
                        @error('message')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="rgpd" id="modal_rgpd" value="1" {{ old('rgpd') ? 'checked' : '' }} required>
                        <label class="form-check-label" for="modal_rgpd">Aceito o tratamento dos meus dados nos termos da política de privacidade.</label>
                        @error('rgpd')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const rentalModal = document.getElementById('rentalModal');
    rentalModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const carId = button.getAttribute('data-car-id');
        const carTitle = button.getAttribute('data-car-title');
        rentalModal.querySelector('#car_id').value = carId;
        rentalModal.querySelector('#rentalModalLabel').textContent = 'Pedir contacto - ' + carTitle;
    });
</script>
@endsection
