<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MakeInstallPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:make-install-package 
        {--source= : Ligacao de origem (default: mysql_production)} 
        {--target=mysql_sandbox : Ligacao destino (default: mysql_sandbox)} 
        {--target-database= : Nome da base de dados destino (default da ligacao)} 
        {--chunk=500 : Numero de linhas por insercao}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copia integralmente uma base de dados para outra ligacao (ex: producao -> sandbox/local)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceName = $this->option('source') ?: 'mysql_production';
        $targetName = $this->option('target') ?: 'mysql_sandbox';
        $chunkSize = max((int) $this->option('chunk'), 1);

        $sourceConfig = config("database.connections.{$sourceName}");
        if (!$sourceConfig) {
            $this->error("Ligacao de origem '{$sourceName}' nao existe.");

            return self::FAILURE;
        }

        $targetConfig = config("database.connections.{$targetName}");
        if (!$targetConfig) {
            $this->error("Ligacao de destino '{$targetName}' nao existe.");

            return self::FAILURE;
        }

        $targetDatabase = $this->option('target-database') ?: Arr::get($targetConfig, 'database');
        if (!$targetDatabase) {
            $this->error("Nao foi possivel determinar a base de dados destino. Use --target-database= ou configure a ligacao.");

            return self::FAILURE;
        }

        try {
            $this->ensureDatabaseExists($targetConfig, $targetDatabase);
        } catch (\Throwable $e) {
            $this->error("Falha ao criar/verificar base de dados destino '{$targetDatabase}': {$e->getMessage()}");

            return self::FAILURE;
        }

        config([
            "database.connections.{$targetName}.database" => $targetDatabase,
        ]);
        DB::purge($targetName);

        $source = DB::connection($sourceName);
        $target = DB::connection($targetName);
        $source->disableQueryLog();

        $tableKey = 'Tables_in_' . $source->getDatabaseName();
        $tables = collect($source->select('SHOW TABLES'))
            ->map(function ($row) use ($tableKey) {
                $rowArray = (array) $row;
                return $rowArray[$tableKey] ?? Arr::first(array_values($rowArray));
            })
            ->filter()
            ->values();

        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            try {
                $create = (array) $source->selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;

                if (!$createSql) {
                    throw new \RuntimeException("Nao foi possivel obter CREATE TABLE para {$table}");
                }

                $target->statement("DROP TABLE IF EXISTS `{$table}`");
                $target->statement($createSql);

                if (isset($create['Create View'])) {
                    $this->info("View {$table} criada.");
                    continue;
                }

                $orderColumn = $this->resolveOrderColumn($source, $table);
                $copiedRows = 0;

                $source->table($table)
                    ->orderBy($orderColumn)
                    ->chunk($chunkSize, function ($rows) use ($target, $table, &$copiedRows) {
                        $batch = $rows->map(fn($row) => (array) $row)->toArray();

                        if (!empty($batch)) {
                            $target->table($table)->insert($batch);
                            $copiedRows += count($batch);
                        }
                    });

                $this->info("Tabela {$table}: {$copiedRows} linhas copiadas.");
            } catch (\Throwable $e) {
                $this->warn("Falha na tabela {$table}: {$e->getMessage()}");
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('');
        $this->info("Base de dados origem: {$source->getDatabaseName()} ({$sourceName})");
        $this->info("Base de dados destino: {$targetDatabase} ({$targetName})");
        $this->info('Tabelas processadas: ' . $tables->count());

        return self::SUCCESS;
    }

    private function resolveOrderColumn($connection, string $table): string
    {
        $columns = collect($connection->select("SHOW COLUMNS FROM `{$table}`"))->pluck('Field')->all();

        if (in_array('id', $columns, true)) {
            return 'id';
        }

        if (in_array('created_at', $columns, true)) {
            return 'created_at';
        }

        if (!empty($columns)) {
            return $columns[0];
        }

        throw new \RuntimeException("Nao foi possivel determinar colunas para {$table}");
    }

    private function ensureDatabaseExists(array $config, string $database): void
    {
        $host = Arr::get($config, 'host', '127.0.0.1');
        $port = Arr::get($config, 'port', '3306');
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');
        $charset = Arr::get($config, 'charset', 'utf8mb4');
        $collation = Arr::get($config, 'collation', 'utf8mb4_unicode_ci');

        $safeDatabase = str_replace('`', '``', $database);

        $dsn = "mysql:host={$host};port={$port}";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDatabase}` CHARACTER SET {$charset} COLLATE {$collation}");
    }
}
