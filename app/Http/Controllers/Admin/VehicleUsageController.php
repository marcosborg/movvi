<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyVehicleUsageRequest;
use App\Http\Requests\StoreVehicleUsageRequest;
use App\Http\Requests\UpdateVehicleUsageRequest;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class VehicleUsageController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_usage_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = VehicleUsage::with(['driver', 'vehicle_item'])->select(sprintf('%s.*', (new VehicleUsage)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'vehicle_usage_show';
                $editGate      = 'vehicle_usage_edit';
                $deleteGate    = 'vehicle_usage_delete';
                $crudRoutePart = 'vehicle-usages';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('vehicle_item_license_plate', function ($row) {
                return $row->vehicle_item ? $row->vehicle_item->license_plate : '';
            });

            $table->editColumn('usage_exceptions', function ($row) {
                return $row->usage_exceptions ? VehicleUsage::USAGE_EXCEPTIONS_RADIO[$row->usage_exceptions] : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'driver', 'vehicle_item']);

            return $table->make(true);
        }

        return view('admin.vehicleUsages.index');
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_usage_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleUsages.create', compact('drivers', 'vehicle_items'));
    }

    public function store(StoreVehicleUsageRequest $request)
    {
        // As datas estão validadas no formato Y-m-d H:i:s, podemos usá-las diretamente
        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        // Criar SEMPRE o novo registo
        $newUsage = VehicleUsage::create($request->all());

        // Verificar sobreposição com outros registos (excluindo o registo recém-criado)
        $hasOverlap = VehicleUsage::where('vehicle_item_id', $request->vehicle_item_id)
            ->where('id', '!=', $newUsage->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->first();

        if ($hasOverlap) {
            return redirect()->route('admin.vehicle-usages.index')
                ->with('error_message', "Utilização criada com sucesso (ID {$newUsage->id}), mas sobrepõe a utilização existente com ID {$hasOverlap->id}.");
        }

        return redirect()->route('admin.vehicle-usages.index')
            ->with('success', "Utilização criada com sucesso (ID {$newUsage->id}).");
    }

    public function edit(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicleUsage->load('driver', 'vehicle_item');

        return view('admin.vehicleUsages.edit', compact('drivers', 'vehicleUsage', 'vehicle_items'));
    }

    public function update(UpdateVehicleUsageRequest $request, VehicleUsage $vehicleUsage)
    {
        $vehicleUsage->update($request->all());

        return redirect()->route('admin.vehicle-usages.index');
    }

    public function show(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleUsage->load('driver', 'vehicle_item');

        return view('admin.vehicleUsages.show', compact('vehicleUsage'));
    }

    public function destroy(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleUsage->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleUsageRequest $request)
    {
        $vehicleUsages = VehicleUsage::find(request('ids'));

        foreach ($vehicleUsages as $vehicleUsage) {
            $vehicleUsage->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function usage()
    {
        $usages = VehicleUsage::with(['vehicle_item'])
            ->orderBy('start_date')
            ->get();

        $grouped = $usages->groupBy('vehicle_item.license_plate');
        $occupancyStats = [];
        $monthlyStats = [];
        $monthlyStackedStats = []; // para o gráfico
        $yearlyMap = [];

        foreach ($grouped as $plate => $usagesForVehicle) {
            $years = [];

            foreach ($usagesForVehicle as $usage) {
                $startRaw = $usage->getRawOriginal('start_date');
                $endRaw = $usage->getRawOriginal('end_date');

                try {
                    $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startRaw);
                } catch (\Exception $e) {
                    \Log::error("Data inválida em VehicleUsage ID {$usage->id} (start_date): '{$startRaw}'");
                    continue;
                }

                try {
                    $end = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $endRaw);
                } catch (\Exception $e) {
                    \Log::error("Data inválida em VehicleUsage ID {$usage->id} (end_date): '{$endRaw}'");
                    continue;
                }

                $exception = $usage->usage_exceptions ?? 'usage';
                $period = \Carbon\CarbonPeriod::create($start, $end);

                foreach ($period as $day) {
                    $year = $day->year;
                    $month = $day->month;
                    $monthKey = sprintf("%s (%04d-%02d)", $plate, $year, $month);

                    // Monthly simples (total de dias em qualquer estado)
                    if (!isset($monthlyStats[$monthKey])) {
                        $monthlyStats[$monthKey] = [
                            'label' => $monthKey,
                            'plate' => $plate,
                            'year'  => (string) $year,
                            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                            'days'  => 0,
                        ];
                    }
                    $monthlyStats[$monthKey]['days']++;

                    // Monthly detalhado para stacked
                    if (!isset($monthlyStackedStats[$monthKey])) {
                        $monthlyStackedStats[$monthKey] = [
                            'label'       => $monthKey,
                            'plate'       => $plate,
                            'year'        => (string) $year,
                            'month'       => str_pad($month, 2, '0', STR_PAD_LEFT),
                            'usage'       => 0,
                            'maintenance' => 0,
                            'accident'    => 0,
                            'unassigned'  => 0,
                            'personal'    => 0,
                        ];
                    }
                    if (array_key_exists($exception, $monthlyStackedStats[$monthKey])) {
                        $monthlyStackedStats[$monthKey][$exception]++;
                    }

                    // Yearly
                    if (!isset($years[$year])) {
                        $years[$year] = [];
                    }
                    $years[$year][$day->format('Y-m-d')] = true;
                }
            }

            foreach ($years as $year => $usedDays) {
                $totalDays = \Carbon\Carbon::create($year, 1, 1)->daysInYear;
                $usedCount = count($usedDays);
                $occupancyStats[$plate][$year] = [
                    'used'    => $usedCount,
                    'total'   => $totalDays,
                    'percent' => round(($usedCount / $totalDays) * 100, 2),
                ];

                $yearKey = "$plate ($year)";
                if (!isset($yearlyMap[$yearKey])) {
                    $yearlyMap[$yearKey] = [
                        'label'        => $yearKey,
                        'year'         => (string) $year,
                        'totalPercent' => 0,
                        'months'       => 0,
                    ];
                }
            }
        }

        foreach ($monthlyStats as &$stat) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $stat['month'], (int) $stat['year']);
            $stat['percent'] = $daysInMonth > 0 ? round(($stat['days'] / $daysInMonth) * 100, 2) : 0;

            $yearKey = "{$stat['plate']} ({$stat['year']})";
            if (isset($yearlyMap[$yearKey])) {
                $yearlyMap[$yearKey]['totalPercent'] += $stat['percent'];
                $yearlyMap[$yearKey]['months']++;
            }
        }
        unset($stat);

        $yearlyStats = [];
        foreach ($yearlyMap as $entry) {
            $yearlyStats[] = [
                'label'   => $entry['label'],
                'year'    => $entry['year'],
                'percent' => $entry['months'] > 0 ? round($entry['totalPercent'] / $entry['months'], 2) : 0,
            ];
        }

        usort($yearlyStats, fn($a, $b) => $b['percent'] <=> $a['percent']);

        $availableYears = [];
        foreach ($occupancyStats as $years) {
            foreach ($years as $year => $data) {
                $availableYears[$year] = true;
            }
        }
        ksort($availableYears);

        // Reindexar para garantir ordem estável no @json da Blade
        $monthlyStackedStats = array_values($monthlyStackedStats);

        return view('admin.vehicleUsages.usage', compact(
            'grouped',
            'occupancyStats',
            'yearlyStats',
            'monthlyStats',
            'availableYears',
            'monthlyStackedStats'
        ));
    }
}
