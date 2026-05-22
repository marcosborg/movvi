<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReplaceAluguerWithCedencia extends Command
{
    protected $signature = 'content:replace-aluguer-with-cedencia
        {--apply : Apply the replacements. Without this option the command only reports matches.}
        {--connection= : Database connection to use. Defaults to the app default connection.}
        {--backup-file= : JSON backup path for matched rows before --apply. Defaults to storage/app/db-backups.}';

    protected $description = 'Replace visible content text from aluguer to cedencia in database text columns';

    private const REPLACEMENTS = [
        'ALUGUERES' => 'CEDÊNCIAS',
        'Alugueres' => 'Cedências',
        'alugueres' => 'cedências',
        'ALUGUER' => 'CEDÊNCIA',
        'Aluguer' => 'Cedência',
        'aluguer' => 'cedência',
    ];

    private const EXCLUDED_TABLES = [
        'migrations',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
        'sessions',
        'jobs',
    ];

    private const EXCLUDED_COLUMN_NAMES = [
        'id',
        'uuid',
        'slug',
        'url',
        'uri',
        'path',
        'route',
        'endpoint',
        'link',
        'href',
        'action',
        'method',
        'type',
        'category',
        'key',
        'code',
        'token',
        'secret',
        'password',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'email',
        'phone',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    private const TEXT_COLUMN_TYPES = [
        'char',
        'varchar',
        'tinytext',
        'text',
        'mediumtext',
        'longtext',
        'json',
    ];

    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $connection = DB::connection($connectionName);
        $database = $connection->getDatabaseName();
        $apply = (bool) $this->option('apply');

        if (! $database) {
            $this->error('Could not determine database name for connection: ' . $connectionName);

            return self::FAILURE;
        }

        try {
            $candidates = $this->matchingColumns($connectionName, $database);
        } catch (Throwable $exception) {
            $this->error('Could not inspect database content: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($candidates === []) {
            $this->info('No matching database content found.');

            return self::SUCCESS;
        }

        $this->table(['table', 'column', 'rows'], array_map(static function (array $candidate): array {
            return [$candidate['table'], $candidate['column'], $candidate['rows']];
        }, $candidates));

        if (! $apply) {
            $this->warn('Dry run only. Re-run with --apply after taking a production backup.');

            return self::SUCCESS;
        }

        try {
            $backupFile = $this->backupMatches($connection, $connectionName, $database, $candidates);
            $this->info('Matched rows backed up to: ' . $backupFile);

            $connection->transaction(function () use ($connection, $candidates): void {
                foreach ($candidates as $candidate) {
                    $expression = $this->replacementExpression($connection, $candidate['column']);

                    $connection->update(
                        sprintf(
                            'UPDATE %s SET %s = %s WHERE LOWER(%s) LIKE ?',
                            $this->quoteIdentifier($candidate['table']),
                            $this->quoteIdentifier($candidate['column']),
                            $expression,
                            $this->quoteIdentifier($candidate['column'])
                        ),
                        ['%aluguer%']
                    );
                }
            });
        } catch (Throwable $exception) {
            $this->error('Could not apply database content replacements: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Database content replacements applied.');

        return self::SUCCESS;
    }

    private function backupMatches(ConnectionInterface $connection, string $connectionName, string $database, array $candidates): string
    {
        $backupFile = $this->option('backup-file') ?: storage_path(
            'app/db-backups/aluguer-cedencia-' . now()->format('Ymd-His') . '.json'
        );
        $backupDir = dirname($backupFile);

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backup = [
            'created_at' => now()->toDateTimeString(),
            'connection' => $connectionName,
            'database' => $database,
            'replacements' => self::REPLACEMENTS,
            'matches' => [],
        ];

        foreach ($candidates as $candidate) {
            $rows = $connection->select(
                sprintf(
                    'SELECT * FROM %s WHERE LOWER(%s) LIKE ?',
                    $this->quoteIdentifier($candidate['table']),
                    $this->quoteIdentifier($candidate['column'])
                ),
                ['%aluguer%']
            );

            $backup['matches'][] = [
                'table' => $candidate['table'],
                'column' => $candidate['column'],
                'rows' => array_map(static fn ($row): array => (array) $row, $rows),
            ];
        }

        file_put_contents(
            $backupFile,
            json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $backupFile;
    }

    private function matchingColumns(string $connectionName, string $database): array
    {
        $rows = DB::connection($connectionName)->select(
            "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME, ORDINAL_POSITION",
            [$database]
        );

        $matches = [];

        foreach ($rows as $row) {
            $table = (string) $row->TABLE_NAME;
            $column = (string) $row->COLUMN_NAME;
            $type = strtolower((string) $row->DATA_TYPE);

            if (! $this->isCandidateColumn($table, $column, $type)) {
                continue;
            }

            $count = (int) DB::connection($connectionName)->table($table)
                ->whereRaw('LOWER(' . $this->quoteIdentifier($column) . ') LIKE ?', ['%aluguer%'])
                ->count();

            if ($count > 0) {
                $matches[] = [
                    'table' => $table,
                    'column' => $column,
                    'rows' => $count,
                ];
            }
        }

        return $matches;
    }

    private function isCandidateColumn(string $table, string $column, string $type): bool
    {
        if (in_array($table, self::EXCLUDED_TABLES, true)) {
            return false;
        }

        if (! in_array($type, self::TEXT_COLUMN_TYPES, true)) {
            return false;
        }

        $normalized = strtolower($column);

        if (in_array($normalized, self::EXCLUDED_COLUMN_NAMES, true)) {
            return false;
        }

        foreach (self::EXCLUDED_COLUMN_NAMES as $excluded) {
            if (str_contains($normalized, $excluded)) {
                return false;
            }
        }

        return true;
    }

    private function replacementExpression(ConnectionInterface $connection, string $column): string
    {
        $expression = $this->quoteIdentifier($column);

        foreach (self::REPLACEMENTS as $search => $replace) {
            $expression = sprintf(
                'REPLACE(%s, %s, %s)',
                $expression,
                $connection->getPdo()->quote($search),
                $connection->getPdo()->quote($replace)
            );
        }

        return $expression;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
