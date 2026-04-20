<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserFavorite;
use App\Services\SidebarFavoritesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserFavoriteController extends Controller
{
    public function __construct(protected SidebarFavoritesService $favoritesService)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(!$user, 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'route_params' => ['nullable', 'array'],
            'active_pattern' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$this->favoritesService->canAdd($user)) {
            return back()->with('error_message', 'Pode guardar no máximo 10 favoritos.');
        }

        $alreadyExists = $user->favorites()
            ->where('url', rtrim($data['url'], '/'))
            ->exists();

        if ($alreadyExists) {
            return back()->with('message', 'Este link já está nos favoritos.');
        }

        $data['url'] = rtrim($data['url'], '/');
        $data['sort_order'] = $this->favoritesService->nextSortOrder($user);

        $user->favorites()->create($data);

        return back()->with('message', 'Favorito adicionado com sucesso.');
    }

    public function destroy(UserFavorite $userFavorite): RedirectResponse
    {
        abort_if($userFavorite->user_id !== auth()->id(), 403);

        $userFavorite->delete();

        return back()->with('message', 'Favorito removido com sucesso.');
    }
}
