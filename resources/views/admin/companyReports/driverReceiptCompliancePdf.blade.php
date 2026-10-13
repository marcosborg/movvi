<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:5px}.text-right{text-align:right}</style></head><body>
<h2>Recibos dos motoristas ativos</h2>
<p>Período: {{ $report['month'] ?: ($report['weeks']->first()?->start_date.' a '.$report['weeks']->last()?->end_date) }}</p>
@include('admin.companyReports.driverReceiptComplianceTable')
</body></html>
