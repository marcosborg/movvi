<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleRevenueAllocationOverride extends Model
{
    protected $fillable = ['tvde_week_id', 'driver_id', 'vehicle_item_id', 'created_by', 'reason'];

    public function driver() { return $this->belongsTo(Driver::class); }
    public function vehicle_item() { return $this->belongsTo(VehicleItem::class); }
}
