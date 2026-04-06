<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriversBalance extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_PAID = 'paid';
    public const STATUS_NEGATIVE = 'negative';
    public const STATUS_SETTLEMENT = 'settlement';

    public const STATUS_LABELS = [
        self::STATUS_PAID => 'Pago',
        self::STATUS_NEGATIVE => 'Negativo',
        self::STATUS_SETTLEMENT => 'Acerto',
    ];

    public $table = 'drivers_balances';

    protected $casts = [
        'value' => 'float',
        'last_balance' => 'float',
        'new_balance' => 'float',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'driver_id',
        'tvde_week_id',
        'value',
        'last_balance',
        'new_balance',
        'manual_status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'manual_status_label',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function tvde_week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function getManualStatusLabelAttribute(): ?string
    {
        if (!$this->manual_status) {
            return null;
        }

        return self::STATUS_LABELS[$this->manual_status] ?? $this->manual_status;
    }
}
