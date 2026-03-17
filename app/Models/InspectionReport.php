<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionReport extends Model
{
    use HasFactory;

    public $table = 'inspection_reports';

    protected $fillable = [
        'inspection_id',
        'pdf_path',
        'pdf_hash',
        'snapshot_json',
        'generated_at',
        'immutable',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'generated_at' => 'datetime',
        'immutable' => 'boolean',
    ];
}
