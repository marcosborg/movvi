@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.vehicleUsage.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.vehicle-usages.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label for="driver_id">{{ trans('cruds.vehicleUsage.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id">
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleUsage.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vehicle_item') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_item_id">{{ trans('cruds.vehicleUsage.fields.vehicle_item') }}</label>
                            <select class="form-control select2" name="vehicle_item_id" id="vehicle_item_id" required>
                                @foreach($vehicle_items as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_item_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_item'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleUsage.fields.vehicle_item_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('start_date') ? 'has-error' : '' }}">
                            <label class="required" for="start_date">{{ trans('cruds.vehicleUsage.fields.start_date') }}</label>
                            <input class="form-control datetime" type="text" name="start_date" id="start_date" value="{{ old('start_date') }}" required>
                            @if($errors->has('start_date'))
                                <span class="help-block" role="alert">{{ $errors->first('start_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleUsage.fields.start_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('end_date') ? 'has-error' : '' }}">
                            <label for="end_date">{{ trans('cruds.vehicleUsage.fields.end_date') }}</label>
                            <input class="form-control datetime" type="text" name="end_date" id="end_date" value="{{ old('end_date') }}">
                            @if($errors->has('end_date'))
                                <span class="help-block" role="alert">{{ $errors->first('end_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleUsage.fields.end_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('usage_exceptions') ? 'has-error' : '' }}">
                            <label>{{ trans('cruds.vehicleUsage.fields.usage_exceptions') }}</label>
                            @foreach(App\Models\VehicleUsage::USAGE_EXCEPTIONS_RADIO as $key => $label)
                                <div>
                                    <input type="radio" id="usage_exceptions_{{ $key }}" name="usage_exceptions" value="{{ $key }}" {{ old('usage_exceptions', '') === (string) $key ? 'checked' : '' }}>
                                    <label for="usage_exceptions_{{ $key }}" style="font-weight: 400">{{ $label }}</label>
                                </div>
                            @endforeach
                            @if($errors->has('usage_exceptions'))
                                <span class="help-block" role="alert">{{ $errors->first('usage_exceptions') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.vehicleUsage.fields.usage_exceptions_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection