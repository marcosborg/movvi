<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(!$user, 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->favorites()->count() >= 10) {
            return back()->with('error_message', 'Pode guardar no máximo 10 favoritos.');
        }

        $normalizedUrl = rtrim($data['url'], '/');

        if ($user->favorites()->where('url', $normalizedUrl)->exists()) {
            return back()->with('message', 'Este link já está nos favoritos.');
        }

        $user->favorites()->create([
            'label' => trim($data['label']),
            'url' => $normalizedUrl,
            'icon' => $data['icon'] ?: null,
            'order' => ((int) $user->favorites()->max('order')) + 1,
        ]);

        return back()->with('message', 'Favorito adicionado com sucesso.');
    }

    public function destroy(UserFavorite $favorite): RedirectResponse
    {
        abort_if($favorite->user_id !== auth()->id(), 403);

        $favorite->delete();

        return back()->with('message', 'Favorito removido com sucesso.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(!$user, 403);

        $ids = $request->validate([
            'favorites' => ['required', 'array'],
            'favorites.*' => ['integer', 'exists:user_favorites,id'],
        ])['favorites'];

        $allowedIds = $user->favorites()->pluck('id')->all();

        foreach ($ids as $index => $id) {
            if (!in_array($id, $allowedIds, true)) {
                continue;
            }

            UserFavorite::where('id', $id)->update(['order' => $index + 1]);
        }

        return back()->with('message', 'Favoritos reordenados com sucesso.');
    }
}
