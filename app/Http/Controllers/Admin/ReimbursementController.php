<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyReimbursementRequest;
use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\Driver;
use App\Models\Reimbursement;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ReimbursementController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('reimbursement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reimbursements = Reimbursement::with(['driver', 'tvde_week', 'media'])->get();

        return view('admin.reimbursements.index', compact('reimbursements'));
    }

    public function create()
    {
        abort_if(Gate::denies('reimbursement_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.reimbursements.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreReimbursementRequest $request)
    {
        $reimbursement = Reimbursement::create($request->all());

        if ($request->input('file', false)) {
            $reimbursement->addMedia(storage_path('tmp/uploads/' . basename($request->input('file'))))->toMediaCollection('file');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $reimbursement->id]);
        }

        return redirect()->back()->with('message', 'Enviado com sucesso');
    }

    public function edit(Reimbursement $reimbursement)
    {
        abort_if(Gate::denies('reimbursement_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $reimbursement->load('driver', 'tvde_week');

        return view('admin.reimbursements.edit', compact('drivers', 'reimbursement', 'tvde_weeks'));
    }

    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        $reimbursement->update($request->all());

        if ($request->input('file', false)) {
            if (! $reimbursement->file || $request->input('file') !== $reimbursement->file->file_name) {
                if ($reimbursement->file) {
                    $reimbursement->file->delete();
                }
                $reimbursement->addMedia(storage_path('tmp/uploads/' . basename($request->input('file'))))->toMediaCollection('file');
            }
        } elseif ($reimbursement->file) {
            $reimbursement->file->delete();
        }

        return redirect()->route('admin.reimbursements.index');
    }

    public function show(Reimbursement $reimbursement)
    {
        abort_if(Gate::denies('reimbursement_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reimbursement->load('driver', 'tvde_week');

        return view('admin.reimbursements.show', compact('reimbursement'));
    }

    public function destroy(Reimbursement $reimbursement)
    {
        abort_if(Gate::denies('reimbursement_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reimbursement->delete();

        return back();
    }

    public function massDestroy(MassDestroyReimbursementRequest $request)
    {
        $reimbursements = Reimbursement::find(request('ids'));

        foreach ($reimbursements as $reimbursement) {
            $reimbursement->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('reimbursement_create') && Gate::denies('reimbursement_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Reimbursement();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
