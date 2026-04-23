<?php

namespace App\Http\Middleware;

use App\Services\AdminDriverImpersonationService;
use Closure;
use Illuminate\Http\Request;

class PreventWritesWhileImpersonating
{
    public function handle(Request $request, Closure $next)
    {
        $impersonationService = app(AdminDriverImpersonationService::class);
        $routeName = optional($request->route())->getName();
        $allowedRoutes = [
            'admin.impersonation.start',
            'admin.impersonation.stop',
            'logout',
        ];

        if (
            $impersonationService->isImpersonating()
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && !in_array($routeName, $allowedRoutes, true)
        ) {
            return redirect()->back()->with('error_message', 'Modo motorista ativo em leitura. Saia deste modo para editar.');
        }

        return $next($request);
    }
}
