<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adjustment extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'adjustments';

    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_CAUTION_RECEIVED = 'caucao_recebida';
    public const CATEGORY_CAUTION_RETURNED = 'caucao_devolvida';
    public const CATEGORY_RENT_DISCOUNT = 'abatimento_aluguer';
    public const CATEGORY_MINIMUM_BILLING_DIFFERENCE = 'diferenca_faturacao_minima';
    public const CATEGORY_MANUAL = 'ajuste_manual';
    public const CATEGORY_COMPANY_ENERGY = 'energia_paga_empresa';
    public const CATEGORY_EXCESS_KILOMETERS = 'quilometros_excedentes';

    public const TYPE_RADIO = [
        'deduct' => 'Deduct',
        'refund' => 'Refund',
    ];

    public const CATEGORY_SELECT = [
        self::CATEGORY_GENERAL => 'Geral',
        self::CATEGORY_CAUTION_RECEIVED => 'Caucao recebida',
        self::CATEGORY_CAUTION_RETURNED => 'Caucao devolvida',
        self::CATEGORY_RENT_DISCOUNT => 'Abatimento de cedência',
        self::CATEGORY_MINIMUM_BILLING_DIFFERENCE => 'Diferenca de faturacao minima',
        self::CATEGORY_MANUAL => 'Ajuste manual',
        self::CATEGORY_COMPANY_ENERGY => 'Energia paga pela empresa',
        self::CATEGORY_EXCESS_KILOMETERS => 'Quilometros excedentes',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'category_label',
    ];

    protected $fillable = [
        'name',
        'type',
        'amount',
        'percent',
        'category',
        'start_date',
        'end_date',
        'company_id',
        'company_expense',
        'fleet_management',
        'affects_vehicle_profitability',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getCategoryLabelAttribute(): string
    {
        $category = $this->attributes['category'] ?? self::CATEGORY_GENERAL;

        return self::CATEGORY_SELECT[$category] ?? self::CATEGORY_SELECT[self::CATEGORY_GENERAL];
    }

    public function drivers()
    {
        return $this->belongsToMany(Driver::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
