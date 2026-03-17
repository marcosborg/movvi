<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionAudit extends Model
{
    use HasFactory;

    public $table = 'inspection_audits';

    public $timestamps = false;

    protected $fillable = [
        'inspection_id',
        'user_id',
        'action',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
