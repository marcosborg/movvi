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
        $newUsage = VehicleUsage::create($request->all());

        return redirect()->route('admin.vehicle-usages.index')
            ->with('success', "Utilizacao criada com sucesso (ID {$newUsage->id}).");
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

    public function usage(Request $request)
    {
        abort_if(Gate::denies('vehicle_usage_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $today = now()->toDateString();
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'to' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'vehicle_ids' => ['nullable', 'array'], 'vehicle_ids.*' => ['integer', 'distinct'],
            'group' => ['nullable', 'string', 'max:80'],
            'selection' => ['nullable', 'in:all,selected'],
            'section' => ['nullable', 'in:all,timeline,chart,detail'],
            'format' => ['nullable', 'in:pdf'],
            'active_only' => ['nullable', 'boolean'],
            'breakdown' => ['nullable', 'in:years,months,weeks'],
        ]);
        $from = $request->input('from') ?: now()->startOfYear()->toDateString();
        $to = $request->input('to') ?: $today;
        abort_if($from > $to, 422, 'A data inicial deve ser anterior à data final.');
        abort_if(\Carbon\Carbon::parse($from)->diffInDays($to) > 3660, 422, 'Selecione um período até 10 anos.');
        $companyId = session('company_id') ?: auth()->user()?->company_id;
        $vehicles = VehicleItem::with(['vehicle_model', 'vehicle_brand'])
            ->addSelect(['first_usage_at' => VehicleUsage::selectRaw('MIN(start_date)')
                ->whereColumn('vehicle_item_id', 'vehicle_items.id')
                ->where(fn ($q) => $q->where('usage_exceptions', 'usage')
                    ->orWhere(fn ($q) => $q->whereNull('usage_exceptions')->whereNotNull('driver_id')))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereColumn('end_date', '>', 'start_date'))])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('license_plate')->get();
        $groups = [];
        foreach ($vehicles as $vehicle) {
            if ($vehicle->vehicle_brand) $groups['brand:'.$vehicle->vehicle_brand_id] = 'Marca: '.$vehicle->vehicle_brand->name;
            if ($vehicle->vehicle_model) $groups['model:'.$vehicle->vehicle_model_id] = 'Modelo: '.$vehicle->vehicle_model->name;
        }
        asort($groups);
        $group = $request->input('group', '');
        abort_if($group && !isset($groups[$group]), 422, 'Grupo de viaturas inválido.');
        $ids = array_map('intval', $request->input('vehicle_ids', []));
        $activeOnly = $request->boolean('active_only', true);
        $breakdown = $request->input('breakdown', 'years');
        abort_if(array_diff($ids, $vehicles->modelKeys()), 403, 'Viatura fora da empresa selecionada.');
        abort_if($request->input('selection') === 'selected' && !$ids, 422, 'Selecione pelo menos uma viatura.');
        $selected = $vehicles->filter(function ($vehicle) use ($group, $ids, $request, $activeOnly, $today) {
            if ($activeOnly && ($vehicle->suspended || !$vehicle->first_usage_at
                || substr($vehicle->first_usage_at, 0, 10) > $today
                || ($vehicle->getRawOriginal('sale_date') && $vehicle->getRawOriginal('sale_date') <= $today))) return false;
            if ($group) {
                [$kind, $id] = explode(':', $group);
                if ((int) $vehicle->{$kind === 'brand' ? 'vehicle_brand_id' : 'vehicle_model_id'} !== (int) $id) return false;
            }
            return $request->input('selection') !== 'selected' || in_array($vehicle->id, $ids, true);
        });
        $usages = VehicleUsage::with('driver')->whereIn('vehicle_item_id', $selected->modelKeys())
            ->where('start_date', '<', \Carbon\Carbon::parse($to)->addDay()->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>', $from))->get();
        $canViewRevenue = Gate::allows('vehicle_profitability_access');
        $revenueWeeks = $canViewRevenue ? app(\App\Services\VehicleUsageRevenueService::class)->weeks($selected, $from, $to) : [];
        $report = (new \App\Services\VehicleUsageReportService)->build($selected, $usages, $from, $to, $revenueWeeks);
        $section = $request->input('section', 'all');
        $companyName = $companyId ? \App\Models\Company::find($companyId)?->name : 'Todas as empresas';
        $data = compact('report', 'vehicles', 'groups', 'group', 'ids', 'from', 'to', 'section', 'companyName', 'activeOnly', 'breakdown', 'canViewRevenue');
        if ($request->input('format') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vehicleUsages.usage-pdf', $data)->setPaper('a4', 'landscape');
            $pdf->render();
            $pdf->getDomPDF()->getCanvas()->page_text(745, 572, '{PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.4, 0.4, 0.4]);
            return $pdf->stream('utilizacao-viaturas-'.$from.'-'.$to.'.pdf');
        }
        return view('admin.vehicleUsages.usage', $data);
    }
}
