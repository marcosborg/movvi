<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyVehicleEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'weekly_vehicle_evaluations';

    public const FUEL_LEVELS = [
        'full' => 'Cheio (4/4)',
        'three_quarters' => 'Entre meio e Cheio (3/4)',
        'half' => 'Meio tanque (2/4)',
        'quarter' => 'Entre Reserva e Meio (1/4)',
        'reserve' => 'Reserva (Abastecer)',
    ];

    public const TIRE_STATUSES = [
        'ok' => 'Ok',
        'replace' => 'Precisa trocar',
    ];

    public const OIL_LEVELS = [
        'low' => 'Baixo',
        'normal' => 'Normal',
        'high' => 'Elevado',
    ];

    protected $dates = [
        'submitted_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'has_vehicle_issue' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    protected $fillable = [
        'tvde_week_id',
        'driver_id',
        'vehicle_item_id',
        'submitted_by_user_id',
        'final_mileage',
        'fuel_level',
        'front_tire_status',
        'rear_tire_status',
        'oil_level',
        'has_vehicle_issue',
        'issue_notes',
        'submitted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function tvdeWeek()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function getSubmittedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
