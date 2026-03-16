<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCarTrackRequest;
use App\Http\Requests\StoreCarTrackRequest;
use App\Http\Requests\UpdateCarTrackRequest;
use App\Models\CarTrack;
use App\Models\TvdeWeek;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CarTrackController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('car_track_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = CarTrack::query()
                ->leftJoin('tvde_weeks', 'tvde_weeks.id', '=', 'car_tracks.tvde_week_id')
                ->select([
                    'car_tracks.id',
                    'car_tracks.date',
                    'car_tracks.license_plate',
                    'car_tracks.value',
                    'tvde_weeks.start_date as tvde_week_start_date',
                    'car_tracks.deleted_at',
                ]);

            $table = DataTables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'car_track_show';
                $editGate      = 'car_track_edit';
                $deleteGate    = 'car_track_delete';
                $crudRoutePart = 'car-tracks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', fn($row) => $row->id ?: '');
            $table->editColumn('date', fn($row) => $row->date ?: '');
            $table->editColumn('license_plate', fn($row) => $row->license_plate ?: '');
            $table->editColumn('value', fn($row) => $row->value ?: '');
            $table->editColumn('tvde_week_start_date', fn($row) => $row->tvde_week_start_date ?: '');

            // já não tens nenhuma coluna HTML chamada 'tvde_week'
            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get();

        return view('admin.carTracks.index', compact('tvde_weeks'));
    }

    public function create()
    {
        abort_if(Gate::denies('car_track_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.carTracks.create', compact('tvde_weeks'));
    }

    public function store(StoreCarTrackRequest $request)
    {
        $carTrack = CarTrack::create($request->all());

        return redirect()->route('admin.car-tracks.index');
    }

    public function edit(CarTrack $carTrack)
    {
        abort_if(Gate::denies('car_track_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carTrack->load('tvde_week');

        return view('admin.carTracks.edit', compact('carTrack', 'tvde_weeks'));
    }

    public function update(UpdateCarTrackRequest $request, CarTrack $carTrack)
    {
        $carTrack->update($request->all());

        return redirect()->route('admin.car-tracks.index');
    }

    public function show(CarTrack $carTrack)
    {
        abort_if(Gate::denies('car_track_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.carTracks.show', compact('carTrack'));
    }

    public function destroy(CarTrack $carTrack)
    {
        abort_if(Gate::denies('car_track_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carTrack->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarTrackRequest $request)
    {
        $carTracks = CarTrack::find(request('ids'));

        foreach ($carTracks as $carTrack) {
            $carTrack->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function uploadViaVerde(Request $request)
    {
        abort_if(Gate::denies('car_track_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'via_verde_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = [];
        $uploadedFile = $request->file('via_verde_file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: '');

        foreach ($this->readViaVerdeFileRows($uploadedFile->getRealPath(), $extension) as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $carTrackRow = $this->mapViaVerdeRow($row, (int) $validated['tvde_week_id']);
            if (!$carTrackRow) {
                continue;
            }

            $signature = implode('|', [
                $carTrackRow['tvde_week_id'],
                $carTrackRow['license_plate'],
                $carTrackRow['date'] ?? '',
                $carTrackRow['value'],
            ]);

            $rows[$signature] = $carTrackRow;
        }

        if (empty($rows)) {
            return redirect()->back()
                ->withErrors(['via_verde_file' => 'O ficheiro Via Verde nao tem linhas validas para importar.'])
                ->withInput();
        }

        foreach ($rows as $row) {
            $existing = CarTrack::withTrashed()
                ->where('tvde_week_id', $row['tvde_week_id'])
                ->where('license_plate', $row['license_plate'])
                ->where('value', $row['value']);

            if ($row['date']) {
                $existing->where('date', $row['date']);
            } else {
                $existing->whereNull('date');
            }

            $existing = $existing->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                continue;
            }

            CarTrack::create($row);
        }

        return redirect()->route('admin.car-tracks.index')
            ->with('message', sprintf('Importados %d registos Via Verde com sucesso.', count($rows)));
    }

    protected function readViaVerdeFileRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($path);
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        throw new \RuntimeException('Formato de ficheiro Via Verde nao suportado.');
    }

    protected function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Nao foi possivel abrir o ficheiro CSV.');
        }

        $delimiter = $this->detectCsvDelimiter($handle);
        rewind($handle);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    protected function readXlsxRows(string $path): array
    {
        $csvPath = tempnam(sys_get_temp_dir(), 'via_verde_');
        if ($csvPath === false) {
            throw new \RuntimeException('Nao foi possivel preparar o ficheiro temporario para importacao.');
        }

        $csvWithExtension = $csvPath . '.csv';
        if (file_exists($csvWithExtension)) {
            unlink($csvWithExtension);
        }

        rename($csvPath, $csvWithExtension);

        try {
            $this->convertExcelToCsv($path, $csvWithExtension);

            return $this->readCsvRows($csvWithExtension);
        } finally {
            if (file_exists($csvWithExtension)) {
                unlink($csvWithExtension);
            }
        }
    }

    protected function convertExcelToCsv(string $sourcePath, string $targetPath): void
    {
        $sourcePath = str_replace("'", "''", $sourcePath);
        $targetPath = str_replace("'", "''", $targetPath);

        $command = sprintf(
            "powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command \"\$excel = New-Object -ComObject Excel.Application; \$excel.Visible = \$false; \$excel.DisplayAlerts = \$false; \$workbook = \$excel.Workbooks.Open('%s'); \$worksheet = \$workbook.Worksheets.Item(1); \$worksheet.SaveAs('%s', 6); \$workbook.Close(\$false); \$excel.Quit(); [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$worksheet) | Out-Null; [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$workbook) | Out-Null; [System.Runtime.Interopservices.Marshal]::ReleaseComObject(\$excel) | Out-Null;\"",
            $sourcePath,
            $targetPath
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($targetPath)) {
            throw new \RuntimeException('Nao foi possivel converter o ficheiro XLSX da Via Verde para CSV.');
        }
    }

    protected function detectCsvDelimiter($handle): string
    {
        $firstLine = fgets($handle);

        if ($firstLine === false) {
            return ';';
        }

        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');

        return $semicolonCount >= $commaCount ? ';' : ',';
    }

    protected function mapViaVerdeRow(array $row, int $weekId): ?array
    {
        $licensePlate = trim((string) ($row[2] ?? ''));
        $value = $this->normalizeImportedNumber($row[9] ?? null);
        $date = $this->normalizeViaVerdeDate($row[6] ?? null);

        if ($licensePlate === '' || $value === null) {
            return null;
        }

        return [
            'tvde_week_id' => $weekId,
            'license_plate' => $licensePlate,
            'date' => $date,
            'value' => $value,
        ];
    }

    protected function normalizeViaVerdeDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '--') {
            return null;
        }

        foreach ([
            'd-m-Y H:i:s',
            'd/m/Y H:i:s',
            'Y-m-d H:i:s',
            'd-m-Y H:i',
            'd/m/Y H:i',
        ] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
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

        if ($value === '' || $value === '-') {
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
}
