@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Visualizacao online da inspecao #{{ $inspection->id }}
                    <span class="label label-info" style="margin-left:8px;">{{ config('inspections.status_labels.' . $inspection->status, $inspection->status) }}</span>
                </div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        <strong>Viatura:</strong> {{ $inspection->vehicle->license_plate ?? '-' }} |
                        <strong>Condutor:</strong> {{ $inspection->driver->name ?? '-' }} |
                        <strong>Tipo:</strong> {{ config('inspections.type_labels.' . $inspection->type, $inspection->type) }}
                    </div>

                    <p>
                        <a class="btn btn-info" href="{{ route('admin.inspections.edit', $inspection->id) }}">Voltar ao wizard</a>
                        <a class="btn btn-default" href="{{ route('admin.inspections.index') }}">Voltar a lista</a>
                        @if($inspection->report)
                            <a class="btn btn-primary" href="{{ asset('storage/' . $inspection->report->pdf_path) }}" target="_blank">Abrir PDF</a>
                        @endif
                    </p>

                    <div class="inspection-grid">
                        <div class="inspection-card">
                            <h4>Resumo</h4>
                            <table class="table table-bordered table-condensed">
                                <tr><th>ID</th><td>{{ $inspection->id }}</td></tr>
                                <tr><th>Criado por</th><td>{{ $inspection->createdBy->name ?? '-' }}</td></tr>
                                <tr><th>Responsavel</th><td>{{ $inspection->responsibleUser->name ?? '-' }}</td></tr>
                                <tr><th>Local</th><td>{{ $inspection->location_text ?: '-' }} ({{ $inspection->location_lat ?: '-' }}, {{ $inspection->location_lng ?: '-' }})</td></tr>
                                <tr><th>Criado em</th><td>{{ optional($inspection->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td></tr>
                                <tr><th>Concluido em</th><td>{{ optional($inspection->completed_at)->format('Y-m-d H:i:s') ?: '-' }}</td></tr>
                            </table>
                        </div>

                        <div class="inspection-card">
                            <h4>Checklist rapido</h4>
                            <table class="table table-bordered table-condensed">
                                <tr><th>Campo</th><th>Valor</th></tr>
                                <tr><td>Limpeza exterior</td><td>{{ isset($checklist['cleanliness']['external']) ? $checklist['cleanliness']['external'] . '/10' : '-' }}</td></tr>
                                <tr><td>Limpeza interior</td><td>{{ isset($checklist['cleanliness']['interior']) ? $checklist['cleanliness']['interior'] . '/10' : '-' }}</td></tr>
                                <tr><td>Combustivel/Energia</td><td>{{ isset($checklist['fuel_energy']['level']) ? $checklist['fuel_energy']['level'] . '/10' : '-' }}</td></tr>
                                <tr><td>Estado pneus</td><td>{{ isset($checklist['tire_condition']['level']) ? $checklist['tire_condition']['level'] . '/10' : '-' }}</td></tr>
                                <tr><td>Quilometragem</td><td>{{ !empty($checklist['mileage']['odometer_km']) ? $checklist['mileage']['odometer_km'] . ' km' : '-' }}</td></tr>
                                <tr><td>Avisos painel</td><td>{{ !empty($checklist['panel_warnings']['panel_warning']) ? 'Sim' : 'Nao' }}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="inspection-card">
                        <h4>Pendencias</h4>
                        @if(empty($missingItems))
                            <span class="label label-success">Sem pendencias</span>
                        @else
                            @foreach($missingItems as $item)
                                <span class="label label-danger label-many">{{ $item['group'] }}: {{ $item['item'] }}</span>
                            @endforeach
                        @endif
                    </div>

                    <div class="inspection-card">
                        <h4>Danos registados ({{ count($damageItems) }})</h4>
                        @if(empty($damageItems))
                            <p class="text-muted">Sem danos registados.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Scope</th>
                                            <th>Local</th>
                                            <th>Peca</th>
                                            <th>Parte</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Fotos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($damageItems as $damage)
                                            <tr>
                                                <td>{{ $damage['id'] }}</td>
                                                <td>{{ $damage['scope'] }}</td>
                                                <td>{{ $damage['location'] }}</td>
                                                <td>{{ $damage['piece'] }}</td>
                                                <td>{{ $damage['part'] ?: '-' }}</td>
                                                <td>{{ $damage['type'] }}</td>
                                                <td>{{ $damage['status'] }}</td>
                                                <td>
                                                    @if(empty($damage['photos']))
                                                        <span class="text-muted">Sem fotos</span>
                                                    @else
                                                        @foreach($damage['photos'] as $photo)
                                                            <button
                                                                type="button"
                                                                class="thumb-btn"
                                                                data-gallery="damage-{{ $damage['id'] }}"
                                                                data-index="{{ $loop->index }}"
                                                                data-images='@json($damage['photos'])'
                                                            >
                                                                <img src="{{ $photo['thumb'] }}" alt="{{ $photo['title'] }}">
                                                            </button>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="inspection-card">
                        <h4>Assinaturas</h4>
                        @if(empty($signatureItems))
                            <p class="text-muted">Sem assinaturas.</p>
                        @else
                            <div class="row">
                                @foreach($signatureItems as $signature)
                                    <div class="col-md-6" style="margin-bottom:12px;">
                                        <div class="signature-box">
                                            <strong>{{ $signature['role'] }}</strong><br>
                                            <span>{{ $signature['name'] ?: '-' }}</span><br>
                                            <small class="text-muted">{{ $signature['signed_at'] ?: '-' }}</small>
                                            @if($signature['image'])
                                                <div style="margin-top:8px;">
                                                    <img class="signature-preview" src="{{ $signature['image'] }}" alt="{{ $signature['role'] }}">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @foreach($photoSections as $section)
                        <div class="inspection-card">
                            <h4>{{ $section['title'] }} ({{ count($section['items']) }})</h4>
                            @if(empty($section['items']))
                                <p class="text-muted">Sem fotos nesta secao.</p>
                            @else
                                <div class="photo-grid">
                                    @foreach($section['items'] as $item)
                                        <div class="photo-card">
                                            <button
                                                type="button"
                                                class="thumb-btn thumb-btn--large"
                                                data-gallery="{{ \Illuminate\Support\Str::slug($section['title']) }}"
                                                data-index="{{ $loop->index }}"
                                                data-images='@json($section['items'])'
                                            >
                                                <img src="{{ $item['thumb'] }}" alt="{{ $item['label'] }}">
                                            </button>
                                            <div class="photo-label">{{ $item['label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="inspection-modal" id="inspectionGalleryModal" aria-hidden="true">
    <button type="button" class="inspection-modal__close" data-gallery-close>&times;</button>
    <button type="button" class="inspection-modal__nav inspection-modal__nav--prev" data-gallery-prev>&lsaquo;</button>
    <div class="inspection-modal__body">
        <img id="inspectionGalleryImage" src="" alt="">
        <div class="inspection-modal__caption" id="inspectionGalleryCaption"></div>
    </div>
    <button type="button" class="inspection-modal__nav inspection-modal__nav--next" data-gallery-next>&rsaquo;</button>
</div>
@endsection

@section('styles')
@parent
<style>
    .inspection-grid {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .inspection-grid .inspection-card {
        flex: 1 1 360px;
    }
    .inspection-card {
        border: 1px solid #d9e3ee;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 14px;
        background: #fff;
    }
    .inspection-card h4 {
        margin-top: 0;
        margin-bottom: 12px;
        color: #0f4c81;
        font-weight: 700;
    }
    .photo-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .photo-card {
        width: 180px;
    }
    .photo-label {
        margin-top: 6px;
        font-size: 12px;
        color: #53657a;
    }
    .thumb-btn {
        padding: 0;
        border: 1px solid #d4deea;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
        cursor: pointer;
    }
    .thumb-btn img {
        display: block;
        width: 120px;
        height: 82px;
        object-fit: cover;
    }
    .thumb-btn--large img {
        width: 178px;
        height: 132px;
    }
    .signature-box {
        border: 1px solid #d4deea;
        border-radius: 6px;
        padding: 10px;
        background: #fafcfe;
    }
    .signature-preview {
        max-width: 100%;
        max-height: 90px;
        width: auto;
        height: auto;
        border: 1px solid #d4deea;
        background: #fff;
    }
    .inspection-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(14, 20, 26, 0.88);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 40px 70px;
    }
    .inspection-modal.is-open {
        display: flex;
    }
    .inspection-modal__body {
        max-width: 90vw;
        max-height: 86vh;
        text-align: center;
    }
    .inspection-modal__body img {
        max-width: 90vw;
        max-height: 78vh;
        width: auto;
        height: auto;
        border-radius: 8px;
        background: #fff;
    }
    .inspection-modal__caption {
        color: #fff;
        margin-top: 10px;
        font-size: 14px;
    }
    .inspection-modal__close,
    .inspection-modal__nav {
        position: absolute;
        border: 0;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        width: 44px;
        height: 44px;
        border-radius: 999px;
        font-size: 30px;
        line-height: 44px;
        text-align: center;
    }
    .inspection-modal__close {
        top: 18px;
        right: 24px;
    }
    .inspection-modal__nav--prev {
        left: 18px;
    }
    .inspection-modal__nav--next {
        right: 18px;
    }
</style>
@endsection

@section('scripts')
@parent
<script>
(function () {
    var modal = document.getElementById('inspectionGalleryModal');
    var image = document.getElementById('inspectionGalleryImage');
    var caption = document.getElementById('inspectionGalleryCaption');
    var closeBtn = modal && modal.querySelector('[data-gallery-close]');
    var prevBtn = modal && modal.querySelector('[data-gallery-prev]');
    var nextBtn = modal && modal.querySelector('[data-gallery-next]');
    var state = { images: [], index: 0 };

    function render() {
        if (!state.images.length) return;
        var item = state.images[state.index];
        image.src = item.url || '';
        image.alt = item.title || '';
        caption.textContent = item.label || item.title || '';
    }

    function open(images, index) {
        state.images = images || [];
        state.index = index || 0;
        render();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function close() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        image.src = '';
        state = { images: [], index: 0 };
    }

    function move(step) {
        if (!state.images.length) return;
        state.index = (state.index + step + state.images.length) % state.images.length;
        render();
    }

    document.querySelectorAll('[data-images]').forEach(function (button) {
        button.addEventListener('click', function () {
            var images = [];
            try {
                images = JSON.parse(button.getAttribute('data-images') || '[]');
            } catch (e) {
                images = [];
            }
            open(images, Number(button.getAttribute('data-index') || 0));
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);
    if (prevBtn) prevBtn.addEventListener('click', function () { move(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { move(1); });

    document.addEventListener('keydown', function (event) {
        if (!modal.classList.contains('is-open')) return;
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') move(-1);
        if (event.key === 'ArrowRight') move(1);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) close();
    });
})();
</script>
@endsection
