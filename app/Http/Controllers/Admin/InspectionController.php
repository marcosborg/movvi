<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyInspectionRequest;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\UpdateInspectionStepRequest;
use App\Models\Driver;
use App\Models\Inspection;
use App\Models\InspectionCheckItem;
use App\Models\InspectionDamage;
use App\Models\User;
use App\Models\VehicleItem;
use App\Services\Inspections\InspectionWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InspectionController extends Controller
{
    private const ACCESSORY_ITEMS = [
        'via_verde' => 'Via Verde / identificador eletronico',
        'charging_cable' => 'Cabo(s) de carregamento (viaturas eletricas)',
        'charging_adapter' => 'Adaptadores de carregamento',
        'spare_tire' => 'Pneu suplente',
        'anti_puncture_kit' => 'Kit anti-furos',
        'jack_wrench' => 'Macaco e chave de rodas',
        'warning_triangle' => 'Triangulo de sinalizacao',
        'reflective_vest' => 'Colete refletor',
    ];

    private const ACCESSORY_STATE_OPTIONS = [
        'good' => 'Bom',
        'fair' => 'Razoavel',
        'poor' => 'Mau',
    ];

    private InspectionWorkflowService $workflow;

    public function __construct(InspectionWorkflowService $workflow)
    {
        $this->workflow = $workflow;
    }

    public function index(Request $request)
    {
        $this->authorizeInspectionAccess($request);

        $query = Inspection::with(['vehicle', 'driver', 'createdBy']);

        if (!$this->isManager($request->user())) {
            $driver = Driver::query()->where('user_id', $request->user()->id)->first();

            if (!$driver) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($relatedQuery) use ($driver, $request) {
                    $relatedQuery->where('driver_id', $driver->id)
                        ->orWhereHas('vehicle', function ($vehicleQuery) use ($driver) {
                            $vehicleQuery->where('driver_id', $driver->id);
                        })
                        ->orWhere('created_by_user_id', $request->user()->id)
                        ->orWhere('responsible_user_id', $request->user()->id);
                });
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('created_by_user_id')) {
            $query->where('created_by_user_id', $request->integer('created_by_user_id'));
        }

        if ($request->filled('plate')) {
            $plate = trim((string) $request->input('plate'));
            $query->whereHas('vehicle', function ($q) use ($plate) {
                $q->where('license_plate', 'like', '%' . $plate . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $inspections = $query->latest('id')->paginate(25)->withQueryString();

        $vehicles = VehicleItem::orderBy('license_plate')->get(['id', 'license_plate']);
        $drivers = Driver::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);

        $summary = [
            'total' => Inspection::count(),
            'in_progress' => Inspection::whereIn('status', ['draft', 'in_progress', 'ready_to_sign'])->count(),
            'closed' => Inspection::where('status', 'closed')->count(),
            'today' => Inspection::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('admin.inspections.index', compact('inspections', 'vehicles', 'drivers', 'users', 'summary'));
    }

    public function create()
    {
        abort_unless($this->isManager(request()->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = VehicleItem::with(['vehicle_brand', 'vehicle_model', 'driver'])->orderBy('license_plate')->get();
        $drivers = Driver::orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.inspections.create', compact('vehicles', 'drivers'));
    }

    public function store(StoreInspectionRequest $request)
    {
        abort_unless($this->isManager($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inspection = $this->workflow->create($request->validated(), $request->user());

        return redirect()->route('admin.inspections.edit', $inspection->id)
            ->with('message', 'Inspeção iniciada com sucesso.');
    }

    public function show(Inspection $inspection)
    {
        $this->authorizeInspectionRelation(request(), $inspection);

        $inspection->load([
            'vehicle.vehicle_brand',
            'vehicle.vehicle_model',
            'driver',
            'photos',
            'damages.photos',
            'signatures',
            'report',
            'createdBy',
            'responsibleUser',
            'audits.user',
            'previousInspection',
            'checkItems',
        ]);

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

            $checklist[$item->group_key][$item->item_key] = (bool) $item->value_bool;
        }

        $missingItems = $this->buildMissingItems($inspection);

        return view('admin.inspections.show', [
            'inspection' => $inspection,
            'checklist' => $checklist,
            'missingItems' => $missingItems,
            'damageItems' => $this->buildDamageItems($inspection),
            'photoSections' => $this->buildPhotoSections($inspection),
            'signatureItems' => $this->buildSignatureItems($inspection),
        ]);
    }

    public function edit(Inspection $inspection)
    {
        $this->authorizeInspectionRelation(request(), $inspection);

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

        $steps = config('inspections.step_labels');
        $requiredExterior = config('inspections.required_slots.exterior');
        $requiredInterior = config('inspections.required_slots.interior');
        $slotLabels = config('inspections.slot_labels', []);

        $exteriorBySlot = [];
        foreach ($requiredExterior as $slot) {
            $exteriorBySlot[$slot] = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'exterior' && $photo->slot === $slot;
            });
        }

        $interiorBySlot = [];
        foreach ($requiredInterior as $slot) {
            $interiorBySlot[$slot] = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'interior' && $photo->slot === $slot;
            });
        }

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

            $checklist[$item->group_key][$item->item_key] = (bool) $item->value_bool;
        }

        $checklistPhotoBySlot = [];
        foreach (array_merge(
            ['dua', 'insurance', 'inspection_periodic', 'tvde_stickers', 'no_smoking_sticker', 'fuel_energy', 'tires', 'panel_warning'],
            array_keys(self::ACCESSORY_ITEMS)
        ) as $slot) {
            $checklistPhotoBySlot[$slot] = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'document' && $photo->slot === 'doc_' . $slot;
            });
        }
        $odometerPhoto = $inspection->photos->first(function ($photo) {
            return $photo->category === 'document' && $photo->slot === 'doc_odometer';
        });

        $drivers = Driver::orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $missingItems = $this->buildMissingItems($inspection);
        $signatureNames = [
            'responsible' => optional($inspection->signatures->firstWhere('role', 'responsible'))->signed_by_name,
            'driver' => optional($inspection->signatures->firstWhere('role', 'driver'))->signed_by_name,
        ];

        return view('admin.inspections.edit', [
            'inspection' => $inspection,
            'steps' => $steps,
            'requiredExterior' => $requiredExterior,
            'requiredInterior' => $requiredInterior,
            'slotLabels' => $slotLabels,
            'exteriorBySlot' => $exteriorBySlot,
            'interiorBySlot' => $interiorBySlot,
            'damageLocations' => config('inspections.damage_locations'),
            'damageParts' => config('inspections.damage_parts'),
            'damageTypes' => config('inspections.damage_types'),
            'checklist' => $checklist,
            'checklistPhotoBySlot' => $checklistPhotoBySlot,
            'odometerPhoto' => $odometerPhoto,
            'drivers' => $drivers,
            'missingItems' => $missingItems,
            'signatureNames' => $signatureNames,
            'accessoryItems' => self::ACCESSORY_ITEMS,
            'accessoryStateOptions' => self::ACCESSORY_STATE_OPTIONS,
        ]);
    }

    public function updateStep(UpdateInspectionStepRequest $request, Inspection $inspection)
    {
        $this->authorizeInspectionRelation($request, $inspection);

        $validated = $request->validated();
        $step = (int) $validated['step'];
        $action = $validated['action'] ?? 'save';

        if ($step < 1 || $step > 12) {
            throw ValidationException::withMessages(['step' => 'Etapa inválida.']);
        }

        if ($step > $inspection->current_step) {
            throw ValidationException::withMessages(['step' => 'Não pode avançar sem concluir etapas anteriores.']);
        }

        if ($step === 2 && !empty($validated['driver_id'])) {
            $inspection->update(['driver_id' => $validated['driver_id']]);
        }

        if (in_array($step, [3, 4, 5], true)) {
            $checklistInput = (array) $request->input('checklist', []);
            $checklistPhotos = (array) $request->file('checklist_photos', []);

            $this->storeChecklist($inspection, $checklistInput);
            $this->storeChecklistPhotos($inspection, $checklistPhotos);
            $this->enforcePanelWarningEvidence($inspection, $checklistInput, $checklistPhotos);

            if ($step === 5 && $action === 'complete') {
                $this->enforceAccessoryEvidence($inspection, $checklistInput, $checklistPhotos);
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
                    throw ValidationException::withMessages(['damage_photo' => 'Cada dano deve ter pelo menos 1 foto.']);
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
                    throw ValidationException::withMessages(['inspector_name' => 'Indique o nome do responsavel para assinar.']);
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
                    throw ValidationException::withMessages(['driver_signature_name' => 'Indique o nome do condutor para assinar.']);
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

            return redirect()->route('admin.inspections.edit', $inspection->id)->with('message', 'Etapa concluída com sucesso.');
        }

        return redirect()->route('admin.inspections.edit', $inspection->id)->with('message', 'Dados guardados com sucesso.');
    }

    public function backStep(Inspection $inspection)
    {
        $this->authorizeInspectionRelation(request(), $inspection);

        if ($inspection->locked_at) {
            return back()->withErrors(['inspection' => 'Inspeções fechadas não podem regredir etapas.']);
        }

        if ((int) $inspection->current_step <= 1) {
            return back()->withErrors(['inspection' => 'A inspeção já está na primeira etapa.']);
        }

        $inspection->update([
            'current_step' => max(1, ((int) $inspection->current_step) - 1),
            'status' => 'in_progress',
        ]);

        return redirect()->route('admin.inspections.edit', $inspection->id)->with('message', 'Regrediu para a etapa anterior.');
    }

    public function resolveDamage(Inspection $inspection, InspectionDamage $damage)
    {
        abort_unless($this->isManager(request()->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeInspectionRelation(request(), $inspection);
        abort_if($damage->inspection_id !== $inspection->id, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->workflow->resolveDamage($damage);

        return back()->with('message', 'Dano marcado como reparado.');
    }

    public function close(Inspection $inspection)
    {
        $this->authorizeInspectionRelation(request(), $inspection);

        $this->workflow->close($inspection, false);

        return redirect()->route('admin.inspections.show', $inspection->id)->with('message', 'Inspeção fechada e PDF gerado.');
    }

    public function destroy(Inspection $inspection)
    {
        abort_unless($this->isManager(request()->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($inspection->locked_at) {
            return back()->withErrors(['inspection' => 'Inspeções fechadas não podem ser eliminadas.']);
        }

        $inspection->delete();

        return back();
    }

    public function massDestroy(MassDestroyInspectionRequest $request)
    {
        abort_unless($this->isManager($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $inspections = Inspection::whereIn('id', $request->input('ids', []))->get();

        foreach ($inspections as $inspection) {
            if (!$inspection->locked_at) {
                $inspection->delete();
            }
        }

        return response(null, Response::HTTP_NO_CONTENT);
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
                    'value_bool' => ($isNumericValue || $isTextValue) ? null : (bool) $value,
                    'value_int' => $isNumericValue ? (int) $value : null,
                    'value_text' => $isTextValue ? trim($value) : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        if (!empty($rows)) {
            InspectionCheckItem::upsert($rows, ['inspection_id', 'group_key', 'item_key'], ['value_bool', 'value_int', 'value_text', 'updated_at']);
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

    private function enforcePanelWarningEvidence(Inspection $inspection, array $checklist, array $checklistPhotos): void
    {
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

    private function enforceAccessoryEvidence(Inspection $inspection, array $checklist, array $checklistPhotos): void
    {
        $accessories = (array) ($checklist['accessories'] ?? []);
        $missingPhotos = [];

        foreach (array_keys(self::ACCESSORY_ITEMS) as $key) {
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

    private function buildMissingItems(Inspection $inspection): array
    {
        $inspection->loadMissing(['photos', 'checkItems', 'signatures']);

        $missing = [];

        if (!$inspection->driver_id) {
            $missing[] = ['group' => 'Etapa 2', 'item' => 'Condutor não selecionado'];
        }

        $docs = [
            'dua' => 'Documento Unico Automovel (DUA)',
            'insurance' => 'Seguro',
            'inspection_periodic' => 'Inspecao periodica',
            'tvde_stickers' => 'Disticos TVDE',
            'no_smoking_sticker' => 'Autocolante de proibicao de fumar',
        ];

        $checked = [];
        foreach ($inspection->checkItems->where('group_key', 'documents') as $item) {
            $checked[$item->item_key] = (bool) $item->value_bool;
        }

        foreach ($docs as $key => $label) {
            if (empty($checked[$key])) {
                $missing[] = ['group' => 'Etapa 3', 'item' => $label . ' não assinalado'];
                continue;
            }

            $hasPhoto = $inspection->photos->first(function ($photo) use ($key) {
                return $photo->category === 'document' && $photo->slot === 'doc_' . $key;
            });

            if (!$hasPhoto) {
                $missing[] = ['group' => 'Etapa 3', 'item' => $label . ' sem foto'];
            }
        }

        $extLabels = config('inspections.slot_labels.exterior', []);
        foreach (config('inspections.required_slots.exterior', []) as $slot) {
            $has = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'exterior' && $photo->slot === $slot;
            });
            if (!$has) {
                $missing[] = ['group' => 'Etapa 6', 'item' => 'Foto exterior em falta: ' . ($extLabels[$slot] ?? $slot)];
            }
        }

        $intLabels = config('inspections.slot_labels.interior', []);
        foreach (config('inspections.required_slots.interior', []) as $slot) {
            $has = $inspection->photos->first(function ($photo) use ($slot) {
                return $photo->category === 'interior' && $photo->slot === $slot;
            });
            if (!$has) {
                $missing[] = ['group' => 'Etapa 7', 'item' => 'Foto interior em falta: ' . ($intLabels[$slot] ?? $slot)];
            }
        }

        $roles = $inspection->signatures->pluck('role')->toArray();
        if (!in_array('driver', $roles, true)) {
            $missing[] = ['group' => 'Etapa 11', 'item' => 'Assinatura do condutor em falta'];
        }
        if (in_array($inspection->type, ['handover', 'return'], true) && !in_array('responsible', $roles, true)) {
            $missing[] = ['group' => 'Etapa 11', 'item' => 'Assinatura do responsavel em falta'];
        }

        return $missing;
    }

    private function buildDamageItems(Inspection $inspection): array
    {
        $damageLocations = (array) config('inspections.damage_locations', []);
        $damageTypes = (array) config('inspections.damage_types', []);
        $damageParts = (array) config('inspections.damage_parts', []);

        return $inspection->damages
            ->sortByDesc('id')
            ->map(function ($damage) use ($damageLocations, $damageTypes, $damageParts) {
                $locationKey = (string) $damage->location;
                $pieceKey = (string) $damage->part;

                return [
                    'id' => (int) $damage->id,
                    'scope' => ucfirst((string) $damage->scope),
                    'location' => $damageLocations[$locationKey] ?? $locationKey,
                    'piece' => $damageParts[$locationKey]['sections'][$pieceKey] ?? $pieceKey,
                    'part' => (string) ($damage->part_section ?? ''),
                    'type' => $damageTypes[$damage->damage_type] ?? (string) $damage->damage_type,
                    'status' => $damage->is_resolved ? 'Resolvido' : 'Aberto',
                    'photos' => $damage->photos->map(function ($photo) use ($damage) {
                        return [
                            'url' => asset('storage/' . $photo->path),
                            'thumb' => asset('storage/' . $photo->path),
                            'title' => 'Dano #' . $damage->id,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildPhotoSections(Inspection $inspection): array
    {
        $slotLabels = (array) config('inspections.slot_labels', []);

        return $inspection->photos
            ->groupBy(fn ($photo) => (string) $photo->category)
            ->map(function ($photos, $category) use ($slotLabels) {
                return [
                    'title' => match ((string) $category) {
                        'document' => 'Documentacao',
                        'exterior' => 'Fotografias exteriores',
                        'interior' => 'Fotografias interiores',
                        'extra' => 'Fotos extra',
                        default => ucfirst(str_replace('_', ' ', (string) $category)),
                    },
                    'items' => $photos->map(function ($photo) use ($category, $slotLabels) {
                        $slot = (string) ($photo->slot ?? '');
                        $label = $slot;

                        if ((string) $category === 'exterior') {
                            $label = $slotLabels['exterior'][$slot] ?? $slot;
                        } elseif ((string) $category === 'interior') {
                            $label = $slotLabels['interior'][$slot] ?? $slot;
                        } elseif (str_starts_with($slot, 'doc_')) {
                            $label = ucfirst(str_replace('_', ' ', substr($slot, 4)));
                        }

                        return [
                            'label' => $label,
                            'url' => asset('storage/' . $photo->path),
                            'thumb' => asset('storage/' . $photo->path),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildSignatureItems(Inspection $inspection): array
    {
        return $inspection->signatures
            ->map(function ($signature) {
                $path = (string) ($signature->signature_path ?? '');
                $imageUrl = $path !== '' && !str_starts_with($path, 'typed-signature:')
                    ? asset('storage/' . $path)
                    : null;

                return [
                    'role' => strtoupper((string) $signature->role),
                    'name' => (string) ($signature->signed_by_name ?? ''),
                    'signed_at' => optional($signature->signed_at)->format('Y-m-d H:i:s'),
                    'image' => $imageUrl,
                ];
            })
            ->values()
            ->all();
    }

    private function authorizeInspectionAccess(Request $request): void
    {
        abort_unless($this->isManager($request->user()) || $this->isDriver($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function authorizeInspectionRelation(Request $request, Inspection $inspection): void
    {
        $this->authorizeInspectionAccess($request);

        if ($this->isManager($request->user())) {
            return;
        }

        $driver = Driver::query()->where('user_id', $request->user()->id)->first();
        $isRelatedDriver = $driver && (
            (int) $inspection->driver_id === (int) $driver->id
            || (int) ($inspection->vehicle?->driver_id ?? 0) === (int) $driver->id
        );
        $isInspectionOwner = (int) $inspection->created_by_user_id === (int) $request->user()->id
            || (int) $inspection->responsible_user_id === (int) $request->user()->id;

        abort_unless($isRelatedDriver || $isInspectionOwner, Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function isManager(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roles = $user->roles->pluck('title')->map(fn ($role) => mb_strtolower((string) $role))->toArray();

        return in_array('admin', $roles, true) || in_array('gestor', $roles, true);
    }

    private function isDriver(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roles = $user->roles->pluck('title')->map(fn ($role) => mb_strtolower((string) $role))->toArray();

        return in_array('driver', $roles, true);
    }
}
