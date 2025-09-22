@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-md-8">
                            {{ trans('cruds.vehicleUsage.title') }} - Visão Geral
                        </div>
                        <div class="col-md-4">
                            <a href="/admin/vehicle-usages/create" class="btn btn-primary btn-sm pull-right">Vehicle usage</a>
                        </div>
                    </div>
                </div>

                <div class="panel-body">
                    <div>
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#home" role="tab" data-toggle="tab">Linha do Tempo das Viaturas</a>
                            </li>
                            <li role="presentation">
                                <a href="#profile" role="tab" data-toggle="tab">Gráfico da Taxa de Ocupação</a>
                            </li>
                            <li role="presentation">
                                <a href="#messages" role="tab" data-toggle="tab">Detalhe da Ocupação por Viatura</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- Linha do Tempo -->
                            <div role="tabpanel" class="tab-pane active" id="home">
                                <h3>Linha do Tempo das Viaturas</h3>
                                <div id="timelineContainer" style="margin-bottom: 40px;">
                                    <div id="timeline" style="height: auto;"></div>
                                </div>
                            </div>

                            <!-- Gráfico -->
                            <div role="tabpanel" class="tab-pane" id="profile">
                                <div class="form-inline" style="margin-top:15px;">
                                    <div class="form-group" style="margin-right:10px;">
                                        <label for="yearFilter" style="margin-right:6px;">Selecionar Ano:</label>
                                        <select id="yearFilter" class="form-control" style="max-width: 200px;">
                                            <option value="all">Todos os anos</option>
                                            @foreach(array_keys($availableYears) as $year)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="monthFilter" style="margin-right:6px;">Selecionar Mês:</label>
                                        <select id="monthFilter" class="form-control" style="max-width: 200px;">
                                            <option value="all">Todos os meses</option>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <h3 id="chartTitle" style="margin-top:20px;">Gráfico da Taxa de Ocupação</h3>

                                {{-- Altura controlada no CONTÊINER, não no canvas --}}
                                <div id="occupancyChartContainer" style="width:100%; height:420px;">
                                    <canvas id="occupancyChart"></canvas>
                                </div>
                                <p class="text-muted" style="margin-top:10px;">
                                    As barras estão ordenadas por <strong>maior percentagem de utilização</strong> (verde).
                                </p>
                            </div>

                            <!-- Detalhe por Viatura -->
                            <div role="tabpanel" class="tab-pane" id="messages">
                                <h3 class="mt-5">Detalhe da Ocupação por Viatura</h3>
                                @foreach($occupancyStats as $plate => $years)
                                    <h4>{{ $plate }}</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Ano</th>
                                                <th>Dias em uso</th>
                                                <th>Total de dias</th>
                                                <th>Taxa de ocupação (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($years as $year => $data)
                                                <tr>
                                                    <td>{{ $year }}</td>
                                                    <td>{{ $data['used'] }}</td>
                                                    <td>{{ $data['total'] }}</td>
                                                    <td>{{ $data['percent'] }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endforeach
                            </div>
                        </div><!-- /.tab-content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://unpkg.com/vis-timeline@latest/styles/vis-timeline-graph2d.min.css" rel="stylesheet" />
<script src="https://unpkg.com/vis-timeline@latest/standalone/umd/vis-timeline-graph2d.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // === TIMELINE ===
    const timelineItems = new vis.DataSet([
        @foreach($grouped as $plate => $records)
            @foreach($records as $record)
                {
                    id: {{ $record->id }},
                    content: '{{ addslashes($record->driver ? $record->driver->name : ($record->usage_exceptions ? ucfirst($record->usage_exceptions) : "Sem motorista")) }}',
                    start: '{{ \Carbon\Carbon::parse($record->getRawOriginal("start_date"))->toDateString() }}',
                    end: '{{ \Carbon\Carbon::parse($record->getRawOriginal("end_date"))->toDateString() }}',
                    group: '{{ $plate }}',
                    @if($record->usage_exceptions)
                        className: '{{ $record->usage_exceptions }}-item',
                    @elseif(!$record->driver)
                        className: 'exception-item',
                    @endif
                },
            @endforeach
        @endforeach
    ]);

    const timelineGroups = new vis.DataSet([
        @foreach($grouped as $plate => $records)
            { id: '{{ $plate }}', content: '{{ $plate }}' },
        @endforeach
    ]);

    new vis.Timeline(document.getElementById('timeline'), timelineItems, timelineGroups, {
        stack: false,
        groupOrder: 'content',
        editable: false,
        margin: { item: 10, axis: 5 },
        orientation: 'top'
    });

    // === CHART.JS (STACKED HORIZONTAL) ===
    const ctx        = document.getElementById('occupancyChart').getContext('2d');
    const container  = document.getElementById('occupancyChartContainer');

    // dados já reindexados pelo controller
    const stackedStats = @json($monthlyStackedStats, JSON_NUMERIC_CHECK);

    const categoryLabels = {
        usage: 'Utilização',
        maintenance: 'Manutenção',
        accident: 'Sinistrado',
        unassigned: 'Sem utilização',
        personal: 'Utilização pessoal'
    };
    const categoryColors = {
        usage: '#28a745',
        maintenance: '#fd7e14',
        accident: '#dc3545',
        unassigned: '#ffc107',
        personal: '#6f42c1'
    };
    const categories = ['usage', 'maintenance', 'accident', 'unassigned', 'personal'];

    // Criar UMA instância de Chart
    const chart = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false, // usa a altura do contêiner
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.raw}%`
                    }
                },
                legend: { position: 'bottom' }
            },
            scales: {
                x: {
                    stacked: true,
                    min: 0,
                    max: 100,
                    ticks: { callback: v => v + '%' }
                },
                y: { stacked: true }
            }
        }
    });

    function buildDatasets(filteredData) {
        return categories.map(cat => ({
            label: categoryLabels[cat],
            backgroundColor: categoryColors[cat],
            data: filteredData.map(stat => {
                const total = categories.reduce((sum, key) => sum + (stat[key] || 0), 0);
                return total > 0 ? +(((stat[cat] || 0) / total) * 100).toFixed(2) : 0;
            }),
            stack: 'ocupacao'
        }));
    }

    function filterStackedStats() {
        const year  = document.getElementById('yearFilter').value;
        const month = document.getElementById('monthFilter').value;

        // filtra por ano/mês
        let filtered = stackedStats.filter(stat =>
            (year  === 'all' || stat.year  == year) &&
            (month === 'all' || stat.month == month)
        );

        // se mês = all, agrega por viatura(ano)
        if (month === 'all') {
            const grouped = {};
            filtered.forEach(stat => {
                const key = `${stat.plate} (${stat.year})`;
                if (!grouped[key]) {
                    grouped[key] = { label: key, plate: stat.plate, year: stat.year, usage: 0, maintenance: 0, accident: 0, unassigned: 0, personal: 0 };
                }
                categories.forEach(cat => grouped[key][cat] += (stat[cat] || 0));
            });
            filtered = Object.values(grouped);
        }

        // ordenar por % de utilização desc (verde)
        filtered.sort((a, b) => {
            const totA = categories.reduce((s, k) => s + (a[k] || 0), 0);
            const totB = categories.reduce((s, k) => s + (b[k] || 0), 0);
            const pA = totA ? (a.usage || 0) / totA : 0;
            const pB = totB ? (b.usage || 0) / totB : 0;
            if (pB === pA) return ('' + a.label).localeCompare(b.label); // tie-break estável
            return pB - pA;
        });

        // Altura dinâmica no CONTÊINER (evita loop de resize do canvas)
        const BAR_HEIGHT = 20; // px por item
        const targetHeight = Math.max(320, filtered.length * BAR_HEIGHT);
        if (container.style.height !== targetHeight + 'px') {
            container.style.height = targetHeight + 'px';
            chart.resize(); // pede ao chart para adaptar-se ao novo contêiner
        }

        // Atualizar gráfico sem destruir
        chart.data.labels   = filtered.map(d => d.label);
        chart.data.datasets = buildDatasets(filtered);
        chart.update();
    }

    // Recalcular quando a aba de gráfico for exibida
    const tabLink = document.querySelector('a[href="#profile"]');
    if (tabLink) {
        if (window.jQuery) {
            $(tabLink).on('shown.bs.tab', () => { chart.resize(); filterStackedStats(); });
        } else {
            tabLink.addEventListener('click', () => {
                setTimeout(() => { chart.resize(); filterStackedStats(); }, 0);
            });
        }
    }

    document.getElementById('yearFilter').addEventListener('change', filterStackedStats);
    document.getElementById('monthFilter').addEventListener('change', filterStackedStats);

    // Render inicial
    filterStackedStats();
});
</script>
@endsection

@section('styles')
<style>
/* Canvas ocupa 100% do contêiner */
#occupancyChart { width:100% !important; height:100% !important; }

/* Cores da timeline por exceção */
.vis-item.usage-item      { background-color:#28a745 !important; border-color:#1e7e34 !important; color:#fff !important; font-weight:bold; }
.vis-item.maintenance-item{ background-color:#fd7e14 !important; border-color:#e8590c !important; color:#fff !important; font-weight:bold; }
.vis-item.accident-item   { background-color:#dc3545 !important; border-color:#a71d2a !important; color:#fff !important; font-weight:bold; }
.vis-item.unassigned-item { background-color:#ffc107 !important; border-color:#e0a800 !important; color:#333 !important; font-weight:bold; }
.vis-item.personal-item   { background-color:#6f42c1 !important; border-color:#5936a2 !important; color:#fff !important; font-weight:bold; }
.vis-item.exception-item  { background-color:#ff4d4d !important; border-color:#cc0000 !important; color:#fff !important; font-weight:bold; }
</style>
@endsection
