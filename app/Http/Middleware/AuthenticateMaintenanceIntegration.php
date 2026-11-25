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
        abort_unless(app()->environment('maintenance-sandbox', 'testing') && $config['enabled'], 404);

        $companyId = filter_var($config['company_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = $request->bearerToken();
        $expectedHash = $config['token_hash'];
        $expiresAt = $config['expires_at'];
        abort_unless($companyId && $token && is_string($expectedHash)
            && hash_equals($expectedHash, hash('sha256', $token))
            && is_string($expiresAt) && strtotime($expiresAt) !== false
            && CarbonImmutable::parse($expiresAt)->isFuture(), 401, 'Credencial de integração inválida ou expirada.');

        abort_if($config['allowed_ip'] && $request->ip() !== $config['allowed_ip'], 403);
        $request->attributes->set('maintenance_company_id', $companyId);

        return $next($request);
    }
}
