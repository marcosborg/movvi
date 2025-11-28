<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPeriodsOfTheYearRequest;
use App\Http\Requests\StorePeriodsOfTheYearRequest;
use App\Http\Requests\UpdatePeriodsOfTheYearRequest;
use App\Models\PeriodsOfTheYear;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriodsOfTheYearController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('periods_of_the_year_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $periodsOfTheYears = PeriodsOfTheYear::all();

        return view('admin.periodsOfTheYears.index', compact('periodsOfTheYears'));
    }

    public function create()
    {
        abort_if(Gate::denies('periods_of_the_year_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.periodsOfTheYears.create');
    }

    public function store(StorePeriodsOfTheYearRequest $request)
    {
        $periodsOfTheYear = PeriodsOfTheYear::create($request->all());

        return redirect()->route('admin.periods-of-the-years.index');
    }

    public function edit(PeriodsOfTheYear $periodsOfTheYear)
    {
        abort_if(Gate::denies('periods_of_the_year_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.periodsOfTheYears.edit', compact('periodsOfTheYear'));
    }

    public function update(UpdatePeriodsOfTheYearRequest $request, PeriodsOfTheYear $periodsOfTheYear)
    {
        $periodsOfTheYear->update($request->all());

        return redirect()->route('admin.periods-of-the-years.index');
    }

    public function show(PeriodsOfTheYear $periodsOfTheYear)
    {
        abort_if(Gate::denies('periods_of_the_year_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.periodsOfTheYears.show', compact('periodsOfTheYear'));
    }

    public function destroy(PeriodsOfTheYear $periodsOfTheYear)
    {
        abort_if(Gate::denies('periods_of_the_year_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $periodsOfTheYear->delete();

        return back();
    }

    public function massDestroy(MassDestroyPeriodsOfTheYearRequest $request)
    {
        $periodsOfTheYears = PeriodsOfTheYear::find(request('ids'));

        foreach ($periodsOfTheYears as $periodsOfTheYear) {
            $periodsOfTheYear->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
