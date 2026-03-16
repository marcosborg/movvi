<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyCombustionTransactionRequest;
use App\Http\Requests\StoreCombustionTransactionRequest;
use App\Http\Requests\UpdateCombustionTransactionRequest;
use App\Models\CombustionTransaction;
use App\Models\TvdeWeek;
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

    public function store(StoreCombustionTransactionRequest $request)
    {
        $combustionTransaction = CombustionTransaction::create($request->all());

        return redirect()->route('admin.combustion-transactions.index');
    }

    public function edit(CombustionTransaction $combustionTransaction)
    {
        abort_if(Gate::denies('combustion_transaction_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $combustionTransaction->load('tvde_week');

        return view('admin.combustionTransactions.edit', compact('combustionTransaction', 'tvde_weeks'));
    }

    public function update(UpdateCombustionTransactionRequest $request, CombustionTransaction $combustionTransaction)
    {
        $combustionTransaction->update($request->all());

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

    public function uploadSupplierFile(Request $request)
    {
        abort_if(Gate::denies('combustion_transaction_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'tvde_week_id' => ['required', 'integer', 'exists:tvde_weeks,id'],
            'supplier' => ['required', 'in:repsol,prio'],
            'supplier_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $uploadedFile = $request->file('supplier_file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: '');
        $rows = $this->readImportRows($uploadedFile->getRealPath(), $extension);
        $transactions = [];

        foreach ($rows as $index => $row) {
            $transaction = $validated['supplier'] === 'repsol'
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

                continue;
            }

            CombustionTransaction::create($transaction);
        }

        return redirect()->route('admin.combustion-transactions.index')
            ->with('message', sprintf('Importadas %d transacoes de %s com sucesso.', count($transactions), strtoupper($validated['supplier'])));
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
