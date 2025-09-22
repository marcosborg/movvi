@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">

            {{-- Título --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleProfitability.title') ?? 'Vehicle Profitability' }}
                </div>

                {{-- FILTROS --}}
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.vehicle-profitabilities.index') }}" class="form-inline" id="filtersForm">
                        {{-- Período --}}
                        <div class="form-group" style="margin-right:8px;">
                            <label for="period">Período:&nbsp;</label>
                            <select name="period" id="period" class="form-control">
                                <option value="week"  {{ $period=='week'  ? 'selected' : '' }}>Semanas</option>
                                <option value="month" {{ $period=='month' ? 'selected' : '' }}>Mês</option>
                                <option value="year"  {{ $period=='year'  ? 'selected' : '' }}>Ano</option>
                                <option value="custom"{{ $period=='custom'? 'selected' : '' }}>Intervalo</option>
                            </select>
                        </div>

                        {{-- Ano --}}
                        <div class="form-group period-block period-month period-year" style="margin-right:8px; display:none;">
                            <label for="year">Ano:&nbsp;</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">—</option>
                                @foreach($tvde_years as $y)
                                    <option value="{{ $y }}" {{ (string)$year === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Mês --}}
                        <div class="form-group period-block period-month" style="margin-right:8px; display:none;">
                            <label for="month">Mês:&nbsp;</label>
                            <select name="month" id="month" class="form-control">
                                <option value="">—</option>
                                @foreach($tvde_months as $m)
                                    <option value="{{ $m }}" {{ (string)$month === (string)$m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->locale('pt_PT')->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Semanas (multi) --}}
                        <div class="form-group period-block period-week" style="margin-right:8px; display:none; min-width:280px;">
                            <label for="weeks">Semanas:&nbsp;</label>
                            <select name="weeks[]" id="weeks" class="form-control" multiple size="4" style="min-width:260px;">
                                @foreach($tvde_weeks as $w)
                                    <option value="{{ $w->id }}"
                                        {{ collect(request('weeks', []))->contains($w->id) ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($w->start_date)->format('d/m/Y') }} —
                                        {{ \Carbon\Carbon::parse($w->end_date)->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Intervalo de datas --}}
                        <div class="form-group period-block period-custom" style="margin-right:8px; display:none;">
                            <label for="start_date">De:&nbsp;</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="form-group period-block period-custom" style="margin-right:8px; display:none;">
                            <label for="end_date">a:&nbsp;</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>

                        {{-- Agrupar por --}}
                        <div class="form-group" style="margin-right:8px;">
                            <label for="group_by">Agrupar por:&nbsp;</label>
                            <select name="group_by" id="group_by" class="form-control">
                                <option value="week"  {{ $groupBy=='week'  ? 'selected' : '' }}>Semana</option>
                                <option value="month" {{ $groupBy=='month' ? 'selected' : '' }}>Mês</option>
                                <option value="year"  {{ $groupBy=='year'  ? 'selected' : '' }}>Ano</option>
                            </select>
                        </div>

                        <button class="btn btn-primary" style="margin-left:8px;">Aplicar</button>
                    </form>

                    {{-- Tabs das viaturas --}}
                    <ul class="nav nav-tabs" style="margin-top:15px;">
                        @foreach ($vehicle_items as $vi)
                            <li role="presentation" {{ $vehicle_item_id == $vi->id ? 'class=active' : '' }}>
                                <a href="/admin/vehicle-profitabilities/set-vehicle-item-id/{{ $vi->id }}">
                                    {{ $vi->license_plate }}
                                    {{ $vi->driver ? '(' . $vi->driver->name . ')' : '' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Cartões de totais --}}
            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">Resultados</div>
                        <div class="panel-body">
                            <table width="100%"><tbody>
                                <tr>
                                    <th><h1><small>Tesouraria:&nbsp;</small></h1></th>
                                    <td><h1>{{ number_format($totals['treasury'] ?? 0, 2, ',', '.') }}<small>€</small></h1></td>
                                </tr>
                                <tr>
                                    <th><h1><small>Impostos:&nbsp;</small></h1></th>
                                    <td><h1>{{ number_format($totals['taxes'] ?? 0, 2, ',', '.') }}<small>€</small></h1></td>
                                </tr>
                            </tbody></table>
                        </div>
                    </div>
                </div>

                @php $final = $totals['final'] ?? 0; @endphp
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">{{ $final >= 0 ? 'Lucro' : 'Prejuízo' }}</div>
                        <div class="panel-body">
                            <h1 style="font-size:50px;">{{ number_format($final, 2, ',', '.') }}<small>€</small></h1>
                        </div>
                    </div>
                </div>

                {{-- Gráfico --}}
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">Gráfico</div>
                        <div class="panel-body">
                            <canvas id="rentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABELA POR GRUPOS --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    @if($groupBy === 'year') Totais por Ano
                    @elseif($groupBy === 'month') Totais por Mês
                    @else Totais por Semana
                    @endif
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Tesouraria</th>
                                <th>Impostos</th>
                                <th>Final</th>
                                <th>Semanas</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($groups as $label => $g)
                            <tr>
                                <td>
                                    @if($groupBy === 'year')
                                        {{ $label }}
                                    @elseif($groupBy === 'month')
                                        @php
                                            [$yy,$mm] = explode('-', $label);
                                            $labelText = \Carbon\Carbon::create($yy, $mm, 1)->locale('pt_PT')->translatedFormat('F \\d\\e Y');
                                        @endphp
                                        {{ $labelText }}
                                    @else
                                        @php
                                            $w = $g['weeks'][0]['week'];
                                        @endphp
                                        {{ \Carbon\Carbon::parse($w->start_date)->format('d/m') }} a {{ \Carbon\Carbon::parse($w->end_date)->format('d/m') }}
                                    @endif
                                </td>
                                <td>{{ number_format($g['treasury'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($g['taxes'], 2, ',', '.') }} €</td>
                                <td><strong>{{ number_format($g['final'], 2, ',', '.') }} €</strong></td>
                                <td>
                                    {{-- Lista resumida de semanas do grupo --}}
                                    @if(!empty($g['weeks']))
                                        <details>
                                            <summary>ver semanas</summary>
                                            <ul style="margin:6px 0 0 18px;">
                                                @foreach($g['weeks'] as $row)
                                                    <li>
                                                        {{ \Carbon\Carbon::parse($row['week']->start_date)->format('d/m') }}
                                                        —
                                                        {{ \Carbon\Carbon::parse($row['week']->end_date)->format('d/m') }}
                                                        (Final: {{ number_format($row['final_total'], 2, ',', '.') }} €)
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Sem resultados para os filtros selecionados.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- (Opcional) Tabela detalhada por semana (rows) --}}
            <div class="panel panel-default">
                <div class="panel-heading">Detalhe por Semana</div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <th>Motorista</th>
                                <th>Tesouraria</th>
                                <th>Impostos</th>
                                <th>Final</th>
                                <th>Transferido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $r)
                                <tr>
                                    <td>
                                      {{ \Carbon\Carbon::parse($r['week']->start_date)->format('d/m') }}
                                      a
                                      {{ \Carbon\Carbon::parse($r['week']->end_date)->format('d/m') }}
                                    </td>
                                    <td>{{ $r['driver']->name ?? '—' }}</td>
                                    <td>{{ number_format($r['total_treasury'], 2, ',', '.') }} €</td>
                                    <td>{{ number_format($r['total_taxes'], 2, ',', '.') }} €</td>
                                    <td><strong>{{ number_format($r['final_total'], 2, ',', '.') }} €</strong></td>
                                    <td>{{ number_format($r['receipt']->amount_transferred ?? 0, 2, ',', '.') }} €</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
    // Mostrar/ocultar blocos do formulário conforme o período
    function togglePeriodBlocks() {
        var p = document.getElementById('period').value;
        document.querySelectorAll('.period-block').forEach(el => el.style.display = 'none');
        if (p === 'week') {
            document.querySelectorAll('.period-week').forEach(el => el.style.display = 'inline-block');
        } else if (p === 'month') {
            document.querySelectorAll('.period-month').forEach(el => el.style.display = 'inline-block');
        } else if (p === 'year') {
            document.querySelectorAll('.period-year').forEach(el => el.style.display = 'inline-block');
        } else if (p === 'custom') {
            document.querySelectorAll('.period-custom').forEach(el => el.style.display = 'inline-block');
        }
    }
    document.getElementById('period').addEventListener('change', togglePeriodBlocks);
    togglePeriodBlocks(); // inicial

    // Dados do gráfico (labels = grupos; valores = final)
    const chartLabels = @json($groups->keys()->values());
    const chartDataFinal = @json($groups->map(fn($g)=>$g['final'])->values());

    const ctx = document.getElementById('rentChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Resultado (Final) €',
                data: chartDataFinal,
                borderWidth: 1,
                // podes remover as cores se preferires o default
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Rentabilidade por ' + ({
                        'week': 'Semana',
                        'month': 'Mês',
                        'year': 'Ano'
                    })['{{ $groupBy }}']
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (v)=> v + '€' }
                }
            }
        }
    });
</script>
@endsection
