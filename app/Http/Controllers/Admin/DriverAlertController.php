<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\TvdeWeek;
use App\Services\ReceiptControlService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverAlertController extends Controller
{
    public function index(Request $request, ReceiptControlService $receiptControlService)
    {
        abort_if(Gate::denies('receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $selectedDriverId = $request->integer('driver_id') ?: null;
        $selectedWeekId = $request->integer('tvde_week_id') ?: null;
        $selectedStatus = $request->input('status', ReceiptControlService::STATUS_ACTIVE);
        $allowedStatuses = array_keys(ReceiptControlService::STATUS_LABELS);

        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = ReceiptControlService::STATUS_ACTIVE;
        }

        $drivers = Driver::orderBy('name')
            ->pluck('name', 'id')
            ->prepend(trans('global.all'), '');

        $tvdeWeeks = TvdeWeek::orderByDesc('start_date')
            ->pluck('start_date', 'id')
            ->prepend(trans('global.all'), '');

        $statuses = ReceiptControlService::STATUS_LABELS;

        $receiptRows = $receiptControlService->rows([
            'driver_id' => $selectedDriverId,
            'tvde_week_id' => $selectedWeekId,
            'status' => $selectedStatus,
        ]);

        return view('admin.driverAlerts.index', compact(
            'receiptRows',
            'drivers',
            'tvdeWeeks',
            'statuses',
            'selectedDriverId',
            'selectedWeekId',
            'selectedStatus'
        ));
    }
}
