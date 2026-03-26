<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCombustionTransactionRequest;
use App\Http\Requests\StoreCombustionTransactionRequest;
use App\Http\Requests\UpdateCombustionTransactionRequest;
use App\Models\CombustionTransaction;
use App\Models\TvdeWeek;
use App\Services\CombustionTransactionAssignmentService;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CombustionTransactionController extends Controller
{
    use CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('combustion_transaction_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = CombustionTransaction::with(['tvde_week'])->select(sprintf('%s.*', (new CombustionTransaction)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'combustion_transaction_show';
                $editGate      = 'combustion_transaction_edit';
                $deleteGate    = 'combustion_transaction_delete';
                $crudRoutePart = 'combustion-transactions';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->editColumn('card', function ($row) {
                return $row->card ? $row->card : '';
            });

            $table->editColumn('date', function ($row) {
                return $row->date ? $row->date->format('d-m-Y H:i') : '';
            });

            $table->editColumn('exist', function ($row) {
                if (!$row->card) {
                    return '<span class="badge badge-secondary">Sem cartão</span>';
                }

                // Procurar driver com este cartão
                $driver = \App\Models\Driver::where('card_id', function ($query) use ($row) {
                    $query->select('id')
                        ->from('cards')
                        ->where('code', $row->card)
                        ->limit(1);
                })
                    ->orWhereHas('cards', function ($query) use ($row) {
                        $query->where('code', $row->card);
                    })
                    ->first();

                if ($driver) {
                    return '';
                }

                return '<span class="badge badge-danger">Não existe</span>';
            });


            $table->editColumn('amount', function ($row) {
                return $row->amount ? $row->amount : '';
            });
            $table->editColumn('total', function ($row) {
                return $row->total ? $row->total : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'tvde_week', 'exist']);

            return $table->make(true);
        }

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get();

        return view('admin.combustionTransactions.index', compact('tvde_weeks'));
    }

    public function create()
    {
        abort_if(Gate::denies('combustion_transaction_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.combustionTransactions.create', compact('tvde_weeks'));
    }

    public function store(StoreCombustionTransactionRequest $request, CombustionTransactionAssignmentService $assignmentService)
    {
        $combustionTransaction = CombustionTransaction::create($request->all());
        $assignmentService->assign($combustionTransaction);

        return redirect()->route('admin.combustion-transactions.index');
    }

    public function edit(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $combustionTransaction->load('tvde_week');

        return view('admin.combustionTransactions.edit', compact('combustionTransaction', 'tvde_weeks'));
    }

    public function update(UpdateCombustionTransactionRequest $request, CombustionTransaction $combustionTransaction, CombustionTransactionAssignmentService $assignmentService)
    {
        $combustionTransaction->update($request->all());
        $assignmentService->assign($combustionTransaction);

        return redirect()->route('admin.combustion-transactions.index');
    }

    public function show(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $combustionTransaction->load('tvde_week');

        return view('admin.combustionTransactions.show', compact('combustionTransaction'));
    }

    public function destroy(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $combustionTransaction->delete();

        return back();
    }

    public function massDestroy(MassDestroyCombustionTransactionRequest $request)
    {
        $combustionTransactions = CombustionTransaction::find(request('ids'));

        foreach ($combustionTransactions as $combustionTransaction) {
            $combustionTransaction->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function deleteFilter(Request $request)
    {
        $request->validate([
            'week_filter' => ['required', 'integer', 'exists:tvde_weeks,id'],
        ]);

        CombustionTransaction::where('tvde_week_id', $request->week_filter)->delete();

        return redirect()->back()->with('message', 'Eliminado com sucesso');
    }

    public function uploadSupplierFile(Request $request, CombustionTransactionAssignmentService $assignmentService)
    {
        abort_if(Gate::denies('combustion_transaction_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'supplier' => ['nullable', 'in:repsol,prio'],
            'supplier_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $uploadedFile = $request->file('supplier_file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: '');
        $rows = $this->readImportRows($uploadedFile->getRealPath(), $extension);
        $supplier = $validated['supplier'] ?? $this->detectSupplierFromRows($rows);
        $transactions = [];

        if (!$supplier) {
            return redirect()->back()
                ->withErrors(['supplier_file' => 'Nao foi possivel identificar o fornecedor do ficheiro de abastecimentos.'])
                ->withInput();
        }

        foreach ($rows as $index => $row) {
            $transaction = $supplier === 'repsol'
                ? $this->mapRepsolRow($row, $index, (int) $validated['tvde_week_id'])
                : $this->mapPrioRow($row, $index, (int) $validated['tvde_week_id']);

            if (!$transaction) {
                continue;
            }

            $signature = implode('|', [
                $transaction['tvde_week_id'],
                $transaction['card'],
                $transaction['date'] ?? '',
                $transaction['amount'],
                $transaction['total'],
            ]);

            $transactions[$signature] = $transaction;
        }

        if (empty($transactions)) {
            return redirect()->back()
                ->withErrors(['supplier_file' => 'O ficheiro nao tem linhas validas para importar.'])
                ->withInput();
        }

        foreach ($transactions as $transaction) {
            $existing = CombustionTransaction::withTrashed()
                ->where('tvde_week_id', $transaction['tvde_week_id'])
                ->where('card', $transaction['card'])
                ->where('amount', $transaction['amount'])
                ->where('total', $transaction['total']);

            if ($transaction['date']) {
                $existing->where('date', $transaction['date']);
            } else {
                $existing->whereNull('date');
            }

            $existing = $existing->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $assignmentService->assign($existing);

                continue;
            }

            $created = CombustionTransaction::create($transaction);
            $assignmentService->assign($created);
        }

        return redirect()->back()
            ->with('message', sprintf('Importadas %d transacoes de %s com sucesso.', count($transactions), strtoupper($supplier)));
    }

    protected function detectSupplierFromRows(array $rows): ?string
    {
        $repsolMatches = 0;
        $prioMatches = 0;

        foreach ($rows as $index => $row) {
            if ($this->mapRepsolRow($row, $index, 1)) {
                $repsolMatches++;
            }

            if ($this->mapPrioRow($row, $index, 1)) {
                $prioMatches++;
            }
        }

        if ($repsolMatches === 0 && $prioMatches === 0) {
            return null;
        }

        return $repsolMatches >= $prioMatches ? 'repsol' : 'prio';
    }

    protected function mapRepsolRow(array $row, int $index, int $weekId): ?array
    {
        if ($index === 0) {
            return null;
        }

        $card = trim((string) ($row[3] ?? ''));
        $amount = $this->normalizeImportedNumber($row[5] ?? null);
        $total = $this->normalizeImportedNumber($row[7] ?? null);
        $date = $this->normalizeRepsolDate($row[0] ?? null);

        if ($card === '' || $amount === null || $total === null) {
            return null;
        }

        return [
            'tvde_week_id' => $weekId,
            'card' => $card,
            'amount' => $amount,
            'total' => $total,
            'date' => $date,
        ];
    }

    protected function mapPrioRow(array $row, int $index, int $weekId): ?array
    {
        if ($index < 4) {
            return null;
        }

        $card = trim((string) ($row[1] ?? ''));
        $amount = $this->normalizeImportedNumber($row[7] ?? null);
        $total = $this->normalizeImportedNumber($row[12] ?? null);
        $date = $this->normalizePrioDate($row[0] ?? null);

        if ($card === '' || $amount === null || $total === null) {
            return null;
        }

        return [
            'tvde_week_id' => $weekId,
            'card' => $card,
            'amount' => $amount,
            'total' => $total,
            'date' => $date,
        ];
    }

    protected function readImportRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($path);
        }

        if ($extension === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        throw new \RuntimeException('Formato de ficheiro nao suportado.');
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

        $csvPath = tempnam(sys_get_temp_dir(), 'supplier_');
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
            throw new \RuntimeException('Nao foi possivel converter o ficheiro Excel para CSV.');
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

    protected function normalizeRepsolDate($value): ?string
    {
        return $this->normalizeDateByFormats($value, [
            'm/d/Y',
            'd/m/Y',
            'n/j/Y',
            'j/n/Y',
            'Y-m-d',
        ]);
    }

    protected function normalizePrioDate($value): ?string
    {
        return $this->normalizeDateByFormats($value, [
            'j/n/y H:i:s',
            'j/n/Y H:i:s',
            'n/j/y H:i:s',
            'n/j/Y H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s',
            'Y-m-d H:i:s',
        ]);
    }

    protected function normalizeDateByFormats($value, array $formats): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $numericValue = str_replace(',', '.', $value);
        if (is_numeric($numericValue)) {
            return $this->convertExcelSerialDate((float) $numericValue);
        }

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if (str_contains($format, '/y') && $date->year < 100) {
                    $date->year += 2000;
                }

                if (!str_contains($format, 'H:i')) {
                    $date = $date->startOfDay();
                }

                return $date->format('Y-m-d H:i:s');
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
