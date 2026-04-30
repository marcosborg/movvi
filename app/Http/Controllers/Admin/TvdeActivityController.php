<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyTvdeActivityRequest;
use App\Http\Requests\StoreTvdeActivityRequest;
use App\Http\Requests\UpdateTvdeActivityRequest;
use App\Models\Company;
use App\Models\TvdeActivity;
use App\Models\TvdeOperator;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use SpreadsheetReader;

class TvdeActivityController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('tvde_activity_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            if (session()->get('company_id')) {
                $query = TvdeActivity::where('company_id', session()->get('company_id'))->with(['tvde_week', 'tvde_operator', 'company'])->select(sprintf('%s.*', (new TvdeActivity)->table));
            } else {
                $query = TvdeActivity::with(['tvde_week', 'tvde_operator', 'company'])->select(sprintf('%s.*', (new TvdeActivity)->table));
            }

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'tvde_activity_show';
                $editGate = 'tvde_activity_edit';
                $deleteGate = 'tvde_activity_delete';
                $crudRoutePart = 'tvde-activities';

                return view(
                    'partials.datatablesActions',
                    compact(
                        'viewGate',
                        'editGate',
                        'deleteGate',
                        'crudRoutePart',
                        'row'
                    )
                );
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->addColumn('tvde_operator_name', function ($row) {
                return $row->tvde_operator ? $row->tvde_operator->name : '';
            });

            $table->addColumn('company_name', function ($row) {
                return $row->company ? $row->company->name : '';
            });

            $table->editColumn('driver_code', function ($row) {
                return $row->driver_code ? $row->driver_code : '';
            });
            $table->editColumn('gross', function ($row) {
                return $row->gross ? $row->gross : '';
            });
            $table->editColumn('net', function ($row) {
                return $row->net ? $row->net : '';
            });
            $table->editColumn('tips', function ($row) {
                return $row->tips ? $row->tips : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'tvde_week', 'tvde_operator', 'company']);

            return $table->make(true);
        }

        $tvde_weeks = TvdeWeek::all();
        $companies = Company::all();
        $activeCompanyId = $this->resolveCompanyId();
        $activeCompany = $activeCompanyId ? Company::find($activeCompanyId) : null;

        return view('admin.tvdeActivities.index', compact('tvde_weeks', 'companies', 'activeCompany'));
    }

    public function create()
    {
        abort_if(Gate::denies('tvde_activity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_operators = TvdeOperator::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.tvdeActivities.create', compact('companies', 'tvde_operators', 'tvde_weeks'));
    }

    public function store(StoreTvdeActivityRequest $request)
    {
        $tvdeActivity = TvdeActivity::create($request->all());

        return redirect()->route('admin.tvde-activities.index');
    }

    public function edit(TvdeActivity $tvdeActivity)
    {
        abort_if(Gate::denies('tvde_activity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_operators = TvdeOperator::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvdeActivity->load('tvde_week', 'tvde_operator', 'company');

        return view('admin.tvdeActivities.edit', compact('companies', 'tvdeActivity', 'tvde_operators', 'tvde_weeks'));
    }

    public function update(UpdateTvdeActivityRequest $request, TvdeActivity $tvdeActivity)
    {
        $tvdeActivity->update($request->all());

        return redirect()->route('admin.tvde-activities.index');
    }

    public function show(TvdeActivity $tvdeActivity)
    {
        abort_if(Gate::denies('tvde_activity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvdeActivity->load('tvde_week', 'tvde_operator', 'company');

        return view('admin.tvdeActivities.show', compact('tvdeActivity'));
    }

    public function destroy(TvdeActivity $tvdeActivity)
    {
        abort_if(Gate::denies('tvde_activity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvdeActivity->delete();

        return back();
    }

    public function massDestroy(MassDestroyTvdeActivityRequest $request)
    {
        $tvdeActivities = TvdeActivity::find(request('ids'));

        foreach ($tvdeActivities as $tvdeActivity) {
            $tvdeActivity->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteFilter(Request $request)
    {

        $request->validate([
            'week_filter' => 'required',
            'platform' => ['nullable', 'in:uber,bolt'],
        ]);

        if($request->company_filter){
            $tvde_activities = TvdeActivity::where([
                'tvde_week_id' => $request->week_filter,
                'company_id' => $request->company_filter
            ]);
        } else {
            $tvde_activities = TvdeActivity::where([
                'tvde_week_id' => $request->week_filter
            ]);
        }

        if ($request->filled('platform')) {
            $operator = $this->resolvePlatformOperator($request->platform);
            $tvde_activities->where('tvde_operator_id', $operator->id);
        }

        $tvde_activities->delete();

        return redirect()->back()->with('message', 'Eliminado com sucesso');
    }

    public function uploadPlatformCsv(Request $request)
    {
        abort_if(Gate::denies('tvde_activity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'platform' => ['required', 'in:uber,bolt'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $companyId = $this->resolveCompanyId($validated['company_id'] ?? null);

        if (!$companyId) {
            return redirect()->back()
                ->withErrors(['csv_file' => 'Nao foi possivel determinar a empresa ativa para esta importacao.'])
                ->withInput();
        }

        $operator = $this->resolvePlatformOperator($validated['platform']);
        $mapping = $this->platformCsvMapping($validated['platform']);
        $reader = $this->platformCsvRows($request->file('csv_file')->getRealPath(), $validated['platform']);
        $rows = [];

        foreach ($reader as $row) {
            $activity = $this->mapPlatformActivityRow(
                $row,
                $mapping,
                (int) $validated['tvde_week_id'],
                $operator->id,
                (int) $companyId
            );

            if (!$activity) {
                continue;
            }

            $signature = implode('|', [
                $activity['tvde_week_id'],
                $activity['tvde_operator_id'],
                $activity['company_id'],
                $activity['driver_code'],
            ]);

            if (isset($rows[$signature])) {
                $rows[$signature]['gross'] += $activity['gross'];
                $rows[$signature]['net'] += $activity['net'];
                $rows[$signature]['tips'] += $activity['tips'];
                continue;
            }

            $rows[$signature] = $activity;
        }

        if (empty($rows)) {
            return redirect()->back()
                ->withErrors(['csv_file' => 'O ficheiro nao tem linhas validas para importar.'])
                ->withInput();
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $activity) {
                $lookup = [
                    'tvde_week_id' => $activity['tvde_week_id'],
                    'tvde_operator_id' => $activity['tvde_operator_id'],
                    'company_id' => $activity['company_id'],
                    'driver_code' => $activity['driver_code'],
                ];

                $existing = TvdeActivity::withTrashed()->where($lookup)->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->fill([
                        'gross' => $activity['gross'],
                        'net' => $activity['net'],
                        'tips' => $activity['tips'],
                    ])->save();

                    continue;
                }

                TvdeActivity::create($activity);
            }
        });

        return redirect()->back()
            ->with('message', sprintf('Importados %d registos de %s com sucesso.', count($rows), strtoupper($validated['platform'])));
    }

    protected function resolveCompanyId(?int $requestCompanyId = null): ?int
    {
        if ($requestCompanyId) {
            return $requestCompanyId;
        }

        $companyId = session()->get('company_id');

        if ($companyId && $companyId !== '0') {
            return (int) $companyId;
        }

        $userCompany = optional(auth()->user()->company)->id;

        return $userCompany ? (int) $userCompany : null;
    }

    protected function resolvePlatformOperator(string $platform): TvdeOperator
    {
        $name = $platform === 'uber' ? 'Uber' : 'Bolt';

        return TvdeOperator::firstOrCreate(['name' => $name]);
    }

    protected function platformCsvMapping(string $platform): array
    {
        if ($platform === 'uber') {
            return [
                'driver_code' => 0,
                'driver_code_stable' => 0,
                'gross' => 6,
                'net' => 3,
                'tips' => 18,
            ];
        }

        return [
            'driver_code' => 27,
            'driver_code_stable' => 28,
            'gross' => 3,
            'net' => 21,
            'tips' => 9,
        ];
    }

    protected function mapPlatformActivityRow(array $row, array $mapping, int $weekId, int $operatorId, int $companyId): ?array
    {
        $stableDriverCodeIndex = $mapping['driver_code_stable'] ?? null;
        $legacyDriverCodeIndex = $mapping['driver_code'] ?? null;

        $stableDriverCode = $stableDriverCodeIndex !== null
            ? trim((string) ($row[$stableDriverCodeIndex] ?? ''))
            : '';
        $legacyDriverCode = $legacyDriverCodeIndex !== null
            ? trim((string) ($row[$legacyDriverCodeIndex] ?? ''))
            : '';
        $driverCode = $stableDriverCode !== '' ? $stableDriverCode : $legacyDriverCode;
        $gross = $this->normalizeImportedNumber($row[$mapping['gross']] ?? null);
        $net = $this->normalizeImportedNumber($row[$mapping['net']] ?? null);
        $tips = $this->normalizeImportedNumber($row[$mapping['tips']] ?? null);

        if ($driverCode === '') {
            return null;
        }

        if ($gross === null && $net === null && $tips === null) {
            return null;
        }

        return [
            'tvde_week_id' => $weekId,
            'tvde_operator_id' => $operatorId,
            'company_id' => $companyId,
            'driver_code' => $driverCode,
            'gross' => $gross ?? 0,
            'net' => $net ?? 0,
            'tips' => $tips ?? 0,
        ];
    }

    protected function normalizeImportedNumber($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $negative = false;
        if (substr($value, 0, 1) === '(' && substr($value, -1) === ')') {
            $negative = true;
            $value = substr($value, 1, -1);
        }

        $value = preg_replace('/[^0-9,.\-]/', '', $value);

        if ($value === '' || $value === '-' || $value === null) {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $negative ? 0 - $number : $number;
    }

    protected function platformCsvRows(string $path, string $platform): iterable
    {
        if ($platform === 'bolt') {
            return $this->readBoltCsvRows($path);
        }

        return new SpreadsheetReader($path);
    }

    protected function readBoltCsvRows(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        return collect($lines)
            ->map(fn (string $line) => $this->parseBoltCsvLine($line))
            ->filter(fn (array $row) => !empty($row))
            ->values()
            ->all();
    }

    protected function parseBoltCsvLine(string $line): array
    {
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
        $line = trim($line);

        if ($line === '') {
            return [];
        }

        $line = rtrim($line, ';');
        $line = ltrim($line, '"');

        if ($line === '') {
            return [];
        }

        $parts = preg_split('/,""/', $line) ?: [];

        return collect($parts)
            ->map(function (string $value, int $index) {
                if ($index > 0 && str_ends_with($value, '""')) {
                    $value = substr($value, 0, -2);
                }

                return trim($value, '"');
            })
            ->values()
            ->all();
    }
}
