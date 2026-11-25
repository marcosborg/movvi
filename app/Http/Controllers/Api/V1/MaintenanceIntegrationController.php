<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Driver;
use App\Models\VehicleExpense;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaintenanceIntegrationController extends Controller
{
    private const REFERENCE_RULE = 'regex:/^[a-z0-9][a-z0-9._-]{0,79}$/D';
    private const DATETIME_RULE = 'date_format:Y-m-d\TH:i:sP';

    public function vehicles(Request $request)
    {
        return $this->page($request, VehicleItem::where('company_id', $this->company($request)), [
            'id', 'license_plate', 'updated_at',
        ]);
    }

    public function categories(Request $request)
    {
        return response()->json(['data' => collect($this->categoryMap($request))
            ->map(fn ($label, $key) => ['id' => (string) $key, 'label' => $label])->values()]);
    }

    public function usages(Request $request)
    {
        return $this->page($request, $this->records(VehicleUsage::class, $request), [
            'id', 'vehicle_item_id', 'driver_id', 'usage_exceptions', 'start_date', 'end_date', 'updated_at', 'deleted_at',
        ], 'maintenance');
    }

    public function expenses(Request $request)
    {
        return $this->page($request, $this->records(VehicleExpense::class, $request), [
            'id', 'vehicle_item_id', 'expense_type', 'supplier', 'document_number', 'date',
            'description', 'value', 'vat', 'updated_at', 'deleted_at',
        ], 'expense');
    }

    public function openMaintenance(Request $request)
    {
        $data = $request->validate($this->vehicleRules() + [
            'external_reference' => ['required', 'string', self::REFERENCE_RULE],
            'started_at' => ['required', self::DATETIME_RULE],
        ]);

        return $this->create($request, 'maintenance', $data['external_reference'], function () use ($request, $data) {
            $vehicle = $this->resolveVehicle($request, $data);
            $start = $this->localTime($data['started_at']);
            return [
                'vehicle_item_id' => $vehicle->id,
                'driver_id' => null,
                'usage_exceptions' => 'maintenance',
                'start_date' => $start,
                'end_date' => null,
            ];
        }, function (array $values) use ($request) {
            abort_if(VehicleItem::findOrFail($values['vehicle_item_id'])->driver_id !== null, 409,
                'A viatura está atribuída a um motorista.');
            $overlap = VehicleUsage::where('vehicle_item_id', $values['vehicle_item_id'])
                ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $values['start_date']))
                ->exists();
            abort_if($overlap, 409, 'Existe uma utilização sobreposta. Encerra-a no Movvi antes de abrir a manutenção.');

            $driverId = $this->maintenanceDriverId();
            abort_if($driverId !== null && ! Driver::where('company_id', $this->company($request))
                ->whereKey($driverId)->exists(), 409, 'O registo técnico de manutenção não está disponível para esta empresa.');

            return VehicleUsage::create(array_replace($values, ['driver_id' => $driverId]));
        });
    }

    public function closeMaintenanceById(Request $request, string $recordId)
    {
        $recordId = filter_var($recordId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($recordId === false, 404);
        $reference = $this->references($request, 'maintenance')->where('record_id', $recordId)
            ->value('external_reference');
        abort_if($reference === null, 404);

        return $this->closeMaintenance($request, $reference);
    }

    public function closeMaintenance(Request $request, string $reference)
    {
        $data = $request->validate(['ended_at' => ['required', self::DATETIME_RULE]]);

        return DB::transaction(function () use ($request, $reference, $data) {
            $usage = $this->ownedRecord($request, 'maintenance', $reference);
            abort_if($usage->trashed(), 409, 'O período foi anulado.');
            abort_unless($this->isIntegratedMaintenance($usage), 409,
                'O registo deixou de ser uma manutenção da integração.');
            $end = $this->localTime($data['ended_at']);
            $previous = $usage->getRawOriginal('end_date');
            abort_if($end < $usage->getRawOriginal('start_date'), 422, 'O fim é anterior ao início.');
            abort_if($previous && $previous !== $end, 409, 'O período já foi encerrado com outra data.');
            $overlap = VehicleUsage::where('vehicle_item_id', $usage->vehicle_item_id)
                ->where('id', '!=', $usage->id)
                ->where('start_date', '<=', $end)
                ->where(fn ($query) => $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $usage->getRawOriginal('start_date')))
                ->exists();
            abort_if($overlap, 409, 'O fim indicado sobrepõe-se a outra utilização.');
            if (! $previous) {
                $usage->update(['end_date' => $end]);
            }

            return $this->result($usage, $reference);
        });
    }

    public function createExpense(Request $request)
    {
        $data = $request->validate($this->vehicleRules() + $this->expenseRules($request) + [
            'external_reference' => ['required', 'string', self::REFERENCE_RULE],
        ]);

        return $this->create($request, 'expense', $data['external_reference'], function () use ($request, $data) {
            return ['vehicle_item_id' => $this->resolveVehicle($request, $data)->id] + $this->expenseValues($data);
        }, fn ($values) => VehicleExpense::create($values));
    }

    public function updateExpense(Request $request, string $reference)
    {
        $data = $request->validate($this->expenseRules($request));

        return DB::transaction(function () use ($request, $reference, $data) {
            $expense = $this->ownedRecord($request, 'expense', $reference);
            abort_if($expense->trashed(), 409, 'A despesa foi anulada.');
            $expense->update($this->expenseValues($data));

            return $this->result($expense, $reference);
        });
    }

    public function cancelExpense(Request $request, string $reference)
    {
        return $this->cancel($request, 'expense', $reference);
    }

    public function cancelMaintenance(Request $request, string $reference)
    {
        return $this->cancel($request, 'maintenance', $reference);
    }

    private function cancel(Request $request, string $kind, string $reference)
    {
        return DB::transaction(function () use ($request, $kind, $reference) {
            $model = $this->ownedRecord($request, $kind, $reference);
            if ($kind === 'maintenance') {
                abort_unless($this->isIntegratedMaintenance($model), 409);
            }
            if (! $model->trashed()) {
                $model->delete();
            }

            return response()->noContent();
        });
    }

    private function create(Request $request, string $kind, string $reference, callable $values, callable $insert)
    {
        return DB::transaction(function () use ($request, $kind, $reference, $values, $insert) {
            $this->lockCompany($request);
            $attributes = $values();
            $hash = hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
            $mapping = $this->references($request, $kind)->where('external_reference', $reference)->first();
            if ($mapping) {
                abort_unless(hash_equals($mapping->creation_hash, $hash), 409, 'A referência já identifica outro pedido.');
                $model = $this->records($this->modelClass($kind), $request)->findOrFail($mapping->record_id);

                return $this->result($model, $reference);
            }

            $model = $insert($attributes);
            DB::table('maintenance_integration_records')->insert([
                'company_id' => $this->company($request),
                'kind' => $kind,
                'external_reference' => $reference,
                'record_id' => $model->id,
                'creation_hash' => $hash,
            ]);

            return $this->result($model, $reference, 201);
        }, 3);
    }

    private function ownedRecord(Request $request, string $kind, string $reference): VehicleUsage|VehicleExpense
    {
        $this->lockCompany($request);
        $mapping = $this->references($request, $kind)->where('external_reference', $reference)->first();
        abort_if(! $mapping, 404);
        $model = $this->records($this->modelClass($kind), $request)->lockForUpdate()->findOrFail($mapping->record_id);
        VehicleItem::withTrashed()->where('company_id', $this->company($request))
            ->lockForUpdate()->findOrFail($model->vehicle_item_id);

        return $model;
    }

    private function result(VehicleUsage|VehicleExpense $model, string $reference, int $status = 200)
    {
        $fields = $model instanceof VehicleUsage
            ? ['id', 'vehicle_item_id', 'driver_id', 'usage_exceptions', 'start_date', 'end_date', 'deleted_at']
            : ['id', 'vehicle_item_id', 'expense_type', 'value', 'vat', 'date', 'document_number', 'deleted_at'];

        return response()->json([
            'data' => ['external_reference' => $reference] + $model->only($fields),
            'timezone' => config('app.timezone'),
        ], $status);
    }

    private function company(Request $request): int
    {
        return $request->attributes->get('maintenance_company_id');
    }

    private function lockCompany(Request $request): void
    {
        Company::lockForUpdate()->findOrFail($this->company($request));
    }

    private function maintenanceDriverId(): ?int
    {
        return (int) config('maintenance_integration.maintenance_driver_id') ?: null;
    }

    private function isIntegratedMaintenance(VehicleUsage $usage): bool
    {
        return $usage->usage_exceptions === 'maintenance'
            && ($usage->driver_id === null || (int) $usage->driver_id === $this->maintenanceDriverId());
    }

    private function references(Request $request, string $kind)
    {
        return DB::table('maintenance_integration_records')
            ->where('company_id', $this->company($request))
            ->where('kind', $kind);
    }

    private function modelClass(string $kind): string
    {
        return $kind === 'maintenance' ? VehicleUsage::class : VehicleExpense::class;
    }

    private function records(string $class, Request $request): Builder
    {
        return $class::withTrashed()->whereHas('vehicle_item',
            fn ($query) => $query->withTrashed()->where('company_id', $this->company($request)));
    }

    private function page(Request $request, Builder $query, array $fields, ?string $kind = null)
    {
        $data = $request->validate([
            'after_id' => ['sometimes', 'integer', 'min:0'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'updated_since' => ['sometimes', self::DATETIME_RULE],
        ]);
        $limit = $data['per_page'] ?? 50;
        $rows = $query->where('id', '>', $data['after_id'] ?? 0)
            ->when($data['updated_since'] ?? null, function ($query, $since) use ($fields) {
                $threshold = $this->localTime($since);
                $query->where(function ($dates) use ($threshold, $fields) {
                    $dates->where('updated_at', '>=', $threshold);
                    if (in_array('deleted_at', $fields, true)) {
                        $dates->orWhere('deleted_at', '>=', $threshold);
                    }
                });
            })
            ->orderBy('id')->limit($limit + 1)->get($fields);
        $items = $rows->take($limit);
        $references = $kind
            ? DB::table('maintenance_integration_records')->where('company_id', $this->company($request))
                ->where('kind', $kind)->whereIn('record_id', $items->modelKeys())->pluck('external_reference', 'record_id')
            : collect();

        return response()->json([
            'data' => $items->map(function ($row) use ($kind, $fields, $references) {
                $data = $row->only($fields);
                if ($kind) {
                    $data['external_reference'] = $references->get($row->id);
                }
                if ($kind === 'expense') {
                    $data['description'] = html_entity_decode(strip_tags((string) $data['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                return $data;
            })->values(),
            'meta' => [
                'next_after_id' => $rows->count() > $limit ? $items->last()->id : null,
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    private function vehicleRules(): array
    {
        return [
            'vehicle_id' => ['required_without:license_plate', 'prohibits:license_plate', 'integer', 'min:1'],
            'license_plate' => ['required_without:vehicle_id', 'string', 'regex:/^[a-zA-Z0-9 -]{4,20}$/D'],
        ];
    }

    private function resolveVehicle(Request $request, array $data): VehicleItem
    {
        $vehicles = VehicleItem::where('company_id', $this->company($request))->lockForUpdate();
        if (isset($data['vehicle_id'])) {
            return $vehicles->findOrFail($data['vehicle_id']);
        }
        $plate = strtoupper(str_replace(['-', ' '], '', $data['license_plate']));
        $matches = $vehicles->whereRaw("UPPER(REPLACE(REPLACE(license_plate, '-', ''), ' ', '')) = ?", [$plate])
            ->limit(2)->get();
        if ($matches->count() !== 1) {
            throw ValidationException::withMessages(['license_plate' => 'Matrícula inexistente ou ambígua nesta empresa.']);
        }

        return $matches->first();
    }

    private function categoryMap(Request $request): array
    {
        $categories = VehicleExpense::EXPENSE_TYPE_RADIO;
        foreach ($this->records(VehicleExpense::class, $request)->whereNull('deleted_at')
            ->whereNotNull('expense_type')->distinct()->pluck('expense_type') as $category) {
            $categories[$category] ??= $category;
        }

        return $categories;
    }

    private function expenseRules(Request $request): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_keys($this->categoryMap($request)))],
            'net_amount' => ['required', 'numeric', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/D'],
            'vat_rate' => ['required', 'numeric', 'between:0,100', 'regex:/^\d{1,3}(?:\.\d{1,2})?$/D'],
            'date' => ['required', 'date_format:Y-m-d'],
            'document_number' => ['required', 'string', 'max:120'],
            'workshop' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    private function expenseValues(array $data): array
    {
        return [
            'expense_type' => $data['category'],
            'value' => number_format((float) $data['net_amount'], 2, '.', ''),
            'vat' => number_format((float) $data['vat_rate'], 2, '.', ''),
            'date' => $data['date'],
            'document_number' => $data['document_number'],
            'supplier' => $data['workshop'],
            'description' => strip_tags($data['description']),
        ];
    }

    private function localTime(string $timestamp): string
    {
        return CarbonImmutable::parse($timestamp)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
