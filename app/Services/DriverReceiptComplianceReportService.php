<?php

namespace App\Services;

use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\Receipt;
use App\Models\TvdeWeek;
use Carbon\Carbon;

class DriverReceiptComplianceReportService
{
    public function build(int $companyId, ?int $weekId = null, ?string $month = null): array
    {
        $weeks = TvdeWeek::query()
            ->when($weekId, fn ($query) => $query->whereKey($weekId))
            ->when($month, function ($query) use ($month) {
                $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $query->where('start_date', '<=', $end->toDateString())
                    ->where('end_date', '>=', $start->toDateString());
            })
            ->orderBy('start_date')
            ->get();

        $weekIds = $weeks->pluck('id');
        $accounts = CurrentAccount::query()
            ->whereIn('tvde_week_id', $weekIds)
            ->whereHas('driver', fn ($query) => $query->where('company_id', $companyId)->where('state_id', 1))
            ->get()
            ->groupBy('driver_id');
        $receipts = Receipt::query()
            ->whereIn('tvde_week_id', $weekIds)
            ->whereHas('driver', fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($receipt) => $receipt->driver_id . ':' . $receipt->tvde_week_id)
            ->groupBy('driver_id');

        $drivers = Driver::query()
            ->where('company_id', $companyId)
            ->where('state_id', 1)
            ->orderBy('name')
            ->get()
            ->map(function (Driver $driver) use ($weeks, $accounts, $receipts) {
                $driverAccounts = $accounts->get($driver->id, collect())->keyBy('tvde_week_id');
                $driverReceipts = $receipts->get($driver->id, collect())->keyBy('tvde_week_id');
                $rows = [];
                $accumulated = 0.0;

                foreach ($weeks as $week) {
                    $account = $driverAccounts->get($week->id);
                    if (! $account) {
                        continue;
                    }
                    $data = json_decode($account->data ?: '{}');
                    $receipt = $driverReceipts->get($week->id);
                    $transferred = (float) ($receipt->amount_transferred ?? 0);
                    $accumulated = round($accumulated + $transferred, 2);
                    $rows[] = [
                        'week' => $week,
                        'amount_transferred' => $transferred,
                        'vat' => (float) ($data->vat_value ?? 0),
                        'receipt_status' => ! $receipt ? 'Em falta' : ($receipt->verified ? 'Validado' : 'A aguardar validação'),
                        'verified' => (bool) optional($receipt)->verified,
                        'accumulated' => $accumulated,
                    ];
                }

                $applicable = collect($rows);
                return [
                    'driver' => $driver,
                    'weeks' => $rows,
                    'amount_transferred' => round((float) $applicable->sum('amount_transferred'), 2),
                    'vat' => round((float) $applicable->sum('vat'), 2),
                    'accumulated' => $accumulated,
                    'verified_weeks' => $applicable->where('verified', true)->count(),
                    'applicable_weeks' => $applicable->count(),
                    'status' => $applicable->isNotEmpty() && $applicable->every(fn ($row) => $row['verified']) ? 'Em dia' : 'Pendente',
                ];
            })
            ->filter(fn (array $row) => $row['applicable_weeks'] > 0)
            ->values();

        return [
            'drivers' => $drivers,
            'weeks' => $weeks,
            'week_id' => $weekId,
            'month' => $month,
            'totals' => [
                'amount_transferred' => round((float) $drivers->sum('amount_transferred'), 2),
                'vat' => round((float) $drivers->sum('vat'), 2),
                'in_compliance' => $drivers->where('status', 'Em dia')->count(),
                'pending' => $drivers->where('status', 'Pendente')->count(),
            ],
        ];
    }
}
