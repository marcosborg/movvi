<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovviChargeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'movvi_charge_import_id',
        'driver_id',
        'driver_name',
        'license_plate',
        'sessions',
        'kwh',
        'value',
    ];

    protected $casts = [
        'kwh' => 'float',
        'value' => 'float',
    ];

    public function import()
    {
        return $this->belongsTo(MovviChargeImport::class, 'movvi_charge_import_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
