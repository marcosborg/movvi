<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvdeActivityEntry extends Model
{
    protected $fillable = [
        'tvde_week_id', 'tvde_operator_id', 'company_id', 'driver_id', 'vehicle_item_id',
        'driver_code', 'occurred_at', 'gross', 'net', 'tips', 'allocation_status',
        'allocation_reason', 'source_hash',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function driver() { return $this->belongsTo(Driver::class); }
    public function vehicle_item() { return $this->belongsTo(VehicleItem::class); }
    public function tvde_operator() { return $this->belongsTo(TvdeOperator::class); }
}
