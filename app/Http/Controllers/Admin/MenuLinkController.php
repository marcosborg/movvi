<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuLinkRequest;
use App\Http\Requests\UpdateMenuLinkRequest;
use App\Models\MenuLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class MenuLinkController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('website_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $links = MenuLink::orderBy('position')->get();

        return view('admin.menuLinks.index', compact('links'));
    }

    public function store(StoreMenuLinkRequest $request)
    {
        MenuLink::create($request->validated() + ['position' => MenuLink::max('position') + 1]);

        return redirect()->route('admin.menu-links.index')->with('status', 'Link criado.');
    }

    public function update(UpdateMenuLinkRequest $request, MenuLink $menu_link)
    {
        $menu_link->update($request->validated());

        return redirect()->route('admin.menu-links.index')->with('status', 'Link atualizado.');
    }

    public function destroy(MenuLink $menu_link)
    {
        abort_if(Gate::denies('website_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $menu_link->delete();

        return back()->with('status', 'Link removido.');
    }

    public function order(Request $request)
    {
        abort_if(Gate::denies('website_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $ids = $request->input('order', []);
        foreach ($ids as $index => $id) {
            MenuLink::where('id', $id)->update(['position' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
