<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAlert extends Model
{
    use HasFactory;

    public $table = 'driver_alerts';

    protected $dates = [
        'resolved_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'driver_id',
        'type',
        'message',
        'resolved_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
