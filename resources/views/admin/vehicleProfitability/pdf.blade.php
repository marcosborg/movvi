<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vehicle Profitability</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; font-size: 12px; }
        h1, h2 { margin: 0 0 8px 0; }
        .section { margin-bottom: 24px; }
        .badge { padding: 4px 8px; border-radius: 4px; color: #fff; font-weight: bold; }
        .badge.positive { background: #2d862d; }
        .badge.neutral { background: #999; }
        .badge.negative { background: #b30000; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .kpi { font-size: 16px; font-weight: bold; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="section">
        <h1>Receitas semanais por viatura</h1>
        <p><strong>Viatura:</strong> {{ $result['vehicle']['license_plate'] }} @if($result['vehicle']['model']) ({{ $result['vehicle']['model'] }}) @endif</p>
        <p><strong>Semana:</strong> {{ $result['week']['start_date'] }} → {{ $result['week']['end_date'] }}</p>
        @if(!empty($result['meta']['missing_current_accounts']))
            <p><strong>Aviso:</strong> Existem motoristas sem dados validados em <code>/admin/company-reports</code> nesta semana.</p>
        @endif
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Receitas por tipo</h2>
        <table>
            <tbody>
                <tr><th>Aluguer (€)</th><td>{{ number_format($result['revenues']['rental_total'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Percentagem (€)</th><td>{{ number_format($result['revenues']['commission_total'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Ajustes operacionais (€)</th><td>{{ number_format($result['revenues']['adjustments_total'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Ajustes gerais (€)</th><td>{{ number_format($result['revenues']['general_adjustments_total'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Diferença faturação mínima (€)</th><td>{{ number_format($result['revenues']['minimum_billing_difference_total'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><th>Total (€)</th><td class="kpi">{{ number_format($result['revenues']['total_revenue'] ?? 0, 2, ',', '.') }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Motoristas</h2>
        <table>
            <thead>
                <tr>
                    <th>Motorista</th>
                    <th>Tipo</th>
                    <th style="text-align:right;">Aluguer</th>
                    <th style="text-align:right;">Percentagem</th>
                    <th style="text-align:right;">Ajustes</th>
                    <th style="text-align:right;">Fat. mínima</th>
                    <th style="text-align:right;">Uso (segundos)</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($result['meta']['drivers'] ?? []) as $d)
                    <tr>
                        <td>{{ $d['name'] ?? ('#' . $d['id']) }}</td>
                        <td>{{ $d['type'] }}</td>
                        <td style="text-align:right;">{{ number_format($d['rental'] ?? 0, 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($d['commission'] ?? 0, 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($d['adjustments'] ?? 0, 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($d['minimum_billing_difference'] ?? 0, 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($d['usage_seconds'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
