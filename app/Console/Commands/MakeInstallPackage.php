<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class MakeInstallPackage extends Command
{
    protected $aliases = ['db:copy-production-to-sandbox'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:make-install-package 
        {--source= : Ligacao de origem (default: mysql_production)} 
        {--target=mysql_sandbox : Ligacao destino (default: mysql_sandbox)} 
        {--target-database= : Nome da base de dados destino (default da ligacao)} 
        {--chunk=500 : Numero de linhas por insercao no modo legacy} 
        {--mode=dump : dump (mysqldump/mysql) ou legacy (tabela a tabela)} 
        {--transport=pipe : pipe (stream direto) ou file (dump temporario em disco)} 
        {--mysqldump-bin=mysqldump : Binario mysqldump a usar no modo dump} 
        {--mysql-bin=mysql : Binario mysql a usar no modo dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clona integralmente uma base de dados para outra ligacao, por defeito via dump/import direto em streaming';

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
        $mode = strtolower((string) ($this->option('mode') ?: 'dump'));
        $transport = strtolower((string) ($this->option('transport') ?: 'pipe'));

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

        if (!in_array($mode, ['dump', 'legacy'], true)) {
            $this->error("Modo '{$mode}' invalido. Use --mode=dump ou --mode=legacy.");

            return self::FAILURE;
        }

        if (!in_array($transport, ['pipe', 'file'], true)) {
            $this->error("Transporte '{$transport}' invalido. Use --transport=pipe ou --transport=file.");

            return self::FAILURE;
        }

        if ($mode === 'dump') {
            try {
                $this->cloneDatabaseWithDump($sourceConfig, $targetConfig, $targetDatabase, $transport);
            } catch (\Throwable $e) {
                $this->error("Falha na copia em lote via dump: {$e->getMessage()}");

                return self::FAILURE;
            }

            $this->line('');
            $this->info("Base de dados origem: " . Arr::get($sourceConfig, 'database') . " ({$sourceName})");
            $this->info("Base de dados destino: {$targetDatabase} ({$targetName})");
            $this->info('Modo utilizado: dump');
            $this->info("Transporte utilizado: {$transport}");

            return self::SUCCESS;
        }

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
        $this->info('Modo utilizado: legacy');

        return self::SUCCESS;
    }

    private function cloneDatabaseWithDump(array $sourceConfig, array $targetConfig, string $targetDatabase, string $transport): void
    {
        $sourceDatabase = Arr::get($sourceConfig, 'database');
        if (!$sourceDatabase) {
            throw new \RuntimeException('A ligacao de origem nao tem base de dados configurada.');
        }

        $mysqldumpBinary = $this->resolveMysqlBinary(
            (string) ($this->option('mysqldump-bin') ?: 'mysqldump'),
            'mysqldump.exe',
            [
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            ]
        );
        $mysqlBinary = $this->resolveMysqlBinary(
            (string) ($this->option('mysql-bin') ?: 'mysql'),
            'mysql.exe',
            [
                'C:\\xampp\\mysql\\bin\\mysql.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysql.exe',
            ]
        );
        $this->prepareFreshDatabase($targetConfig, $targetDatabase);

        $dumpArgs = array_filter([
            $mysqldumpBinary,
            '--host=' . Arr::get($sourceConfig, 'host', '127.0.0.1'),
            '--port=' . Arr::get($sourceConfig, 'port', '3306'),
            '--user=' . Arr::get($sourceConfig, 'username'),
            $this->passwordArgument(Arr::get($sourceConfig, 'password')),
            '--default-character-set=' . Arr::get($sourceConfig, 'charset', 'utf8mb4'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--events',
            '--no-tablespaces',
            $sourceDatabase,
        ], fn($value) => $value !== null && $value !== '');

        $importArgs = array_filter([
            $mysqlBinary,
            '--host=' . Arr::get($targetConfig, 'host', '127.0.0.1'),
            '--port=' . Arr::get($targetConfig, 'port', '3306'),
            '--user=' . Arr::get($targetConfig, 'username'),
            $this->passwordArgument(Arr::get($targetConfig, 'password')),
            '--default-character-set=' . Arr::get($targetConfig, 'charset', 'utf8mb4'),
            '--database=' . $targetDatabase,
        ], fn($value) => $value !== null && $value !== '');

        if ($transport === 'pipe') {
            $this->info("A clonar {$sourceDatabase} para {$targetDatabase} por stream direto...");
            $this->runShellPipeline($dumpArgs, $importArgs, 'Falha ao clonar a base de dados por stream direto');

            return;
        }

        $dumpFile = tempnam(sys_get_temp_dir(), 'movvi-db-copy-');

        if ($dumpFile === false) {
            throw new \RuntimeException('Nao foi possivel criar o ficheiro temporario do dump.');
        }

        $dumpFileSql = $dumpFile . '.sql';

        if (!@rename($dumpFile, $dumpFileSql)) {
            @unlink($dumpFile);
            throw new \RuntimeException('Nao foi possivel preparar o ficheiro temporario do dump.');
        }

        try {
            $this->info("A gerar dump completo de {$sourceDatabase} para ficheiro temporario...");

            $fileDumpArgs = [...$dumpArgs, '--result-file=' . $dumpFileSql];
            $this->runProcess(new Process($fileDumpArgs), 'Falha ao gerar o dump da base de dados origem');

            $this->info("A importar dump completo em {$targetDatabase}...");

            $importProcess = new Process($importArgs);
            $importHandle = fopen($dumpFileSql, 'r');
            if ($importHandle === false) {
                throw new \RuntimeException('Nao foi possivel abrir o dump temporario para importacao.');
            }

            try {
                $importProcess->setInput($importHandle);
                $this->runProcess($importProcess, 'Falha ao importar o dump na base de dados destino');
            } finally {
                fclose($importHandle);
            }
        } finally {
            @unlink($dumpFileSql);
        }
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

    private function prepareFreshDatabase(array $config, string $database): void
    {
        $host = Arr::get($config, 'host', '127.0.0.1');
        $port = Arr::get($config, 'port', '3306');
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');
        $charset = Arr::get($config, 'charset', 'utf8mb4');
        $collation = Arr::get($config, 'collation', 'utf8mb4_unicode_ci');

        $safeDatabase = str_replace('`', '``', $database);

        $pdo = new \PDO("mysql:host={$host};port={$port}", $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("DROP DATABASE IF EXISTS `{$safeDatabase}`");
        $pdo->exec("CREATE DATABASE `{$safeDatabase}` CHARACTER SET {$charset} COLLATE {$collation}");
    }

    private function passwordArgument(?string $password): ?string
    {
        if ($password === null) {
            return null;
        }

        return '--password=' . $password;
    }

    private function runProcess(Process $process, string $failureMessage): void
    {
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $buffer = trim($buffer);
            if ($buffer === '') {
                return;
            }

            if ($type === Process::ERR) {
                $this->newLine();
                $this->warn($buffer);

                return;
            }

            $this->output->write($buffer . PHP_EOL);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($failureMessage . ': ' . $process->getErrorOutput() . PHP_EOL . $process->getOutput()));
        }
    }

    private function runShellPipeline(array $leftCommand, array $rightCommand, string $failureMessage): void
    {
        $commandLine = $this->buildShellCommand($leftCommand) . ' | ' . $this->buildShellCommand($rightCommand);

        $process = Process::fromShellCommandline($commandLine);
        $this->runProcess($process, $failureMessage);
    }

    private function buildShellCommand(array $parts): string
    {
        return implode(' ', array_map(function ($part) {
            $value = (string) $part;

            if (DIRECTORY_SEPARATOR === '\\') {
                return '"' . str_replace('"', '\"', $value) . '"';
            }

            return escapeshellarg($value);
        }, $parts));
    }

    private function resolveMysqlBinary(string $configuredBinary, string $windowsBinaryName, array $commonWindowsPaths): string
    {
        if ($configuredBinary !== '' && $this->isExecutableCommandAvailable($configuredBinary)) {
            return $configuredBinary;
        }

        foreach ($commonWindowsPaths as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if (DIRECTORY_SEPARATOR === '\\' && is_file('C:\\xampp\\mysql\\bin\\' . $windowsBinaryName)) {
            return 'C:\\xampp\\mysql\\bin\\' . $windowsBinaryName;
        }

        throw new \RuntimeException("Nao foi encontrado o binario '{$configuredBinary}'. Configure --mysqldump-bin/--mysql-bin ou coloque o MySQL no PATH.");
    }

    private function isExecutableCommandAvailable(string $command): bool
    {
        if ($command === '') {
            return false;
        }

        if (str_contains($command, '\\') || str_contains($command, '/')) {
            return is_file($command);
        }

        $checkCommand = DIRECTORY_SEPARATOR === '\\'
            ? ['where', $command]
            : ['which', $command];

        $process = new Process($checkCommand);
        $process->run();

        return $process->isSuccessful();
    }
}
