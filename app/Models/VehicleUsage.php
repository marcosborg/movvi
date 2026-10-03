<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class VehicleUsage extends Model
{
    use SoftDeletes, HasFactory;

    protected static function booted(): void
    {
        static::created(function (VehicleUsage $usage) {
            $usage->recordAudit('created', [], $usage->auditAttributes());
        });

        static::updated(function (VehicleUsage $usage) {
            $changes = collect($usage->getChanges())->except('updated_at')->all();

            if ($changes === []) {
                return;
            }

            $oldValues = collect(array_keys($changes))
                ->mapWithKeys(fn (string $key) => [$key => $usage->getRawOriginal($key)])
                ->all();

            $usage->recordAudit('updated', $oldValues, $changes);
        });

        static::deleted(function (VehicleUsage $usage) {
            $usage->recordAudit('deleted', $usage->auditAttributes(), []);
        });
    }

    public $table = 'vehicle_usages';

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'driver_id',
        'vehicle_item_id',
        'start_date',
        'end_date',
        'usage_exceptions',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const USAGE_EXCEPTIONS_RADIO = [
        'usage'       => 'Utilização',
        'maintenance' => 'Manutenção',
        'accident'    => 'Sinistrado',
        'unassigned'  => 'Sem utilização',
        'personal'    => 'Utilização pessoal',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle_item()
    {
        return $this->belongsTo(VehicleItem::class, 'vehicle_item_id');
    }

    public function audits()
    {
        return $this->hasMany(VehicleUsageAudit::class)->latest();
    }

    protected function auditAttributes(): array
    {
        return collect($this->getAttributes())
            ->only(['driver_id', 'vehicle_item_id', 'start_date', 'end_date', 'usage_exceptions'])
            ->all();
    }

    protected function recordAudit(string $action, array $oldValues, array $newValues): void
    {
        if (! Schema::hasTable('vehicle_usage_audits')) {
            return;
        }

        VehicleUsageAudit::create([
            'vehicle_usage_id' => $this->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
