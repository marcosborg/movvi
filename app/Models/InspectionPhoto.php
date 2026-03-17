<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionPhoto extends Model
{
    use HasFactory;

    public $table = 'inspection_photos';

    protected $fillable = [
        'inspection_id',
        'category',
        'slot',
        'path',
        'original_name',
        'mime',
        'size',
        'taken_at',
        'meta_json',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'meta_json' => 'array',
    ];
}
