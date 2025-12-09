<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MakeInstallPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:make-install-package 
        {--limit=500 : Numero de linhas por tabela} 
        {--connection= : Ligacao a utilizar (default do projeto)} 
        {--path= : Caminho do ficheiro .sql de saida (default: storage/app/install-package.sql)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um pacote de instalacao (.sql) com as ultimas linhas de cada tabela';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? $limit : 500;

        $connectionName = $this->option('connection') ?: config('database.default');
        $connection = DB::connection($connectionName);

        $path = $this->option('path') ?: storage_path('app/install-package.sql');

        $tableKey = 'Tables_in_' . $connection->getDatabaseName();
        $tables = collect($connection->select('SHOW TABLES'))
            ->map(function ($row) use ($tableKey) {
                $rowArray = (array) $row;
                return $rowArray[$tableKey] ?? Arr::first(array_values($rowArray));
            })
            ->filter()
            ->values();

        $sqlLines = [];
        $sqlLines[] = '-- Install package generated for database ' . $connection->getDatabaseName();
        $sqlLines[] = 'SET FOREIGN_KEY_CHECKS=0;';

        $pdo = $connection->getPdo();

        foreach ($tables as $table) {
            try {
                $create = (array) $connection->selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;

                if (!$createSql) {
                    throw new \RuntimeException("Nao foi possivel obter CREATE TABLE para {$table}");
                }

                $columns = collect($connection->select("SHOW COLUMNS FROM `{$table}`"))->pluck('Field')->all();
                $orderColumn = $this->resolveOrderColumn($connection, $table);

                $query = $connection->table($table);
                if ($orderColumn) {
                    $query->orderByDesc($orderColumn);
                }

                $rows = $query->limit($limit)->get()->reverse();

                $sqlLines[] = "\n-- {$table}";
                $sqlLines[] = "DROP TABLE IF EXISTS `{$table}`;";
                $sqlLines[] = rtrim($createSql, ';') . ';';

                if ($rows->isEmpty()) {
                    continue;
                }

                $columnList = '`' . implode('`, `', $columns) . '`';

                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column} ?? null;
                        $values[] = is_null($value) ? 'NULL' : $pdo->quote($value);
                    }

                    $sqlLines[] = "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");";
                }
            } catch (\Throwable $e) {
                $this->warn("Falha na tabela {$table}: {$e->getMessage()}");
            }
        }

        $sqlLines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", $sqlLines));

        $this->info("Pacote gerado em: {$path}");
        $this->info('Tabelas processadas: ' . $tables->count());

        return self::SUCCESS;
    }

    private function resolveOrderColumn($connection, string $table): ?string
    {
        $hasId = !empty($connection->select("SHOW COLUMNS FROM `{$table}` LIKE 'id'"));
        if ($hasId) {
            return 'id';
        }

        $hasCreated = !empty($connection->select("SHOW COLUMNS FROM `{$table}` LIKE 'created_at'"));
        if ($hasCreated) {
            return 'created_at';
        }

        return null;
    }
}
