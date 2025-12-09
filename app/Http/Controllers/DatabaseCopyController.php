<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DatabaseCopyController extends Controller
{
    /**
     * Copia as ultimas N (padrao 500) linhas de cada tabela da ligacao principal
     * para uma base de dados destino definida por variaveis de ambiente.
     *
     * URL: /db-copy/latest-500?token=SEU_TOKEN&limit=500
     */
    public function copyLatest(Request $request)
    {
        $token = env('DB_COPY_TOKEN');

        if (!$token || $request->query('token') !== $token) {
            abort(Response::HTTP_FORBIDDEN, 'Token invalido ou ausente.');
        }

        $limit = (int) $request->query('limit', 500);
        $limit = $limit > 0 ? $limit : 500;

        $targetDatabase = env('DB_COPY_DATABASE');
        if (!$targetDatabase) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Configure DB_COPY_DATABASE no .env');
        }

        $targetConnection = 'copy_target';
        $baseConfig = config('database.connections.mysql');

        config([
            "database.connections.{$targetConnection}" => array_merge($baseConfig, [
                'host' => env('DB_COPY_HOST', env('DB_HOST')),
                'port' => env('DB_COPY_PORT', env('DB_PORT')),
                'database' => $targetDatabase,
                'username' => env('DB_COPY_USERNAME', env('DB_USERNAME')),
                'password' => env('DB_COPY_PASSWORD', env('DB_PASSWORD')),
                'charset' => env('DB_COPY_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COPY_COLLATION', 'utf8mb4_unicode_ci'),
            ]),
        ]);

        $source = DB::connection();
        $target = DB::connection($targetConnection);

        $tableKey = 'Tables_in_' . $source->getDatabaseName();
        $tables = collect($source->select('SHOW TABLES'))
            ->map(function ($row) use ($tableKey) {
                $rowArray = (array) $row;
                return $rowArray[$tableKey] ?? Arr::first(array_values($rowArray));
            })
            ->filter()
            ->values();

        $copied = [];

        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            try {
                // Recria a estrutura da tabela no destino
                $create = (array) $source->selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;
                if (!$createSql) {
                    throw new \RuntimeException("Nao foi possivel obter CREATE TABLE para {$table}");
                }

                $target->statement("DROP TABLE IF EXISTS `{$table}`");
                $target->statement($createSql);

                $orderColumn = $this->resolveOrderColumn($source, $table);

                $query = $source->table($table);
                if ($orderColumn) {
                    $query->orderByDesc($orderColumn);
                }

                $data = $query->limit($limit)->get();
                $rows = $data->reverse()->map(fn($row) => (array) $row);

                foreach ($rows->chunk(100) as $chunk) {
                    if ($chunk->isNotEmpty()) {
                        $target->table($table)->insert($chunk->toArray());
                    }
                }

                $copied[] = [
                    'table' => $table,
                    'copied_rows' => $rows->count(),
                    'order_column' => $orderColumn ?? 'none',
                ];
            } catch (\Throwable $e) {
                $copied[] = [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'source_db' => $source->getDatabaseName(),
            'target_db' => $targetDatabase,
            'limit' => $limit,
            'tables' => $copied,
        ]);
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
