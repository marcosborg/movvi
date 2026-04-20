<?php

namespace App\Console\Commands;

use App\Services\ContaAzulService;
use Illuminate\Console\Command;

class SyncContaAzul extends Command
{
    protected $signature = 'conta-azul:sync-expenses';

    protected $description = 'Sync Conta Azul expenses into local external_expenses table';

    public function handle(ContaAzulService $contaAzulService): int
    {
        $synced = $contaAzulService->syncExpenses();

        $this->info('Conta Azul expenses synced: ' . $synced);

        return self::SUCCESS;
    }
}
