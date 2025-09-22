<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyReceiptRequest;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use App\Models\Driver;
use App\Models\Receipt;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DriversBalance;
use App\Models\Company;
use App\Models\TvdeWeek;

class ReceiptController extends Controller
{
    use MediaUploadingTrait;

    public function index(Request $request)
    {

        abort_if(Gate::denies('receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {

            $query = Receipt::where('paid', url()->current() == url('/admin/receipts/paid') ? 1 : 0)
                ->with(['driver.company', 'tvde_week'])
                ->leftJoin('tvde_weeks', 'receipts.tvde_week_id', '=', 'tvde_weeks.id')
                ->select([
                    'receipts.*',
                    'tvde_weeks.start_date as tvde_week_start_date',
                ]);

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'receipt_show';
                $editGate = 'receipt_edit';
                $deleteGate = 'receipt_delete';
                $crudRoutePart = 'receipts';

                return view(
                    'partials.datatablesActions',
                    compact(
                        'viewGate',
                        'editGate',
                        'deleteGate',
                        'crudRoutePart',
                        'row'
                    )
                );
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });
            $table->editColumn('balance', function ($row) {
                return $row->balance ? $row->balance : '';
            });

            $table->editColumn('iva', function ($row) {
                $driver = Driver::find($row->driver->id)->load('contract_vat');
                $factor = $driver->contract_vat->iva / 100;
                $value = number_format(($row->balance * $factor), 2, '.');
                return $driver ? $value : '';
            });

            $table->editColumn('rf', function ($row) {
                $driver = Driver::find($row->driver->id)->load('contract_vat');
                $factor = $driver->contract_vat->rf / 100;
                $value = number_format(- ($row->balance * $factor), 2, '.');
                return $driver ? $value : '';
            });

            $table->editColumn('final', function ($row) {
                $driver = Driver::find($row->driver->id)->load('contract_vat');
                $final = number_format($row->balance, 2, '.', '');
                return $driver ? $final : '';
            });

            $table->editColumn('file', function ($row) {
                return $row->file ? '<a href="' . $row->file->getUrl() . '" target="_blank">' . trans('global.downloadFile') . '</a>' : '';
            });
            $table->editColumn('receipt_value', function ($row) {
                return '<input id="receipt_value-' . $row->id . '" type="number" value="' . $row->verified_value . '" ' . ($row->verified ? 'disabled' : '') . '>';
            });
            $table->editColumn('verified', function ($row) {
                return '<input id="verified-' . $row->id . '" onclick="checkVerified(' . $row->id . ')" type="checkbox" ' . ($row->verified ? 'disabled' : '') . ' ' . ($row->verified ? 'checked' : null) . '>';
            });
            $table->editColumn('paid', function ($row) {
                return '<input id="check-' . $row->id . '" onclick="checkPay(' . $row->id . ')" type="checkbox" ' . ($row->paid ? 'disabled' : '') . ' ' . ($row->paid ? 'checked' : null) . '>';
            });

            $table->editColumn('amount_transferred', function ($row) {
                return '<input id="amount_transferred-' . $row->id . '" type="number" value="' . $row->amount_transferred . '" ' . ($row->verified ? 'disabled' : '') . '>';
            });

            $table->addColumn('tvde_week_start_date', function ($row) {
                return $row->tvde_week ? $row->tvde_week->start_date : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'driver', 'file', 'receipt_value', 'amount_transferred', 'paid', 'verified', 'tvde_week']);

            return $table->make(true);
        }

        $drivers = Driver::get();
        $companies = Company::all();
        $tvde_weeks = TvdeWeek::get();

        return view('admin.receipts.index', compact('drivers', 'companies', 'tvde_weeks'));
    }

    public function create()
    {
        abort_if(Gate::denies('receipt_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.receipts.create', compact('drivers', 'tvde_weeks'));
    }

    public function store(StoreReceiptRequest $request)
    {

        $tvde_week_id = session()->get('tvde_week_id');

        $receipt = Receipt::create($request->all());

        if ($request->input('file', false)) {
            $receipt->addMedia(storage_path('tmp/uploads/' . basename($request->input('file'))))->toMediaCollection('file');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $receipt->id]);
        }

        //AtualDriversBalance
        $driver_id = $request->driver_id;
        $value = $request->value;
        $drivers_balance = DriversBalance::where([
            'driver_id' => $driver_id,
            'tvde_week_id' => $tvde_week_id
        ])->first();
        $drivers_balance->drivers_balance = $value;
        $drivers_balance->save();

        return redirect()->back()->with('message', 'Recibo enviado com sucesso. Obrigado.');
    }

    public function edit(Receipt $receipt)
    {
        abort_if(Gate::denies('receipt_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $receipt->load('driver', 'tvde_week');
        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.receipts.edit', compact('drivers', 'receipt', 'tvde_weeks'));
    }

    public function update(UpdateReceiptRequest $request, Receipt $receipt)
    {
        $receipt->update($request->all());

        if ($request->input('file', false)) {
            if (!$receipt->file || $request->input('file') !== $receipt->file->file_name) {
                if ($receipt->file) {
                    $receipt->file->delete();
                }
                $receipt->addMedia(storage_path('tmp/uploads/' . basename($request->input('file'))))->toMediaCollection('file');
            }
        } elseif ($receipt->file) {
            $receipt->file->delete();
        }

        return redirect()->route('admin.receipts.index');
    }

    public function show(Receipt $receipt)
    {
        abort_if(Gate::denies('receipt_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $receipt->load('driver');

        return view('admin.receipts.show', compact('receipt'));
    }

    public function destroy(Receipt $receipt)
    {
        abort_if(Gate::denies('receipt_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $receipt->delete();

        return back();
    }

    public function massDestroy(MassDestroyReceiptRequest $request)
    {
        $receipts = Receipt::find(request('ids'));

        foreach ($receipts as $receipt) {
            $receipt->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('receipt_create') && Gate::denies('receipt_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new Receipt();
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }

    public function checkPay($receipt_id)
    {
        $receipt = Receipt::find($receipt_id);
        $receipt->paid = true;
        $receipt->save();
    }

    public function checkVerified($receipt_id, $receipt_value, $amount_transferred)
    {
        $receipt = Receipt::find($receipt_id);
        $receipt->verified = true;
        $receipt->verified_value = $receipt_value;
        $receipt->amount_transferred = $amount_transferred;
        $receipt->save();

        //AtualDriversBalance
        $driver_id = $receipt->driver_id;
        $drivers_balance = DriversBalance::where([
            'driver_id' => $driver_id
        ])->orderBy('id', 'desc')->first();
        $balance = $drivers_balance->balance - $receipt_value;
        $drivers_balance->balance = $balance;
        $drivers_balance->drivers_balance = $balance;
        $drivers_balance->save();
    }
}
