<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverAlert;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverAlertController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $selectedDriverId = $request->integer('driver_id') ?: null;

        $drivers = Driver::orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('global.all'), '');

        $alerts = DriverAlert::with('driver.company')
            ->where('type', 'like', 'missing_receipt_week_%')
            ->when($selectedDriverId, fn ($query) => $query->where('driver_id', $selectedDriverId))
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.driverAlerts.index', compact('alerts', 'drivers', 'selectedDriverId'));
    }
}
