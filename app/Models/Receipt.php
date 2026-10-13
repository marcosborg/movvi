<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Receipt extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory;

    public $table = 'receipts';

    protected $appends = [
        'file',
    ];

    protected $dates = [
        'verified_at',
        'balance_applied_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'driver_id',
        'value',
        'balance',
        'verified_value',
        'verified',
        'verified_at',
        'verified_by',
        'balance_applied_at',
        'paid',
        'amount_transferred',
        'tvde_week_id',
        'submitted_by_admin',
        'processed_as',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'paid' => 'boolean',
        'verified_at' => 'datetime',
        'balance_applied_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function getFileAttribute()
    {
        return $this->getMedia('file')->last();
    }

    public function tvde_week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

}
