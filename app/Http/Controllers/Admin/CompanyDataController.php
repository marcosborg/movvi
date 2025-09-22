<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCompanyDataRequest;
use App\Http\Requests\StoreCompanyDataRequest;
use App\Http\Requests\UpdateCompanyDataRequest;
use App\Models\Company;
use App\Models\CompanyData;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyDataController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('company_data_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyDatas = CompanyData::with(['company', 'tvde_week'])->get();

        return view('admin.companyDatas.index', compact('companyDatas'));
    }

    public function create()
    {
        abort_if(Gate::denies('company_data_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.companyDatas.create', compact('companies', 'tvde_weeks'));
    }

    public function store(StoreCompanyDataRequest $request)
    {
        $companyData = CompanyData::create($request->all());

        return redirect()->route('admin.company-datas.index');
    }

    public function edit(CompanyData $companyData)
    {
        abort_if(Gate::denies('company_data_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companyData->load('company', 'tvde_week');

        return view('admin.companyDatas.edit', compact('companies', 'companyData', 'tvde_weeks'));
    }

    public function update(UpdateCompanyDataRequest $request, CompanyData $companyData)
    {
        $companyData->update($request->all());

        return redirect()->route('admin.company-datas.index');
    }

    public function show(CompanyData $companyData)
    {
        abort_if(Gate::denies('company_data_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyData->load('company', 'tvde_week');

        return view('admin.companyDatas.show', compact('companyData'));
    }

    public function destroy(CompanyData $companyData)
    {
        abort_if(Gate::denies('company_data_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyData->delete();

        return back();
    }

    public function massDestroy(MassDestroyCompanyDataRequest $request)
    {
        $companyDatas = CompanyData::find(request('ids'));

        foreach ($companyDatas as $companyData) {
            $companyData->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
