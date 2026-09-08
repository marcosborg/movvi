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
    public function build(Collection $vehicles, Collection $usages, string $from, string $to, array $revenueWeeks = []): array
    {
        $start = CarbonImmutable::parse($from, 'UTC')->startOfDay();
        $today = CarbonImmutable::parse(now()->toDateString(), 'UTC');
        $last = CarbonImmutable::parse($to, 'UTC')->startOfDay()->min($today);
        $end = $last->addDay(); // Exclusive upper bound; includes the selected final day.
        $rows = [];
        $byVehicle = $usages->groupBy('vehicle_item_id');
        foreach ($vehicles as $vehicle) {
            $firstUsage = $vehicle->first_usage_at ?: $byVehicle->get($vehicle->id, collect())
                ->filter(fn ($usage) => ($usage->usage_exceptions === 'usage' || (!$usage->usage_exceptions && $usage->driver_id))
                    && $usage->getRawOriginal('start_date')
                    && (!$usage->getRawOriginal('end_date') || $usage->getRawOriginal('end_date') > $usage->getRawOriginal('start_date')))
                ->map(fn ($usage) => $usage->getRawOriginal('start_date'))->min();
            if (!$firstUsage) continue;
            $operationalStart = CarbonImmutable::parse($firstUsage, 'UTC');
            $eligibleStart = $start->max($operationalStart);
            $eligibleEnd = $end;
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
            $weeks = [];
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
                    $next = min($b, $cursor->startOfDay()->addDay()->timestamp);
                    $key = $cursor->format('Y-m');
                    $months[$key] ??= array_fill_keys(array_keys(self::CATEGORIES), 0.0);
                    $months[$key][$category] += ($next - $cursor->timestamp) / 86400;
                    $weekKey = $cursor->format('o-\\WW');
                    $weeks[$weekKey] ??= array_fill_keys(array_keys(self::CATEGORIES), 0.0);
                    $weeks[$weekKey][$category] += ($next - $cursor->timestamp) / 86400;
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
            foreach ($weeks as $week => $counts) $weeks[$week] = $this->withRate($counts);
            $row = ['id' => $vehicle->id, 'plate' => $vehicle->license_plate,
                'model' => $vehicle->vehicle_model?->name, 'segments' => $segments,
                'first_usage_at' => $operationalStart->toDateTimeString(),
                'months' => $months, 'years' => $years, 'weeks' => $weeks, 'stats' => $this->withRate($totals)];
            $this->addRevenue($row, $revenueWeeks, $operationalStart, $eligibleStart, $eligibleEnd,
                $vehicle->getRawOriginal('sale_date'), $today->addDay());
            $rows[] = $row;
        }
        $fleet = array_fill_keys(array_keys(self::CATEGORIES), 0.0);
        foreach ($rows as $row) foreach ($fleet as $category => $value) $fleet[$category] += $row['stats'][$category];
        $fleet = $this->withRate($fleet);
        $fleet['revenue'] = array_sum(array_column(array_column($rows, 'stats'), 'revenue'));
        $fleet['daily_average'] = $fleet['total'] > 0 ? $fleet['revenue'] / $fleet['total'] : 0.0;
        $fleet['incomplete'] = in_array(true, array_column(array_column($rows, 'stats'), 'incomplete'), true);
        $ranking = $rows;
        usort($ranking, fn ($a, $b) => ($b['stats']['percent'] <=> $a['stats']['percent']) ?: strcmp($a['plate'], $b['plate']));
        return ['rows' => $rows, 'ranking' => $ranking, 'fleet' => $fleet,
            'from' => $start->toDateString(), 'to' => $last->toDateString(),
            'categories' => self::CATEGORIES, 'colors' => self::COLORS];
    }

    private function withRate(array $counts): array
    {
        $total = array_sum($counts);
        return $counts + ['total' => $total, 'idle' => $total - $counts['usage'],
            'percent' => $total > 0 ? 100 * $counts['usage'] / $total : 0.0,
            'revenue' => 0.0, 'daily_average' => 0.0, 'incomplete' => false];
    }

    private function addRevenue(array &$row, array $weeks, CarbonImmutable $first, CarbonImmutable $from,
        CarbonImmutable $to, ?string $saleDate, CarbonImmutable $todayEnd): void
    {
        foreach ($weeks as $week) {
            $value = $week['vehicles'][$row['id']] ?? null;
            if (!$value) continue;
            // Allocate on the full operational week BEFORE clipping to the requested interval.
            // This makes adjacent report periods additive and preserves the weekly source total.
            $a = CarbonImmutable::parse($week['from'], 'UTC')->startOfDay()->max($first);
            $b = CarbonImmutable::parse($week['to'], 'UTC')->startOfDay()->addDay()->min($todayEnd);
            if ($saleDate) $b = $b->min(CarbonImmutable::parse($saleDate, 'UTC')->startOfDay()->addDay());
            if ($a >= $b) continue;
            $seconds = $b->timestamp - $a->timestamp;
            $cursor = $a->max($from);
            $limit = $b->min($to);
            while ($cursor < $limit) {
                $next = $cursor->startOfDay()->addDay()->min($limit);
                $amount = $value['revenue'] * ($next->timestamp - $cursor->timestamp) / $seconds;
                $row['stats']['revenue'] += $amount;
                $row['stats']['incomplete'] = $row['stats']['incomplete'] || $value['missing_accounts'] > 0;
                foreach (['months'=>$cursor->format('Y-m'), 'years'=>$cursor->format('Y'), 'weeks'=>$cursor->format('o-\\WW')] as $group=>$key) {
                    $row[$group][$key]['revenue'] += $amount;
                    $row[$group][$key]['incomplete'] = $row[$group][$key]['incomplete'] || $value['missing_accounts'] > 0;
                }
                $cursor = $next;
            }
        }
        foreach (['months', 'years', 'weeks'] as $group) {
            foreach ($row[$group] as &$stats) $stats['daily_average'] = $stats['total'] > 0 ? $stats['revenue'] / $stats['total'] : 0.0;
            unset($stats);
        }
        $row['stats']['daily_average'] = $row['stats']['total'] > 0 ? $row['stats']['revenue'] / $row['stats']['total'] : 0.0;
    }
}
