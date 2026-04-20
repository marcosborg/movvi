<?php

namespace App\Providers;

use App\Services\SidebarFavoritesService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SidebarFavoritesService::class, function ($app) {
            return new SidebarFavoritesService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer(['partials.menu', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $favoritesService = app(SidebarFavoritesService::class);

            $view->with('sidebarFavorites', $favoritesService->forUser($user));
            $view->with('currentFavorite', $favoritesService->currentFavorite($user, request()));
            $view->with('favoriteCandidate', $favoritesService->buildCandidate(request()));
            $view->with('favoritesMaxReached', !$favoritesService->canAdd($user));
        });
    }
}
