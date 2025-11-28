<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyRecruitmentFormRequest;
use App\Http\Requests\StoreRecruitmentFormRequest;
use App\Http\Requests\UpdateRecruitmentFormRequest;
use App\Models\Company;
use App\Models\RecruitmentForm;
use App\Notifications\RecruitmentFormNotification;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Notification;

class RecruitmentFormController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('recruitment_form_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $company_id = session()->get('company_id');

        if ($company_id > 0) {
            $recruitmentForms = RecruitmentForm::where('company_id', $company_id)->with(['company', 'media'])->get();
        } else {
            $recruitmentForms = RecruitmentForm::with(['company', 'media'])->get();
        }


        return view('admin.recruitmentForms.index', compact('recruitmentForms'));
    }

    public function create()
    {
        abort_if(Gate::denies('recruitment_form_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.recruitmentForms.create', compact('companies'));
    }

    public function store(StoreRecruitmentFormRequest $request)
    {
        $recruitmentForm = RecruitmentForm::create($request->all())->load('company');

        if ($request->input('cv', false)) {
            $recruitmentForm->addMedia(storage_path('tmp/uploads/' . basename($request->input('cv'))))->toMediaCollection('cv');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $recruitmentForm->id]);
        }

        Notification::route('mail', $recruitmentForm->company->email)
            ->notify(new RecruitmentFormNotification($recruitmentForm));

        return redirect()->route('admin.recruitment-forms.index');
    }

    public function edit(RecruitmentForm $recruitmentForm)
    {
        abort_if(Gate::denies('recruitment_form_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $recruitmentForm->load('company');

        return view('admin.recruitmentForms.edit', compact('companies', 'recruitmentForm'));
    }

    public function update(UpdateRecruitmentFormRequest $request, RecruitmentForm $recruitmentForm)
    {
        $recruitmentForm->update($request->all());

        if ($request->input('cv', false)) {
            if (!$recruitmentForm->cv || $request->input('cv') !== $recruitmentForm->cv->file_name) {
                if ($recruitmentForm->cv) {
                    $recruitmentForm->cv->delete();
                }
                $recruitmentForm->addMedia(storage_path('tmp/uploads/' . basename($request->input('cv'))))->toMediaCollection('cv');
            }
        } elseif ($recruitmentForm->cv) {
            $recruitmentForm->cv->delete();
        }

        return redirect()->route('admin.recruitment-forms.index');
    }

    public function show(RecruitmentForm $recruitmentForm)
    {
        abort_if(Gate::denies('recruitment_form_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $recruitmentForm->load('company');

        return view('admin.recruitmentForms.show', compact('recruitmentForm'));
    }

    public function destroy(RecruitmentForm $recruitmentForm)
    {
        abort_if(Gate::denies('recruitment_form_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $recruitmentForm->delete();

        return back();
    }

    public function massDestroy(MassDestroyRecruitmentFormRequest $request)
    {
        $recruitmentForms = RecruitmentForm::find(request('ids'));

        foreach ($recruitmentForms as $recruitmentForm) {
            $recruitmentForm->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('recruitment_form_create') && Gate::denies('recruitment_form_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new RecruitmentForm();
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}