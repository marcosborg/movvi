<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionStepState extends Model
{
    use HasFactory;

    public $table = 'inspection_step_states';

    protected $fillable = [
        'inspection_id',
        'step',
        'is_completed',
        'completed_at',
        'completed_by_user_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];
}
