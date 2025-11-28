<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormInput extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'form_inputs';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'label',
        'name',
        'type',
        'form_name_id',
        'required',
        'position',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const TYPE_RADIO = [
        'text'     => 'Text',
        'number'   => 'Number',
        'date'     => 'Date',
        'textarea' => 'Textarea',
        'checkbox' => 'Checkbox',
        'radio'    => 'Radio',
        'photos'   => 'Photos',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function form_name()
    {
        return $this->belongsTo(FormName::class, 'form_name_id');
    }
}
