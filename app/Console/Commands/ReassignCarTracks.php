<?php

namespace App\Console\Commands;

use App\Models\CarTrack;
use App\Models\VehicleUsage;
use App\Services\CarTrackAssignmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReassignCarTracks extends Command
{
    protected $signature = 'movvi:reassign-car-tracks
        {--week= : Filtrar por tvde_week_id de debito}
        {--from= : Data real inicial da passagem (Y-m-d ou Y-m-d H:i:s)}
        {--to= : Data real final da passagem (Y-m-d ou Y-m-d H:i:s)}
        {--dry-run : Nao persiste alteracoes}
        {--audit-usages : Mostra tambem diagnostico de overlaps em vehicle_usages}';

    protected $description = 'Reatribui Via Verde ao motorista que usava a viatura na data real da passagem.';

    public function handle(CarTrackAssignmentService $assignmentService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = CarTrack::query()->withTrashed();

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
            CarTrack::STATUS_ASSIGNED => 0,
            CarTrack::STATUS_VEHICLE_NOT_FOUND => 0,
            CarTrack::STATUS_NO_USAGE_MATCH => 0,
            CarTrack::STATUS_MULTIPLE_USAGE_MATCHES => 0,
            CarTrack::STATUS_MISSING_TIMESTAMP => 0,
        ];

        $query->orderBy('id')->chunkById(500, function ($carTracks) use ($assignmentService, $dryRun, &$summary) {
            foreach ($carTracks as $carTrack) {
                $decision = $assignmentService->assignWithDiagnostics($carTrack, !$dryRun, false);

                $summary['processed']++;
                $summary[$decision['status']] = ($summary[$decision['status']] ?? 0) + 1;

                if ($decision['changed']) {
                    $summary['changed']++;
                }
            }
        });

        $this->info($dryRun ? 'Dry-run de Via Verde concluido.' : 'Reprocessamento de Via Verde concluido.');
        $this->table(
            ['processed', 'changed', 'assigned', 'vehicle_not_found', 'no_usage_match', 'multiple_usage_matches', 'missing_timestamp'],
            [[
                $summary['processed'],
                $summary['changed'],
                $summary[CarTrack::STATUS_ASSIGNED],
                $summary[CarTrack::STATUS_VEHICLE_NOT_FOUND],
                $summary[CarTrack::STATUS_NO_USAGE_MATCH],
                $summary[CarTrack::STATUS_MULTIPLE_USAGE_MATCHES],
                $summary[CarTrack::STATUS_MISSING_TIMESTAMP],
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
