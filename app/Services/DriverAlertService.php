<?php

namespace App\Services;

use App\Models\DriverAlert;
use Illuminate\Support\Carbon;

class DriverAlertService
{
    public function __construct(protected ReceiptControlService $receiptControlService)
    {
    }

    public function checkMissingReceipts(): int
    {
        $rows = $this->receiptControlService->rows([
            'status' => ReceiptControlService::STATUS_ALL,
        ]);
        $now = Carbon::now();
        $alertCount = 0;
        $upserts = [];
        $resolveIds = [];
        $driverIds = $rows->pluck('driver.id')->filter()->unique();

        if ($driverIds->isEmpty()) {
            return 0;
        }

        $existingAlerts = DriverAlert::whereIn('driver_id', $driverIds)
            ->where('type', 'like', 'missing_receipt_week_%')
            ->get()
            ->keyBy(fn ($alert) => $alert->driver_id . ':' . $alert->type);

        foreach ($rows as $row) {
            $driver = $row['driver'];
            $week = $row['week'];

            if (!$driver || !$week) {
                continue;
            }

            $type = $this->buildAlertType((int) $week->id);
            $alertKey = $driver->id . ':' . $type;
            $existingAlert = $existingAlerts->get($alertKey);

            if ($row['status'] !== ReceiptControlService::STATUS_MISSING) {
                if ($existingAlert && $existingAlert->resolved_at === null) {
                    $resolveIds[] = $existingAlert->id;
                }
                continue;
            }

            $alertCount++;
            $upserts[] = [
                'driver_id' => $driver->id,
                'type' => $type,
                'message' => sprintf(
                    'O condutor %s tem %.2f EUR a receber na semana %s e nao tem recibo associado.',
                    $driver->name,
                    (float) $row['required_value'],
                    $week->start_date . ' a ' . $week->end_date
                ),
                'resolved_at' => null,
                'created_at' => $existingAlert ? $existingAlert->created_at : $now,
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

    protected function buildAlertType(int $tvdeWeekId): string
    {
        return 'missing_receipt_week_' . $tvdeWeekId;
    }
}
