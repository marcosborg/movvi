<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyFormNameRequest;
use App\Http\Requests\StoreFormNameRequest;
use App\Http\Requests\UpdateFormNameRequest;
use App\Models\FormName;
use App\Models\Role;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class FormNameController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('form_name_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formNames = FormName::with(['roles'])->get();

        return view('admin.formNames.index', compact('formNames'));
    }

    public function create()
    {
        abort_if(Gate::denies('form_name_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        return view('admin.formNames.create', compact('roles'));
    }

    public function store(StoreFormNameRequest $request)
    {
        $formName = FormName::create($request->all());
        $formName->roles()->sync($request->input('roles', []));
        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $formName->id]);
        }

        return redirect()->route('admin.form-names.index');
    }

    public function edit(FormName $formName)
    {
        abort_if(Gate::denies('form_name_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        $formName->load('roles');

        return view('admin.formNames.edit', compact('formName', 'roles'));
    }

    public function update(UpdateFormNameRequest $request, FormName $formName)
    {
        $formName->update($request->all());
        $formName->roles()->sync($request->input('roles', []));

        return redirect()->route('admin.form-names.index');
    }

    public function show(FormName $formName)
    {
        abort_if(Gate::denies('form_name_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formName->load('roles');

        return view('admin.formNames.show', compact('formName'));
    }

    public function destroy(FormName $formName)
    {
        abort_if(Gate::denies('form_name_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formName->delete();

        return back();
    }

    public function massDestroy(MassDestroyFormNameRequest $request)
    {
        $formNames = FormName::find(request('ids'));

        foreach ($formNames as $formName) {
            $formName->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('form_name_create') && Gate::denies('form_name_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new FormName();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}