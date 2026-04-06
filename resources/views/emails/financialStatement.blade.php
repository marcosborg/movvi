<p>Ola {{ $driver->name }},</p>

<p>Segue em anexo o teu extrato semanal referente a semana {{ $tvde_week->display_number ?? $tvde_week->number }}/{{ $tvde_week->display_year ?? '' }}, de {{ \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m/Y') }}.</p>

<p>Valor semanal sem impostos: <strong>{{ number_format((float) $final_total, 2, ',', '.') }} EUR</strong></p>

<p>Cumprimentos,<br>{{ $company->name ?? config('app.name') }}</p>
