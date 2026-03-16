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
        if (class_exists(\ZipArchive::class)) {
            return $this->readXlsxRowsFromZip($path);
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            throw new \RuntimeException('O servidor nao suporta leitura de ficheiros Excel (.xlsx). Ative a extensao ZIP do PHP ou importe em CSV.');
        }

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

    protected function readXlsxRowsFromZip(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Nao foi possivel abrir o ficheiro Excel.');
        }

        try {
            $sharedStrings = $this->extractXlsxSharedStrings($zip);
            $styleMap = $this->extractXlsxStyleMap($zip);
            $worksheetPath = $this->resolveFirstWorksheetPath($zip);
            $worksheetXml = $zip->getFromName($worksheetPath);

            if ($worksheetXml === false) {
                throw new \RuntimeException('Nao foi possivel ler a primeira folha do ficheiro Excel.');
            }

            $worksheet = simplexml_load_string($worksheetXml);
            if ($worksheet === false || !isset($worksheet->sheetData)) {
                throw new \RuntimeException('O ficheiro Excel tem uma estrutura invalida.');
            }

            $rows = [];

            foreach ($worksheet->sheetData->row as $rowNode) {
                $row = [];

                foreach ($rowNode->c as $cell) {
                    $reference = (string) ($cell['r'] ?? '');
                    $columnIndex = $this->xlsxColumnReferenceToIndex($reference);

                    if ($columnIndex < 0) {
                        continue;
                    }

                    while (count($row) < $columnIndex) {
                        $row[] = '';
                    }

                    $row[$columnIndex] = $this->extractXlsxCellValue($cell, $sharedStrings, $styleMap);
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            $zip->close();
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

    protected function extractXlsxSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $strings = [];
        foreach ($document->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $value = '';
            foreach ($item->r as $run) {
                $value .= (string) $run->t;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    protected function extractXlsxStyleMap(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/styles.xml');
        if ($xml === false) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $customFormats = [];
        if (isset($document->numFmts->numFmt)) {
            foreach ($document->numFmts->numFmt as $numFmt) {
                $customFormats[(int) $numFmt['numFmtId']] = (string) $numFmt['formatCode'];
            }
        }

        $styleMap = [];
        if (!isset($document->cellXfs->xf)) {
            return $styleMap;
        }

        foreach ($document->cellXfs->xf as $index => $xf) {
            $numFmtId = (int) ($xf['numFmtId'] ?? 0);
            $formatCode = $customFormats[$numFmtId] ?? null;

            $styleMap[(int) $index] = $this->isExcelDateFormat($numFmtId, $formatCode);
        }

        return $styleMap;
    }

    protected function resolveFirstWorksheetPath(\ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        if ($workbook === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $namespaces = $workbook->getNamespaces(true);
        $relationshipsNamespace = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $sheets = $workbook->sheets->sheet ?? null;

        if ($sheets === null || !isset($sheets[0])) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $sheets[0]->attributes($relationshipsNamespace)->id;
        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rels = simplexml_load_string($relsXml);
        if ($rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        foreach ($rels->Relationship as $relationship) {
            if ((string) ($relationship['Id'] ?? '') !== $relationshipId) {
                continue;
            }

            $target = (string) ($relationship['Target'] ?? '');
            if ($target === '') {
                break;
            }

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return 'xl/' . ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    protected function extractXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings, array $styleMap)
    {
        $type = (string) ($cell['t'] ?? '');
        $styleIndex = isset($cell['s']) ? (int) $cell['s'] : null;

        if ($type === 'inlineStr') {
            if (isset($cell->is->t)) {
                return (string) $cell->is->t;
            }

            $value = '';
            foreach ($cell->is->r as $run) {
                $value .= (string) $run->t;
            }

            return $value;
        }

        if ($type === 's') {
            $sharedStringIndex = isset($cell->v) ? (int) $cell->v : null;
            return $sharedStrings[$sharedStringIndex] ?? '';
        }

        if ($type === 'b') {
            return ((string) ($cell->v ?? '')) === '1' ? '1' : '0';
        }

        if ($type === 'str') {
            return (string) ($cell->v ?? '');
        }

        if (isset($cell->f) && !isset($cell->v)) {
            return '';
        }

        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($value !== '' && $styleIndex !== null && ($styleMap[$styleIndex] ?? false) && is_numeric($value)) {
            return $this->convertExcelSerialDate((float) $value);
        }

        return $value;
    }

    protected function xlsxColumnReferenceToIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($reference));
        if ($letters === '') {
            return -1;
        }

        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    protected function isExcelDateFormat(int $numFmtId, ?string $formatCode): bool
    {
        $builtinDateFormats = [
            14, 15, 16, 17, 18, 19, 20, 21, 22,
            27, 28, 29, 30, 31, 32, 33, 34, 35, 36,
            45, 46, 47, 50, 51, 52, 53, 54, 55, 56, 57, 58,
        ];

        if (in_array($numFmtId, $builtinDateFormats, true)) {
            return true;
        }

        if ($formatCode === null || $formatCode === '') {
            return false;
        }

        $normalized = strtolower(preg_replace('/"[^"]*"|\[[^\]]*]/', '', $formatCode));

        return str_contains($normalized, 'yy')
            || str_contains($normalized, 'dd')
            || str_contains($normalized, 'mm')
            || str_contains($normalized, 'hh')
            || str_contains($normalized, 'ss');
    }

    protected function convertExcelSerialDate(float $value): string
    {
        $base = Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC');
        $wholeDays = (int) floor($value);
        $seconds = (int) round(($value - $wholeDays) * 86400);

        return $base->copy()
            ->addDays($wholeDays)
            ->addSeconds($seconds)
            ->setTimezone(config('app.timezone', 'UTC'))
            ->format('Y-m-d H:i:s');
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
