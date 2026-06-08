@php
    $summary = $report['summary'];
    $fmt = fn ($value) => number_format((float) ($value ?? 0), 2, ',', '.') . ' €';
    $pct = fn ($value) => number_format((float) ($value ?? 0), 1, ',', '.') . '%';
    $maxOutcome = max(1, collect($report['outcome_lines'])->max('value') ?: 1);
    $maxIncome = max(1, collect($report['income_lines'])->max('value') ?: 1);
@endphp
<!doctype html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>MUV - Relatório financeiro</title>
    <style>
        @page { margin: 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; margin: 0; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        h2 { font-size: 15px; margin: 22px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { text-align: left; background: #f3f4f6; }
        .muted { color: #6b7280; }
        .cover { background: #111827; color: #fff; padding: 22px; border-radius: 10px; margin-bottom: 18px; }
        .cover p { color: #d1d5db; margin-bottom: 0; }
        .kpi-table td { width: 25%; border: 0; padding: 0 8px 0 0; }
        .kpi { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; min-height: 75px; }
        .kpi-label { color: #6b7280; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        .kpi-value { font-size: 18px; font-weight: bold; margin-top: 8px; }
        .positive { color: #047857; }
        .negative { color: #b91c1c; }
        .section { border: 1px solid #e5e7eb; border-radius: 8px; padding: 13px; margin-bottom: 14px; }
        .bar-row { margin-bottom: 9px; }
        .bar-label { width: 38%; display: inline-block; font-weight: bold; }
        .bar-track { width: 42%; display: inline-block; background: #eef2f7; border-radius: 8px; height: 9px; vertical-align: middle; }
        .bar-fill { display: block; height: 9px; border-radius: 8px; background: #2563eb; }
        .bar-fill.out { background: #dc2626; }
        .bar-value { width: 17%; display: inline-block; text-align: right; }
        .two-col td { width: 50%; border: 0; padding: 0 8px 0 0; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 10px; background: #eef2ff; color: #3730a3; font-size: 9px; font-weight: bold; }
        footer { position: fixed; bottom: -18px; left: 0; right: 0; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <footer>MUV · Relatório financeiro gerado em {{ $report['generated_at']->format('d/m/Y H:i') }}</footer>

    <div class="cover">
        <h1>MUV · Relatório financeiro para investidores</h1>
        <p>
            Período analisado: {{ \Carbon\Carbon::parse($report['filters']['start_date'])->format('d/m/Y') }}
            a {{ \Carbon\Carbon::parse($report['filters']['end_date'])->format('d/m/Y') }}.
            Empresa: {{ $report['filters']['company']?->name ?? 'Todas as empresas' }}.
        </p>
    </div>

    <table class="kpi-table">
        <tr>
            <td><div class="kpi"><div class="kpi-label">Entradas</div><div class="kpi-value">{{ $fmt($summary['income_total']) }}</div></div></td>
            <td><div class="kpi"><div class="kpi-label">Saídas</div><div class="kpi-value">{{ $fmt($summary['outcome_total']) }}</div></div></td>
            <td><div class="kpi"><div class="kpi-label">Resultado</div><div class="kpi-value {{ $summary['estimated_result'] >= 0 ? 'positive' : 'negative' }}">{{ $fmt($summary['estimated_result']) }}</div></div></td>
            <td><div class="kpi"><div class="kpi-label">Margem</div><div class="kpi-value">{{ $pct($summary['margin']) }}</div></div></td>
        </tr>
    </table>

    <div class="section" style="margin-top: 16px;">
        <h2>Resumo executivo</h2>
        <p>
            No período, o projeto registou {{ $fmt($summary['income_total']) }} em entradas consolidadas
            contra {{ $fmt($summary['outcome_total']) }} em saídas, resultando num resultado estimado de
            <strong>{{ $fmt($summary['estimated_result']) }}</strong>.
        </p>
        <p>
            A receita TVDE bruta foi de <strong>{{ $fmt($summary['tvde_gross']) }}</strong>, com receita líquida
            operacional de <strong>{{ $fmt($summary['tvde_net']) }}</strong> após comissões dos operadores.
            A Conta Azul apresenta {{ $fmt($summary['conta_azul_exported_revenue']) }} em receita exportada e
            {{ $fmt($summary['conta_azul_synced_expenses']) }} em despesas sincronizadas no período.
        </p>
    </div>

    <table class="two-col">
        <tr>
            <td>
                <div class="section">
                    <h2>Entradas por origem</h2>
                    @foreach($report['income_lines'] as $row)
                        <div class="bar-row">
                            <span class="bar-label">{{ $row['label'] }}</span>
                            <span class="bar-track"><span class="bar-fill" style="width: {{ min(100, ($row['value'] / $maxIncome) * 100) }}%;"></span></span>
                            <span class="bar-value">{{ $fmt($row['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>Saídas por origem</h2>
                    @foreach($report['outcome_lines'] as $row)
                        <div class="bar-row">
                            <span class="bar-label">{{ $row['label'] }}</span>
                            <span class="bar-track"><span class="bar-fill out" style="width: {{ min(100, ($row['value'] / $maxOutcome) * 100) }}%;"></span></span>
                            <span class="bar-value">{{ $fmt($row['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Reconciliação Conta Azul</h2>
        <table>
            <tr>
                <th>Indicador</th>
                <th style="text-align:right;">Valor</th>
                <th>Nota</th>
            </tr>
            <tr>
                <td>Receitas live Conta Azul</td>
                <td style="text-align:right;">{{ $summary['conta_azul_live_revenue'] !== null ? $fmt($summary['conta_azul_live_revenue']) : 'N/D' }}</td>
                <td>{{ $report['conta_azul']['live']['available'] ? 'Obtido por API para o período.' : ($report['conta_azul']['live']['message'] ?? 'Sem dados live.') }}</td>
            </tr>
            <tr>
                <td>Despesas live Conta Azul</td>
                <td style="text-align:right;">{{ $summary['conta_azul_live_expenses'] !== null ? $fmt($summary['conta_azul_live_expenses']) : 'N/D' }}</td>
                <td>Usado como espelho externo, não duplicado nas linhas internas.</td>
            </tr>
            <tr>
                <td>Receita exportada para Conta Azul</td>
                <td style="text-align:right;">{{ $fmt($summary['conta_azul_exported_revenue']) }}</td>
                <td>{{ $report['conta_azul']['exports_count'] }} exportações; {{ $report['conta_azul']['exports_errors'] }} com erro.</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Maiores gastos no período</h2>
        <table>
            <tr>
                <th>Data</th>
                <th>Origem</th>
                <th>Descrição</th>
                <th style="text-align:right;">Valor</th>
            </tr>
            @forelse($report['tables']['top_expenses'] as $row)
                <tr>
                    <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                    <td><span class="badge">{{ $row['source'] ?? $row['category'] }}</span></td>
                    <td>{{ $row['description'] ?? $row['category'] }}</td>
                    <td style="text-align:right;">{{ $fmt($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;" class="muted">Sem gastos no período.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="section">
        <h2>Notas de leitura para investidores</h2>
        <p class="muted">
            Este relatório junta dados operacionais internos validados por semanas TVDE, despesas registadas no sistema,
            despesas sincronizadas da Conta Azul e exportações de receita para Conta Azul. A secção Conta Azul deve ser
            usada como reconciliação externa, porque algumas receitas exportadas podem corresponder a receitas TVDE já
            refletidas nos dados operacionais.
        </p>
    </div>
</body>
</html>
