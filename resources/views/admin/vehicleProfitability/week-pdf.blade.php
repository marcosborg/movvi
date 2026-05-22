<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rentabilidade semanal por viatura</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
            margin: 0;
        }

        @page {
            margin: 28px 32px;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #d1d5db;
            padding-bottom: 12px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .subtitle {
            margin: 0;
            color: #4b5563;
            font-size: 11px;
        }

        .kpis {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 8px 0 18px 0;
        }

        .kpi-card {
            width: 25%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px 12px;
            vertical-align: top;
        }

        .kpi-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 18px 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
        }

        th {
            background: #eef2f7;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
        }

        td {
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .totals-row td {
            font-weight: 700;
            background: #f8fafc;
        }

        .note {
            margin-top: 12px;
            padding: 10px 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 10px;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $week = $result['week'] ?? [];
        $totals = $result['totals'] ?? [];
        $vehicles = $result['vehicles'] ?? [];
        $weekStart = !empty($week['start_date']) ? \Carbon\Carbon::parse($week['start_date']) : null;
        $weekEnd = !empty($week['end_date']) ? \Carbon\Carbon::parse($week['end_date']) : null;
        $weekNumber = $week['display_number'] ?? ($weekStart ? $weekStart->isoWeek() : '-');
        $weekYear = $week['display_year'] ?? ($weekStart ? $weekStart->isoWeekYear() : '-');
    @endphp

    <div class="header">
        <p class="title">Rentabilidade semanal por viatura</p>
        <p class="subtitle">
            @if($company)
                <strong>{{ $company->name }}</strong> |
            @endif
            Semana {{ $weekNumber }}/{{ $weekYear }}
            | {{ $weekStart ? $weekStart->format('d/m/Y') : '-' }} a {{ $weekEnd ? $weekEnd->format('d/m/Y') : '-' }}
        </p>
    </div>

    <table class="kpis">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Cedência</div>
                <div class="kpi-value">{{ number_format($totals['rental_total'] ?? 0, 2, ',', '.') }} EUR</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Percentual</div>
                <div class="kpi-value">{{ number_format($totals['commission_total'] ?? 0, 2, ',', '.') }} EUR</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Ajustes</div>
                <div class="kpi-value">{{ number_format($totals['adjustments_total'] ?? 0, 2, ',', '.') }} EUR</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Total semanal</div>
                <div class="kpi-value">{{ number_format($totals['total_revenue'] ?? 0, 2, ',', '.') }} EUR</div>
            </td>
        </tr>
    </table>

    <p class="section-title">Resumo por viatura</p>
    <table>
        <thead>
            <tr>
                <th>Matricula</th>
                <th>Modelo</th>
                <th class="text-right">Cedência</th>
                <th class="text-right">Percentual</th>
                <th class="text-right">Ajustes</th>
                <th class="text-right">Total</th>
                <th class="text-right">Motoristas</th>
                <th class="text-right">Sem validacao</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle['license_plate'] ?? '-' }}</td>
                    <td>{{ $vehicle['model'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format($vehicle['rental_total'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($vehicle['commission_total'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($vehicle['adjustments_total'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($vehicle['total_revenue'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($vehicle['drivers_count'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($vehicle['missing_accounts_count'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="2">Totais</td>
                <td class="text-right">{{ number_format($totals['rental_total'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['commission_total'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['adjustments_total'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['total_revenue'] ?? 0, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        <strong>Leitura operacional:</strong>
        <span class="muted">{{ $result['meta']['exclusions']['receipts'] ?? '' }}</span>
        <span class="muted">{{ $result['meta']['exclusions']['reimbursements'] ?? '' }}</span>
    </div>
</body>
</html>
