<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyVehicleMileage extends Model
{
    use HasFactory;

    public $table = 'weekly_vehicle_mileages';

    protected $dates = [
        'source_period_start',
        'source_period_end',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'tvde_week_id',
        'license_plate',
        'description',
        'odometer_start',
        'odometer_end',
        'distance_km',
        'source_period_start',
        'source_period_end',
        'created_at',
        'updated_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function tvde_week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function getSourcePeriodStartAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function setSourcePeriodStartAttribute($value)
    {
        $this->attributes['source_period_start'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getSourcePeriodEndAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function setSourcePeriodEndAttribute($value)
    {
        $this->attributes['source_period_end'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
