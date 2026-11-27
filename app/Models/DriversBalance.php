<?php

namespace App\Models;

use DateTimeInterface;
use App\Services\PaidDriverWeek;
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

    protected static function booted(): void
    {
        static::saving(function (self $balance) {
            if (!$balance->exists || $balance->isDirty([
                'driver_id', 'tvde_week_id', 'value', 'last_balance', 'new_balance', 'manual_status', 'deleted_at',
            ])) {
                PaidDriverWeek::assertRecordOpen($balance);
            }
        });
        static::deleting(fn (self $balance) => PaidDriverWeek::assertRecordOpen($balance));
    }

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
