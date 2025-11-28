<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleExpense extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory;

    protected $appends = [
        'files',
    ];

    public $table = 'vehicle_expenses';

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const EXPENSE_TYPE_RADIO = [
        'Manutenção' => 'Manutenção',
        'Bate-chapa' => 'Bate-chapa',
        'Penus'      => 'Pneus',
        'Rent'       => 'Rent',
        'Seguro'     => 'Seguro',
        'Aquisição da viatura' => 'Aquisição da viatura',
        'Inspeção' => 'Inspeção',
        'IUC' => 'IUC',
        'Limpeza' => 'Limpeza',
        'Acessórios' => 'Acessórios',
        'Distico verde' => 'Distico verde',
        'Outros'     => 'Outros',
    ];

    protected $fillable = [
        'vehicle_item_id',
        'expense_type',
        'date',
        'description',
        'value',
        'vat',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getFilesAttribute()
    {
        return $this->getMedia('files');
    }

    public function vehicle_item()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }
}
