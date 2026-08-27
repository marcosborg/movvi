<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Inspection;
use App\Models\InspectionCheckItem;
use App\Models\InspectionDamage;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\Inspections\InspectionWorkflowService;
use App\Services\Inspections\InspectionVehicleUsageService;
use App\Support\InspectionRoutineConfig;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MobileInspectionController extends Controller
{
    private const ACCESSORY_ITEMS = [
        'via_verde' => 'Via Verde / identificador eletronico',
        'charging_cable' => 'Cabo(s) de carregamento',
        'charging_adapter' => 'Adaptadores de carregamento',
        'spare_tire' => 'Pneu suplente',
        'anti_puncture_kit' => 'Kit anti-furos',
        'jack_wrench' => 'Macaco e chave de rodas',
        'warning_triangle' => 'Triangulo de sinalizacao',
        'reflective_vest' => 'Colete refletor',
    ];

    public function __construct(
        private InspectionWorkflowService $workflow,
        private InspectionVehicleUsageService $vehicleUsage
    )
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->ensureUserIsAdmin($request);

        $query = Inspection::query()
            ->with(['vehicle:id,license_plate', 'driver:id,name'])
            ->orderByDesc('id');

        $statusFilter = (string) $request->input('status', '');

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        } elseif ($statusFilter !== 'all') {
            $query->whereIn('status', ['draft', 'in_progress', 'ready_to_sign', 'signed']);
        }

        $inspections = $query->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'data' => $inspections->getCollection()->map(function (Inspection $inspection) {
                $transferContext = $this->resolveTransferContext($inspection);

                return [
                    'id' => (int) $inspection->id,
                    'type' => (string) $inspection->type,
                    'type_label' => (string) config('inspections.type_labels.' . $inspection->type, $inspection->type),
                    'status' => (string) $inspection->status,
                    'status_label' => (string) config('inspections.status_labels.' . $inspection->status, $inspection->status),
                    'current_step' => (int) $inspection->current_step,
                    'vehicle' => [
                        'id' => $inspection->vehicle_id ? (int) $inspection->vehicle_id : null,
                        'license_plate' => $inspection->vehicle->license_plate ?? null,
                    ],
                    'driver' => [
                        'id' => $inspection->driver_id ? (int) $inspection->driver_id : null,
                        'name' => $inspection->driver->name ?? null,
                    ],
                    'started_at' => optional($inspection->started_at)->toDateTimeString(),
                    'locked_at' => optional($inspection->locked_at)->toDateTimeString(),
                    'transfer_context' => $transferContext,
                ];
            })->values(),
            'meta' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
                'is_admin' => true,
                'can_create' => true,
            ],
        ]);
    }

    public function show(Request $request, Inspection $inspection)
    {
        $this->ensureUserIsAdmin($request);

        $inspection->load([
            'vehicle.vehicle_brand',
            'vehicle.vehicle_model',
            'driver',
            'photos',
            'damages.photos',
            'signatures',
            'stepStates',
            'checkItems',
            'report',
        ]);
        $transferContext = $this->resolveTransferContext($inspection);

        $checklist = [];
        foreach ($inspection->checkItems as $item) {
            if ($item->value_int !== null) {
                $checklist[$item->group_key][$item->item_key] = (int) $item->value_int;
                continue;
            }
            if ($item->value_text !== null) {
                $checklist[$item->group_key][$item->item_key] = (string) $item->value_text;
                continue;
            }
            $checklist[$item->group_key][$item->item_key] = $item->value_bool === null ? null : (bool) $item->value_bool;
        }

        $routineConfig = $this->resolveRoutineConfig($inspection);
        $requiredSlots = [
            'exterior' => $routineConfig['exterior_slots'],
            'interior' => $routineConfig['interior_slots'],
        ];

        return response()->json([
            'inspection' => [
                'id' => (int) $inspection->id,
                'type' => (string) $inspection->type,
                'type_label' => (string) config('inspections.type_labels.' . $inspection->type, $inspection->type),
                'status' => (string) $inspection->status,
                'status_label' => (string) config('inspections.status_labels.' . $inspection->status, $inspection->status),
                'current_step' => (int) $inspection->current_step,
                'extra_observations' => $inspection->extra_observations,
                'location' => [
                    'text' => $inspection->location_text,
                    'lat' => $inspection->location_lat,
                    'lng' => $inspection->location_lng,
                ],
                'vehicle' => [
                    'id' => $inspection->vehicle_id ? (int) $inspection->vehicle_id : null,
                    'license_plate' => $inspection->vehicle->license_plate ?? null,
                    'brand' => $inspection->vehicle->vehicle_brand->name ?? null,
                    'model' => $inspection->vehicle->vehicle_model->name ?? null,
                    'year' => $inspection->vehicle->year ?? null,
                ],
                'driver' => [
                    'id' => $inspection->driver_id ? (int) $inspection->driver_id : null,
                    'name' => $inspection->driver->name ?? null,
                ],
                'report_pdf_url' => $inspection->report ? asset('storage/' . $inspection->report->pdf_path) : null,
                'transfer_context' => $transferContext,
                'enabled_steps' => $this->enabledStepsForRoutine($routineConfig),
                'routine_preset' => $this->resolveRoutinePreset($inspection),
            ],
            'driver_options' => Driver::query()
                ->where('state_id', '!=', 2)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Driver $driver) => [
                    'id' => (int) $driver->id,
                    'name' => (string) $driver->name,
                ])
                ->values(),
            'steps' => config('inspections.step_labels'),
            'required_slots' => $requiredSlots,
            'slot_labels' => [
                'exterior' => array_intersect_key((array) config('inspections.slot_labels.exterior', []), array_flip($requiredSlots['exterior'])),
                'interior' => array_intersect_key((array) config('inspections.slot_labels.interior', []), array_flip($requiredSlots['interior'])),
            ],
            'document_keys' => $routineConfig['documents'],
            'operational_checks' => $routineConfig['operational_checks'],
            'accessory_keys' => $routineConfig['accessories'],
            'damage_locations' => config('inspections.damage_locations'),
            'damage_types' => config('inspections.damage_types'),
            'damage_parts' => config('inspections.damage_parts'),
            'checklist' => $checklist,
            'photos' => $inspection->photos->map(function ($photo) {
                return [
                    'id' => (int) $photo->id,
                    'category' => (string) $photo->category,
                    'slot' => $photo->slot,
                    'url' => asset('storage/' . $photo->path),
                    'original_name' => $photo->original_name,
                ];
            })->values(),
            'damages' => $inspection->damages->map(function (InspectionDamage $damage) {
                return [
                    'id' => (int) $damage->id,
                    'scope' => (string) $damage->scope,
                    'location' => (string) $damage->location,
                    'part' => (string) $damage->part,
                    'part_section' => $damage->part_section,
                    'damage_type' => (string) $damage->damage_type,
                    'notes' => $damage->notes,
                    'is_resolved' => (bool) $damage->is_resolved,
                    'photos' => $damage->photos->map(function ($photo) {
                        return [
                            'id' => (int) $photo->id,
                            'url' => asset('storage/' . $photo->path),
                            'original_name' => $photo->original_name,
                        ];
                    })->values(),
                ];
            })->values(),
            'signatures' => [
                'responsible' => optional($inspection->signatures->firstWhere('role', 'responsible'))->signed_by_name,
                'driver' => optional($inspection->signatures->firstWhere('role', 'driver'))->signed_by_name,
            ],
            'meta' => [
                'is_admin' => true,
            ],
        ]);
    }

    public function createOptions(Request $request)
    {
        $this->ensureUserCanManageTransfers($request);
        $validated = $request->validate([
            'show_all' => ['nullable', 'boolean'],
        ]);

        $showAll = filter_var($validated['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $now = now();

        $activeUsages = VehicleUsage::query()
            ->with('driver:id,name')
            ->where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->orderByDesc('start_date')
            ->get();
        $activeUsagesByVehicle = $activeUsages
            ->unique('vehicle_item_id')
            ->keyBy('vehicle_item_id');
        $activeUsagesByDriver = $activeUsages
            ->filter(fn (VehicleUsage $usage) => !empty($usage->driver_id))
            ->unique('driver_id')
            ->keyBy('driver_id');
        $vehiclesById = VehicleItem::query()
            ->where(function ($query) {
                $query->whereNull('suspended')->orWhere('suspended', false)->orWhere('suspended', 0);
            })
            ->get(['id', 'license_plate'])
            ->keyBy('id');

        return response()->json([
            'types' => collect(array_values((array) config('inspections.types', [])))
                ->map(fn ($key) => ['key' => (string) $key, 'label' => (string) config('inspections.type_labels.' . $key, $key)])
                ->values(),
            'routine_presets' => collect(InspectionRoutineConfig::presets())
                ->map(fn (array $preset, string $key) => [
                    'key' => $key,
                    'label' => (string) ($preset['label'] ?? $key),
                ])
                ->values(),
            'vehicles' => VehicleItem::query()
                ->where(function ($query) {
                    $query->whereNull('suspended')->orWhere('suspended', false)->orWhere('suspended', 0);
                })
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'driver_id'])
                ->map(function (VehicleItem $vehicle) use ($activeUsagesByVehicle) {
                    $activeUsage = $activeUsagesByVehicle->get($vehicle->id);

                    return [
                        'id' => (int) $vehicle->id,
                        'license_plate' => (string) $vehicle->license_plate,
                        'driver_id' => $activeUsage?->driver_id ? (int) $activeUsage->driver_id : null,
                        'driver_name' => $activeUsage?->driver?->name,
                    ];
                })
                ->when(!$showAll, fn ($collection) => $collection->filter(fn (array $vehicle) => empty($vehicle['driver_id'])))
                ->values(),
            'drivers' => Driver::query()
                ->where('state_id', '!=', 2)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function (Driver $driver) use ($activeUsagesByDriver, $vehiclesById) {
                    $activeUsage = $activeUsagesByDriver->get($driver->id);
                    $currentVehicle = $activeUsage?->vehicle_item_id
                        ? $vehiclesById->get($activeUsage->vehicle_item_id)
                        : null;

                    return [
                        'id' => (int) $driver->id,
                        'name' => (string) $driver->name,
                        'current_vehicle_id' => $currentVehicle?->id ? (int) $currentVehicle->id : null,
                        'current_vehicle_license_plate' => $currentVehicle?->license_plate,
                    ];
                })
                ->when(!$showAll, fn ($collection) => $collection->filter(fn (array $driver) => empty($driver['current_vehicle_id'])))
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->ensureUserIsAdmin($request);

        $validated = Validator::make($request->all(), [
            'type' => ['required', 'in:handover,return'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicle_items,id'],
            'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('state_id', '!=', 2))],
            'routine_preset' => ['nullable', Rule::in(array_keys(InspectionRoutineConfig::presets()))],
            'source_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'transfer_mode' => ['nullable', 'in:entrega,recolha,passagem'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'location_accuracy' => ['nullable', 'numeric'],
            'location_timezone' => ['nullable', 'string', 'max:60'],
        ])->validate();

        $routinePreset = (string) ($validated['routine_preset'] ?? 'full');
        $validated['routine_preset'] = $routinePreset;
        $validated['routine_config'] = InspectionRoutineConfig::preset($routinePreset);

        $inspection = $this->workflow->create($validated, $user);

        return response()->json([
            'message' => 'Inspecao iniciada com sucesso.',
            'inspection_id' => (int) $inspection->id,
            'current_step' => (int) $inspection->current_step,
        ], Response::HTTP_CREATED);
    }

    public function storeTransfer(Request $request)
    {
        $this->ensureUserCanManageTransfers($request);

        $validated = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'integer', 'exists:vehicle_items,id'],
            'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('state_id', '!=', 2))],
            'source_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'transfer_mode' => ['required', 'in:entrega,recolha,passagem'],
        ])->validate();

        $vehicle = VehicleItem::query()->findOrFail((int) $validated['vehicle_id']);
        $plan = $this->vehicleUsage->applyDirectTransfer($vehicle, [
            'mode' => $validated['transfer_mode'],
            'source_driver_id' => $validated['source_driver_id'] ?? null,
            'target_driver_id' => $validated['driver_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Operacao registada com sucesso sem inspecao.',
            'transfer' => [
                'vehicle_id' => (int) $vehicle->id,
                'driver_id' => $vehicle->driver_id ? (int) $vehicle->driver_id : null,
                'mode' => $plan['mode'],
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Inspection $inspection)
    {
        $this->ensureUserIsAdmin($request);

        if ($inspection->locked_at || in_array((string) $inspection->status, ['closed', 'completed'], true)) {
            throw ValidationException::withMessages([
                'inspection' => 'So pode eliminar inspecoes ainda em curso.',
            ]);
        }

        $inspection->load(['photos', 'damages.photos', 'signatures', 'report']);

        foreach ($inspection->photos as $photo) {
            if ($photo->path) {
                Storage::disk('public')->delete($photo->path);
            }
        }

        foreach ($inspection->damages as $damage) {
            foreach ($damage->photos as $photo) {
                if ($photo->path) {
                    Storage::disk('public')->delete($photo->path);
                }
            }
        }

        foreach ($inspection->signatures as $signature) {
            if ($signature->signature_path) {
                Storage::disk('public')->delete($signature->signature_path);
            }
        }

        if ($inspection->report?->pdf_path) {
            Storage::disk('public')->delete($inspection->report->pdf_path);
        }

        $inspection->forceDelete();

        return response()->json([
            'message' => 'Inspecao eliminada com sucesso.',
        ]);
    }

    public function updateStep(Request $request, Inspection $inspection)
    {
        $this->ensureUserIsAdmin($request);

        $validated = Validator::make($request->all(), [
            'step' => ['required', 'integer', 'min:1', 'max:12'],
            'action' => ['nullable', 'in:save,complete'],
            'driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('state_id', '!=', 2))],
            'location' => ['nullable', 'string', 'max:30'],
            'part' => ['nullable', 'string', 'max:120'],
            'part_section' => ['nullable', 'string', 'max:120'],
            'damage_type' => ['nullable', 'string', 'max:40'],
            'damage_notes' => ['nullable', 'string'],
            'damage_photo' => ['nullable', 'array'],
            'damage_photo.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'inspector_name' => ['nullable', 'string', 'max:255'],
            'driver_signature_name' => ['nullable', 'string', 'max:255'],
            'inspector_signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'driver_signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'inspector_signature_data' => ['nullable', 'string'],
            'driver_signature_data' => ['nullable', 'string'],
            'extra_observations' => ['nullable', 'string'],
            'extra_photos' => ['nullable', 'array'],
            'extra_photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ])->validate();

        $step = (int) $validated['step'];
        $action = $validated['action'] ?? 'save';
        $routineConfig = $this->resolveRoutineConfig($inspection);

        if ($step > (int) $inspection->current_step) {
            throw ValidationException::withMessages(['step' => 'Nao pode avancar sem concluir etapas anteriores.']);
        }

        if ($step === 2 && !empty($validated['driver_id'])) {
            $inspection->update(['driver_id' => $validated['driver_id']]);
        }

        if (in_array($step, [3, 4, 5], true)) {
            $checklistInput = (array) $request->input('checklist', []);
            $checklistPhotos = (array) $request->file('checklist_photos', []);

            $this->storeChecklist($inspection, $checklistInput);
            $this->storeChecklistPhotos($inspection, $checklistPhotos);
            $this->enforcePanelWarningEvidence($inspection, $checklistInput, $checklistPhotos, $routineConfig);
            if ($step === 5 && $action === 'complete') {
                $this->enforceAccessoryEvidence($inspection, $checklistInput, $checklistPhotos, $routineConfig);
            }
        }

        if ($step === 6) {
            $this->storeSlotPhotos($inspection, (array) $request->file('exterior_photos', []), 'exterior');
        }

        if ($step === 7) {
            $this->storeSlotPhotos($inspection, (array) $request->file('interior_photos', []), 'interior');
        }

        if (in_array($step, [8, 9], true)) {
            $hasDamageInput = !empty($validated['location'])
                || !empty($validated['part'])
                || !empty($validated['part_section'])
                || !empty($validated['damage_type'])
                || !empty($validated['damage_notes'])
                || $request->hasFile('damage_photo');

            if ($hasDamageInput) {
                if (empty($validated['location']) || empty($validated['part']) || empty($validated['damage_type'])) {
                    throw ValidationException::withMessages([
                        'damage' => 'Para guardar um dano, preencha Local, Peca e Tipo.',
                    ]);
                }
                if (!$request->hasFile('damage_photo')) {
                    throw ValidationException::withMessages([
                        'damage_photo' => 'Cada dano deve ter pelo menos 1 foto.',
                    ]);
                }

                $this->workflow->addDamage($inspection, [
                    'scope' => $step === 8 ? 'exterior' : 'interior',
                    'location' => $validated['location'],
                    'part' => $validated['part'],
                    'part_section' => $validated['part_section'] ?? null,
                    'damage_type' => $validated['damage_type'],
                    'notes' => $validated['damage_notes'] ?? null,
                    'damage_photos' => (array) $request->file('damage_photo', []),
                ]);
            } elseif ($action === 'save') {
                throw ValidationException::withMessages([
                    'damage' => 'Nenhum dano foi submetido. Preencha os campos e anexe uma foto.',
                ]);
            }
        }

        if ($step === 10) {
            $inspection->update(['extra_observations' => $validated['extra_observations'] ?? null]);
            if ($request->hasFile('extra_photos')) {
                foreach ((array) $request->file('extra_photos') as $extraPhoto) {
                    if ($extraPhoto) {
                        $this->workflow->uploadPhoto($inspection, $extraPhoto, 'extra');
                    }
                }
            }
        }

        if ($step === 11) {
            $inspectorSignatureFile = $request->file('inspector_signature');
            $driverSignatureFile = $request->file('driver_signature');
            $inspectorSignatureData = $request->input('inspector_signature_data');
            $driverSignatureData = $request->input('driver_signature_data');

            if (!empty($validated['inspector_name']) || $inspectorSignatureFile || !empty($inspectorSignatureData)) {
                $responsibleName = (string) ($validated['inspector_name'] ?? optional($inspection->signatures->firstWhere('role', 'responsible'))->signed_by_name ?? '');
                if ($responsibleName === '') {
                    throw ValidationException::withMessages([
                        'inspector_name' => 'Indique o nome do responsavel para assinar.',
                    ]);
                }

                $this->workflow->sign(
                    $inspection,
                    'responsible',
                    $responsibleName,
                    null,
                    $inspectorSignatureFile,
                    $inspectorSignatureData
                );
            }

            if (!empty($validated['driver_signature_name']) || $driverSignatureFile || !empty($driverSignatureData)) {
                $driverName = (string) ($validated['driver_signature_name'] ?? optional($inspection->signatures->firstWhere('role', 'driver'))->signed_by_name ?? '');
                if ($driverName === '') {
                    throw ValidationException::withMessages([
                        'driver_signature_name' => 'Indique o nome do condutor para assinar.',
                    ]);
                }

                $this->workflow->sign(
                    $inspection,
                    'driver',
                    $driverName,
                    null,
                    $driverSignatureFile,
                    $driverSignatureData
                );
            }
        }

        if ($action === 'complete' && $step < 12) {
            $this->workflow->completeStep($inspection, $step, [
                'extra_observations' => $validated['extra_observations'] ?? null,
            ]);
        }

        $inspection->refresh();

        return response()->json([
            'message' => $action === 'complete' ? 'Etapa concluida com sucesso.' : 'Dados guardados com sucesso.',
            'inspection_id' => (int) $inspection->id,
            'current_step' => (int) $inspection->current_step,
            'status' => (string) $inspection->status,
        ]);
    }

    public function backStep(Request $request, Inspection $inspection)
    {
        $this->ensureUserIsAdmin($request);

        if ($inspection->locked_at) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspecao fechada nao pode regredir etapas.',
            ]);
        }

        if ((int) $inspection->current_step <= 1) {
            throw ValidationException::withMessages([
                'inspection' => 'A inspecao ja esta na primeira etapa.',
            ]);
        }

        $inspection->update([
            'current_step' => $this->workflow->previousEnabledStep($inspection, (int) $inspection->current_step),
            'status' => 'in_progress',
        ]);

        return response()->json([
            'message' => 'Regrediu para a etapa anterior.',
            'current_step' => (int) $inspection->current_step,
        ]);
    }

    public function resolveDamage(Request $request, Inspection $inspection, InspectionDamage $damage)
    {
        $this->ensureUserIsAdmin($request);

        if ((int) $damage->inspection_id !== (int) $inspection->id) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $this->workflow->resolveDamage($damage);

        return response()->json(['message' => 'Dano marcado como reparado.']);
    }

    public function close(Request $request, Inspection $inspection)
    {
        $this->ensureUserIsAdmin($request);

        $this->workflow->close($inspection, false);
        $inspection->load('report');

        return response()->json([
            'message' => 'Inspecao fechada e PDF gerado.',
            'report_pdf_url' => $inspection->report ? asset('storage/' . $inspection->report->pdf_path) : null,
        ]);
    }

    private function ensureUserIsAdmin(Request $request): void
    {
        if (!$this->isAdmin($request->user())) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }
    }

    private function ensureUserCanManageTransfers(Request $request): void
    {
        $user = $request->user();

        if (!$user || (!$user->hasRole('Admin') && !$user->hasRole('Gestor'))) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }
    }

    private function isAdmin($user): bool
    {
        $roles = $user->roles->pluck('title')->map(fn ($value) => mb_strtolower((string) $value))->toArray();
        return in_array('admin', $roles, true);
    }

    private function storeChecklist(Inspection $inspection, array $checklist): void
    {
        $rows = [];

        foreach ($checklist as $groupKey => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $itemKey => $value) {
                $isNumericValue = in_array($groupKey, ['cleanliness', 'mileage', 'fuel_energy', 'tire_condition'], true) && is_numeric($value);
                $isTextValue = is_string($value) && !is_numeric($value);

                $rows[] = [
                    'inspection_id' => $inspection->id,
                    'group_key' => (string) $groupKey,
                    'item_key' => (string) $itemKey,
                    'value_bool' => ($isNumericValue || $isTextValue) ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                    'value_int' => $isNumericValue ? (int) $value : null,
                    'value_text' => $isTextValue ? trim($value) : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        if (!empty($rows)) {
            InspectionCheckItem::upsert(
                $rows,
                ['inspection_id', 'group_key', 'item_key'],
                ['value_bool', 'value_int', 'value_text', 'updated_at']
            );
        }
    }

    private function storeSlotPhotos(Inspection $inspection, array $filesBySlot, string $category): void
    {
        foreach ($filesBySlot as $slot => $slotFiles) {
            foreach ($this->normalizeFiles($slotFiles) as $file) {
                $this->workflow->uploadPhoto($inspection, $file, $category, (string) $slot);
            }
        }
    }

    private function storeChecklistPhotos(Inspection $inspection, array $filesByKey): void
    {
        foreach ($filesByKey as $key => $keyFiles) {
            $slot = 'doc_' . (string) $key;
            foreach ($this->normalizeFiles($keyFiles) as $file) {
                $this->workflow->uploadPhoto($inspection, $file, 'document', $slot);
            }
        }
    }

    /**
     * @param mixed $files
     * @return UploadedFile[]
     */
    private function normalizeFiles($files): array
    {
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (!is_array($files)) {
            return [];
        }

        $normalized = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $normalized[] = $file;
            }
        }

        return $normalized;
    }

    private function enforcePanelWarningEvidence(Inspection $inspection, array $checklist, array $checklistPhotos, array $routineConfig): void
    {
        if (!in_array('panel_warnings', $routineConfig['operational_checks'], true)) {
            return;
        }

        $warnings = (array) ($checklist['panel_warnings'] ?? []);

        if (empty($warnings['panel_warning'])) {
            return;
        }

        $newPhotos = $this->normalizeFiles($checklistPhotos['panel_warning'] ?? []);
        if (!empty($newPhotos)) {
            return;
        }

        $hasExisting = $inspection->photos()
            ->where('category', 'document')
            ->where('slot', 'doc_panel_warning')
            ->exists();

        if (!$hasExisting) {
            throw ValidationException::withMessages([
                'checklist_photos.panel_warning' => 'Se assinalar avisos no painel, anexe pelo menos uma foto.',
            ]);
        }
    }

    private function enforceAccessoryEvidence(Inspection $inspection, array $checklist, array $checklistPhotos, array $routineConfig): void
    {
        $accessories = (array) ($checklist['accessories'] ?? []);
        $missingPhotos = [];
        $enabledAccessories = (array) ($routineConfig['accessories'] ?? []);

        foreach ($enabledAccessories as $key) {
            if (!isset(self::ACCESSORY_ITEMS[$key])) {
                continue;
            }

            $presence = isset($accessories[$key . '_present']) ? (int) $accessories[$key . '_present'] : 0;
            if ($presence !== 1) {
                continue;
            }

            $newPhotos = $this->normalizeFiles($checklistPhotos[$key] ?? []);
            if (!empty($newPhotos)) {
                continue;
            }

            $hasExisting = $inspection->photos()
                ->where('category', 'document')
                ->where('slot', 'doc_' . $key)
                ->exists();

            if (!$hasExisting) {
                $missingPhotos[] = self::ACCESSORY_ITEMS[$key];
            }
        }

        if (!empty($missingPhotos)) {
            throw ValidationException::withMessages([
                'checklist_photos.accessories' => 'Falta foto para os acessorios presentes: ' . implode(', ', $missingPhotos) . '.',
            ]);
        }
    }

    private function resolveRoutineConfig(Inspection $inspection): array
    {
        $audit = $inspection->audits()->where('action', 'routine_config_applied')->latest('id')->first();
        $payload = (array) ($audit?->payload ?? []);

        if (!empty($payload['routine_config']) && is_array($payload['routine_config'])) {
            return InspectionRoutineConfig::sanitize($payload['routine_config']);
        }

        return InspectionRoutineConfig::defaults();
    }

    private function resolveRoutinePreset(Inspection $inspection): string
    {
        $audit = $inspection->audits()->where('action', 'routine_config_applied')->latest('id')->first();
        $payload = (array) ($audit?->payload ?? []);
        $preset = (string) ($payload['preset'] ?? '');

        return array_key_exists($preset, InspectionRoutineConfig::presets()) ? $preset : 'full';
    }

    private function enabledStepsForRoutine(array $routineConfig): array
    {
        $steps = [1, 2, 4, 10, 11, 12];

        if (!empty($routineConfig['documents'])) {
            $steps[] = 3;
        }

        if (!empty($routineConfig['accessories'])) {
            $steps[] = 5;
        }

        if (!empty($routineConfig['exterior_slots'])) {
            $steps[] = 6;
            $steps[] = 8;
        }

        if (!empty($routineConfig['interior_slots'])) {
            $steps[] = 7;
            $steps[] = 9;
        }

        $steps = array_values(array_unique($steps));
        sort($steps);

        return $steps;
    }

    private function resolveTransferContext(Inspection $inspection): ?array
    {
        $audit = $inspection->audits()
            ->where('action', 'vehicle_usage_planned')
            ->latest('id')
            ->first();

        $payload = is_array($audit?->payload) ? $audit->payload : [];
        $mode = (string) ($payload['mode'] ?? '');

        if ($mode === '') {
            return null;
        }

        $sourceDriver = null;
        if (!empty($payload['source_driver_id'])) {
            $sourceDriver = Driver::query()->find((int) $payload['source_driver_id'], ['id', 'name']);
        }

        return [
            'mode' => $mode,
            'mode_label' => match ($mode) {
                'entrega' => 'Entrega',
                'recolha' => 'Recolha',
                'passagem' => 'Passagem',
                default => ucfirst($mode),
            },
            'source_driver' => $sourceDriver ? [
                'id' => (int) $sourceDriver->id,
                'name' => (string) $sourceDriver->name,
            ] : null,
        ];
    }
}
