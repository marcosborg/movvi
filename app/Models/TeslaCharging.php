<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeslaCharging extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'tesla_chargings';

    public const CARD_TYPE_SELECT = [
        'Tesla' => 'Tesla',
        'Continente' => 'Continente',
        'Auchan' => 'Auchan',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'value',
        'license',
        'datetime',
        'card_type',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

}
