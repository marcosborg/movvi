<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Models\WeeklyVehicleEvaluation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WeeklyVehicleEvaluationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canManage($request), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = WeeklyVehicleEvaluation::with(['tvdeWeek', 'driver', 'vehicle', 'submittedBy', 'media'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($request->filled('tvde_week_id')) {
            $query->where('tvde_week_id', $request->integer('tvde_week_id'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('vehicle_item_id')) {
            $query->where('vehicle_item_id', $request->integer('vehicle_item_id'));
        }

        if ($request->filled('has_vehicle_issue')) {
            $query->where('has_vehicle_issue', $request->boolean('has_vehicle_issue'));
        }

        $evaluations = $query->paginate(25)->withQueryString();
        $weeks = TvdeWeek::orderByDesc('start_date')->limit(26)->get(['id', 'number', 'start_date', 'end_date']);
        $drivers = Driver::orderBy('name')->get(['id', 'name']);
        $vehicles = VehicleItem::orderBy('license_plate')->get(['id', 'license_plate']);

        return view('admin.weeklyVehicleEvaluations.index', compact('evaluations', 'weeks', 'drivers', 'vehicles'));
    }

    public function show(WeeklyVehicleEvaluation $weeklyVehicleEvaluation)
    {
        abort_unless($this->canManage(request()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $weeklyVehicleEvaluation->load(['tvdeWeek', 'driver.company', 'vehicle.vehicle_brand', 'vehicle.vehicle_model', 'submittedBy', 'media']);

        return view('admin.weeklyVehicleEvaluations.show', [
            'evaluation' => $weeklyVehicleEvaluation,
            'fuelLevels' => WeeklyVehicleEvaluation::FUEL_LEVELS,
            'tireStatuses' => WeeklyVehicleEvaluation::TIRE_STATUSES,
            'oilLevels' => WeeklyVehicleEvaluation::OIL_LEVELS,
        ]);
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasRole('Admin') || $user->hasRole('Gestor'));
    }
}
