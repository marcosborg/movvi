@extends('layouts.website')

@section('content')
<section class="pt-5">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Stand de Viaturas</h1>
            <p class="lead text-muted">Escolha a viatura ideal para si. Filtre por marca, modelo, ano ou caixa.</p>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filterBrand" class="form-label">Marca</label>
                        <select id="filterBrand" class="form-select">
                            <option value="">Todas</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterModel" class="form-label">Modelo</label>
                        <select id="filterModel" class="form-select">
                            <option value="">Todos</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filterYear" class="form-label">Ano</label>
                        <select id="filterYear" class="form-select">
                            <option value="">Todos</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filterTransmission" class="form-label">Caixa</label>
                        <select id="filterTransmission" class="form-select">
                            <option value="">Todas</option>
                            @foreach($transmissions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="filterBtn" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4" id="vehicleList">
            @forelse($standCars as $car)
                @php
                    $image = $car->images->first() ? $car->images->first()->getUrl() : 'https://via.placeholder.com/600x400?text=Viatura';
                    $yearLabel = optional($car->catalogYear)->name;
                @endphp
                <div class="col-md-4 vehicle-card"
                     data-brand="{{ optional($car->brand)->name }}"
                     data-model="{{ optional($car->car_model)->name }}"
                     data-year="{{ $yearLabel }}"
                     data-transmission="{{ $car->transmision }}">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="ratio" style="--bs-aspect-ratio: 60%;">
                            <img src="{{ $image }}" class="card-img-top object-fit-cover" alt="{{ optional($car->brand)->name }} {{ optional($car->car_model)->name }}">
                        </div>
                        <div class="card-body d-flex flex-column pt-2 pb-3">
                            <h5 class="card-title mb-1">{{ optional($car->brand)->name }} {{ optional($car->car_model)->name }}</h5>
                            <p class="text-muted small mb-1">{{ $yearLabel }} • {{ $car->transmision }} • {{ $car->kilometers }}</p>
                            <p class="fw-bold text-primary fs-5 mb-2">{{ $car->price }} €</p>
                            <a href="{{ route('website.stand.show', [$car->id, $car->slug]) }}" class="btn btn-outline-primary mt-auto">Saber mais</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">Sem viaturas disponíveis.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    (function() {
        const filterBtn = document.getElementById('filterBtn');
        const cards = Array.from(document.querySelectorAll('.vehicle-card'));
        const brandSelect = document.getElementById('filterBrand');
        const modelSelect = document.getElementById('filterModel');

        function rebuildModelOptions(brand) {
            const options = new Set();
            cards.forEach(card => {
                if (!brand || card.dataset.brand === brand) {
                    if (card.dataset.model) {
                        options.add(card.dataset.model);
                    }
                }
            });

            const currentModel = modelSelect.value;
            modelSelect.innerHTML = '<option value=\"\">Todos</option>';
            Array.from(options).sort().forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                modelSelect.appendChild(opt);
            });

            if (!brand) {
                modelSelect.value = currentModel;
            } else {
                modelSelect.value = '';
            }
        }

        function applyFilters() {
            const brand = brandSelect.value;
            const model = modelSelect.value;
            const year = document.getElementById('filterYear').value;
            const transmission = document.getElementById('filterTransmission').value;

            cards.forEach(card => {
                const matchBrand = !brand || card.dataset.brand === brand;
                const matchModel = !model || card.dataset.model === model;
                const matchYear = !year || card.dataset.year === year;
                const matchTransmission = !transmission || card.dataset.transmission === transmission;

                card.style.display = (matchBrand && matchModel && matchYear && matchTransmission) ? '' : 'none';
            });
        }

        brandSelect.addEventListener('change', () => {
            rebuildModelOptions(brandSelect.value);
            applyFilters();
        });

        filterBtn.addEventListener('click', applyFilters);
    })();
</script>
@endsection
