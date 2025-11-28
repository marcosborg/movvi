<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyFormDataRequest;
use App\Http\Requests\StoreFormDataRequest;
use App\Http\Requests\UpdateFormDataRequest;
use App\Models\Driver;
use App\Models\FormData;
use App\Models\FormName;
use App\Models\User;
use App\Models\VehicleItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class FormDataController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('form_data_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!request()->query('status')) {
            return redirect('/admin/form-datas?status=unsolved');
        }

        if ($request->ajax()) {
            switch (request()->query('status')) {
                case 'unsolved':
                    $query = FormData::where('solved', false)->with(['form_name', 'driver', 'vehicle_item', 'user'])->select(sprintf('%s.*', (new FormData)->table));
                    break;
                case 'solved':
                    $query = FormData::where('solved', true)->with(['form_name', 'driver', 'vehicle_item', 'user'])->select(sprintf('%s.*', (new FormData)->table));
                    break;
                case 'all':
                    $query = FormData::with(['form_name', 'driver', 'vehicle_item', 'user'])->select(sprintf('%s.*', (new FormData)->table));
                    break;
                default:
                    return redirect('/admin/form-datas?status=unsolved');
            }

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'form_data_show';
                $editGate = 'form_data_edit';
                $deleteGate = 'form_data_delete';
                $crudRoutePart = 'form-datas';

                return view(
                    'partials.datatablesActions',
                    compact(
                        'viewGate',
                        'editGate',
                        'deleteGate',
                        'crudRoutePart',
                        'row'
                    )
                );
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('form_name_name', function ($row) {
                return $row->form_name ? $row->form_name->name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('vehicle_item_license_plate', function ($row) {
                return $row->vehicle_item ? $row->vehicle_item->license_plate : '';
            });

            $table->addColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : '';
            });

            $table->editColumn('data', function ($row) {
                $html = '';
                if (json_decode($row->data, true)) {
                    foreach (json_decode($row->data) as $key => $value) {
                        if (strpos($key, 'photos') !== false) {
                            $value = json_decode($value, true);
                            if (isset($value[1])) {
                                $value = '<a target="_new" href="' . url('/') . '/storage/' . $value[1] . '">Link</a>';
                            } else {
                                $value = '';
                            }
                            $html .= '<label>' . $key . '</label>: ' . $value . '<br>';
                        } else {
                            $html .= '<label>' . $key . '</label>: ' . $value . '<br>';
                        }
                    }
                } else {
                    $html = '';
                }
                return $row->data ? $html : '';
            });

            $table->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at : '';
            });
            $table->editColumn('solved', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->solved ? 'checked' : null) . '>';
            });

            $table->rawColumns(['actions', 'placeholder', 'form_name', 'driver', 'vehicle_item', 'user', 'solved', 'data']);

            return $table->make(true);
        }

        $form_names = FormName::get();
        $drivers = Driver::get();
        $vehicle_items = VehicleItem::get();
        $users = User::whereHas('roles', function ($role) {
            $role->where('title', 'Técnico');
        })->get();

        return view('admin.formDatas.index', compact('form_names', 'drivers', 'vehicle_items', 'users'));
    }

    public function create()
    {
        abort_if(Gate::denies('form_data_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $form_names = FormName::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::whereHas('roles', function ($role) {
            $role->where('title', 'Técnico');
        })->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.formDatas.create', compact('drivers', 'form_names', 'users', 'vehicle_items'));
    }

    public function store(StoreFormDataRequest $request)
    {
        $formData = FormData::create($request->all());

        return redirect()->route('admin.form-datas.index');
    }

    public function edit(FormData $formData)
    {
        abort_if(Gate::denies('form_data_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $form_names = FormName::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $users = User::whereHas('roles', function ($role) {
            $role->where('title', 'Técnico');
        })->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $formData->load('form_name', 'driver', 'vehicle_item', 'user');

        return view('admin.formDatas.edit', compact('drivers', 'formData', 'form_names', 'users', 'vehicle_items'));
    }

    public function update(UpdateFormDataRequest $request, FormData $formData)
    {
        $formData->update($request->all());

        return redirect()->route('admin.form-datas.index');
    }

    public function show(FormData $formData)
    {
        abort_if(Gate::denies('form_data_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formData->load('form_name', 'driver', 'vehicle_item', 'user');

        return view('admin.formDatas.show', compact('formData'));
    }

    public function destroy(FormData $formData)
    {
        abort_if(Gate::denies('form_data_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formData->delete();

        return back();
    }

    public function massDestroy(MassDestroyFormDataRequest $request)
    {
        $formDatas = FormData::find(request('ids'));

        foreach ($formDatas as $formData) {
            $formData->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}