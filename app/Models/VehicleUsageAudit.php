<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleUsageAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_usage_id', 'user_id', 'action', 'old_values', 'new_values',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function vehicleUsage()
    {
        return $this->belongsTo(VehicleUsage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
