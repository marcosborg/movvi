<?php

namespace App\Services\ContaAzul;

use App\Models\Company;
use App\Models\ContaAzulConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ContaAzulOAuthService
{
    public function buildAuthorizationUrl(Company $company, int $userId): string
    {
        $state = Str::random(48);

        Cache::put($this->stateCacheKey($state), [
            'company_id' => $company->id,
            'user_id' => $userId,
        ], now()->addMinutes(config('conta_azul.state_ttl_minutes', 10)));

        return rtrim(config('conta_azul.auth_base_url'), '/') . '/login?' . http_build_query([
            'response_type' => 'code',
            'client_id' => config('conta_azul.client_id'),
            'redirect_uri' => $this->resolveRedirectUri(),
            'state' => $state,
            'scope' => config('conta_azul.scope'),
        ]);
    }

    public function exchangeAuthorizationCode(string $code, string $state): ContaAzulConnection
    {
        $statePayload = Cache::pull($this->stateCacheKey($state));

        if (! is_array($statePayload) || empty($statePayload['company_id'])) {
            throw new \RuntimeException('Estado OAuth invalido ou expirado.');
        }

        $company = Company::findOrFail((int) $statePayload['company_id']);
        $response = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->resolveRedirectUri(),
        ]);

        return $this->persistTokenResponse($company, $response->json(), ContaAzulConnection::STATUS_CONNECTED, [
            'last_grant_type' => 'authorization_code',
            'authorized_user_id' => $statePayload['user_id'] ?? null,
        ]);
    }

    public function refresh(ContaAzulConnection $connection): ContaAzulConnection
    {
        if (! $connection->refresh_token) {
            throw new \RuntimeException('A ligacao Conta Azul nao tem refresh token guardado.');
        }

        $response = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);

        return $this->persistTokenResponse($connection->company, $response->json(), ContaAzulConnection::STATUS_CONNECTED, [
            'last_grant_type' => 'refresh_token',
        ], $connection);
    }

    public function disconnect(ContaAzulConnection $connection): void
    {
        $connection->update([
            'status' => ContaAzulConnection::STATUS_DISCONNECTED,
            'access_token' => null,
            'refresh_token' => null,
            'token_type' => null,
            'scope' => null,
            'expires_at' => null,
            'last_error' => null,
            'token_payload' => null,
            'oauth_meta' => array_merge($connection->oauth_meta ?? [], [
                'disconnected_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    public function isConfigured(): bool
    {
        return filled(config('conta_azul.client_id'))
            && filled(config('conta_azul.client_secret'))
            && filled($this->resolveRedirectUri());
    }

    public function resolveRedirectUri(): ?string
    {
        return config('conta_azul.redirect_uri') ?: route('admin.conta-azul.callback');
    }

    protected function tokenRequest(array $payload): Response
    {
        $response = Http::asForm()
            ->acceptJson()
            ->withBasicAuth((string) config('conta_azul.client_id'), (string) config('conta_azul.client_secret'))
            ->post(rtrim(config('conta_azul.auth_base_url'), '/') . '/oauth2/token', $payload);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Falha ao obter tokens da Conta Azul: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response;
    }

    protected function persistTokenResponse(
        Company $company,
        array $payload,
        string $status,
        array $oauthMeta = [],
        ?ContaAzulConnection $connection = null
    ): ContaAzulConnection {
        $connection ??= ContaAzulConnection::firstOrNew([
            'company_id' => $company->id,
        ]);

        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $connection->fill([
            'status' => $status,
            'access_token' => $payload['access_token'] ?? null,
            'refresh_token' => $payload['refresh_token'] ?? null,
            'token_type' => $payload['token_type'] ?? 'Bearer',
            'scope' => $payload['scope'] ?? config('conta_azul.scope'),
            'expires_at' => Carbon::now()->addSeconds($expiresIn),
            'connected_at' => $connection->connected_at ?? now(),
            'last_refreshed_at' => now(),
            'last_error' => null,
            'token_payload' => $payload,
            'oauth_meta' => array_merge($connection->oauth_meta ?? [], $oauthMeta),
        ]);
        $connection->save();

        return $connection->fresh('company');
    }

    protected function stateCacheKey(string $state): string
    {
        return 'conta_azul_oauth_state:' . $state;
    }
}
