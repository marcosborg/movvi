<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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
        if ($translated = $this->resolveTranslatedLabelFromRoute($request->route())) {
            return $translated;
        }

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
        if ($translated = $this->resolveTranslatedLabelFromUrl((string) $favorite->url)) {
            return $translated;
        }

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

    protected function resolveTranslatedLabelFromUrl(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return null;
        }

        $query = (string) parse_url($url, PHP_URL_QUERY);

        try {
            $route = app('router')->getRoutes()->match(HttpRequest::create(
                $path . ($query !== '' ? '?' . $query : ''),
                'GET'
            ));
        } catch (\Throwable $e) {
            return null;
        }

        return $this->resolveTranslatedLabelFromRoute($route);
    }

    protected function resolveTranslatedLabelFromRoute(?Route $route): ?string
    {
        $routeName = (string) optional($route)->getName();
        if ($routeName === '') {
            return null;
        }

        foreach ($this->favoriteRouteLabelOverrides() as $prefix => $label) {
            if (Str::startsWith($routeName, $prefix)) {
                return $label;
            }
        }

        if ($routeName === 'admin.home') {
            return 'Dashboard';
        }

        $segments = explode('.', $routeName);
        $resource = $segments[1] ?? null;
        if (!$resource) {
            return null;
        }

        $crudKey = Str::camel(Str::singular(str_replace('-', '_', $resource)));
        $translation = trans('cruds.' . $crudKey . '.title');

        if (is_string($translation) && $translation !== 'cruds.' . $crudKey . '.title') {
            return $translation;
        }

        return null;
    }

    protected function favoriteRouteLabelOverrides(): array
    {
        return [
            'admin.combustion-transactions.' => 'Abastecimentos',
            'admin.company-reports.' => 'Relatorio semanal',
            'admin.vehicle-usage' => 'Utilização da viatura',
        ];
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
