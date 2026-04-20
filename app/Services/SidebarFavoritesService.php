<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SidebarFavoritesService
{
    public const MAX_FAVORITES = 10;

    public function forUser(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        return $user->favorites()
            ->get()
            ->map(function (UserFavorite $favorite) {
                $favorite->is_active = $this->isFavoriteActive($favorite);
                $favorite->display_icon = $favorite->icon ?: 'fas fa-star';

                return $favorite;
            });
    }

    public function currentFavorite(?User $user, Request $request): ?UserFavorite
    {
        if (!$user) {
            return null;
        }

        $currentUrl = $this->normalizeUrl($request->fullUrl());

        return $user->favorites()
            ->get()
            ->first(function (UserFavorite $favorite) use ($currentUrl) {
                return $this->normalizeUrl($favorite->url) === $currentUrl;
            });
    }

    public function buildCandidate(Request $request): ?array
    {
        if (!$request->route() || !$request->is('admin*')) {
            return null;
        }

        $route = $request->route();
        $routeName = $route->getName();
        $uri = trim($route->uri(), '/');

        if (!$uri || in_array($routeName, ['admin.user-favorites.store', 'admin.user-favorites.destroy'], true)) {
            return null;
        }

        return [
            'label' => $this->guessLabel($routeName, $uri),
            'url' => $this->normalizeUrl($request->fullUrl()),
            'route_name' => $routeName,
            'route_params' => $route->parameters(),
            'active_pattern' => $this->buildActivePattern($uri),
            'icon' => $this->guessIcon($routeName, $uri),
        ];
    }

    public function canAdd(User $user): bool
    {
        return $user->favorites()->count() < self::MAX_FAVORITES;
    }

    public function nextSortOrder(User $user): int
    {
        return (int) $user->favorites()->max('sort_order') + 1;
    }

    protected function isFavoriteActive(UserFavorite $favorite): bool
    {
        if ($favorite->active_pattern && request()->is($favorite->active_pattern)) {
            return true;
        }

        return $this->normalizeUrl($favorite->url) === $this->normalizeUrl(request()->fullUrl());
    }

    protected function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    protected function buildActivePattern(string $uri): string
    {
        if ($uri === 'admin') {
            return 'admin';
        }

        if (preg_match('/\{[^}]+\}/', $uri)) {
            $segments = explode('/', $uri);
            $stableSegments = [];

            foreach ($segments as $segment) {
                if (Str::startsWith($segment, '{')) {
                    break;
                }

                $stableSegments[] = $segment;
            }

            $base = implode('/', $stableSegments);

            return $base !== '' ? $base . '*' : 'admin*';
        }

        return $uri . '*';
    }

    protected function guessLabel(?string $routeName, string $uri): string
    {
        if ($routeName === 'admin.home') {
            return 'Dashboard';
        }

        $segments = explode('.', (string) $routeName);
        $resource = $segments[1] ?? basename($uri);
        $resource = str_replace('-', ' ', $resource);
        $resource = Str::singular($resource);

        return Str::title($resource);
    }

    protected function guessIcon(?string $routeName, string $uri): string
    {
        $map = [
            'admin.home' => 'fas fa-tachometer-alt',
            'admin.users.index' => 'fas fa-user',
            'admin.drivers.index' => 'fas fa-address-card',
            'admin.combustion-transactions.index' => 'fas fa-gas-pump',
            'admin.electric-transactions.index' => 'fas fa-bolt',
            'admin.vehicle-usages.index' => 'fas fa-calendar-plus',
            'admin.vehicle-items.index' => 'fas fa-car',
            'admin.company-reports.index' => 'fas fa-file-alt',
            'admin.financial-statements.index' => 'fas fa-file-invoice-dollar',
        ];

        if ($routeName && isset($map[$routeName])) {
            return $map[$routeName];
        }

        return Str::contains($uri, 'report') ? 'fas fa-star' : 'far fa-star';
    }
}
