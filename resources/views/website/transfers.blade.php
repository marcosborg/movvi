@extends('layouts.website')

@section('content')
<section class="pt-5">
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold">Transfers e Tours</h1>
            <p class="lead text-muted">Desfrute de viagens personalizadas, cómodas e seguras, adaptadas ao seu estilo.</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success text-center">{{ session('status') }}</div>
        @endif

        <div class="row g-4">
            @forelse($tours as $tour)
                @php
                    $image = $tour->photo->first() ? $tour->photo->first()->getUrl('preview') : 'https://via.placeholder.com/600x400?text=Tour';
                @endphp
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="ratio ratio-4x3">
                            <img src="{{ $image }}" class="card-img-top object-fit-contain p-2 rounded-top" alt="{{ $tour->name }}">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1">{{ $tour->name }}</h5>
                            @if(!$tour->under_consultation && $tour->price)
                                <p class="fw-bold text-primary mb-2">Desde {{ $tour->price }} €</p>
                            @else
                                <p class="fw-bold text-primary mb-2">Sob consulta</p>
                            @endif
                            <div class="d-flex gap-2 mt-auto">
                                <a href="{{ route('website.transfers.show', [$tour->id, $tour->slug]) }}" class="btn btn-outline-secondary w-100">Ver detalhes</a>
                                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#transferModal" data-tour-id="{{ $tour->id }}" data-tour-name="{{ $tour->name }}">Solicitar Orçamento</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">Sem tours disponíveis no momento.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">Solicitar Orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('website.transfers.form') }}">
                @csrf
                <input type="hidden" name="transfer_tour_id" id="transfer_tour_id" value="">
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
    const transferModal = document.getElementById('transferModal');
    transferModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const tourId = button.getAttribute('data-tour-id');
        const tourName = button.getAttribute('data-tour-name');
        transferModal.querySelector('#transfer_tour_id').value = tourId;
        transferModal.querySelector('#transferModalLabel').textContent = 'Solicitar Orçamento - ' + tourName;
    });
</script>
@endsection
