<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DriverReferral extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_JOINED = 'joined';
    public const STATUS_ELIGIBLE = 'eligible';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_SUBMITTED => 'Recebida',
        self::STATUS_JOINED => 'Entrou na Movvi',
        self::STATUS_ELIGIBLE => 'Prémio disponível',
        self::STATUS_PAID => 'Pago',
        self::STATUS_REJECTED => 'Não elegível',
    ];

    protected $fillable = [
        'company_id',
        'referrer_driver_id',
        'referred_driver_id',
        'referred_name',
        'referred_phone',
        'referred_phone_normalized',
        'status',
        'reward_amount',
        'consent_confirmed_at',
        'qualified_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'reward_amount' => 'float',
        'consent_confirmed_at' => 'datetime',
        'qualified_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function referrer()
    {
        return $this->belongsTo(Driver::class, 'referrer_driver_id');
    }

    public function referredDriver()
    {
        return $this->belongsTo(Driver::class, 'referred_driver_id');
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '00351')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '351') && strlen($digits) > 9) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    public function refreshEligibility(): self
    {
        if (in_array($this->status, [self::STATUS_PAID, self::STATUS_REJECTED], true)) {
            return $this;
        }

        $driver = $this->referredDriver;
        if (! $driver) {
            if ($this->status !== self::STATUS_SUBMITTED) {
                $this->forceFill(['status' => self::STATUS_SUBMITTED, 'qualified_at' => null])->save();
            }

            return $this;
        }

        $rawStartDate = $driver->getRawOriginal('start_date');
        $qualificationDate = $rawStartDate ? Carbon::parse($rawStartDate)->startOfDay()->addDays(30) : null;
        $rawEndDate = $driver->getRawOriginal('end_date');
        $completedThirtyDays = $qualificationDate
            && $qualificationDate->isPast()
            && (! $rawEndDate || Carbon::parse($rawEndDate)->endOfDay()->greaterThanOrEqualTo($qualificationDate));

        $nextStatus = $completedThirtyDays ? self::STATUS_ELIGIBLE : self::STATUS_JOINED;
        $nextQualifiedAt = $completedThirtyDays ? $qualificationDate : null;

        $currentQualifiedDate = $this->qualified_at?->toDateString();
        $nextQualifiedDate = $nextQualifiedAt?->toDateString();

        if ($this->status !== $nextStatus || $currentQualifiedDate !== $nextQualifiedDate) {
            $this->forceFill([
                'status' => $nextStatus,
                'qualified_at' => $nextQualifiedAt,
            ])->save();
        }

        return $this;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
