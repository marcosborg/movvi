<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleItemRequest;
use App\Http\Requests\StoreVehicleItemRequest;
use App\Http\Requests\UpdateVehicleItemRequest;
use App\Models\Company;
use App\Models\VehicleBrand;
use App\Models\VehicleItem;
use App\Models\VehicleModel;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Driver;

class VehicleItemController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('vehicle_item_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleItems = VehicleItem::with(['company', 'vehicle_brand', 'vehicle_model', 'media', 'driver'])->get();

        return view('admin.vehicleItems.index', compact('vehicleItems'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_item_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_brands = VehicleBrand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_models = VehicleModel::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleItems.create', compact('companies', 'vehicle_brands', 'vehicle_models', 'drivers'));
    }

    public function store(StoreVehicleItemRequest $request)
    {
        $vehicleItem = VehicleItem::create($request->all());

        foreach ($request->input('documents', []) as $file) {
            $vehicleItem->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $vehicleItem->id]);
        }

        return redirect()->route('admin.vehicle-items.index');
    }

    public function edit(VehicleItem $vehicleItem)
    {
        abort_if(Gate::denies('vehicle_item_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $companies = Company::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_brands = VehicleBrand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_models = VehicleModel::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicleItem->load('company', 'vehicle_brand', 'vehicle_model', 'driver');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleItems.edit', compact('companies', 'vehicleItem', 'vehicle_brands', 'vehicle_models', 'drivers'));
    }

    public function update(UpdateVehicleItemRequest $request, VehicleItem $vehicleItem)
    {
        $vehicleItem->update($request->all());

        if (count($vehicleItem->documents) > 0) {
            foreach ($vehicleItem->documents as $media) {
                if (! in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicleItem->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicleItem->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        return redirect()->route('admin.vehicle-items.index');
    }

    public function show(VehicleItem $vehicleItem)
    {
        abort_if(Gate::denies('vehicle_item_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleItem->load('company', 'vehicle_brand', 'vehicle_model', 'vehicleItemVehicleEvents');

        return view('admin.vehicleItems.show', compact('vehicleItem'));
    }

    public function destroy(VehicleItem $vehicleItem)
    {
        abort_if(Gate::denies('vehicle_item_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleItem->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleItemRequest $request)
    {
        $vehicleItems = VehicleItem::find(request('ids'));

        foreach ($vehicleItems as $vehicleItem) {
            $vehicleItem->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('vehicle_item_create') && Gate::denies('vehicle_item_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new VehicleItem();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
