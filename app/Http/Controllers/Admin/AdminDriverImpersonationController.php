<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDriverImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDriverImpersonationController extends Controller
{
    public function start(Request $request, AdminDriverImpersonationService $impersonationService): RedirectResponse
    {
        abort_if(!$impersonationService->canUse($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $driver = $impersonationService->start((int) $validated['driver_id']);

        return redirect()->route('admin.home')
            ->with('message', 'Modo motorista ativo: ' . $driver->name . '.');
    }

    public function stop(Request $request, AdminDriverImpersonationService $impersonationService): RedirectResponse
    {
        abort_if(!$impersonationService->canUse($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $impersonationService->stop();

        return redirect()->route('admin.home')
            ->with('message', 'Modo motorista terminado.');
    }

    public function drivers(Request $request, AdminDriverImpersonationService $impersonationService): JsonResponse
    {
        abort_if(!$impersonationService->canUse($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'results' => $impersonationService->searchEligibleDrivers((string) $request->query('q', '')),
        ]);
    }
}
