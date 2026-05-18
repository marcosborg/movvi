<?php

namespace App\Http\Middleware;

use App\Services\AdminDriverImpersonationService;
use Closure;
use Illuminate\Support\Facades\DB;
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
            if ($this->isAllowedForcedReceiptSubmission($request, $impersonationService)) {
                return $next($request);
            }

            return redirect()->back()->with('error_message', 'Modo motorista ativo em leitura. Saia deste modo para editar.');
        }

        return $next($request);
    }

    protected function isAllowedForcedReceiptSubmission(Request $request, AdminDriverImpersonationService $impersonationService): bool
    {
        $routeName = optional($request->route())->getName();

        if (!in_array($routeName, ['admin.receipts.store', 'admin.receipts.storeMedia'], true)) {
            return false;
        }

        $admin = $impersonationService->resolveOriginalAdmin($request->user());
        if (!$admin) {
            return false;
        }

        if ($admin->is_admin || $admin->hasRole('Admin') || $admin->hasRole('Administrador')) {
            return true;
        }

        return DB::table('permissions')
            ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->join('role_user', 'permission_role.role_id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $admin->id)
            ->where('permissions.title', 'force_driver_receipt_submission')
            ->exists();
    }
}
