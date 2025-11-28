<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Notifications\NewReceipt;
use Gate;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DriversBalance;

class MyReceiptsController extends Controller
{
    public function index(Request $request)
    {

        abort_if(Gate::denies('my_receipt_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!session()->has('company_id')) {
            $company_id = auth()->user()->company->id;
            session()->put('company_id', $company_id);
        }

        if ($request->ajax()) {

            if (auth()->user()->hasRole('Empresas Associadas')) {
                $query = Receipt::where('paid', url()->current() == url('/admin/my-receipts/paid') ? 1 : 0)
                    ->with(['driver.company'])
                    ->whereHas('driver', function ($driver) {
                        $driver->whereHas('company', function ($company) {
                            $company->where('id', session()->get('company_id'));
                        });
                    })
                    ->select(sprintf('%s.*', (new Receipt)->table));
            } elseif (auth()->user()->hasRole('Admin')) {
                $query = Receipt::where('paid', url()->current() == url('/admin/my-receipts/paid') ? 1 : 0)
                    ->with(['driver.company'])
                    ->select(sprintf('%s.*', (new Receipt)->table));
            } else {
                $query = Receipt::where('paid', url()->current() == url('/admin/my-receipts/paid') ? 1 : 0)
                    ->with(['driver.company'])
                    ->whereHas('driver', function ($driver) {
                        $driver->where('id', session()->get('driver_id'));
                    })
                    ->select(sprintf('%s.*', (new Receipt)->table));
            }

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

            $table->editColumn('driver.code', function ($row) {
                return $row->driver ? (is_string($row->driver) ? $row->driver : $row->driver->code) : '';
            });
            $table->editColumn('value', function ($row) {
                return $row->value ? $row->value : '';
            });
            $table->editColumn('balance', function ($row) {
                return $row->balance ? $row->balance : '';
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

            $table->rawColumns(['actions', 'placeholder', 'driver', 'file', 'receipt_value', 'amount_transferred', 'paid', 'verified']);

            return $table->make(true);
        }

        if (auth()->user()->hasRole('Empresas Associadas')) {
            $drivers = Driver::where('company_id', session()->get('company_id'))->get();
        } elseif (auth()->user()->hasRole('Admin')) {
            $drivers = Driver::get();
        } else {
            $drivers = Driver::where('id', session()->get('driver_id'))->get();
        }

        return view('admin.myReceipts.index', compact('drivers'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'value' => 'required',
            'file' => 'required',
        ]);

        $driver = Driver::where('user_id', auth()->user()->id)->first();

        $receipt = new Receipt;
        $receipt->driver_id = $driver->id;
        $receipt->value = $request->value;
        $receipt->save();

        if ($request->input('file', false)) {
            $receipt->addMedia(storage_path('tmp/uploads/' . basename($request->input('file'))))->toMediaCollection('file');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $receipt->id]);
        }

        //SEND EMAIL TO ADMIN
        User::find(2)->notify(new NewReceipt($driver));

        return redirect()->back()->with('message', 'Enviado com sucesso');
    }

    public function payReceipt($receipt_id, $paid)
    {
        $receipt = Receipt::find($receipt_id);
        $receipt->paid = $paid;
        $receipt->save();
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