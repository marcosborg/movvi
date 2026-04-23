<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminDriverImpersonationService
{
    public const SESSION_ADMIN_ID = 'impersonator_admin_id';
    public const SESSION_ADMIN_NAME = 'impersonator_admin_name';
    public const SESSION_DRIVER_ID = 'impersonated_driver_id';
    public const SESSION_PREVIOUS_COMPANY_ID = 'impersonator_previous_company_id';
    public const SESSION_PREVIOUS_DRIVER_ID = 'impersonator_previous_driver_id';

    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_ADMIN_ID) && session()->has(self::SESSION_DRIVER_ID);
    }

    public function canUse(?User $user = null): bool
    {
        return $this->resolveOriginalAdmin($user) !== null;
    }

    public function resolveOriginalAdmin(?User $user = null): ?User
    {
        $user = $user ?: auth()->user();

        if ($this->isAdminUser($user)) {
            return $user;
        }

        $adminId = session()->get(self::SESSION_ADMIN_ID);
        if (!$adminId) {
            return null;
        }

        return User::find($adminId);
    }

    public function impersonatedDriver(): ?Driver
    {
        $driverId = session()->get(self::SESSION_DRIVER_ID);
        if (!$driverId) {
            return null;
        }

        return Driver::with('user')->find($driverId);
    }

    public function start(int $driverId): Driver
    {
        $admin = $this->resolveOriginalAdmin(auth()->user());
        if (!$admin) {
            throw new AuthorizationException('403 Forbidden');
        }

        $driver = Driver::query()
            ->with('user')
            ->findOrFail($driverId);

        if (!$driver->user_id || !$driver->user) {
            throw ValidationException::withMessages([
                'driver_id' => 'O motorista selecionado nao tem utilizador associado.',
            ]);
        }

        $previousCompanyId = $this->isImpersonating()
            ? session()->get(self::SESSION_PREVIOUS_COMPANY_ID)
            : session()->get('company_id');

        $previousDriverId = $this->isImpersonating()
            ? session()->get(self::SESSION_PREVIOUS_DRIVER_ID)
            : session()->get('driver_id');

        Auth::login($driver->user);

        session()->put(self::SESSION_ADMIN_ID, $admin->id);
        session()->put(self::SESSION_ADMIN_NAME, $admin->name);
        session()->put(self::SESSION_DRIVER_ID, $driver->id);
        session()->put(self::SESSION_PREVIOUS_COMPANY_ID, $previousCompanyId);
        session()->put(self::SESSION_PREVIOUS_DRIVER_ID, $previousDriverId);
        session()->put('driver_id', $driver->id);

        if ($driver->company_id) {
            session()->put('company_id', $driver->company_id);
        } else {
            session()->forget('company_id');
        }

        return $driver;
    }

    public function stop(): User
    {
        $admin = $this->resolveOriginalAdmin();
        if (!$admin) {
            throw new AuthorizationException('403 Forbidden');
        }

        $previousCompanyId = session()->get(self::SESSION_PREVIOUS_COMPANY_ID);
        $previousDriverId = session()->get(self::SESSION_PREVIOUS_DRIVER_ID);

        Auth::login($admin);

        $this->clearImpersonationSession();

        if ($previousCompanyId !== null && $previousCompanyId !== '') {
            session()->put('company_id', $previousCompanyId);
        } else {
            session()->forget('company_id');
        }

        if ($previousDriverId !== null && $previousDriverId !== '') {
            session()->put('driver_id', $previousDriverId);
        } else {
            session()->forget('driver_id');
        }

        return $admin;
    }

    public function searchEligibleDrivers(string $search = '', int $limit = 20): array
    {
        $query = Driver::query()
            ->with('user:id,name,email')
            ->whereNotNull('user_id')
            ->whereHas('user')
            ->orderBy('name')
            ->limit($limit);

        $search = trim($search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('drivers.name', 'like', '%' . $search . '%')
                    ->orWhere('drivers.code', 'like', '%' . $search . '%')
                    ->orWhere('drivers.email', 'like', '%' . $search . '%')
                    ->orWhere('drivers.id', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->get()
            ->map(function (Driver $driver) {
                return [
                    'id' => $driver->id,
                    'text' => $this->formatDriverLabel($driver),
                    'name' => $driver->name,
                    'email' => $driver->user->email ?? $driver->email,
                ];
            })
            ->values()
            ->all();
    }

    public function viewState(?User $user = null): array
    {
        $admin = $this->resolveOriginalAdmin($user);
        $driver = $this->impersonatedDriver();

        return [
            'can_use' => $admin !== null,
            'is_active' => $this->isImpersonating(),
            'admin_name' => session()->get(self::SESSION_ADMIN_NAME) ?: $admin?->name,
            'driver' => $driver,
            'driver_option' => $driver ? [
                'id' => $driver->id,
                'text' => $this->formatDriverLabel($driver),
            ] : null,
        ];
    }

    public function clearImpersonationSession(): void
    {
        session()->forget([
            self::SESSION_ADMIN_ID,
            self::SESSION_ADMIN_NAME,
            self::SESSION_DRIVER_ID,
            self::SESSION_PREVIOUS_COMPANY_ID,
            self::SESSION_PREVIOUS_DRIVER_ID,
        ]);
    }

    protected function isAdminUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (bool) ($user->is_admin || $user->hasRole('Admin') || $user->hasRole('Administrador'));
    }

    protected function formatDriverLabel(Driver $driver): string
    {
        $email = $driver->user->email ?? $driver->email;
        $parts = [$driver->name . ' (#' . $driver->id . ')'];

        if ($driver->code) {
            $parts[] = $driver->code;
        }

        if ($email) {
            $parts[] = $email;
        }

        return implode(' - ', $parts);
    }
}
