<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverAlert;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class DriverAlertController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $alerts = DriverAlert::with('driver.company')
            ->orderByRaw('CASE WHEN resolved_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.driverAlerts.index', compact('alerts'));
    }
}
