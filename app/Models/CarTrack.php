<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarTrack extends Model
{
    use SoftDeletes, HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_VEHICLE_NOT_FOUND = 'vehicle_not_found';
    public const STATUS_NO_USAGE_MATCH = 'no_usage_match';
    public const STATUS_MULTIPLE_USAGE_MATCHES = 'multiple_usage_matches';
    public const STATUS_MISSING_TIMESTAMP = 'missing_timestamp';

    public $table = 'car_tracks';

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'date',
        'license_plate',
        'value',
        'tvde_week_id',
        'vehicle_item_id',
        'driver_id',
        'vehicle_usage_id',
        'assignment_status',
        'assignment_notes',
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

    public function vehicle_usage()
    {
        return $this->belongsTo(VehicleUsage::class, 'vehicle_usage_id');
    }
}
