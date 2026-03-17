<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionDamage extends Model
{
    use HasFactory;

    public $table = 'inspection_damages';

    protected $fillable = [
        'inspection_id',
        'origin_damage_id',
        'scope',
        'location',
        'part',
        'part_section',
        'damage_type',
        'notes',
        'is_resolved',
        'resolved_at',
        'resolved_by_user_id',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function photos()
    {
        return $this->hasMany(InspectionDamagePhoto::class, 'damage_id');
    }

    public function inspection()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id');
    }
}
