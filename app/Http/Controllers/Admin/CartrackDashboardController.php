<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\Reports;
use App\Services\Cartrack\CartrackFleetApiService;
use App\Models\Driver;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Client\RequestException;
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
                'license'   => $plate,
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

        try {
            $trips  = $cartrack->getTripsByRegistration($plate, $params);
            $events = $cartrack->getEventsByRegistration($plate, $params);

            return response()->json([
                'plate'     => $plate,
                'km'        => $this->sumDistance($trips),
                'incidents' => $this->summarizeIncidents($events),
            ]);
        } catch (RequestException $e) {
            $status = optional($e->response)->status() ?: 500;
            $body   = optional($e->response)->json() ?? optional($e->response)->body();

            Log::warning('Cartrack fetch failed', [
                'driver_id' => $driverId,
                'plate'     => $plate,
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
        } catch (Throwable $e) {
            Log::error('Cartrack fetch exception', [
                'driver_id' => $driverId,
                'plate'     => $plate,
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

            $plate = optional($usage->vehicle_item)->license_plate ?? '';
        }

        return $plate ? $this->normalizePlate($plate) : null;
    }

    protected function normalizePlate(string $plate): string
    {
        return Str::of($plate)->upper()->replace('-', '')->replace(' ', '')->__toString();
    }

    protected function rangeQuery(?string $from, ?string $to): array
    {
        $data = [];

        if ($from) {
            $fromCarbon = Carbon::parse($from)->startOfDay();
            $data['start_timestamp']    = $fromCarbon->timestamp;            // seconds
            $data['start_timestamp_ms'] = $fromCarbon->timestamp * 1000;     // ms
            $data['start']              = $fromCarbon->toIso8601String();
        }

        if ($to) {
            $toCarbon = Carbon::parse($to)->endOfDay();
            $data['end_timestamp']      = $toCarbon->timestamp;              // seconds
            $data['end_timestamp_ms']   = $toCarbon->timestamp * 1000;       // ms
            $data['end']                = $toCarbon->toIso8601String();
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

    protected function summarizeIncidents($events): array
    {
        $counts = [
            'braking'      => 0,
            'cornering'    => 0,
            'acceleration' => 0,
            'other'        => 0,
        ];

        $list = is_array($events) && array_key_exists('data', $events) ? $events['data'] : $events;

        if (!is_iterable($list)) {
            return $counts;
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

        return $counts;
    }
}
