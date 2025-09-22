<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyConsultancyRequest;
use App\Http\Requests\StoreConsultancyRequest;
use App\Http\Requests\UpdateConsultancyRequest;
use App\Models\Company;
use App\Models\Consultancy;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultancyController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('consultancy_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $consultancies = Consultancy::with(['company'])->get();

        return view('admin.consultancies.index', compact('consultancies'));
    }

    public function create()
    {
        abort_if(Gate::denies('consultancy_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.consultancies.create', compact('companies'));
    }

    public function store(StoreConsultancyRequest $request)
    {
        $consultancy = Consultancy::create($request->all());

        return redirect()->route('admin.consultancies.index');
    }

    public function edit(Consultancy $consultancy)
    {
        abort_if(Gate::denies('consultancy_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $consultancy->load('company');

        return view('admin.consultancies.edit', compact('companies', 'consultancy'));
    }

    public function update(UpdateConsultancyRequest $request, Consultancy $consultancy)
    {
        $consultancy->update($request->all());

        return redirect()->route('admin.consultancies.index');
    }

    public function show(Consultancy $consultancy)
    {
        abort_if(Gate::denies('consultancy_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $consultancy->load('company');

        return view('admin.consultancies.show', compact('consultancy'));
    }

    public function destroy(Consultancy $consultancy)
    {
        abort_if(Gate::denies('consultancy_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $consultancy->delete();

        return back();
    }

    public function massDestroy(MassDestroyConsultancyRequest $request)
    {
        $consultancies = Consultancy::find(request('ids'));

        foreach ($consultancies as $consultancy) {
            $consultancy->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}