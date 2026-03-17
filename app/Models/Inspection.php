<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inspection extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'inspections';

    protected $fillable = [
        'type',
        'vehicle_id',
        'driver_id',
        'created_by_user_id',
        'responsible_user_id',
        'status',
        'current_step',
        'previous_inspection_id',
        'location_lat',
        'location_lng',
        'location_text',
        'location_accuracy',
        'location_timezone',
        'started_at',
        'completed_at',
        'locked_at',
        'extra_observations',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'locked_at' => 'datetime',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'location_accuracy' => 'decimal:2',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function previousInspection()
    {
        return $this->belongsTo(self::class, 'previous_inspection_id');
    }

    public function stepStates()
    {
        return $this->hasMany(InspectionStepState::class, 'inspection_id');
    }

    public function checkItems()
    {
        return $this->hasMany(InspectionCheckItem::class, 'inspection_id');
    }

    public function photos()
    {
        return $this->hasMany(InspectionPhoto::class, 'inspection_id');
    }

    public function damages()
    {
        return $this->hasMany(InspectionDamage::class, 'inspection_id');
    }

    public function signatures()
    {
        return $this->hasMany(InspectionSignature::class, 'inspection_id');
    }

    public function report()
    {
        return $this->hasOne(InspectionReport::class, 'inspection_id');
    }

    public function audits()
    {
        return $this->hasMany(InspectionAudit::class, 'inspection_id');
    }
}
