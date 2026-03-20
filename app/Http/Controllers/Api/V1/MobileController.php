<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Notifications\NewReceipt;
use App\Models\CurrentAccount;
use App\Models\Document;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\ExpenseReceipt;
use App\Models\Receipt;
use App\Models\Reimbursement;
use App\Models\TvdeWeek;
use App\Models\User;
use App\Models\VehicleUsage;
use App\Services\VehicleProfitabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        $driver = Driver::with(['company', 'contract_vat', 'state'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('title')->values(),
            ],
            'driver' => $driver ? $this->serializeDriver($driver) : null,
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user()->load('roles');
        $roles = $user->roles->pluck('title')->values();
        $driver = Driver::with(['company', 'contract_vat', 'state'])
            ->where('user_id', $user->id)
            ->first();

        [$week, $requestedDate] = $this->resolveWeek($request->query('date'));

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
                'received' => $requestedDate,
            ], 404);
        }

        $isAdmin = $this->hasRole($roles, 'Admin');
        $isManager = $this->hasRole($roles, 'Gestor');
        $isDriver = $this->hasRole($roles, 'Driver');

        return response()->json([
            'viewer' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
            ],
            'capabilities' => [
                'is_admin' => $isAdmin,
                'is_manager' => $isManager,
                'is_driver' => $isDriver,
            ],
            'week' => $this->serializeWeek($week, $requestedDate),
            'financial_hub' => $this->buildFinancialHub($isAdmin, $isManager),
            'operations_hub' => $this->buildOperationsHub($isAdmin),
            'driver_hub' => $this->buildDriverHub($isDriver, $driver, $week, $requestedDate),
        ]);
    }

    public function driverReceipts(Request $request)
    {
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        [$week, $requestedDate] = $this->resolveWeek($request->query('date'));
        $weekId = $week?->id;
        $driverBalance = $week ? $this->resolveDriverBalance($driver->id, $week->id) : null;
        $expenseReceiptForWeek = $week
            ? ExpenseReceipt::where('driver_id', $driver->id)->where('tvde_week_id', $week->id)->first()
            : null;

        $receipts = Receipt::where('driver_id', $driver->id)
            ->when($weekId, function ($query) use ($weekId) {
                $query->where('tvde_week_id', $weekId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Receipt $receipt) {
                return [
                    'id' => $receipt->id,
                    'type' => 'receipt',
                    'value' => (float) ($receipt->value ?? 0),
                    'balance' => $receipt->balance !== null ? (float) $receipt->balance : null,
                    'verified_value' => $receipt->verified_value !== null ? (float) $receipt->verified_value : null,
                    'amount_transferred' => $receipt->amount_transferred !== null ? (float) $receipt->amount_transferred : null,
                    'verified' => (bool) $receipt->verified,
                    'paid' => (bool) $receipt->paid,
                    'created_at' => optional($receipt->created_at)->format('Y-m-d H:i:s'),
                    'tvde_week_id' => $receipt->tvde_week_id,
                    'file_url' => $receipt->file ? $receipt->file->getUrl() : null,
                ];
            })
            ->values();

        $expenseReceipts = ExpenseReceipt::where('driver_id', $driver->id)
            ->when($weekId, function ($query) use ($weekId) {
                $query->where('tvde_week_id', $weekId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ExpenseReceipt $expenseReceipt) {
                return [
                    'id' => $expenseReceipt->id,
                    'type' => 'expense_receipt',
                    'approved_value' => $expenseReceipt->approved_value !== null ? (float) $expenseReceipt->approved_value : null,
                    'verified' => (bool) $expenseReceipt->verified,
                    'created_at' => optional($expenseReceipt->created_at)->format('Y-m-d H:i:s'),
                    'tvde_week_id' => $expenseReceipt->tvde_week_id,
                    'files' => collect($expenseReceipt->receipts)->map(function ($media) {
                        return [
                            'name' => $media->file_name,
                            'url' => $media->getUrl(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        $reimbursements = Reimbursement::where('driver_id', $driver->id)
            ->when($weekId, function ($query) use ($weekId) {
                $query->where('tvde_week_id', $weekId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Reimbursement $reimbursement) {
                return [
                    'id' => $reimbursement->id,
                    'type' => 'reimbursement',
                    'value' => (float) ($reimbursement->value ?? 0),
                    'verified' => (bool) $reimbursement->verified,
                    'created_at' => optional($reimbursement->created_at)->format('Y-m-d H:i:s'),
                    'tvde_week_id' => $reimbursement->tvde_week_id,
                    'file_url' => $reimbursement->file ? $reimbursement->file->getUrl() : null,
                ];
            })
            ->values();

        return response()->json([
            'driver' => $this->serializeDriver($driver),
            'week' => $week ? $this->serializeWeek($week, $requestedDate) : null,
            'submission' => [
                'balance' => $driverBalance ? $this->serializeBalance($driverBalance, $driver) : null,
                'can_submit_receipt' => (bool) ($driverBalance && (float) $driverBalance->new_balance > 0),
                'expense_receipt' => $expenseReceiptForWeek ? [
                    'id' => $expenseReceiptForWeek->id,
                    'verified' => (bool) $expenseReceiptForWeek->verified,
                ] : null,
            ],
            'receipts' => $receipts,
            'expense_receipts' => $expenseReceipts,
            'reimbursements' => $reimbursements,
        ]);
    }

    public function storeDriverReceipt(Request $request): JsonResponse
    {
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:d-m-Y'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        [$week] = $this->resolveWeek($validated['date']);

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
            ], 404);
        }

        $driverBalance = $this->resolveDriverBalance($driver->id, $week->id);

        if (! $driverBalance || (float) $driverBalance->new_balance <= 0) {
            return response()->json([
                'error' => 'O saldo da semana selecionada nao permite o envio de recibo.',
            ], 422);
        }

        $receipt = Receipt::create([
            'driver_id' => $driver->id,
            'tvde_week_id' => $week->id,
            'balance' => (float) $driverBalance->new_balance,
            'value' => round((float) $driverBalance->new_balance, 2),
        ]);

        $receipt->addMediaFromRequest('file')->toMediaCollection('file');

        User::find(2)?->notify(new NewReceipt($driver));

        return response()->json([
            'message' => 'Recibo enviado com sucesso.',
            'receipt' => [
                'id' => $receipt->id,
                'value' => (float) $receipt->value,
                'created_at' => optional($receipt->created_at)->format('Y-m-d H:i:s'),
                'file_url' => $receipt->file ? $receipt->file->getUrl() : null,
            ],
        ], 201);
    }

    public function storeDriverExpenseReceipt(Request $request): JsonResponse
    {
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:d-m-Y'],
            'approved_value' => ['nullable', 'numeric', 'min:0'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240'],
        ]);

        [$week] = $this->resolveWeek($validated['date']);

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
            ], 404);
        }

        $expenseReceipt = ExpenseReceipt::firstOrCreate(
            [
                'driver_id' => $driver->id,
                'tvde_week_id' => $week->id,
            ],
            [
                'approved_value' => (float) ($validated['approved_value'] ?? 0),
                'verified' => 0,
            ]
        );

        if ((bool) $expenseReceipt->verified) {
            return response()->json([
                'error' => 'Os recibos de despesa desta semana ja foram validados e nao podem ser alterados.',
            ], 422);
        }

        $expenseReceipt->approved_value = (float) ($validated['approved_value'] ?? 0);
        $expenseReceipt->save();

        foreach ($request->file('files', []) as $file) {
            $expenseReceipt->addMedia($file)->toMediaCollection('receipts');
        }

        return response()->json([
            'message' => 'Recibos de despesa enviados com sucesso.',
            'expense_receipt' => [
                'id' => $expenseReceipt->id,
                'approved_value' => (float) ($expenseReceipt->approved_value ?? 0),
                'verified' => (bool) $expenseReceipt->verified,
                'files_count' => $expenseReceipt->refresh()->receipts->count(),
            ],
        ], 201);
    }

    public function storeDriverReimbursement(Request $request): JsonResponse
    {
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:d-m-Y'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        [$week] = $this->resolveWeek($validated['date']);

        if (! $week) {
            return response()->json([
                'error' => 'Semana TVDE nao encontrada.',
            ], 404);
        }

        $reimbursement = Reimbursement::create([
            'driver_id' => $driver->id,
            'tvde_week_id' => $week->id,
            'value' => (float) ($validated['value'] ?? 0),
            'verified' => 0,
        ]);

        $reimbursement->addMediaFromRequest('file')->toMediaCollection('file');

        return response()->json([
            'message' => 'Devolucao submetida com sucesso.',
            'reimbursement' => [
                'id' => $reimbursement->id,
                'value' => (float) ($reimbursement->value ?? 0),
                'created_at' => optional($reimbursement->created_at)->format('Y-m-d H:i:s'),
                'file_url' => $reimbursement->file ? $reimbursement->file->getUrl() : null,
            ],
        ], 201);
    }

    public function driverWeeks(Request $request)
    {
        $user = $request->user();
        $canBrowseWeeks = $user && ($user->hasRole('Admin') || $user->hasRole('Gestor'));
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver && ! $canBrowseWeeks) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        $weeks = TvdeWeek::orderByDesc('start_date')
            ->limit(24)
            ->get()
            ->map(function (TvdeWeek $week) {
                return [
                    'id' => $week->id,
                    'number' => $week->number,
                    'start_date' => $week->start_date,
                    'end_date' => $week->end_date,
                    'date_key' => Carbon::parse($week->getRawOriginal('start_date'))->format('d-m-Y'),
                    'label' => sprintf(
                        'Semana %s · %s a %s',
                        $week->number ?? '-',
                        Carbon::parse($week->getRawOriginal('start_date'))->format('d/m'),
                        Carbon::parse($week->getRawOriginal('end_date'))->format('d/m')
                    ),
                ];
            })
            ->values();

        return response()->json([
            'driver' => $this->serializeDriver($driver),
            'weeks' => $weeks,
        ]);
    }

    public function weeks(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $weeks = TvdeWeek::query()
            ->when($validated['date_from'] ?? null, fn ($query, $dateFrom) => $query->whereDate('start_date', '>=', $dateFrom))
            ->when($validated['date_to'] ?? null, fn ($query, $dateTo) => $query->whereDate('start_date', '<=', $dateTo))
            ->orderByDesc('start_date')
            ->limit(24)
            ->get()
            ->map(function (TvdeWeek $week) {
                return [
                    'id' => $week->id,
                    'number' => $week->number,
                    'start_date' => $week->start_date,
                    'end_date' => $week->end_date,
                    'date_key' => Carbon::parse($week->getRawOriginal('start_date'))->format('d-m-Y'),
                    'label' => sprintf(
                        'Semana %s · %s a %s',
                        $week->number ?? '-',
                        Carbon::parse($week->getRawOriginal('start_date'))->format('d/m'),
                        Carbon::parse($week->getRawOriginal('end_date'))->format('d/m')
                    ),
                ];
            })
            ->values();

        return response()->json([
            'filters' => [
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ],
            'weeks' => $weeks,
        ]);
    }

    public function driverDocuments(Request $request)
    {
        $driver = $this->resolveAuthenticatedDriver($request);

        if (! $driver) {
            return response()->json([
                'error' => 'Motorista nao encontrado para o utilizador autenticado.',
            ], 404);
        }

        $document = Document::firstOrCreate([
            'driver_id' => $driver->id,
        ]);

        return response()->json([
            'driver' => $this->serializeDriver($driver),
            'documents' => [
                [
                    'key' => 'citizen_card',
                    'title' => 'Cartao de cidadao',
                    'files' => $this->serializeMediaCollection($document->citizen_card),
                ],
                [
                    'key' => 'tvde_driver_certificate',
                    'title' => 'Certificado TVDE',
                    'files' => $this->serializeMediaCollection($document->tvde_driver_certificate),
                ],
                [
                    'key' => 'criminal_record',
                    'title' => 'Registo criminal',
                    'files' => $this->serializeMediaCollection($document->criminal_record),
                ],
                [
                    'key' => 'profile_picture',
                    'title' => 'Fotografia',
                    'files' => $this->serializeMediaCollection($document->profile_picture),
                ],
                [
                    'key' => 'driving_license',
                    'title' => 'Carta de conducao',
                    'files' => $this->serializeMediaCollection($document->driving_license),
                ],
                [
                    'key' => 'iban',
                    'title' => 'Comprovativo de IBAN',
                    'files' => $this->serializeMediaCollection($document->iban),
                ],
                [
                    'key' => 'address',
                    'title' => 'Comprovativo de morada',
                    'files' => $this->serializeMediaCollection($document->address),
                ],
            ],
        ]);
    }

    public function vehicleUsages(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        $roles = $user->roles->pluck('title')->values();
        $isAdmin = $this->hasRole($roles, 'Admin');
        $isManager = $this->hasRole($roles, 'Gestor');
        $isDriver = $this->hasRole($roles, 'Driver');

        if (! $isAdmin && ! $isManager && ! $isDriver) {
            return response()->json([
                'error' => '403 Forbidden',
            ], 403);
        }

        $driver = null;
        if ($isDriver) {
            $driver = $this->resolveAuthenticatedDriver($request);

            if (! $driver) {
                return response()->json([
                    'error' => 'Motorista nao encontrado para o utilizador autenticado.',
                ], 404);
            }
        }

        $validated = $request->validate([
            'driver' => ['nullable', 'string', 'max:255'],
            'license_plate' => ['nullable', 'string', 'max:255'],
            'start_date_from' => ['nullable', 'date'],
            'start_date_to' => ['nullable', 'date'],
            'end_date_from' => ['nullable', 'date'],
            'end_date_to' => ['nullable', 'date'],
            'active_on' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $searchDriver = trim((string) ($validated['driver'] ?? ''));
        $searchPlate = trim((string) ($validated['license_plate'] ?? ''));
        $perPage = min(max((int) ($validated['per_page'] ?? 25), 1), 100);
        $startDateFrom = $validated['start_date_from'] ?? null;
        $startDateTo = $validated['start_date_to'] ?? null;
        $endDateFrom = $validated['end_date_from'] ?? null;
        $endDateTo = $validated['end_date_to'] ?? null;
        $activeOn = $validated['active_on'] ?? null;

        $query = VehicleUsage::with(['driver.company', 'vehicle_item.vehicle_model', 'vehicle_item.company'])
            ->when($isDriver && $driver, function (Builder $builder) use ($driver) {
                $builder->where('driver_id', $driver->id);
            })
            ->when($searchDriver !== '', function (Builder $builder) use ($searchDriver) {
                $builder->whereHas('driver', function (Builder $driverQuery) use ($searchDriver) {
                    $driverQuery->where('name', 'like', '%' . $searchDriver . '%');
                });
            })
            ->when($searchPlate !== '', function (Builder $builder) use ($searchPlate) {
                $builder->whereHas('vehicle_item', function (Builder $vehicleQuery) use ($searchPlate) {
                    $vehicleQuery->where('license_plate', 'like', '%' . $searchPlate . '%');
                });
            })
            ->when($startDateFrom, fn (Builder $builder, $value) => $builder->whereDate('start_date', '>=', $value))
            ->when($startDateTo, fn (Builder $builder, $value) => $builder->whereDate('start_date', '<=', $value))
            ->when($endDateFrom, fn (Builder $builder, $value) => $builder->whereDate('end_date', '>=', $value))
            ->when($endDateTo, fn (Builder $builder, $value) => $builder->whereDate('end_date', '<=', $value))
            ->when($activeOn, function (Builder $builder, $value) {
                $builder->whereDate('start_date', '<=', $value)
                    ->where(function (Builder $nested) use ($value) {
                        $nested->whereNull('end_date')->orWhereDate('end_date', '>=', $value);
                    });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'filters' => [
                'driver' => $searchDriver !== '' ? $searchDriver : null,
                'license_plate' => $searchPlate !== '' ? $searchPlate : null,
                'start_date_from' => $startDateFrom,
                'start_date_to' => $startDateTo,
                'end_date_from' => $endDateFrom,
                'end_date_to' => $endDateTo,
                'active_on' => $activeOn,
                'per_page' => $perPage,
            ],
            'viewer' => [
                'roles' => $roles,
                'is_admin' => $isAdmin,
                'is_manager' => $isManager,
                'is_driver' => $isDriver,
            ],
            'items' => collect($paginator->items())->map(function (VehicleUsage $usage) {
                return [
                    'id' => $usage->id,
                    'start_date' => $usage->start_date,
                    'end_date' => $usage->end_date,
                    'usage_exception' => $usage->usage_exceptions,
                    'usage_exception_label' => VehicleUsage::USAGE_EXCEPTIONS_RADIO[$usage->usage_exceptions] ?? $usage->usage_exceptions,
                    'driver' => $usage->driver ? [
                        'id' => $usage->driver->id,
                        'name' => $usage->driver->name,
                        'company' => $usage->driver->company ? [
                            'id' => $usage->driver->company->id,
                            'name' => $usage->driver->company->name,
                        ] : null,
                    ] : null,
                    'vehicle' => $usage->vehicle_item ? [
                        'id' => $usage->vehicle_item->id,
                        'license_plate' => $usage->vehicle_item->license_plate,
                        'brand' => $usage->vehicle_item->brand,
                        'model' => $usage->vehicle_item->vehicle_model?->name,
                        'company' => $usage->vehicle_item->company ? [
                            'id' => $usage->vehicle_item->company->id,
                            'name' => $usage->vehicle_item->company->name,
                        ] : null,
                    ] : null,
                ];
            })->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function resolveWeek(?string $date): array
    {
        $requestedDate = trim((string) $date);

        if ($requestedDate !== '') {
            try {
                $dbDate = Carbon::createFromFormat('d-m-Y', $requestedDate)->format('Y-m-d');
            } catch (\Throwable $e) {
                return [null, $requestedDate];
            }

            return [TvdeWeek::where('start_date', $dbDate)->first(), $requestedDate];
        }

        $week = TvdeWeek::orderByDesc('start_date')->first();

        return [$week, $week?->start_date];
    }

    private function resolveVehicleForWeek(int $driverId, TvdeWeek $week)
    {
        $weekStart = Carbon::parse($week->getRawOriginal('start_date'))->startOfDay();
        $weekEnd = Carbon::parse($week->getRawOriginal('end_date'))->endOfDay();

        $usage = VehicleUsage::with('vehicle_item.vehicle_model')
            ->where('driver_id', $driverId)
            ->where('start_date', '<=', $weekEnd)
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $weekStart);
            })
            ->orderByDesc('start_date')
            ->first();

        return $usage?->vehicle_item;
    }

    private function resolveAuthenticatedDriver(Request $request): ?Driver
    {
        $user = $request->user();

        return Driver::with(['company', 'contract_vat', 'state'])
            ->where('user_id', $user->id)
            ->first();
    }

    private function hasRole($roles, string $title): bool
    {
        return $roles->contains(function ($role) use ($title) {
            return mb_strtolower((string) $role) === mb_strtolower($title);
        });
    }

    private function serializeWeek(TvdeWeek $week, ?string $requestedDate): array
    {
        return [
            'id' => $week->id,
            'number' => $week->number,
            'start_date' => $week->start_date,
            'end_date' => $week->end_date,
            'requested_date' => $requestedDate,
        ];
    }

    private function buildFinancialHub(bool $isAdmin, bool $isManager): array
    {
        if (! $isAdmin && ! $isManager) {
            return [
                'enabled' => false,
                'provider' => 'Conta Azul',
                'status' => 'locked',
                'modules' => [],
            ];
        }

        $modules = [
            [
                'key' => 'conta_azul_overview',
                'title' => 'Resumo financeiro',
                'summary' => 'Visao financeira agregada da empresa puxada da integracao Conta Azul.',
                'status' => 'planned',
                'scope' => $isAdmin ? 'admin' : 'manager',
            ],
            [
                'key' => 'conta_azul_cashflow',
                'title' => 'Fluxo de caixa',
                'summary' => 'Leitura de entradas, saidas e indicadores operacionais vindos do ERP.',
                'status' => 'planned',
                'scope' => $isAdmin ? 'admin' : 'manager',
            ],
        ];

        if ($isAdmin) {
            $modules[] = [
                'key' => 'conta_azul_executive',
                'title' => 'Indicadores executivos',
                'summary' => 'Camada de decisao com rentabilidade, liquidez e comparacao global.',
                'status' => 'planned',
                'scope' => 'admin',
            ];
        }

        return [
            'enabled' => true,
            'provider' => 'Conta Azul',
            'status' => 'planned',
            'modules' => $modules,
        ];
    }

    private function buildOperationsHub(bool $isAdmin): array
    {
        if (! $isAdmin) {
            return [
                'enabled' => false,
                'modules' => [],
            ];
        }

        return [
            'enabled' => true,
            'modules' => [
                [
                    'key' => 'vehicle_deliveries',
                    'title' => 'Entregas de viaturas',
                    'summary' => 'Fluxo de entrega com checklist, confirmacao e assinatura.',
                    'status' => 'planned',
                ],
                [
                    'key' => 'vehicle_inspections',
                    'title' => 'Inspecoes',
                    'summary' => 'Registo de estado, ocorrencias e validacao operacional por viatura.',
                    'status' => 'planned',
                ],
            ],
        ];
    }

    private function buildDriverHub(bool $isDriver, ?Driver $driver, TvdeWeek $week, ?string $requestedDate): array
    {
        if (! $isDriver) {
            return [
                'enabled' => false,
                'status' => 'locked',
                'driver' => null,
                'week' => $this->serializeWeek($week, $requestedDate),
                'statement_metrics' => null,
                'account_summary' => null,
                'balance' => null,
                'vehicle' => null,
                'vehicle_profitability' => null,
                'recent_receipts' => [],
                'actions' => [],
            ];
        }

        if (! $driver) {
            return [
                'enabled' => true,
                'status' => 'configuration_required',
                'reason' => 'O utilizador tem a role Driver mas ainda nao esta associado a um registo de motorista.',
                'driver' => null,
                'week' => $this->serializeWeek($week, $requestedDate),
                'statement_metrics' => null,
                'account_summary' => null,
                'balance' => null,
                'vehicle' => null,
                'vehicle_profitability' => null,
                'recent_receipts' => [],
                'actions' => $this->driverActions(),
            ];
        }

        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
        ])->first();

        $driverBalance = DriversBalance::where([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
        ])->first();

        $vehicle = $this->resolveVehicleForWeek($driver->id, $week);
        $profitability = $vehicle
            ? VehicleProfitabilityService::make((int) $vehicle->id, (int) $week->id)
            : null;

        $accountSummary = $currentAccount ? (json_decode($currentAccount->data, true) ?? []) : null;

        return [
            'enabled' => true,
            'status' => 'available',
            'driver' => $this->serializeDriver($driver),
            'week' => $this->serializeWeek($week, $requestedDate),
            'statement_metrics' => $this->serializeStatementMetrics($accountSummary),
            'account_summary' => $accountSummary,
            'balance' => $this->serializeBalance($driverBalance, $driver),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'model' => optional($vehicle->vehicle_model)->name,
            ] : null,
            'vehicle_profitability' => $profitability,
            'recent_receipts' => $this->serializeRecentReceipts($driver),
            'actions' => $this->driverActions(),
        ];
    }

    private function serializeStatementMetrics(?array $accountSummary): ?array
    {
        if (! $accountSummary) {
            return null;
        }

        return [
            'uber_net' => (float) ($accountSummary['uber_net'] ?? data_get($accountSummary, 'uber.uber_net', 0)),
            'bolt_net' => (float) ($accountSummary['bolt_net'] ?? data_get($accountSummary, 'bolt.bolt_net', 0)),
            'total' => (float) ($accountSummary['total'] ?? $accountSummary['driver_total'] ?? 0),
            'weekly_km' => (float) ($accountSummary['weekly_km'] ?? 0),
            'earnings_per_km' => (float) ($accountSummary['earnings_per_km'] ?? 0),
        ];
    }

    private function serializeRecentReceipts(Driver $driver): array
    {
        return Receipt::with('media')
            ->where('driver_id', $driver->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Receipt $receipt) {
                return [
                    'id' => $receipt->id,
                    'value' => (float) ($receipt->value ?? 0),
                    'verified_value' => $receipt->verified_value !== null ? (float) $receipt->verified_value : null,
                    'amount_transferred' => $receipt->amount_transferred !== null ? (float) $receipt->amount_transferred : null,
                    'verified' => (bool) $receipt->verified,
                    'paid' => (bool) $receipt->paid,
                    'created_at' => optional($receipt->created_at)->format('Y-m-d H:i:s'),
                    'file_url' => $receipt->file ? $receipt->file->getUrl() : null,
                ];
            })
            ->values()
            ->all();
    }

    private function driverActions(): array
    {
        return [
            [
                'key' => 'financial_statement',
                'title' => 'Extratos',
                'summary' => 'Consulta do extrato semanal e dos principais totais do motorista.',
                'status' => 'available',
            ],
            [
                'key' => 'receipt_submission',
                'title' => 'Envio de recibos',
                'summary' => 'Fluxo dedicado para submeter recibos e acompanhar o respetivo estado.',
                'status' => 'available',
            ],
        ];
    }

    private function resolveDriverBalance(int $driverId, int $weekId): ?DriversBalance
    {
        return DriversBalance::where([
            'tvde_week_id' => $weekId,
            'driver_id' => $driverId,
        ])->first();
    }

    private function serializeMediaCollection($mediaItems): array
    {
        return collect($mediaItems)
            ->map(function ($media) {
                return [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                ];
            })
            ->values()
            ->all();
    }

    private function serializeDriver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'code' => $driver->code,
            'name' => $driver->name,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'company' => $driver->company ? [
                'id' => $driver->company->id,
                'name' => $driver->company->name,
            ] : null,
            'state' => $driver->state ? [
                'id' => $driver->state->id,
                'name' => $driver->state->name,
            ] : null,
            'contract_vat' => $driver->contract_vat ? [
                'id' => $driver->contract_vat->id,
                'name' => $driver->contract_vat->name,
                'percent' => $driver->contract_vat->percent,
                'rf' => $driver->contract_vat->rf,
                'iva' => $driver->contract_vat->iva,
            ] : null,
        ];
    }

    private function serializeBalance(?DriversBalance $balance, Driver $driver): ?array
    {
        if (! $balance) {
            return null;
        }

        $vatFactor = (float) optional($driver->contract_vat)->iva / 100;
        $rfFactor = (float) optional($driver->contract_vat)->rf / 100;
        $vat = round(((float) $balance->value) * $vatFactor, 2);
        $rf = round(-((float) $balance->value) * $rfFactor, 2);

        return [
            'value' => (float) $balance->value,
            'last_balance' => (float) $balance->last_balance,
            'new_balance' => (float) $balance->new_balance,
            'vat' => $vat,
            'rf' => $rf,
            'final' => round(((float) $balance->new_balance) + $vat + $rf, 2),
        ];
    }
}
