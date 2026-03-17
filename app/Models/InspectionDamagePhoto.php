<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionDamagePhoto extends Model
{
    use HasFactory;

    public $table = 'inspection_damage_photos';

    protected $fillable = [
        'damage_id',
        'path',
        'original_name',
        'mime',
        'size',
        'taken_at',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];
}
