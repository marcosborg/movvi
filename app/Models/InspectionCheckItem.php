<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionCheckItem extends Model
{
    use HasFactory;

    public $table = 'inspection_check_items';

    protected $fillable = [
        'inspection_id',
        'group_key',
        'item_key',
        'value_bool',
        'value_int',
        'value_text',
        'notes',
    ];

    protected $casts = [
        'value_bool' => 'boolean',
    ];
}
