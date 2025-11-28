@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.vehicleUsage.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-usages.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.driver') }}
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.vehicle_item') }}
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->vehicle_item->license_plate ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.start_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->start_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.end_date') }}
                                    </th>
                                    <td>
                                        {{ $vehicleUsage->end_date }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.vehicleUsage.fields.usage_exceptions') }}
                                    </th>
                                    <td>
                                        {{ App\Models\VehicleUsage::USAGE_EXCEPTIONS_RADIO[$vehicleUsage->usage_exceptions] ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.vehicle-usages.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection