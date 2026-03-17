<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionSignature extends Model
{
    use HasFactory;

    public $table = 'inspection_signatures';

    protected $fillable = [
        'inspection_id',
        'role',
        'signed_by_name',
        'signed_by_document',
        'signature_path',
        'signature_hash',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}
