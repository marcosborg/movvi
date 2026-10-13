<?php

namespace App\Services;

use App\Models\DriversBalance;
use App\Models\Receipt;
use App\Models\TvdeWeek;
use App\Models\User;
use App\Notifications\ReceiptValidated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReceiptValidationService
{
    public function validate(Receipt $receipt, float $receiptValue, float $amountTransferred, User $actor): Receipt
    {
        [$validatedReceipt, $wasValidatedNow] = DB::transaction(function () use ($receipt, $receiptValue, $amountTransferred, $actor) {
            $lockedReceipt = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($lockedReceipt->verified && $lockedReceipt->balance_applied_at) {
                return [$lockedReceipt, false];
            }

            $balance = DriversBalance::query()
                ->where('driver_id', $lockedReceipt->driver_id)
                ->when($lockedReceipt->tvde_week_id, fn ($query) => $query->where('tvde_week_id', $lockedReceipt->tvde_week_id))
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw ValidationException::withMessages([
                    'receipt' => 'Não existe saldo do motorista para validar este recibo.',
                ]);
            }

            if (! $lockedReceipt->balance_applied_at) {
                $balance->new_balance = round((float) ($balance->new_balance ?? 0) - $receiptValue, 2);
                $balance->save();

                if ($lockedReceipt->tvde_week_id) {
                    $this->propagateBalanceDelta((int) $lockedReceipt->driver_id, (int) $lockedReceipt->tvde_week_id, $receiptValue);
                }
            }

            $lockedReceipt->forceFill([
                'verified' => true,
                'verified_value' => round($receiptValue, 2),
                'amount_transferred' => round($amountTransferred, 2),
                'verified_at' => now(),
                'verified_by' => $actor->id,
                'balance_applied_at' => now(),
                'processed_as' => $lockedReceipt->processed_as ?: 'driver_submission',
            ])->save();

            return [$lockedReceipt->fresh(['driver.user']), true];
        });

        if ($wasValidatedNow) {
            $driver = $validatedReceipt->driver;
            if ($driver?->user) {
                $driver->user->notify(new ReceiptValidated($validatedReceipt));
            } elseif ($driver && filter_var($driver->email, FILTER_VALIDATE_EMAIL)) {
                Notification::route('mail', $driver->email)->notify(new ReceiptValidated($validatedReceipt));
            }
        }

        return $validatedReceipt;
    }

    private function propagateBalanceDelta(int $driverId, int $weekId, float $value): void
    {
        $week = TvdeWeek::find($weekId);
        if (! $week) {
            return;
        }

        DriversBalance::query()
            ->select('drivers_balances.*')
            ->join('tvde_weeks', 'drivers_balances.tvde_week_id', '=', 'tvde_weeks.id')
            ->where('drivers_balances.driver_id', $driverId)
            ->where('tvde_weeks.start_date', '>', $week->getRawOriginal('start_date'))
            ->orderBy('tvde_weeks.start_date')
            ->orderBy('drivers_balances.id')
            ->lockForUpdate()
            ->get()
            ->each(function (DriversBalance $balance) use ($value) {
                $balance->last_balance = round((float) ($balance->last_balance ?? 0) - $value, 2);
                $balance->new_balance = round((float) ($balance->new_balance ?? 0) - $value, 2);
                $balance->save();
            });
    }
}
