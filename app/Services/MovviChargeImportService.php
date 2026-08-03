<?php

namespace App\Services;

use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\MovviChargeImport;
use App\Models\TvdeWeek;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovviChargeImportService
{
    private const SHEET_NAME = 'por motorista';

    private const EXPECTED_HEADERS = [
        'id',
        'motorista',
        'matricula',
        'sessoes',
        'kwh',
        'valor (eur)',
        '%',
    ];

    public function import(UploadedFile $file, ?int $userId): array
    {
        $parsed = $this->parse($file->getRealPath(), $file->getClientOriginalName());
        $week = $this->resolveWeek($parsed['year'], $parsed['week']);
        $this->ensureWeekIsOpen($week);

        $driverIds = collect($parsed['rows'])->pluck('driver_id')->unique()->values();
        $drivers = Driver::whereIn('id', $driverIds)->get(['id', 'name'])->keyBy('id');
        $unknownDriverIds = $driverIds->reject(fn ($id) => $drivers->has($id))->values()->all();
        $validRows = collect($parsed['rows'])
            ->filter(fn ($row) => $drivers->has($row['driver_id']))
            ->groupBy('driver_id')
            ->map(function ($rows, $driverId) use ($drivers) {
                $first = $rows->first();

                return [
                    'driver_id' => (int) $driverId,
                    'driver_name' => $drivers->get($driverId)->name,
                    'license_plate' => $first['license_plate'],
                    'sessions' => (int) $rows->sum('sessions'),
                    'kwh' => round((float) $rows->sum('kwh'), 2),
                    'value' => round((float) $rows->sum('value'), 2),
                ];
            })
            ->values();

        if ($validRows->isEmpty()) {
            throw ValidationException::withMessages([
                'charge_file' => 'O ficheiro não contém IDs de motoristas válidos.',
            ]);
        }

        $wasReplacement = MovviChargeImport::where('tvde_week_id', $week->id)->exists();
        $totals = [
            'row_count' => $validRows->count(),
            'total_sessions' => (int) $validRows->sum('sessions'),
            'total_kwh' => round((float) $validRows->sum('kwh'), 2),
            'total_value' => round((float) $validRows->sum('value'), 2),
        ];

        DB::transaction(function () use ($file, $userId, $week, $validRows, $totals) {
            $lockedWeek = TvdeWeek::whereKey($week->id)->lockForUpdate()->firstOrFail();
            $this->ensureWeekIsOpen($lockedWeek);

            MovviChargeImport::where('tvde_week_id', $week->id)->delete();

            $import = MovviChargeImport::create(array_merge($totals, [
                'tvde_week_id' => $week->id,
                'imported_by' => $userId,
                'original_filename' => $file->getClientOriginalName(),
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'imported_at' => now(),
            ]));

            $import->entries()->createMany($validRows->all());
        });

        return array_merge($totals, [
            'year' => $parsed['year'],
            'week' => $parsed['week'],
            'unknown_driver_ids' => $unknownDriverIds,
            'was_replacement' => $wasReplacement,
        ]);
    }

    public function parse(string $path, string $originalFilename): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'charge_file' => 'Não foi possível ler o ficheiro Excel.',
            ]);
        }

        $sheet = collect($spreadsheet->getWorksheetIterator())
            ->first(fn (Worksheet $candidate) => $this->normalize($candidate->getTitle()) === self::SHEET_NAME);

        if (! $sheet) {
            throw ValidationException::withMessages([
                'charge_file' => 'A folha “Por Motorista” não foi encontrada.',
            ]);
        }

        $titleCode = $this->extractWeekCode((string) $sheet->getCell('A1')->getFormattedValue());
        $filenameCode = $this->extractWeekCode($originalFilename);

        if (! $titleCode || ! $filenameCode) {
            throw ValidationException::withMessages([
                'charge_file' => 'Não foi possível identificar a semana no título da folha e no nome do ficheiro.',
            ]);
        }

        if ($titleCode !== $filenameCode) {
            throw ValidationException::withMessages([
                'charge_file' => 'A semana no nome do ficheiro não coincide com a semana indicada na folha.',
            ]);
        }

        $headers = [];
        foreach (range('A', 'G') as $column) {
            $headers[] = $this->normalize((string) $sheet->getCell($column.'2')->getFormattedValue());
        }

        if ($headers !== self::EXPECTED_HEADERS) {
            throw ValidationException::withMessages([
                'charge_file' => 'Os cabeçalhos da folha “Por Motorista” não correspondem ao formato esperado.',
            ]);
        }

        $rows = [];
        for ($rowNumber = 3; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
            $idValue = trim((string) $sheet->getCell('A'.$rowNumber)->getFormattedValue());
            if ($idValue === '' || $this->normalize($idValue) === 'total') {
                continue;
            }

            if (! ctype_digit($idValue)) {
                throw $this->invalidRow($rowNumber, 'ID inválido');
            }

            $sessions = $this->numericCell($sheet, 'D', $rowNumber, 'Sessões');
            $kwh = $this->numericCell($sheet, 'E', $rowNumber, 'kWh');
            $value = $this->numericCell($sheet, 'F', $rowNumber, 'Valor (EUR)');

            if ($sessions < 0 || $kwh < 0 || $value < 0 || floor($sessions) !== $sessions) {
                throw $this->invalidRow($rowNumber, 'Sessões, kWh ou valor inválidos');
            }

            $rows[] = [
                'driver_id' => (int) $idValue,
                'driver_name' => trim((string) $sheet->getCell('B'.$rowNumber)->getFormattedValue()),
                'license_plate' => trim((string) $sheet->getCell('C'.$rowNumber)->getFormattedValue()) ?: null,
                'sessions' => (int) $sessions,
                'kwh' => round($kwh, 2),
                'value' => round($value, 2),
            ];
        }

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'charge_file' => 'A folha “Por Motorista” não contém linhas para importar.',
            ]);
        }

        return [
            'year' => $titleCode[0],
            'week' => $titleCode[1],
            'rows' => $rows,
        ];
    }

    private function resolveWeek(int $year, int $week): TvdeWeek
    {
        try {
            $start = Carbon::now()->setISODate($year, $week)->startOfWeek();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['charge_file' => 'A semana indicada no ficheiro é inválida.']);
        }

        $tvdeWeek = TvdeWeek::whereDate('start_date', $start->toDateString())
            ->whereDate('end_date', $start->copy()->endOfWeek()->toDateString())
            ->first();

        if (! $tvdeWeek) {
            throw ValidationException::withMessages([
                'charge_file' => sprintf('A semana %d-W%02d ainda não existe no sistema.', $year, $week),
            ]);
        }

        return $tvdeWeek;
    }

    private function ensureWeekIsOpen(TvdeWeek $week): void
    {
        $hasAccounts = CurrentAccount::where('tvde_week_id', $week->id)->exists();
        $hasBalances = DriversBalance::where('tvde_week_id', $week->id)->exists();

        if ($hasAccounts || $hasBalances) {
            throw ValidationException::withMessages([
                'charge_file' => 'A semana já tem relatórios ou saldos fechados. Reabra-a antes de importar Movvi Charge.',
            ]);
        }
    }

    private function extractWeekCode(string $value): ?array
    {
        if (! preg_match('/(\d{4})[-_ ]?W(\d{1,2})/i', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $week = (int) $matches[2];

        return $week >= 1 && $week <= 53 ? [$year, $week] : null;
    }

    private function numericCell(Worksheet $sheet, string $column, int $row, string $label): float
    {
        $value = $sheet->getCell($column.$row)->getCalculatedValue();
        if (! is_numeric($value)) {
            throw $this->invalidRow($row, $label.' não é numérico');
        }

        return (float) $value;
    }

    private function invalidRow(int $row, string $message): ValidationException
    {
        return ValidationException::withMessages([
            'charge_file' => sprintf('Linha %d: %s.', $row, $message),
        ]);
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/', ' ', Str::lower(Str::ascii(trim($value))));
    }
}
