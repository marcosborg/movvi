<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CombustionTransaction;
use App\Models\Driver;
use App\Models\VehicleUsage;

class CombustionTransactionAssignmentService
{
    public function assign(CombustionTransaction $transaction): CombustionTransaction
    {
        $card = $transaction->card
            ? Card::query()->where('code', $transaction->card)->first()
            : null;

        $vehicleItemId = $card?->vehicle_item_id;
        $driverId = null;

        if ($vehicleItemId) {
            $driverId = $this->resolveDriverFromVehicleUsage($vehicleItemId, $transaction->date);
        }

        if (!$driverId && $card) {
            $driverId = $this->resolveLegacyDriverFromCard($card->code);
        }

        $transaction->forceFill([
            'vehicle_item_id' => $vehicleItemId,
            'driver_id' => $driverId,
        ])->save();

        return $transaction;
    }

    protected function resolveDriverFromVehicleUsage(int $vehicleItemId, $transactionAt): ?int
    {
        if (!$transactionAt) {
            return null;
        }

        $usage = VehicleUsage::query()
            ->where('vehicle_item_id', $vehicleItemId)
            ->where('start_date', '<=', $transactionAt)
            ->where(function ($query) use ($transactionAt) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $transactionAt);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $usage?->driver_id;
    }

    protected function resolveLegacyDriverFromCard(string $cardCode): ?int
    {
        $driver = Driver::query()
            ->where('card_id', function ($query) use ($cardCode) {
                $query->select('id')
                    ->from('cards')
                    ->where('code', $cardCode)
                    ->limit(1);
            })
            ->orWhereHas('cards', function ($query) use ($cardCode) {
                $query->where('code', $cardCode);
            })
            ->orderBy('id')
            ->first();

        return $driver?->id;
    }
}
