<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombustionTransaction extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'combustion_transactions';

    public const SUPPLIER_REPSOL = 'repsol';
    public const SUPPLIER_PRIO = 'prio';
    public const SUPPLIER_PRIO_COMBUSTAO = 'prio_combustao';

    public const SUPPLIER_LABELS = [
        self::SUPPLIER_REPSOL => 'Repsol',
        self::SUPPLIER_PRIO => 'Prio',
        self::SUPPLIER_PRIO_COMBUSTAO => 'Prio Combustao',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'tvde_week_id',
        'vehicle_item_id',
        'driver_id',
        'card',
        'supplier',
        'amount',
        'total',
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function tvde_week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function vehicle_item()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id')->withTrashed();
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id')->withTrashed();
    }

    // App\Models\CombustionTransaction.php

    public function cardRef()
    {
        // local key = 'card' (código no abastecimento)
        // owner key = 'code' (código no cartão)
        return $this->belongsTo(Card::class, 'card', 'code')->withTrashed();
    }

    public function getSupplierLabelAttribute(): string
    {
        return self::SUPPLIER_LABELS[$this->supplier] ?? '';
    }

    /** Unidade calculada a partir do tipo do cartão (kWh ou L) */
    public function getUnitAttribute(): string
    {
        $type = strtolower($this->cardRef->type ?? '');
        return (str_contains($type, 'eletric') || str_contains($type, 'electric') || str_contains($type, 'ev'))
            ? 'kWh'
            : 'L';
    }

    /** Scopes úteis */
    public function scopeElectric($q)
    {
        return $q->whereHas('cardRef', function ($qq) {
            $qq->where(function ($w) {
                $w->where('type', 'like', '%eletric%')
                    ->orWhere('type', 'like', '%electric%')
                    ->orWhere('type', 'like', '%EV%');
            });
        });
    }

    public function scopeFuel($q)
    {
        return $q->whereHas('cardRef', function ($qq) {
            $qq->where(function ($w) {
                $w->where('type', 'not like', '%eletric%')
                    ->where('type', 'not like', '%electric%')
                    ->where('type', 'not like', '%EV%')
                    ->orWhereNull('type');
            });
        });
    }


}
