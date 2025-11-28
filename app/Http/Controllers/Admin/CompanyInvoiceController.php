<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyCompanyInvoiceRequest;
use App\Http\Requests\StoreCompanyInvoiceRequest;
use App\Http\Requests\UpdateCompanyInvoiceRequest;
use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CompanyInvoiceController extends Controller
{
    use MediaUploadingTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('company_invoice_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = CompanyInvoice::with(['company', 'tvde_week'])->select(sprintf('%s.*', (new CompanyInvoice)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'company_invoice_show';
                $editGate      = 'company_invoice_edit';
                $deleteGate    = 'company_invoice_delete';
                $crudRoutePart = 'company-invoices';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('company_name', function ($row) {
                return $row->company ? $row->company->name : '';
            });

            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->editColumn('invoice', function ($row) {
                if (! $row->invoice) {
                    return '';
                }
                $links = [];
                foreach ($row->invoice as $media) {
                    $links[] = '<a href="' . $media->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>';
                }

                return implode(', ', $links);
            });
            $table->editColumn('payment_receipt', function ($row) {
                return $row->payment_receipt ? '<a href="' . $row->payment_receipt->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>' : '';
            });
            $table->editColumn('info', function ($row) {
                return $row->info ? $row->info : '';
            });
            $table->editColumn('payed', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->payed ? 'checked' : null) . '>';
            });

            $table->rawColumns(['actions', 'placeholder', 'company', 'tvde_week', 'invoice', 'payment_receipt', 'payed']);

            return $table->make(true);
        }

        return view('admin.companyInvoices.index');
    }

    public function create()
    {
        abort_if(Gate::denies('company_invoice_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.companyInvoices.create', compact('companies', 'tvde_weeks'));
    }

    public function store(StoreCompanyInvoiceRequest $request)
    {
        $companyInvoice = CompanyInvoice::create($request->all());

        foreach ($request->input('invoice', []) as $file) {
            $companyInvoice->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('invoice');
        }

        if ($request->input('payment_receipt', false)) {
            $companyInvoice->addMedia(storage_path('tmp/uploads/' . basename($request->input('payment_receipt'))))->toMediaCollection('payment_receipt');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $companyInvoice->id]);
        }

        return redirect()->route('admin.company-invoices.index');
    }

    public function edit(CompanyInvoice $companyInvoice)
    {
        abort_if(Gate::denies('company_invoice_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::orderBy('start_date', 'desc')->get()->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $companyInvoice->load('company', 'tvde_week');

        return view('admin.companyInvoices.edit', compact('companies', 'companyInvoice', 'tvde_weeks'));
    }

    public function update(UpdateCompanyInvoiceRequest $request, CompanyInvoice $companyInvoice)
    {
        $companyInvoice->update($request->all());

        if (count($companyInvoice->invoice) > 0) {
            foreach ($companyInvoice->invoice as $media) {
                if (! in_array($media->file_name, $request->input('invoice', []))) {
                    $media->delete();
                }
            }
        }
        $media = $companyInvoice->invoice->pluck('file_name')->toArray();
        foreach ($request->input('invoice', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $companyInvoice->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('invoice');
            }
        }

        if ($request->input('payment_receipt', false)) {
            if (! $companyInvoice->payment_receipt || $request->input('payment_receipt') !== $companyInvoice->payment_receipt->file_name) {
                if ($companyInvoice->payment_receipt) {
                    $companyInvoice->payment_receipt->delete();
                }
                $companyInvoice->addMedia(storage_path('tmp/uploads/' . basename($request->input('payment_receipt'))))->toMediaCollection('payment_receipt');
            }
        } elseif ($companyInvoice->payment_receipt) {
            $companyInvoice->payment_receipt->delete();
        }

        return redirect()->route('admin.company-invoices.index');
    }

    public function show(CompanyInvoice $companyInvoice)
    {
        abort_if(Gate::denies('company_invoice_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyInvoice->load('company', 'tvde_week');

        return view('admin.companyInvoices.show', compact('companyInvoice'));
    }

    public function destroy(CompanyInvoice $companyInvoice)
    {
        abort_if(Gate::denies('company_invoice_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companyInvoice->delete();

        return back();
    }

    public function massDestroy(MassDestroyCompanyInvoiceRequest $request)
    {
        $companyInvoices = CompanyInvoice::find(request('ids'));

        foreach ($companyInvoices as $companyInvoice) {
            $companyInvoice->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('company_invoice_create') && Gate::denies('company_invoice_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new CompanyInvoice();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
