<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCompanyParkRequest;
use App\Http\Requests\StoreCompanyParkRequest;
use App\Http\Requests\UpdateCompanyParkRequest;
use App\Models\Company;
use App\Models\CompanyPark;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyParkController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('company_park_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (session()->get('company_id') != 0) {
            $companyParks = CompanyPark::where('company_id', session()->get('company_id'))->with(['tvde_week', 'company'])->get();
        } else {
            $companyParks = CompanyPark::with(['tvde_week', 'company'])->get();
        }

        return view('admin.companyParks.index', compact('companyParks'));
    }

    public function create()
    {
        abort_if(Gate::denies('company_park_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.companyParks.create', compact('companies', 'tvde_weeks'));
    }

    public function store(StoreCompanyParkRequest $request)
    {
        $companyPark = CompanyPark::create($request->all());

        return redirect()->route('admin.company-parks.index');
    }

    public function edit(CompanyPark $companyPark)
    {
        abort_if(Gate::denies('company_park_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companyPark->load('tvde_week', 'company');

        return view('admin.companyParks.edit', compact('companies', 'companyPark', 'tvde_weeks'));
    }

    public function update(UpdateCompanyParkRequest $request, CompanyPark $companyPark)
    {
        $companyPark->update($request->all());

        return redirect()->route('admin.company-parks.index');
    }

    public function show(CompanyPark $companyPark)
    {
        abort_if(Gate::denies('company_park_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyPark->load('tvde_week', 'company');

        return view('admin.companyParks.show', compact('companyPark'));
    }

    public function destroy(CompanyPark $companyPark)
    {
        abort_if(Gate::denies('company_park_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyPark->delete();

        return back();
    }

    public function massDestroy(MassDestroyCompanyParkRequest $request)
    {
        $companyParks = CompanyPark::find(request('ids'));

        foreach ($companyParks as $companyPark) {
            $companyPark->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
