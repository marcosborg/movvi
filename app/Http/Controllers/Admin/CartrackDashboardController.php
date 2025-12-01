<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\Reports;
use App\Services\Cartrack\CartrackFleetApiService;
use App\Services\Cartrack\Exceptions\CartrackException;
use App\Models\Driver;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CartrackDashboardController extends Controller
{
    use Reports;

    public function index(Request $request)
    {
        abort_if(Gate::denies('website_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->filled('tvde_week_id')) {
            session()->put('tvde_week_id', (int) $request->tvde_week_id);

            return redirect()->route('admin.cartrack.dashboard');
        }

        $filter      = $this->filter();
        $tvdeWeek    = $filter['tvde_week'];
        $tvde_weeks  = $filter['tvde_weeks'];
        $drivers     = $filter['drivers'];
        $range       = $this->weekRange($tvdeWeek);
        $usagePlates = $this->usagePlates($range['from'], $range['to']);

        $rows = [];
        foreach ($drivers as $driver) {
            $plate = trim((string) $driver->license_plate);
            if ($plate === '' && isset($usagePlates[$driver->id])) {
                $plate = $usagePlates[$driver->id];
            }

            $rows[] = [
                'driver'    => $driver,
                'license'   => $this->formatPlateForDisplay($plate),
                'km'        => null,
                'incidents' => [
                    'braking'      => 0,
                    'cornering'    => 0,
                    'acceleration' => 0,
                    'other'        => 0,
                ],
                'error'     => $plate === '' ? 'Sem matricula associada ao motorista' : null,
            ];
        }

        return view('admin.cartrack.dashboard', [
            'tvde_weeks' => $tvde_weeks,
            'tvde_week'  => $tvdeWeek,
            'from'       => $range['from'],
            'to'         => $range['to'],
            'rows'       => $rows,
        ]);
    }

    public function fetch(Request $request, CartrackFleetApiService $cartrack)
    {
        abort_if(Gate::denies('website_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driverId = (int) $request->integer('driver_id');
        if (!$driverId) {
            return response()->json(['error' => 'Driver inválido'], 422);
        }

        $weekId   = $request->integer('tvde_week_id') ?: session('tvde_week_id');
        $tvdeWeek = $weekId ? TvdeWeek::find($weekId) : null;
        $range    = $this->weekRange($tvdeWeek);

        $plate = $this->resolvePlate($driverId, $range['from'], $range['to']);
        if (!$plate) {
            return response()->json(['error' => 'Sem matricula associada ao motorista'], 422);
        }

        $params = $this->rangeQuery($range['from'], $range['to']);

        $variants    = $this->plateVariants($plate);
        $usedPlate   = $variants[0] ?? $plate;
        $trips       = null;
        $events      = null;
        $last404Body = null;
        $last404Msg  = null;

        foreach ($variants as $candidate) {
            try {
                $trips     = $cartrack->getTripsByRegistration($candidate, $params);
                $events    = $cartrack->getEventsByRegistration($candidate, $params);
                $usedPlate = $candidate;
                break;
            } catch (CartrackException $e) {
                $status = $e->status ?? 500;
                $body   = $e->body;

                if ($status === 404) {
                    $last404Body = $body;
                    $last404Msg  = $e->getMessage();
                    Log::warning('Cartrack fetch 404, trying next plate format', [
                        'driver_id' => $driverId,
                        'plate'     => $candidate,
                        'params'    => $params,
                        'status'    => $status,
                        'body'      => $body,
                        'message'   => $e->getMessage(),
                    ]);
                    continue;
                }

                Log::warning('Cartrack fetch failed', [
                    'driver_id' => $driverId,
                    'plate'     => $candidate,
                    'params'    => $params,
                    'status'    => $status,
                    'body'      => $body,
                    'message'   => $e->getMessage(),
                ]);

                return response()->json([
                    'error'  => $e->getMessage(),
                    'body'   => $body,
                    'status' => $status,
                ], $status);
            }
        }

        if ($trips === null || $events === null) {
            return response()->json([
                'error'  => $last404Msg ?? 'Falha ao obter dados da matrícula',
                'body'   => $last404Body,
                'status' => 404,
            ], 404);
        }

        try {
            return response()->json([
                'plate'     => $this->formatPlateForDisplay($usedPlate),
                'km'        => $this->sumDistance($trips),
                'incidents' => $this->summarizeIncidents($events, $trips),
            ]);
        } catch (Throwable $e) {
            Log::error('Cartrack fetch exception', [
                'driver_id' => $driverId,
                'plate'     => $usedPlate,
                'params'    => $params,
                'message'   => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function weekRange($tvde_week): array
    {
        $from = $tvde_week?->start_date ?? null;
        $to   = $tvde_week?->end_date ?? null;

        return [
            'from' => $from ? Carbon::parse($from)->toDateString() : null,
            'to'   => $to ? Carbon::parse($to)->toDateString() : null,
        ];
    }

    protected function usagePlates(?string $from, ?string $to): array
    {
        if (!$from || !$to) {
            return [];
        }

        $usages = VehicleUsage::with('vehicle_item')
            ->where('start_date', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $from);
            })
            ->get();

        $map = [];

        foreach ($usages as $usage) {
            $plate = optional($usage->vehicle_item)->license_plate;
            if (!$plate) {
                continue;
            }
            $map[$usage->driver_id] = $this->normalizePlate($plate);
        }

        return $map;
    }

    protected function resolvePlate(int $driverId, ?string $from, ?string $to): ?string
    {
        $driver = Driver::find($driverId);
        if (!$driver) {
            return null;
        }

        $plate = trim((string) $driver->license_plate);

        if ($plate === '' && $from && $to) {
            $usage = VehicleUsage::with('vehicle_item')
                ->where('driver_id', $driverId)
                ->where('start_date', '<=', $to)
                ->where(function ($q) use ($from) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $from);
                })
                ->latest('start_date')
                ->first();

            $plate = $usage && $usage->vehicle_item ? $usage->vehicle_item->license_plate : '';
        }

        return $plate ? $this->normalizePlate($plate) : null;
    }

    protected function normalizePlate(string $plate): string
    {
        return Str::of($plate)->upper()->replace('-', '')->replace(' ', '')->__toString();
    }

    protected function formatPlateForDisplay(?string $plate): ?string
    {
        if (!$plate) {
            return null;
        }

        $normalized = $this->normalizePlate($plate);

        if (strlen($normalized) === 6) {
            return implode('-', str_split($normalized, 2));
        }

        return Str::of($normalized)->upper()->__toString();
    }

    protected function plateVariants(string $plate): array
    {
        $normalized = $this->normalizePlate($plate);
        $variants   = [];

        if (strlen($normalized) === 6) {
            $variants[] = implode('-', str_split($normalized, 2)); // prefer AA-00-AA first
        }

        $variants[] = $normalized; // plain, sem hífen
        $variants[] = Str::of($plate)->upper()->__toString(); // original (uppercase)

        return array_values(array_unique(array_filter($variants)));
    }

    protected function rangeQuery(?string $from, ?string $to): array
    {
        $data = [];

        if ($from) {
            $fromCarbon = Carbon::parse($from)->startOfDay();
            $data['start_timestamp'] = $fromCarbon->toDateTimeString(); // format expected by Cartrack
        }

        if ($to) {
            $toCarbon = Carbon::parse($to)->endOfDay();
            $data['end_timestamp'] = $toCarbon->toDateTimeString();   // format expected by Cartrack
        }

        return $data;
    }

    protected function sumDistance($trips): float
    {
        $total = 0.0;
        $list  = is_array($trips) && array_key_exists('data', $trips) ? $trips['data'] : $trips;

        if (is_iterable($list)) {
            foreach ($list as $trip) {
                if (!is_array($trip)) {
                    continue;
                }

                $distance = $trip['distance'] ?? $trip['totalDistance'] ?? $trip['total_distance'] ?? null;

                if ($distance === null && isset($trip['odometerStart'], $trip['odometerEnd'])) {
                    $distance = $trip['odometerEnd'] - $trip['odometerStart'];
                }

                if ($distance === null && isset($trip['start_odometer'], $trip['end_odometer'])) {
                    $distance = $trip['end_odometer'] - $trip['start_odometer'];
                }

                if ($distance === null && isset($trip['trip_distance'])) {
                    $distance = $trip['trip_distance'];
                }

                if ($distance === null && isset($trip['length'])) {
                    $distance = $trip['length'];
                }

                if ($distance !== null) {
                    $total += (float) $distance;
                }
            }
        }

        if ($total > 10000) {
            $total = $total / 1000;
        }

        return round($total, 2);
    }

    protected function summarizeIncidents($events, $trips = null): array
    {
        $counts = [
            'braking'      => 0,
            'cornering'    => 0,
            'acceleration' => 0,
            'other'        => 0,
        ];

        $list = is_array($events) && array_key_exists('data', $events) ? $events['data'] : $events;

        if (!is_iterable($list)) {
            $list = [];
        }

        foreach ($list as $event) {
            if (!is_array($event)) {
                continue;
            }

            $type = strtolower((string) ($event['type'] ?? $event['eventType'] ?? $event['name'] ?? ''));

            if ($type === '') {
                continue;
            }

            if (str_contains($type, 'brak')) {
                $counts['braking']++;
            } elseif (str_contains($type, 'corner') || str_contains($type, 'turn')) {
                $counts['cornering']++;
            } elseif (str_contains($type, 'accel')) {
                $counts['acceleration']++;
            } else {
                $counts['other']++;
            }
        }

        // Fallback/extra contagem usando os campos agregados das trips
        $tripList = is_array($trips) && array_key_exists('data', $trips) ? $trips['data'] : (is_iterable($trips) ? $trips : []);

        if (is_iterable($tripList)) {
            foreach ($tripList as $trip) {
                if (!is_array($trip)) {
                    continue;
                }

                $counts['braking']      += (int) ($trip['harsh_braking_events'] ?? 0);
                $counts['cornering']    += (int) ($trip['harsh_cornering_events'] ?? 0);
                $counts['acceleration'] += (int) ($trip['harsh_acceleration_events'] ?? 0);

                $speeding = (int) ($trip['road_speeding_events'] ?? 0);
                $thresholdSpeeding = (int) ($trip['thresholds_speeding_events'] ?? 0);
                $counts['other'] += $speeding + $thresholdSpeeding;
            }
        }

        return $counts;
    }
}
