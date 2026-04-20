<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SidebarFavoritesService
{
    public function forUser(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        return $user->favorites()
            ->get()
            ->map(function (UserFavorite $favorite) {
                $favorite->is_active = $this->normalizeUrl($favorite->url) === $this->normalizeUrl(request()->fullUrl());
                $favorite->display_icon = $favorite->icon ?: 'fas fa-star';
                $favorite->display_label = $this->resolveFavoriteLabel($favorite);

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

        $uri = trim($request->route()->uri(), '/');

        if (!$uri || in_array($request->route()->getName(), ['admin.favorites.store', 'admin.favorites.destroy', 'admin.favorites.reorder'], true)) {
            return null;
        }

        return [
            'label' => $this->guessLabel($request),
            'url' => $this->normalizeUrl($request->fullUrl()),
            'icon' => $this->guessIcon($uri),
        ];
    }

    public function canAdd(?User $user): bool
    {
        return $user ? $user->favorites()->count() < 10 : false;
    }

    protected function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    protected function guessLabel(Request $request): string
    {
        $title = trim((string) $request->input('page_title', ''));
        if ($title !== '') {
            return Str::limit($title, 255, '');
        }

        $routeName = (string) optional($request->route())->getName();
        if ($routeName === 'admin.home') {
            return 'Dashboard';
        }

        $segments = explode('.', $routeName);
        $resource = $segments[1] ?? basename((string) optional($request->route())->uri());
        $resource = str_replace('-', ' ', $resource);
        $resource = Str::singular($resource);

        return Str::title($resource);
    }

    protected function resolveFavoriteLabel(UserFavorite $favorite): string
    {
        $label = trim((string) $favorite->label);

        if ($label !== '' && mb_strtolower($label) !== mb_strtolower((string) trans('panel.site_title'))) {
            return $label;
        }

        $path = trim((string) parse_url($favorite->url, PHP_URL_PATH), '/');

        if ($path === 'admin' || $path === 'admin/') {
            return 'Dashboard';
        }

        $segments = explode('/', $path);
        $resource = $segments[1] ?? $segments[0] ?? '';
        $resource = str_replace('-', ' ', $resource);

        if ($resource === '') {
            return 'Favorito';
        }

        return Str::title($resource);
    }

    protected function guessIcon(string $uri): string
    {
        if (Str::contains($uri, 'report')) {
            return 'fas fa-file-alt';
        }

        if (Str::contains($uri, 'driver')) {
            return 'fas fa-address-card';
        }

        if (Str::contains($uri, 'vehicle')) {
            return 'fas fa-car';
        }

        return 'fas fa-star';
    }
}
