<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CombustionTransaction;
use App\Models\Driver;
use App\Models\VehicleUsage;
use Illuminate\Support\Facades\Log;

class CombustionTransactionAssignmentService
{
    public function assign(CombustionTransaction $transaction): CombustionTransaction
    {
        $this->assignWithDiagnostics($transaction);

        return $transaction;
    }

    public function assignWithDiagnostics(CombustionTransaction $transaction, bool $persist = true): array
    {
        $card = $transaction->card
            ? Card::query()->where('code', $transaction->card)->first()
            : null;

        $vehicleItemId = $card?->vehicle_item_id;
        $legacyDriverId = $card ? $this->resolveLegacyDriverFromCard($card->code) : null;
        $usageMatches = [];

        if ($vehicleItemId && $transaction->date) {
            $usageMatches = $this->resolveDriverUsageMatches($vehicleItemId, $transaction->date);
        }

        $decision = $this->buildAssignmentDecision(
            $card !== null,
            $vehicleItemId,
            $transaction->date,
            $usageMatches,
            $legacyDriverId
        );

        $decision['vehicle_item_id'] = $vehicleItemId;
        $decision['current_driver_id'] = $transaction->driver_id;
        $decision['current_vehicle_item_id'] = $transaction->vehicle_item_id;
        $decision['changed'] = (int) $transaction->vehicle_item_id !== (int) $vehicleItemId
            || (int) $transaction->driver_id !== (int) ($decision['driver_id'] ?? null);

        if ($persist) {
            $transaction->forceFill([
                'vehicle_item_id' => $vehicleItemId,
                'driver_id' => $decision['driver_id'],
            ])->save();
        }

        $this->logAssignmentDiagnostics($transaction, $decision);

        return $decision;
    }

    protected function resolveDriverUsageMatches(int $vehicleItemId, $transactionAt): array
    {
        return VehicleUsage::query()
            ->where('vehicle_item_id', $vehicleItemId)
            ->where('start_date', '<=', $transactionAt)
            ->where(function ($query) use ($transactionAt) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $transactionAt);
            })
            ->where(function ($query) {
                $query->whereNull('usage_exceptions')
                    ->orWhere('usage_exceptions', 'usage');
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'driver_id'])
            ->map(fn (VehicleUsage $usage) => [
                'id' => (int) $usage->id,
                'driver_id' => $usage->driver_id !== null ? (int) $usage->driver_id : null,
            ])
            ->all();
    }

    protected function buildAssignmentDecision(
        bool $hasCard,
        ?int $vehicleItemId,
        $transactionAt,
        array $usageMatches,
        ?int $legacyDriverId
    ): array {
        if (!$hasCard) {
            return [
                'status' => 'card_not_found',
                'driver_id' => null,
                'usage_ids' => [],
            ];
        }

        if (!$transactionAt) {
            return [
                'status' => $legacyDriverId ? 'legacy_fallback' : 'no_timestamp',
                'driver_id' => $legacyDriverId,
                'usage_ids' => [],
            ];
        }

        if (!$vehicleItemId) {
            return [
                'status' => 'vehicle_not_mapped',
                'driver_id' => null,
                'usage_ids' => [],
            ];
        }

        if (count($usageMatches) === 0) {
            return [
                'status' => 'no_usage_match',
                'driver_id' => null,
                'usage_ids' => [],
            ];
        }

        if (count($usageMatches) > 1) {
            return [
                'status' => 'multiple_usage_matches',
                'driver_id' => null,
                'usage_ids' => array_column($usageMatches, 'id'),
            ];
        }

        $match = $usageMatches[0];

        if (($match['driver_id'] ?? null) === null) {
            return [
                'status' => 'usage_without_driver',
                'driver_id' => null,
                'usage_ids' => [$match['id']],
            ];
        }

        return [
            'status' => 'assigned',
            'driver_id' => $match['driver_id'],
            'usage_ids' => [$match['id']],
        ];
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

    protected function logAssignmentDiagnostics(CombustionTransaction $transaction, array $decision): void
    {
        if (!in_array($decision['status'], ['no_usage_match', 'multiple_usage_matches', 'usage_without_driver'], true)) {
            return;
        }

        Log::warning('Combustion transaction assignment unresolved', [
            'transaction_id' => $transaction->id,
            'card' => $transaction->card,
            'vehicle_item_id' => $decision['vehicle_item_id'] ?? null,
            'date' => $transaction->date,
            'status' => $decision['status'],
            'usage_ids' => $decision['usage_ids'] ?? [],
        ]);
    }
}
