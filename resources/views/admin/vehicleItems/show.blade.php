@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.vehicleItem.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-items.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.company') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->company->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.driver') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.vehicle_brand') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->vehicle_brand->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.vehicle_model') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->vehicle_model->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.year') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->year }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.license_plate') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->license_plate }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.fuel_card') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->fuel_card->code ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.acquisition_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->acquisition_date ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.acquisition_value') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->acquisition_value !== null ? number_format($vehicleItem->acquisition_value, 2, ',', '.') . ' €' : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.sale_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->sale_date ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.sale_value') }}
                                    </th>
                                    <td>
                                        {{ $vehicleItem->sale_value !== null ? number_format($vehicleItem->sale_value, 2, ',', '.') . ' €' : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleItem.fields.documents') }}
                                    </th>
                                    <td>
                                        @foreach($vehicleItem->documents as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-items.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.relatedData') }}
                </div>
                <ul class="nav nav-tabs" role="tablist" id="relationship-tabs">
                    <li role="presentation">
                        <a href="#vehicle_item_vehicle_events" aria-controls="vehicle_item_vehicle_events" role="tab" data-toggle="tab">
                            {{ trans('cruds.vehicleEvent.title') }}
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane" role="tabpanel" id="vehicle_item_vehicle_events">
                        @includeIf('admin.vehicleItems.relationships.vehicleItemVehicleEvents', ['vehicleEvents' => $vehicleItem->vehicleItemVehicleEvents])
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
