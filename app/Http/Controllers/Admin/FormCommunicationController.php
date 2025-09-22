<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Illuminate\Http\Request;
use App\Models\FormName;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class FormCommunicationController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('form_communication_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $userRoles = auth()->user()->roles->pluck('id')->toArray();

        $form_names = FormName::whereHas('roles', function ($query) use ($userRoles) {
            $query->whereIn('id', $userRoles);
        })->get()->load('form_inputs')->chunk(3);

        return view('admin.formCommunications.index', compact('form_names'));
    }

    public function form($form_id)
    {

        $form_name = FormName::find($form_id)->load('form_inputs');
        $drivers = Driver::where('state_id', 1)->get();
        $vehicle_items = VehicleItem::all();
        $users = User::whereHas('roles', function ($role) {
            $role->where('title', 'Técnico');
        })->get();

        return view('admin.formCommunications.form', compact('form_name', 'drivers', 'vehicle_items', 'users'));
    }

}
