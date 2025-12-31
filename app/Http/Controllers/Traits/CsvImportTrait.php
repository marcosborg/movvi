<?php

namespace App\Http\Controllers\Traits;

use \SpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CsvImportTrait
{
    public function processCsvImport(Request $request)
    {
        try {
            $filename = $request->input('filename', false);
            $path     = storage_path('app/csv_import/' . $filename);

            $hasHeader = $request->input('hasHeader', false);

            $fields = $request->input('fields', false);
            $fields = array_flip(array_filter($fields));

            $modelName = $request->input('modelName', false);
            $model     = 'App\\Models\\' . $modelName;

            $reader = new SpreadsheetReader($path);
            $insert = [];

            foreach ($reader as $key => $row) {
                if ($hasHeader && $key == 0) {
                    continue;
                }

                $tmp = [];
                foreach ($fields as $header => $k) {
                    if (isset($row[$k])) {
                        $tmp[$header] = $row[$k];
                    }
                }

                if (count($tmp) > 0) {
                    $tmp = $this->normalizeImportRow($modelName, $tmp);
                    $insert[] = $tmp;
                }
            }

            $insert = $this->dedupeImportRows($modelName, $model, $insert);

            $for_insert = array_chunk($insert, 100);

            foreach ($for_insert as $insert_item) {
                $model::insert($insert_item);
            }

            $rows  = count($insert);
            $table = Str::plural($modelName);

            File::delete($path);

            session()->flash('message', trans('global.app_imported_rows_to_table', ['rows' => $rows, 'table' => $table]));

            return redirect($request->input('redirect'));
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function parseCsvImport(Request $request)
    {
        $file = $request->file('csv_file');
        $request->validate([
            'csv_file' => 'mimes:csv,txt',
        ]);

        $path      = $file->path();
        $hasHeader = $request->input('header', false) ? true : false;

        $reader  = new SpreadsheetReader($path);
        $headers = $reader->current();
        $lines   = [];

        $i = 0;
        while ($reader->next() !== false && $i < 5) {
            $lines[] = $reader->current();
            ++$i;
        }

        $filename = Str::random(10) . '.csv';
        $file->storeAs('csv_import', $filename);

        $modelName     = $request->input('model', false);
        $fullModelName = 'App\\Models\\' . $modelName;

        $model     = new $fullModelName();
        $fillables = $model->getFillable();

        $redirect = url()->previous();

        $routeName = 'admin.' . strtolower(Str::plural(Str::kebab($modelName))) . '.processCsvImport';

        return view('csvImport.parseInput', compact('headers', 'filename', 'fillables', 'hasHeader', 'modelName', 'lines', 'redirect', 'routeName'));
    }

    protected function normalizeImportRow(string $modelName, array $row): array
    {
        if ($modelName === 'ElectricTransaction') {
            // Prio Electric uses the recharge timestamp, not the import time.
            $row = $this->normalizeTimestampFields($row, ['created_at', 'updated_at']);
            if (!isset($row['updated_at']) && isset($row['created_at'])) {
                $row['updated_at'] = $row['created_at'];
            }
        }

        return $row;
    }

    protected function normalizeTimestampFields(array $row, array $fields): array
    {
        foreach ($fields as $field) {
            if (!isset($row[$field]) || $row[$field] === '') {
                continue;
            }

            $row[$field] = $this->parseTimestamp((string) $row[$field]);
        }

        return $row;
    }

    protected function parseTimestamp(string $value): string
    {
        $value = trim($value);

        foreach ([
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
        ] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // Try next format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function dedupeImportRows(string $modelName, string $modelClass, array $rows): array
    {
        $dedupeColumns = $this->importDedupColumns($modelName);
        if (empty($dedupeColumns) || empty($rows)) {
            return $rows;
        }

        // Remove duplicates inside the CSV payload first.
        $uniqueRows = [];
        foreach ($rows as $row) {
            $signature = $this->dedupeSignature($row, $dedupeColumns);
            if (isset($uniqueRows[$signature])) {
                continue;
            }
            $uniqueRows[$signature] = $row;
        }

        $rows = array_values($uniqueRows);

        // Remove rows that already exist in the database to avoid double inserts.
        $query = $modelClass::query()->select($dedupeColumns);

        if (in_array('tvde_week_id', $dedupeColumns, true)) {
            $weekIds = collect($rows)->pluck('tvde_week_id')->filter()->unique()->values()->all();
            if (!empty($weekIds)) {
                $query->whereIn('tvde_week_id', $weekIds);
            }
        }

        if (in_array('card', $dedupeColumns, true)) {
            $cards = collect($rows)->pluck('card')->filter()->unique()->values()->all();
            if (!empty($cards)) {
                $query->whereIn('card', $cards);
            }
        }

        if (in_array('license_plate', $dedupeColumns, true)) {
            $plates = collect($rows)->pluck('license_plate')->filter()->unique()->values()->all();
            if (!empty($plates)) {
                $query->whereIn('license_plate', $plates);
            }
        }

        $existing = $query->get();
        if ($existing->isEmpty()) {
            return $rows;
        }

        $existingSignatures = [];
        foreach ($existing as $row) {
            $existingSignatures[$this->dedupeSignature($row->toArray(), $dedupeColumns)] = true;
        }

        return array_values(array_filter($rows, function ($row) use ($existingSignatures, $dedupeColumns) {
            $signature = $this->dedupeSignature($row, $dedupeColumns);
            return !isset($existingSignatures[$signature]);
        }));
    }

    protected function importDedupColumns(string $modelName): array
    {
        switch ($modelName) {
            case 'ElectricTransaction':
            case 'CombustionTransaction':
                return ['tvde_week_id', 'card', 'amount', 'total', 'created_at'];
            case 'TollPayment':
                return ['tvde_week_id', 'card', 'total', 'created_at'];
            case 'CarTrack':
                return ['tvde_week_id', 'license_plate', 'date', 'value'];
            default:
                return [];
        }
    }

    protected function dedupeSignature(array $row, array $columns): string
    {
        $parts = [];
        foreach ($columns as $column) {
            $parts[] = isset($row[$column]) ? (string) $row[$column] : '';
        }

        return implode('|', $parts);
    }
}
