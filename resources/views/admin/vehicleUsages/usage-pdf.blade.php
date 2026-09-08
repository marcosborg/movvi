<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Utilização das viaturas</title>
@include('admin.vehicleUsages.usage-style')
<style>
@page { margin:35px 35px 40px; }
body {font-family:DejaVu Sans,sans-serif;font-size:10px;color:#263849;}
h1 {font-size:22px;margin:0 0 8px;}
.usage-table {font-size:9px;table-layout:fixed;}
.usage-table th,.usage-table td {padding:6px;}
.usage-method,.text-muted {font-size:9px;color:#526374;}
.usage-section + .usage-section {page-break-before:always;}
.footer {position:fixed;bottom:-23px;font-size:8px;color:#667;}
</style></head><body>
<div class="footer">Movvi | Utilização das viaturas | {{ $from }} a {{ $to }}</div>
<h1>Utilização das viaturas</h1>
<p>{{ $companyName }} | {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} | {{ count($report['rows']) }} viaturas</p>
<div class="usage-summary">Ocupação do grupo: <strong>{{ number_format($report['fleet']['percent'],2,',','.') }}%</strong> | Dias em utilização: {{ number_format($report['fleet']['usage'],2,',','.') }} | Dias disponíveis (soma das viaturas): {{ number_format($report['fleet']['total'],2,',','.') }}</div>
<p>{{ $activeOnly ? 'Apenas viaturas ativas' : 'Inclui histórico de viaturas inativas' }} | Detalhe por {{ ['years'=>'ano','months'=>'mês','weeks'=>'semana'][$breakdown] }}</p>
@include('admin.vehicleUsages.usage-method')
@if($canViewRevenue)<p><strong>Faturação total: {{ number_format($report['fleet']['revenue'],2,',','.') }} €</strong>{{ $report['fleet']['incomplete'] ? ' *' : '' }} | Média por viatura/dia: {{ number_format($report['fleet']['daily_average'],2,',','.') }} €</p>@endif
@include('admin.vehicleUsages.usage-report', ['pdf'=>true])
</body></html>
