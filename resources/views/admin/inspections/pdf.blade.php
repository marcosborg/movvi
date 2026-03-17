<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e2a36; line-height: 1.3; }
        .header { border-bottom: 2px solid #0f4c81; padding-bottom: 8px; margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: 700; color: #0f4c81; margin: 0 0 3px 0; }
        .subtitle { color: #5b6b7b; margin: 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; background: #e7f1fb; color: #0f4c81; margin-right: 4px; }
        .section { border: 1px solid #d8e2ec; border-radius: 8px; padding: 8px; margin-bottom: 10px; }
        .section-title { font-size: 12px; font-weight: 700; margin: 0 0 6px 0; color: #0f4c81; }
        .kv td { border-bottom: 1px solid #eef3f8; padding: 4px 5px; vertical-align: top; }
        .kv td:first-child { width: 32%; color: #5a6b7f; }
        .grid-row { width: 100%; }
        .grid-col { display: inline-block; width: 49%; vertical-align: top; }
        .grid-col + .grid-col { margin-left: 1%; }
        .chip { display: inline-block; margin: 2px 4px 2px 0; padding: 2px 6px; border-radius: 10px; font-size: 9px; border: 1px solid #d8e2ec; }
        .chip-ok { background: #e8f7ee; color: #1f7a47; border-color: #bfe8cd; }
        .chip-warn { background: #fff5e8; color: #8a5a1a; border-color: #f0ddbe; }
        .chip-danger { background: #fdecec; color: #9f2f2f; border-color: #efc2c2; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dde6ef; padding: 4px 5px; vertical-align: top; }
        .table th { background: #f0f6fb; color: #2a3c4f; font-weight: 700; }
        .table thead { display: table-header-group; }
        .table tr { page-break-inside: avoid; }
        .section { page-break-inside: avoid; }
        .thumb-wrap { display: inline-block; width: 118px; height: 82px; border: 1px solid #d3deea; border-radius: 4px; background: #fff; margin: 2px 4px 2px 0; text-align: center; vertical-align: middle; line-height: 78px; overflow: hidden; }
        .thumb { max-width: 116px; max-height: 80px; width: auto; height: auto; vertical-align: middle; }
        .sign-wrap { display: inline-block; width: 160px; height: 52px; border: 1px solid #d3deea; background: #fff; border-radius: 4px; text-align: center; line-height: 48px; overflow: hidden; }
        .sign-img { max-width: 158px; max-height: 50px; width: auto; height: auto; vertical-align: middle; }
        .small { font-size: 9px; color: #5f6f80; }
        .muted { color: #6f8093; }
    </style>
</head>
<body>
    @php
        $typeLabel = config('inspections.type_labels.' . $inspection->type, $inspection->type);
        $statusLabel = config('inspections.status_labels.' . $inspection->status, $inspection->status);
        $createdAt = optional($inspection->created_at)->format('Y-m-d H:i:s');
        $completedAt = optional($inspection->completed_at)->format('Y-m-d H:i:s');
        $locationText = trim((string)($inspection->location_text ?? ''));
        $locationLat = $inspection->location_lat !== null ? (string)$inspection->location_lat : '-';
        $locationLng = $inspection->location_lng !== null ? (string)$inspection->location_lng : '-';
        $documents = [
            'dua' => 'DUA',
            'insurance' => 'Seguro',
            'inspection_periodic' => 'Inspecao periodica',
            'tvde_stickers' => 'Disticos TVDE',
            'no_smoking_sticker' => 'Autocolante proibicao fumar',
        ];
    @endphp

    <div class="header">
        <p class="title">Relatorio de Inspecao #{{ $inspection->id }}</p>
        <p class="subtitle">
            <span class="badge">{{ $typeLabel }}</span>
            <span class="badge">{{ $statusLabel }}</span>
            <span class="badge">Etapa {{ (int) $inspection->current_step }}</span>
        </p>
    </div>

    <div class="section">
        <p class="section-title">Resumo</p>
        <table class="kv">
            <tr><td>Viatura</td><td>{{ $inspection->vehicle->license_plate ?? '-' }} | {{ $inspection->vehicle->vehicle_brand->name ?? '-' }} {{ $inspection->vehicle->vehicle_model->name ?? '' }} ({{ $inspection->vehicle->year ?? '-' }})</td></tr>
            <tr><td>Condutor</td><td>{{ $inspection->driver->name ?? '-' }}</td></tr>
            <tr><td>Criado por</td><td>{{ $inspection->createdBy->name ?? '-' }}</td></tr>
            <tr><td>Responsavel</td><td>{{ $inspection->responsibleUser->name ?? '-' }}</td></tr>
            <tr><td>Criado em</td><td>{{ $createdAt ?: '-' }}</td></tr>
            <tr><td>Concluido em</td><td>{{ $completedAt ?: '-' }}</td></tr>
            <tr><td>Local</td><td>{{ $locationText !== '' ? $locationText : '-' }} ({{ $locationLat }}, {{ $locationLng }})</td></tr>
            <tr><td>Hash PDF</td><td class="small">{{ optional($inspection->report)->pdf_hash ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="grid-row">
        <div class="grid-col">
            <div class="section">
                <p class="section-title">Checklist rapido</p>
                @php
                    $cleanExt = $checklist['cleanliness']['external'] ?? null;
                    $cleanInt = $checklist['cleanliness']['interior'] ?? null;
                    $fuel = $checklist['fuel_energy']['level'] ?? null;
                    $tires = $checklist['tire_condition']['level'] ?? null;
                    $odo = $checklist['mileage']['odometer_km'] ?? null;
                    $panel = !empty($checklist['panel_warnings']['panel_warning']);
                @endphp
                <table class="table">
                    <tr><th>Campo</th><th>Valor</th></tr>
                    <tr><td>Limpeza exterior</td><td>{{ $cleanExt !== null ? $cleanExt . '/10' : '-' }}</td></tr>
                    <tr><td>Limpeza interior</td><td>{{ $cleanInt !== null ? $cleanInt . '/10' : '-' }}</td></tr>
                    <tr><td>Combustivel/Energia</td><td>{{ $fuel !== null ? $fuel . '/10' : '-' }}</td></tr>
                    <tr><td>Estado pneus</td><td>{{ $tires !== null ? $tires . '/10' : '-' }}</td></tr>
                    <tr><td>Quilometragem</td><td>{{ $odo !== null && $odo !== '' ? $odo . ' km' : '-' }}</td></tr>
                    <tr><td>Avisos painel</td><td>{{ $panel ? 'Sim' : 'Nao' }}</td></tr>
                </table>
                <div style="margin-top:6px;">
                    @foreach($documents as $key => $label)
                        @php($ok = !empty($checklist['documents'][$key]))
                        <span class="chip {{ $ok ? 'chip-ok' : 'chip-warn' }}">{{ $label }}: {{ $ok ? 'OK' : 'Nao' }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="grid-col">
            <div class="section">
                <p class="section-title">Assinaturas e pendencias</p>
                @if(!empty($signatures))
                    <table class="table">
                        <tr><th>Papel</th><th>Nome</th><th>Assinatura</th></tr>
                        @foreach($signatures as $signature)
                            <tr>
                                <td>{{ strtoupper($signature['role']) }}</td>
                                <td>{{ $signature['name'] ?: '-' }}<br><span class="small">{{ $signature['signed_at'] ?: '-' }}</span></td>
                                <td>
                                    @if(!empty($signature['image']))
                                        <span class="sign-wrap"><img class="sign-img" src="{{ $signature['image'] }}" alt="assinatura"></span>
                                    @else
                                        <span class="small muted">Assinatura textual</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <span class="chip chip-warn">Sem assinaturas</span>
                @endif

                <div style="margin-top:6px;">
                    @if(!empty($missingItems))
                        @foreach($missingItems as $item)
                            <span class="chip chip-danger">{{ $item }}</span>
                        @endforeach
                    @else
                        <span class="chip chip-ok">Sem pendencias</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <p class="section-title">Danos registados ({{ count($damages) }})</p>
        @if(empty($damages))
            <span class="chip chip-ok">Sem danos</span>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Scope</th>
                        <th>Localizacao</th>
                        <th>Peca</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Fotos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($damages as $damage)
                        <tr>
                            <td>{{ $damage['id'] }}</td>
                            <td>{{ ucfirst($damage['scope']) }}</td>
                            <td>{{ $damage['location'] }}</td>
                            <td>{{ $damage['part'] }}{{ $damage['part_section'] ? ' / ' . $damage['part_section'] : '' }}</td>
                            <td>{{ $damage['damage_type'] }}</td>
                            <td>{{ $damage['resolved'] ? 'Resolvido' : 'Aberto' }}</td>
                            <td>
                                @if(!empty($damage['photos']))
                                    @foreach($damage['photos'] as $photoUri)
                                        <span class="thumb-wrap"><img class="thumb" src="{{ $photoUri }}" alt="dano"></span>
                                    @endforeach
                                @else
                                    <span class="small muted">Sem foto</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @foreach($photoSections as $section)
        <div class="section">
            <p class="section-title">{{ $section['category_label'] }} ({{ count($section['items']) }})</p>
            @if(empty($section['items']))
                <span class="small muted">Sem fotos nesta secao.</span>
            @else
                <table class="table">
                    <thead>
                        <tr><th>Item</th><th>Slot</th><th>Imagem</th></tr>
                    </thead>
                    <tbody>
                        @foreach($section['items'] as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td class="small">{{ $item['slot'] }}</td>
                                <td>
                                    @if(!empty($item['thumb']))
                                        <span class="thumb-wrap"><img class="thumb" src="{{ $item['thumb'] }}" alt="{{ $item['label'] }}"></span>
                                    @else
                                        <span class="small muted">Imagem nao disponivel</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
