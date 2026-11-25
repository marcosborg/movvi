<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;

class AuthenticateMaintenanceIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $config = config('maintenance_integration');
        $production = app()->environment('production');
        $allowedIp = $config['allowed_ip'];
        $enabled = $production
            ? $config['production_enabled'] === true && filter_var($allowedIp, FILTER_VALIDATE_IP) && $request->isSecure()
            : app()->environment('maintenance-sandbox', 'testing') && $config['enabled'];
        abort_unless($enabled, 404);

        $companyId = filter_var($config['company_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = $request->bearerToken();
        $expectedHash = $config['token_hash'];
        $expiresAt = $config['expires_at'];
        abort_unless($companyId && $token && is_string($expectedHash)
            && hash_equals($expectedHash, hash('sha256', $token))
            && is_string($expiresAt) && strtotime($expiresAt) !== false
            && CarbonImmutable::parse($expiresAt)->isFuture(), 401, 'Credencial de integração inválida ou expirada.');

        abort_if($allowedIp && $request->ip() !== $allowedIp, 403);
        abort_if($production && ! $request->isMethodSafe() && $config['production_writes_enabled'] !== true,
            403, 'A escrita está desativada para a integração.');
        $request->attributes->set('maintenance_company_id', $companyId);

        return $next($request);
    }
}
