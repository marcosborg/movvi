<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminContract extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'admin_contracts';

    protected $dates = [
        'signed_at',
        'generated_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'number',
        'driver_id',
        'contract_template_id',
        'template_version',
        'source_content',
        'rendered_content',
        'data_snapshot',
        'generated_at',
        'generated_by',
        'signed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
        'generated_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getSignedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setSignedAtAttribute($value)
    {
        $this->attributes['signed_at'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }
}
