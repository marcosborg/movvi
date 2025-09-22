<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyExpenseReceiptRequest;
use App\Http\Requests\StoreExpenseReceiptRequest;
use App\Http\Requests\UpdateExpenseReceiptRequest;
use App\Models\Driver;
use App\Models\ExpenseReceipt;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ExpenseReceiptsController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('expense_receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expenseReceipts = ExpenseReceipt::with(['driver', 'tvde_week', 'media'])->get();

        return view('admin.expenseReceipts.index', compact('expenseReceipts'));
    }

    public function create()
    {
        abort_if(Gate::denies('expense_receipt_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.expenseReceipts.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreExpenseReceiptRequest $request)
    {
        $expenseReceipt = ExpenseReceipt::create($request->all());

        foreach ($request->input('receipts', []) as $file) {
            $expenseReceipt->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('receipts');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $expenseReceipt->id]);
        }

        return redirect()->back();
    }

    public function edit(ExpenseReceipt $expenseReceipt)
    {
        abort_if(Gate::denies('expense_receipt_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $expenseReceipt->load('driver', 'tvde_week');

        return view('admin.expenseReceipts.edit', compact('drivers', 'expenseReceipt', 'tvde_weeks'));
    }

    public function update(UpdateExpenseReceiptRequest $request, ExpenseReceipt $expenseReceipt)
    {
        $expenseReceipt->update($request->all());

        if (count($expenseReceipt->receipts) > 0) {
            foreach ($expenseReceipt->receipts as $media) {
                if (! in_array($media->file_name, $request->input('receipts', []))) {
                    $media->delete();
                }
            }
        }
        $media = $expenseReceipt->receipts->pluck('file_name')->toArray();
        foreach ($request->input('receipts', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $expenseReceipt->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('receipts');
            }
        }

        return redirect()->back();
    }

    public function show(ExpenseReceipt $expenseReceipt)
    {
        abort_if(Gate::denies('expense_receipt_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expenseReceipt->load('driver', 'tvde_week');

        return view('admin.expenseReceipts.show', compact('expenseReceipt'));
    }

    public function destroy(ExpenseReceipt $expenseReceipt)
    {
        abort_if(Gate::denies('expense_receipt_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expenseReceipt->delete();

        return back();
    }

    public function massDestroy(MassDestroyExpenseReceiptRequest $request)
    {
        $expenseReceipts = ExpenseReceipt::find(request('ids'));

        foreach ($expenseReceipts as $expenseReceipt) {
            $expenseReceipt->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('expense_receipt_create') && Gate::denies('expense_receipt_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new ExpenseReceipt();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
