@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.weeklyVehicleExpense.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.weekly-vehicle-expenses.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.vehicle_item') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->vehicle_item->license_plate ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.driver') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.tvde_week') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->tvde_week->start_date ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.total_km') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->total_km }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.weekly_km') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->weekly_km }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.extra_km') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->extra_km }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.transfers') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->transfers }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.weeklyVehicleExpense.fields.deposit') }}
                                    </th>
                                    <td>
                                        {{ $weeklyVehicleExpense->deposit }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.weekly-vehicle-expenses.index') }}">
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