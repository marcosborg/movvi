<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaAzulVehicleRevenueExport extends Model
{
    use HasFactory;

    public const STATUS_EXPORTED = 'exported';
    public const STATUS_ERROR = 'error';

    public $table = 'conta_azul_vehicle_revenue_exports';

    protected $casts = [
        'request_payload' => 'array',
        'event_payload' => 'array',
        'installment_payload' => 'array',
        'acquittance_payload' => 'array',
        'exported_at' => 'datetime',
    ];

    protected $fillable = [
        'company_id',
        'tvde_week_id',
        'vehicle_item_id',
        'license_plate',
        'amount',
        'description',
        'status',
        'conta_azul_event_id',
        'conta_azul_installment_id',
        'conta_azul_acquittance_id',
        'request_payload',
        'event_payload',
        'installment_payload',
        'acquittance_payload',
        'error_message',
        'exported_at',
        'exported_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function week()
    {
        return $this->belongsTo(TvdeWeek::class, 'tvde_week_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
