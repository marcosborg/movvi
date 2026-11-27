<?php

namespace App\Services;

use App\Models\DriversBalance;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaidDriverWeek
{
    public static function assertOpen(?int $driverId, ?int $weekId): void
    {
        if (!$driverId || !$weekId) {
            return;
        }

        self::lockDrivers([$driverId]);

        $paidBalance = DriversBalance::where('driver_id', $driverId)
            ->where('tvde_week_id', $weekId)->where('manual_status', DriversBalance::STATUS_PAID)
            ->lockForUpdate()->first(['id']);
        $paidReceipt = $paidBalance ? null : Receipt::where('driver_id', $driverId)
            ->where('tvde_week_id', $weekId)->where('paid', true)
            ->lockForUpdate()->first(['id']);

        if ($paidBalance || $paidReceipt) {
            throw ValidationException::withMessages([
                'week' => 'Esta semana já está paga para este motorista. Registe o acerto numa semana em aberto.',
            ]);
        }
    }

    public static function assertRecordOpen(Model $record): void
    {
        if ($record->exists) {
            self::assertOpen($record->getOriginal('driver_id'), $record->getOriginal('tvde_week_id'));
        }

        if (!$record->exists || $record->isDirty(['driver_id', 'tvde_week_id'])) {
            self::assertOpen($record->driver_id, $record->tvde_week_id);
        }
    }

    public static function lockDrivers(array $driverIds): void
    {
        DB::table('drivers')->whereIn('id', array_filter($driverIds))
            ->orderBy('id')->lockForUpdate()->get(['id']);
    }
}
