<?php

namespace App\Services;

use App\Models\TvdeActivityEntry;
use App\Models\VehicleUsage;

class TemporalVehicleRevenueAllocator
{
    public function allocate(TvdeActivityEntry $entry): TvdeActivityEntry
    {
        if (! $entry->driver_id) {
            return $this->pending($entry, 'Motorista nao identificado.');
        }

        if (! $entry->occurred_at) {
            return $this->pending($entry, 'Movimento sem data/hora.');
        }

        $matches = VehicleUsage::query()
            ->with('vehicle_item')
            ->where('driver_id', $entry->driver_id)
            ->where('usage_exceptions', 'usage')
            ->where('start_date', '<=', $entry->occurred_at)
            ->where(function ($query) use ($entry) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $entry->occurred_at);
            })
            ->whereHas('vehicle_item', fn ($query) => $query->where('is_service_vehicle', false))
            ->get()
            ->filter(fn ($usage) => $usage->vehicle_item !== null);

        if ($matches->count() !== 1) {
            return $this->pending(
                $entry,
                $matches->isEmpty() ? 'Sem utilizacao operacional correspondente.' : 'Utilizacoes operacionais sobrepostas.'
            );
        }

        $entry->update([
            'vehicle_item_id' => $matches->first()->vehicle_item_id,
            'allocation_status' => 'assigned',
            'allocation_reason' => null,
        ]);

        return $entry->refresh();
    }

    private function pending(TvdeActivityEntry $entry, string $reason): TvdeActivityEntry
    {
        $entry->update(['vehicle_item_id' => null, 'allocation_status' => 'pending', 'allocation_reason' => $reason]);
        return $entry->refresh();
    }
}
