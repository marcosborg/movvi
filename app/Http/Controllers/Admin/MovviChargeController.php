<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovviChargeImport;
use App\Services\MovviChargeImportService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MovviChargeController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('tesla_charging_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $imports = MovviChargeImport::with(['tvdeWeek', 'importedBy'])
            ->orderByDesc('imported_at')
            ->paginate(25);

        return view('admin.movviCharge.index', compact('imports'));
    }

    public function import(Request $request, MovviChargeImportService $service)
    {
        abort_if(Gate::denies('tesla_charging_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'charge_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $result = $service->import($validated['charge_file'], optional($request->user())->id);
        $message = sprintf(
            '%s %d motoristas da semana %d-W%02d: %d sessões, %.2f kWh e %.2f €.',
            $result['was_replacement'] ? 'Importação substituída com sucesso.' : 'Importação concluída com sucesso.',
            $result['row_count'],
            $result['year'],
            $result['week'],
            $result['total_sessions'],
            $result['total_kwh'],
            $result['total_value']
        );

        if (! empty($result['unknown_driver_ids'])) {
            $message .= ' IDs ignorados: '.implode(', ', $result['unknown_driver_ids']).'.';
        }

        return redirect()->route('admin.movvi-charge.index')->with('message', $message);
    }
}
