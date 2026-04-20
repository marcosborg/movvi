<?php

namespace App\Services;

use App\Models\CurrentAccount;
use App\Models\DriverAlert;
use App\Models\Receipt;
use Illuminate\Support\Carbon;

class DriverAlertService
{
    public function checkMissingReceipts(): int
    {
        $accounts = CurrentAccount::with(['driver', 'tvde_week'])
            ->whereHas('driver')
            ->get(['id', 'driver_id', 'tvde_week_id', 'data']);

        if ($accounts->isEmpty()) {
            return 0;
        }

        $receiptKeys = Receipt::whereIn('driver_id', $accounts->pluck('driver_id')->filter()->unique())
            ->whereIn('tvde_week_id', $accounts->pluck('tvde_week_id')->filter()->unique())
            ->get(['driver_id', 'tvde_week_id'])
            ->map(fn ($receipt) => $receipt->driver_id . ':' . $receipt->tvde_week_id)
            ->flip();

        $existingAlerts = DriverAlert::whereIn('driver_id', $accounts->pluck('driver_id')->filter()->unique())
            ->where('type', 'like', 'missing_receipt_week_%')
            ->get()
            ->keyBy(fn ($alert) => $alert->driver_id . ':' . $alert->type);

        $now = Carbon::now();
        $alertCount = 0;
        $upserts = [];
        $resolveIds = [];

        foreach ($accounts as $account) {
            if (!$account->driver || !$account->tvde_week_id || !$this->hasIncome($account->data)) {
                continue;
            }

            $type = $this->buildAlertType((int) $account->tvde_week_id);
            $alertKey = $account->driver_id . ':' . $type;
            $receiptKey = $account->driver_id . ':' . $account->tvde_week_id;

            if ($receiptKeys->has($receiptKey)) {
                $existingAlert = $existingAlerts->get($alertKey);

                if ($existingAlert && $existingAlert->resolved_at === null) {
                    $resolveIds[] = $existingAlert->id;
                }

                continue;
            }

            $alertCount++;
            $upserts[] = [
                'driver_id' => $account->driver_id,
                'type' => $type,
                'message' => sprintf(
                    'O condutor %s tem rendimento na semana %s e nao tem recibo associado.',
                    $account->driver->name,
                    $account->tvde_week?->start_date . ' a ' . $account->tvde_week?->end_date
                ),
                'resolved_at' => null,
                'created_at' => $existingAlerts->has($alertKey) ? $existingAlerts->get($alertKey)->created_at : $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($upserts)) {
            DriverAlert::upsert(
                $upserts,
                ['driver_id', 'type'],
                ['message', 'resolved_at', 'updated_at']
            );
        }

        if (!empty($resolveIds)) {
            DriverAlert::whereIn('id', $resolveIds)->update([
                'resolved_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $alertCount;
    }

    protected function hasIncome(?string $data): bool
    {
        $earnings = json_decode($data ?? '', true) ?? [];

        $candidates = [
            $earnings['total_net'] ?? null,
            $earnings['total_after_vat'] ?? null,
            $earnings['total_gross'] ?? null,
        ];

        foreach ($candidates as $value) {
            if ((float) $value > 0) {
                return true;
            }
        }

        return false;
    }

    protected function buildAlertType(int $tvdeWeekId): string
    {
        return 'missing_receipt_week_' . $tvdeWeekId;
    }
}
