<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovviChargeImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tvde_week_id',
        'imported_by',
        'original_filename',
        'file_hash',
        'row_count',
        'total_sessions',
        'total_kwh',
        'total_value',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'total_kwh' => 'float',
        'total_value' => 'float',
    ];

    public function tvdeWeek()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function entries()
    {
        return $this->hasMany(MovviChargeEntry::class);
    }
}
