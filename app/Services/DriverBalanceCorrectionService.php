<?php

namespace App\Services;

use App\Models\DriversBalance;
use App\Models\TvdeWeek;
use Illuminate\Support\Facades\DB;

class DriverBalanceCorrectionService
{
    public function correct(int $balanceId, float $newBalance): void
    {
        DB::transaction(function () use ($balanceId, $newBalance) {
            $balance = DriversBalance::lockForUpdate()->findOrFail($balanceId);
            $week = TvdeWeek::findOrFail($balance->tvde_week_id);
            $balance->new_balance = round($newBalance, 2);
            $balance->save();

            $following = DriversBalance::query()
                ->select('drivers_balances.*')
                ->join('tvde_weeks', 'drivers_balances.tvde_week_id', '=', 'tvde_weeks.id')
                ->where('drivers_balances.driver_id', $balance->driver_id)
                ->where('tvde_weeks.start_date', '>', $week->getRawOriginal('start_date'))
                ->whereNull('tvde_weeks.deleted_at')
                ->orderBy('tvde_weeks.start_date')
                ->orderBy('drivers_balances.id')
                ->lockForUpdate()
                ->get();

            $carry = $balance->new_balance;
            foreach ($following as $next) {
                // Preserve the week's earnings, payments and adjustments. Reconcile
                // the carry even when an earlier correction was already saved.
                $movement = round($next->new_balance - $next->last_balance, 2);
                $next->last_balance = $carry;
                $next->new_balance = round($carry + $movement, 2);
                if ($next->isDirty()) {
                    $next->save();
                }
                $carry = $next->new_balance;
            }
        });
    }
}
