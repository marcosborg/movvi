<?php

namespace App\Console\Commands;

use App\Models\CombustionTransaction;
use App\Models\VehicleUsage;
use App\Services\CombustionTransactionAssignmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReassignCombustionTransactions extends Command
{
    protected $signature = 'movvi:reassign-combustion-transactions
        {--week= : Filtrar por tvde_week_id}
        {--from= : Data inicial (Y-m-d ou Y-m-d H:i:s)}
        {--to= : Data final (Y-m-d ou Y-m-d H:i:s)}
        {--dry-run : Nao persiste alteracoes}
        {--audit-usages : Mostra tambem diagnostico de overlaps em vehicle_usages}';

    protected $description = 'Reatribui abastecimentos PRIO com timestamp para o driver ativo no instante exato do abastecimento.';

    public function handle(CombustionTransactionAssignmentService $assignmentService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = CombustionTransaction::query()->whereNotNull('date');

        if ($week = $this->option('week')) {
            $query->where('tvde_week_id', (int) $week);
        }

        if ($from = $this->option('from')) {
            $query->where('date', '>=', $this->normalizeBoundaryDate((string) $from, false));
        }

        if ($to = $this->option('to')) {
            $query->where('date', '<=', $this->normalizeBoundaryDate((string) $to, true));
        }

        $summary = [
            'processed' => 0,
            'changed' => 0,
            'assigned' => 0,
            'legacy_fallback' => 0,
            'no_usage_match' => 0,
            'multiple_usage_matches' => 0,
            'usage_without_driver' => 0,
            'vehicle_not_mapped' => 0,
            'card_not_found' => 0,
            'no_timestamp' => 0,
        ];

        $query->orderBy('id')->chunkById(200, function ($transactions) use ($assignmentService, $dryRun, &$summary) {
            foreach ($transactions as $transaction) {
                $decision = $assignmentService->assignWithDiagnostics($transaction, !$dryRun);

                $summary['processed']++;
                $summary[$decision['status']] = ($summary[$decision['status']] ?? 0) + 1;

                if ($decision['changed']) {
                    $summary['changed']++;
                }
            }
        });

        $this->info($dryRun ? 'Dry-run concluido.' : 'Reprocessamento concluido.');
        $this->table(
            ['processed', 'changed', 'assigned', 'legacy_fallback', 'no_usage_match', 'multiple_usage_matches', 'usage_without_driver', 'vehicle_not_mapped', 'card_not_found', 'no_timestamp'],
            [[
                $summary['processed'],
                $summary['changed'],
                $summary['assigned'],
                $summary['legacy_fallback'],
                $summary['no_usage_match'],
                $summary['multiple_usage_matches'],
                $summary['usage_without_driver'],
                $summary['vehicle_not_mapped'],
                $summary['card_not_found'],
                $summary['no_timestamp'],
            ]]
        );

        if ($this->option('audit-usages')) {
            $this->newLine();
            $this->info('Diagnostico de overlaps em vehicle_usages');
            $this->table(['vehicle_usages_total', 'open_end_date_count', 'overlap_count'], [[
                VehicleUsage::count(),
                VehicleUsage::whereNull('end_date')->count(),
                $this->countVehicleUsageOverlaps(),
            ]]);
        }

        return self::SUCCESS;
    }

    protected function normalizeBoundaryDate(string $value, bool $endOfDay): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $endOfDay
                ? Carbon::createFromFormat('Y-m-d', $value)->endOfDay()->format('Y-m-d H:i:s')
                : Carbon::createFromFormat('Y-m-d', $value)->startOfDay()->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    protected function countVehicleUsageOverlaps(): int
    {
        $overlaps = 0;
        $grouped = VehicleUsage::query()
            ->orderBy('vehicle_item_id')
            ->orderBy('start_date')
            ->get(['id', 'vehicle_item_id', 'start_date', 'end_date'])
            ->groupBy('vehicle_item_id');

        foreach ($grouped as $rows) {
            $previous = null;

            foreach ($rows as $row) {
                $previousEnd = $previous?->end_date ?? '9999-12-31 23:59:59';

                if (
                    $previous
                    && $row->start_date !== null
                    && $previousEnd >= $row->start_date
                ) {
                    $overlaps++;
                }

                $previous = $row;
            }
        }

        return $overlaps;
    }
}
