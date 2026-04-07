<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use Illuminate\Http\Request;
use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use App\Models\Driver;
use App\Models\Reimbursement;
use App\Models\Company;
use App\Models\TvdeWeek;
use App\Models\WeeklyVehicleMileage;
use App\Models\TvdeActivity;
use App\Models\CombustionTransaction;
use App\Models\CarTrack;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CompanyReportController extends Controller
{

    use Reports;
    public function index()
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $results = $this->getWeekReport($company_id, $tvde_week_id);
        $mileageCount = WeeklyVehicleMileage::where('tvde_week_id', $tvde_week_id)->count();
        $importState = [
            'uber' => TvdeActivity::where('tvde_week_id', $tvde_week_id)
                ->where('company_id', $company_id)
                ->where('tvde_operator_id', 1)
                ->exists(),
            'bolt' => TvdeActivity::where('tvde_week_id', $tvde_week_id)
                ->where('company_id', $company_id)
                ->where('tvde_operator_id', 2)
                ->exists(),
            'fuel' => CombustionTransaction::where('tvde_week_id', $tvde_week_id)->exists(),
            'via_verde' => CarTrack::where('tvde_week_id', $tvde_week_id)->exists(),
            'mileage' => $mileageCount > 0,
        ];

        $sortBy = request()->query('sort_by', 'name');
        $sortDirection = request()->query('sort_direction', 'asc');
        $drivers = $this->sortCompanyReportDrivers($results['drivers'], $sortBy, $sortDirection);

        return view('admin.companyReports.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $drivers,
            'totals' => $results['totals'],
            'mileageCount' => $mileageCount,
            'importState' => $importState,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    protected function sortCompanyReportDrivers($drivers, string $sortBy, string $sortDirection = 'asc')
    {
        $collection = collect($drivers);
        $descending = $sortDirection === 'desc';

        $sorted = match ($sortBy) {
            'total' => $descending
                ? $collection->sortByDesc(fn ($driver) => (float) ($driver->total ?? 0))
                : $collection->sortBy(fn ($driver) => (float) ($driver->total ?? 0)),
            'weekly_km' => $descending
                ? $collection->sortByDesc(fn ($driver) => (float) ($driver->weekly_km ?? 0))
                : $collection->sortBy(fn ($driver) => (float) ($driver->weekly_km ?? 0)),
            'earnings_per_km' => $descending
                ? $collection->sortByDesc(fn ($driver) => (float) ($driver->earnings_per_km ?? 0))
                : $collection->sortBy(fn ($driver) => (float) ($driver->earnings_per_km ?? 0)),
            'percent_value' => $descending
                ? $collection->sortByDesc(fn ($driver) => (float) data_get($driver, 'earnings.percent_value', 0))
                : $collection->sortBy(fn ($driver) => (float) data_get($driver, 'earnings.percent_value', 0)),
            'car_hire' => $descending
                ? $collection->sortByDesc(fn ($driver) => (float) data_get($driver, 'earnings.car_hire', 0))
                : $collection->sortBy(fn ($driver) => (float) data_get($driver, 'earnings.car_hire', 0)),
            default => $descending
                ? $collection->sortByDesc(fn ($driver) => mb_strtolower((string) ($driver->name ?? '')))
                : $collection->sortBy(fn ($driver) => mb_strtolower((string) ($driver->name ?? ''))),
        };

        return $sorted->values();
    }

    public function pdf(Request $request, $download = null)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_week = TvdeWeek::find($tvde_week_id);

        $results = $this->getWeekReport($company_id, $tvde_week_id);

        $company = Company::find($company_id);
        $main_company = Company::where('main', true)->first();

        $sortBy = $request->query('sort_by', 'name');
        $sortDirection = $request->query('sort_direction', 'asc');
        $drivers = $this->sortCompanyReportDrivers($results['drivers'], $sortBy, $sortDirection);

        $pdf = Pdf::loadView('admin.companyReports.pdf', [
            'company' => $company,
            'main_company' => $main_company,
            'tvde_week' => $tvde_week,
            'drivers' => $drivers,
            'totals' => $results['totals'],
        ])->setOption([
            'isRemoteEnabled' => true,
        ]);

        $filename = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9\-]/', '', ($company->name ?? 'empresa') . '-' . ($tvde_week->start_date ?? 'semana')))) . '.pdf';

        if ($download) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function uploadMileage(Request $request)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'mileage_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $uploadedFile = $request->file('mileage_file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: '');
        $rows = $this->readMileageFileRows($uploadedFile->getRealPath(), $extension);
        $parsed = $this->parseMileageRows($rows, (int) $validated['tvde_week_id']);

        if (empty($parsed['rows'])) {
            return redirect()->back()
                ->withErrors(['mileage_file' => 'O ficheiro de quil?metros n?o tem linhas v?lidas para importar.'])
                ->withInput();
        }

        foreach ($parsed['rows'] as $row) {
            WeeklyVehicleMileage::updateOrCreate(
                [
                    'tvde_week_id' => $row['tvde_week_id'],
                    'license_plate' => $row['license_plate'],
                ],
                $row
            );
        }

        return redirect()->back()
            ->with('message', sprintf('Importados %d registos de quilómetros com sucesso.', count($parsed['rows'])));
    }

    public function deleteMileage(Request $request)
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
        ]);

        WeeklyVehicleMileage::where('tvde_week_id', $validated['tvde_week_id'])->delete();

        return redirect()->back()
            ->with('message', 'Quil?metros da semana eliminados com sucesso.');
    }

    public function validateData(Request $request)
    {

        foreach ($request->data as $data) {

            // 🔹 Função inline para normalizar valores vindos do front
            $normalize = function ($value): float {
                if (is_numeric($value)) {
                    return (float) $value; // já é número limpo
                }
                $v = str_replace(' ', '', (string) $value);   // remove espaços normais
                $v = str_replace("\xc2\xa0", '', $v);         // remove NBSP (utf-8)
                $v = str_replace('.', '', $v);                // tira separador de milhar
                $v = str_replace(',', '.', $v);               // vírgula → ponto decimal
                return (float) $v;
            };

            // 🔹 Normaliza o total do motorista
            $total = $normalize($data['driver']['total']);

            // 🔹 Registo da conta corrente
            $current_account = new CurrentAccount;
            $current_account->tvde_week_id = $data['tvde_week_id'];
            $current_account->driver_id    = $data['driver']['id'];
            $current_account->data         = json_encode($this->buildCurrentAccountPayload($data['driver']));
            $current_account->save();

            // 🔹 Último saldo
            $last_balance = DriversBalance::where('driver_id', $data['driver']['id'])
                ->where('tvde_week_id', '!=', $data['tvde_week_id'])
                ->orderBy('tvde_week_id', 'desc')
                ->first();

            $last_balance = $last_balance ? (float) $last_balance->new_balance : 0.0;
            $new_balance = $last_balance + $total;

            // 🔹 Novo saldo
            $driver_balance = new DriversBalance;
            $driver_balance->driver_id       = $data['driver']['id'];
            $driver_balance->tvde_week_id    = $data['tvde_week_id'];
            $driver_balance->value           = $total;
            $driver_balance->last_balance    = $last_balance;
            $driver_balance->new_balance     = $new_balance;
            $driver_balance->manual_status   = null;
            $driver_balance->save();

            /*
        $email = $data['driver']['email'];

        Notification::route('mail', $email)
            ->notify(new ActivityLaunchesSend());
        */
        }
    }

    public function revalidateData(Request $request)
    {
        $driver_id = $request->driver_id;
        $company_id = Driver::find($driver_id)->company_id;
        $tvde_week_id = $request->tvde_week_id;
        $data = $request->data;

        $current_account = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();
        $current_account->data = json_encode($this->buildCurrentAccountPayload($data['driver']));
        $current_account->save();

        $existingBalance = DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();
        $existingManualStatus = $existingBalance?->manual_status;

        DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->delete();

        $last_balance = DriversBalance::where([
            'driver_id' => $data['driver']['id'],
        ])->orderBy('tvde_week_id', 'desc')->first();

        $previous_balance = $last_balance ? (float) $last_balance->new_balance : 0.0;
        $new_balance = $previous_balance + ($data['driver']['total'] ?? 0);

        $driver_balance = new DriversBalance;
        $driver_balance->driver_id = $data['driver']['id'];
        $driver_balance->tvde_week_id = $data['tvde_week_id'];
        $driver_balance->value = $data['driver']['total'];
        $driver_balance->last_balance = $previous_balance;
        $driver_balance->new_balance = $new_balance;
        $driver_balance->manual_status = $existingManualStatus;
        $driver_balance->save();
    }

    protected function buildCurrentAccountPayload(array $driverData): array
    {
        $earnings = $driverData['earnings'] ?? [];

        $earnings['weekly_km'] = isset($driverData['weekly_km']) ? (float) $driverData['weekly_km'] : (float) ($earnings['weekly_km'] ?? 0);
        $earnings['earnings_per_km'] = isset($driverData['earnings_per_km']) ? (float) $driverData['earnings_per_km'] : (float) ($earnings['earnings_per_km'] ?? 0);
        $earnings['total'] = isset($driverData['total']) ? (float) $driverData['total'] : (float) ($earnings['total'] ?? $earnings['driver_total'] ?? 0);
        $earnings['driver_total'] = $earnings['total'];

        return $earnings;
    }

    public function deleteData($tvde_week_id, $driver_id)
    {

        $current_account = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();

        if ($current_account) {
            $current_account->delete();
        }

        DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->delete();

        return redirect()->route('admin.company-reports.index')->with('message', 'Data deleted successfully.');
    }

    public function driverReportAllWeeks($driver_id = NULL, $state_id = 1)
    {
        $companyId = session()->get('company_id') ?: \App\Models\Company::where('main', true)->value('id');
        $drivers = Driver::where('company_id', $companyId)
            ->where('state_id', $state_id)
            ->orderBy('name')
            ->get();

        $driver_id = $driver_id ?? $drivers->first()?->id;

        $weeks = \App\Models\TvdeWeek::orderBy('start_date', 'desc')->get();

        $results = [];

        foreach ($weeks as $week) {
            if (!$driver_id) {
                break;
            }

            $account = \App\Models\CurrentAccount::where([
                'tvde_week_id' => $week->id,
                'driver_id' => $driver_id
            ])->first();

            $balance = \App\Models\DriversBalance::where([
                'tvde_week_id' => $week->id,
                'driver_id' => $driver_id
            ])->first();

            $receipt = \App\Models\Receipt::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $week->id,
            ])->latest()->first();

            // ➜ Devoluções validadas (motorista → empresa) nesta semana
            $reimbursed = Reimbursement::where([
                'driver_id'    => $driver_id,
                'tvde_week_id' => $week->id,
                'verified'     => 1,                 // só as validadas
            ])->sum('value');

            $data = $account ? json_decode($account->data) : null;

            $amount_transferred = ($receipt->amount_transferred ?? 0) - $reimbursed;

            $results[] = [
                'week' => $week,
                'uber_gross' => $data->uber->uber_gross ?? 0,
                'bolt_gross' => $data->bolt->bolt_gross ?? 0,
                'uber_net' => $data->uber->uber_net ?? 0,
                'bolt_net' => $data->bolt->bolt_net ?? 0,
                'total_gross' => $data->total_gross ?? 0,
                'total_net' => $data->total_net ?? 0,
                'adjustments' => $data->adjustments ?? 0,
                'total' => $data->total ?? 0,
                'vat_value' => $data->vat_value ?? 0,
                'car_track' => $data->car_track ?? 0,
                'car_hire' => $data->car_hire ?? 0,
                'fuel_transactions' => $data->fuel_transactions ?? 0,
                'driver_balance' => $balance->balance ?? 0,
                'amount_transferred'   => $amount_transferred,
            ];
        }

        return view('admin.companyReports.driverReportAllWeeks')->with([
            'drivers' => $drivers,
            'driver_id' => $driver_id,
            'results' => $results,
            'state_id' => $state_id
        ]);
    }

    protected function readMileageFileRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($path);
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        throw new \RuntimeException('Formato de ficheiro de quilómetros nao suportado.');
    }

    protected function parseMileageRows(array $rows, int $weekId): array
    {
        $headerRowIndex = null;

        foreach ($rows as $index => $row) {
            $firstCell = $this->normalizeMileageLabel($row[0] ?? '');
            $lastCell = $this->normalizeMileageLabel($row[7] ?? '');

            if (Str::startsWith($firstCell, 'matr') && Str::startsWith($lastCell, 'dist')) {
                $headerRowIndex = $index;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return ['rows' => [], 'period' => []];
        }

        $period = $this->extractMileagePeriod($rows);
        $parsedRows = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $plate = trim((string) ($row[0] ?? ''));

            if ($plate === '') {
                continue;
            }

            if (str_starts_with($this->normalizeMileageLabel($plate), 'total')) {
                break;
            }

            $distanceKm = $this->normalizeImportedNumber($row[7] ?? null);
            if ($distanceKm === null) {
                continue;
            }

            $parsedRows[] = [
                'tvde_week_id' => $weekId,
                'license_plate' => $plate,
                'description' => trim((string) ($row[1] ?? '')) ?: null,
                'odometer_start' => $this->normalizeImportedNumber($row[3] ?? null),
                'odometer_end' => $this->normalizeImportedNumber($row[6] ?? null),
                'distance_km' => $distanceKm,
                'source_period_start' => $period['start'] ?? null,
                'source_period_end' => $period['end'] ?? null,
            ];
        }

        return [
            'rows' => $parsedRows,
            'period' => $period,
        ];
    }

    protected function extractMileagePeriod(array $rows): array
    {
        foreach ($rows as $row) {
            $value = trim((string) ($row[0] ?? ''));
            $normalizedValue = $this->normalizeMileageLabel($value);

            if (!str_contains($normalizedValue, 'data in') || !str_contains($normalizedValue, 'data fim')) {
                continue;
            }

            if (!preg_match('/Data\s+in.*?:\s*([0-9:\-\s\+]+)\s*-\s*Data\s+fim:\s*([0-9:\-\s\+]+)/iu', $value, $matches)) {
                continue;
            }

            return [
                'start' => $this->normalizeMileagePeriodDate($matches[1] ?? null),
                'end' => $this->normalizeMileagePeriodDate($matches[2] ?? null),
            ];
        }

        return [];
    }

    protected function normalizeMileagePeriodDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach ([
            'Y-m-d H:i:sO',
            'Y-m-d H:i:s',
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

        $csvPath = tempnam(sys_get_temp_dir(), 'weekly_mileage_');
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
            throw new \RuntimeException('Nao foi possivel converter o ficheiro XLSX de quilómetros para CSV.');
        }
    }

    protected function normalizeMileageLabel($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);

        return trim($value);
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
