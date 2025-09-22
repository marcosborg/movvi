<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyFormInputRequest;
use App\Http\Requests\StoreFormInputRequest;
use App\Http\Requests\UpdateFormInputRequest;
use App\Models\FormInput;
use App\Models\FormName;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormInputController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('form_input_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formInputs = FormInput::with(['form_name'])->get();

        return view('admin.formInputs.index', compact('formInputs'));
    }

    public function create()
    {
        abort_if(Gate::denies('form_input_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $form_names = FormName::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.formInputs.create', compact('form_names'));
    }

    public function store(StoreFormInputRequest $request)
    {
        $formInput = FormInput::create($request->all());

        return redirect()->route('admin.form-inputs.index');
    }

    public function edit(FormInput $formInput)
    {
        abort_if(Gate::denies('form_input_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $form_names = FormName::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $formInput->load('form_name');

        return view('admin.formInputs.edit', compact('formInput', 'form_names'));
    }

    public function update(UpdateFormInputRequest $request, FormInput $formInput)
    {
        $formInput->update($request->all());

        return redirect()->route('admin.form-inputs.index');
    }

    public function show(FormInput $formInput)
    {
        abort_if(Gate::denies('form_input_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formInput->load('form_name');

        return view('admin.formInputs.show', compact('formInput'));
    }

    public function destroy(FormInput $formInput)
    {
        abort_if(Gate::denies('form_input_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $formInput->delete();

        return back();
    }

    public function massDestroy(MassDestroyFormInputRequest $request)
    {
        $formInputs = FormInput::find(request('ids'));

        foreach ($formInputs as $formInput) {
            $formInput->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
