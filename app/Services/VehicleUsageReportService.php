<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class VehicleUsageReportService
{
    public const CATEGORIES = [
        'usage' => 'Utilização', 'maintenance' => 'Manutenção', 'accident' => 'Sinistrado',
        'personal' => 'Utilização pessoal', 'unassigned' => 'Sem utilização',
    ];
    public const COLORS = [
        'usage' => '#238653', 'maintenance' => '#da8521', 'accident' => '#c44444',
        'personal' => '#8055ad', 'unassigned' => '#dce2e7',
    ];

    /** Calendar wall time: a full calendar day always represents one report day. */
    public function build(Collection $vehicles, Collection $usages, string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from, 'UTC')->startOfDay();
        $today = CarbonImmutable::parse(now()->toDateString(), 'UTC');
        $last = CarbonImmutable::parse($to, 'UTC')->startOfDay()->min($today);
        $end = $last->addDay(); // Exclusive upper bound; includes the selected final day.
        $rows = [];
        $byVehicle = $usages->groupBy('vehicle_item_id');
        foreach ($vehicles as $vehicle) {
            $eligibleStart = $start;
            $eligibleEnd = $end;
            if ($date = $vehicle->getRawOriginal('acquisition_date')) {
                $eligibleStart = $eligibleStart->max(CarbonImmutable::parse($date, 'UTC')->startOfDay());
            }
            if ($date = $vehicle->getRawOriginal('sale_date')) {
                $eligibleEnd = $eligibleEnd->min(CarbonImmutable::parse($date, 'UTC')->startOfDay()->addDay());
            }
            if ($eligibleStart >= $eligibleEnd) continue;
            $intervals = [];
            $boundaries = [$eligibleStart->timestamp, $eligibleEnd->timestamp];
            foreach ($byVehicle->get($vehicle->id, collect()) as $usage) {
                if (!$usage->getRawOriginal('start_date')) continue;
                $rawStart = CarbonImmutable::parse($usage->getRawOriginal('start_date'), 'UTC');
                $a = $rawStart->max($eligibleStart);
                $b = $usage->getRawOriginal('end_date')
                    ? CarbonImmutable::parse($usage->getRawOriginal('end_date'), 'UTC')->min($eligibleEnd)
                    : $eligibleEnd;
                if ($a >= $b) continue;
                $category = $usage->usage_exceptions ?: ($usage->driver_id ? 'usage' : 'unassigned');
                if (!isset(self::CATEGORIES[$category])) $category = 'unassigned';
                $intervals[] = ['start' => $a->timestamp, 'end' => $b->timestamp,
                    'original_start' => $rawStart->timestamp, 'id' => $usage->id,
                    'category' => $category, 'driver' => $usage->driver?->name];
                $boundaries[] = $a->timestamp;
                $boundaries[] = $b->timestamp;
            }
            $boundaries = array_values(array_unique($boundaries));
            sort($boundaries);
            // The latest-starting record supersedes an older overlapping assignment.
            usort($intervals, fn ($a, $b) => [$b['original_start'], $b['id']] <=> [$a['original_start'], $a['id']]);
            $segments = [];
            $months = [];
            for ($i = 0; $i < count($boundaries) - 1; $i++) {
                $a = $boundaries[$i]; $b = $boundaries[$i + 1];
                $category = 'unassigned'; $driver = null;
                foreach ($intervals as $interval) {
                    if ($interval['start'] <= $a && $interval['end'] >= $b) {
                        $category = $interval['category']; $driver = $interval['driver']; break;
                    }
                }
                $segments[] = ['start' => gmdate('Y-m-d H:i:s', $a), 'end' => gmdate('Y-m-d H:i:s', $b),
                    'category' => $category, 'driver' => $driver,
                    'offset' => 100 * ($a - $start->timestamp) / max(1, $end->timestamp - $start->timestamp),
                    'width' => 100 * ($b - $a) / max(1, $end->timestamp - $start->timestamp)];
                $cursor = CarbonImmutable::createFromTimestampUTC($a);
                while ($cursor->timestamp < $b) {
                    $next = min($b, $cursor->startOfMonth()->addMonth()->timestamp);
                    $key = $cursor->format('Y-m');
                    $months[$key] ??= array_fill_keys(array_keys(self::CATEGORIES), 0.0);
                    $months[$key][$category] += ($next - $cursor->timestamp) / 86400;
                    $cursor = CarbonImmutable::createFromTimestampUTC($next);
                }
            }
            $years = []; $totals = array_fill_keys(array_keys(self::CATEGORIES), 0.0);
            foreach ($months as $month => $counts) {
                $year = substr($month, 0, 4);
                $years[$year] ??= array_fill_keys(array_keys(self::CATEGORIES), 0.0);
                foreach ($counts as $category => $days) {
                    $years[$year][$category] += $days;
                    $totals[$category] += $days;
                }
                $months[$month] = $this->withRate($counts);
            }
            foreach ($years as $year => $counts) $years[$year] = $this->withRate($counts);
            $rows[] = ['id' => $vehicle->id, 'plate' => $vehicle->license_plate,
                'model' => $vehicle->vehicle_model?->name, 'segments' => $segments,
                'months' => $months, 'years' => $years, 'stats' => $this->withRate($totals)];
        }
        $fleet = array_fill_keys(array_keys(self::CATEGORIES), 0.0);
        foreach ($rows as $row) foreach ($fleet as $category => $value) $fleet[$category] += $row['stats'][$category];
        $ranking = $rows;
        usort($ranking, fn ($a, $b) => ($b['stats']['percent'] <=> $a['stats']['percent']) ?: strcmp($a['plate'], $b['plate']));
        return ['rows' => $rows, 'ranking' => $ranking, 'fleet' => $this->withRate($fleet),
            'from' => $start->toDateString(), 'to' => $last->toDateString(),
            'categories' => self::CATEGORIES, 'colors' => self::COLORS];
    }

    private function withRate(array $counts): array
    {
        $total = array_sum($counts);
        return $counts + ['total' => $total, 'percent' => $total > 0 ? 100 * $counts['usage'] / $total : 0.0];
    }
}
