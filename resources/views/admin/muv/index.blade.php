@extends('layouts.admin')

@php
    $summary = $report['summary'];
    $fmt = fn ($value) => number_format((float) ($value ?? 0), 2, ',', '.') . ' €';
    $pct = fn ($value) => number_format((float) ($value ?? 0), 1, ',', '.') . '%';
@endphp

@section('styles')
<style>
    .muv-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .muv-title {
        margin: 0;
        font-weight: 700;
    }

    .muv-subtitle {
        color: #667085;
        margin-top: 5px;
    }

    .muv-filter-panel,
    .muv-card,
    .muv-chart-panel,
    .muv-table-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }

    .muv-filter-panel {
        padding: 15px;
        margin-bottom: 18px;
    }

    .muv-filter-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }

    .muv-filter-grid .form-group {
        margin-bottom: 0;
    }

    .muv-card {
        padding: 16px;
        min-height: 128px;
        margin-bottom: 18px;
    }

    .muv-card-label {
        color: #667085;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
    }

    .muv-card-value {
        font-size: 28px;
        line-height: 1.15;
        font-weight: 700;
        margin-top: 10px;
        color: #111827;
    }

    .muv-card-note {
        color: #667085;
        font-size: 12px;
        margin-top: 8px;
    }

    .muv-positive { color: #008d4c; }
    .muv-negative { color: #dd4b39; }

    .muv-chart-panel,
    .muv-table-panel {
        padding: 16px;
        margin-bottom: 18px;
    }

    .muv-panel-title {
        font-size: 16px;
        font-weight: 700;
        margin: 0 0 12px;
    }

    .muv-chart-wrap {
        position: relative;
        height: 320px;
    }

    .muv-list {
        padding-left: 18px;
        margin-bottom: 0;
        color: #374151;
    }

    .muv-list li {
        margin-bottom: 8px;
    }

    .muv-table td,
    .muv-table th {
        vertical-align: middle !important;
    }

    .muv-status {
        display: inline-block;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        background: #eef2ff;
        color: #3730a3;
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="muv-header">
        <div>
            <h1 class="muv-title">MUV</h1>
        </div>
        <a class="btn btn-danger" href="{{ route('admin.muv.pdf', request()->query()) }}">
            <i class="fa fa-file-pdf-o"></i> Exportar PDF
        </a>
    </div>

    <div class="muv-filter-panel">
        <form method="GET" action="{{ route('admin.muv.index') }}" class="muv-filter-grid">
            <div class="form-group">
                <label for="start_date">Data inicial</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $report['filters']['start_date'] }}">
            </div>
            <div class="form-group">
                <label for="end_date">Data final</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $report['filters']['end_date'] }}">
            </div>
            <div class="form-group">
                <label for="company_id">Empresa</label>
                <select class="form-control" id="company_id" name="company_id">
                    <option value="">Todas as empresas</option>
                    @foreach($report['companies'] as $company)
                        <option value="{{ $company->id }}" {{ (string) $report['filters']['company_id'] === (string) $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">
                <i class="fa fa-filter"></i> Aplicar filtros
            </button>
        </form>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="muv-card">
                <div class="muv-card-label">Entradas consolidadas</div>
                <div class="muv-card-value">{{ $fmt($summary['income_total']) }}</div>
                <div class="muv-card-note">TVDE, gorjetas, recibos e reembolsos recebidos.</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="muv-card">
                <div class="muv-card-label">Saídas consolidadas</div>
                <div class="muv-card-value">{{ $fmt($summary['outcome_total']) }}</div>
                <div class="muv-card-note">Motoristas, impostos, viaturas, empresa e Conta Azul.</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="muv-card">
                <div class="muv-card-label">Resultado estimado</div>
                <div class="muv-card-value {{ $summary['estimated_result'] >= 0 ? 'muv-positive' : 'muv-negative' }}">
                    {{ $fmt($summary['estimated_result']) }}
                </div>
                <div class="muv-card-note">Margem estimada: {{ $pct($summary['margin']) }}.</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="muv-card">
                <div class="muv-card-label">Conta Azul</div>
                <div class="muv-card-value">{{ $fmt($summary['conta_azul_exported_revenue']) }}</div>
                <div class="muv-card-note">Receita exportada. Despesas sincronizadas: {{ $fmt($summary['conta_azul_synced_expenses']) }}.</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="muv-chart-panel">
                <h3 class="muv-panel-title">Evolução mensal: entradas, saídas e resultado</h3>
                <div class="muv-chart-wrap">
                    <canvas id="muvCashflowChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="muv-chart-panel">
                <h3 class="muv-panel-title">Composição das saídas</h3>
                <div class="muv-chart-wrap">
                    <canvas id="muvExpenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="muv-table-panel">
                <h3 class="muv-panel-title">Leitura executiva</h3>
                <ul class="muv-list">
                    <li><strong>Receita operacional TVDE:</strong> {{ $fmt($summary['tvde_gross']) }} brutos e {{ $fmt($summary['tvde_net']) }} líquidos após comissões dos operadores.</li>
                    <li><strong>Principal saída operacional:</strong> pagamentos/valores a motoristas de {{ $fmt($summary['driver_payouts']) }}.</li>
                    <li><strong>Investimento e manutenção de frota:</strong> despesas de viaturas de {{ $fmt($summary['vehicle_expenses']) }} no período.</li>
                    <li><strong>Conta Azul:</strong> {{ $report['conta_azul']['live']['available'] ? 'ligação ativa incluída na reconciliação.' : ($report['conta_azul']['live']['message'] ?? 'sem dados live disponíveis.') }}</li>
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="muv-chart-panel">
                <h3 class="muv-panel-title">Composição das entradas</h3>
                <div class="muv-chart-wrap">
                    <canvas id="muvIncomeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="muv-table-panel">
                <h3 class="muv-panel-title">Reconciliação Conta Azul</h3>
                <table class="table table-condensed">
                    <tbody>
                        <tr>
                            <th>Receitas live</th>
                            <td class="text-right">{{ $summary['conta_azul_live_revenue'] !== null ? $fmt($summary['conta_azul_live_revenue']) : 'N/D' }}</td>
                        </tr>
                        <tr>
                            <th>Despesas live</th>
                            <td class="text-right">{{ $summary['conta_azul_live_expenses'] !== null ? $fmt($summary['conta_azul_live_expenses']) : 'N/D' }}</td>
                        </tr>
                        <tr>
                            <th>Resultado live</th>
                            <td class="text-right">{{ $summary['conta_azul_live_result'] !== null ? $fmt($summary['conta_azul_live_result']) : 'N/D' }}</td>
                        </tr>
                        <tr>
                            <th>Exportações de receita</th>
                            <td class="text-right">{{ $report['conta_azul']['exports_count'] }}</td>
                        </tr>
                        <tr>
                            <th>Erros de exportação</th>
                            <td class="text-right">{{ $report['conta_azul']['exports_errors'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="muv-table-panel">
                <h3 class="muv-panel-title">Maiores gastos detalhados</h3>
                <table class="table table-striped table-condensed muv-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Origem</th>
                            <th>Descrição</th>
                            <th class="text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['tables']['top_expenses'] as $row)
                            <tr>
                                <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                                <td><span class="muv-status">{{ $row['source'] ?? $row['category'] }}</span></td>
                                <td>{{ $row['description'] ?? $row['category'] }}</td>
                                <td class="text-right">{{ $fmt($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Sem gastos no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="muv-table-panel">
                <h3 class="muv-panel-title">Maiores liquidações a motoristas</h3>
                <table class="table table-striped table-condensed muv-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Motorista</th>
                            <th class="text-right">Bruto</th>
                            <th class="text-right">Impostos</th>
                            <th class="text-right">A pagar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['tables']['driver_settlements'] as $row)
                            <tr>
                                <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                                <td>{{ $row['driver'] }}</td>
                                <td class="text-right">{{ $fmt($row['gross']) }}</td>
                                <td class="text-right">{{ $fmt($row['taxes']) }}</td>
                                <td class="text-right">{{ $fmt($row['driver_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Sem extratos validados no período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const muvCharts = @json($report['charts']);
    const euro = (value) => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value || 0);
    const palette = ['#2563eb', '#16a34a', '#f97316', '#dc2626', '#7c3aed', '#0891b2', '#4b5563', '#db2777'];

    new Chart(document.getElementById('muvCashflowChart'), {
        type: 'bar',
        data: {
            labels: muvCharts.labels,
            datasets: [
                { type: 'bar', label: 'Entradas', data: muvCharts.income, backgroundColor: 'rgba(22, 163, 74, .75)' },
                { type: 'bar', label: 'Saídas', data: muvCharts.outcome, backgroundColor: 'rgba(220, 38, 38, .70)' },
                { type: 'line', label: 'Resultado', data: muvCharts.result, borderColor: '#111827', backgroundColor: '#111827', tension: .25 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${euro(ctx.parsed.y)}` } },
                legend: { position: 'bottom' }
            },
            scales: { y: { ticks: { callback: (value) => euro(value) } } }
        }
    });

    function doughnutChart(elementId, rows) {
        new Chart(document.getElementById(elementId), {
            type: 'doughnut',
            data: {
                labels: rows.map((row) => row.label),
                datasets: [{ data: rows.map((row) => row.value), backgroundColor: palette }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${euro(ctx.parsed)}` } },
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    doughnutChart('muvExpenseChart', muvCharts.expense_breakdown);
    doughnutChart('muvIncomeChart', muvCharts.income_breakdown);
</script>
@endsection
