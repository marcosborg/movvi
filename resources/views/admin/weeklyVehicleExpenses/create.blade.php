@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.weeklyVehicleExpense.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.weekly-vehicle-expenses.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('vehicle_item') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_item_id">{{ trans('cruds.weeklyVehicleExpense.fields.vehicle_item') }}</label>
                            <select class="form-control select2" name="vehicle_item_id" id="vehicle_item_id" required>
                                @foreach($vehicle_items as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_item_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_item'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.vehicle_item_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.weeklyVehicleExpense.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.weeklyVehicleExpense.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ old('tvde_week_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('total_km') ? 'has-error' : '' }}">
                            <label for="total_km">{{ trans('cruds.weeklyVehicleExpense.fields.total_km') }}</label>
                            <input class="form-control" type="number" name="total_km" id="total_km" value="{{ old('total_km', '') }}" step="1">
                            @if($errors->has('total_km'))
                                <span class="help-block" role="alert">{{ $errors->first('total_km') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.total_km_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('weekly_km') ? 'has-error' : '' }}">
                            <label for="weekly_km">{{ trans('cruds.weeklyVehicleExpense.fields.weekly_km') }}</label>
                            <input class="form-control" type="number" name="weekly_km" id="weekly_km" value="{{ old('weekly_km', '') }}" step="1">
                            @if($errors->has('weekly_km'))
                                <span class="help-block" role="alert">{{ $errors->first('weekly_km') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.weekly_km_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('extra_km') ? 'has-error' : '' }}">
                            <label for="extra_km">{{ trans('cruds.weeklyVehicleExpense.fields.extra_km') }}</label>
                            <input class="form-control" type="number" name="extra_km" id="extra_km" value="{{ old('extra_km', '') }}" step="1">
                            @if($errors->has('extra_km'))
                                <span class="help-block" role="alert">{{ $errors->first('extra_km') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.extra_km_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('transfers') ? 'has-error' : '' }}">
                            <label for="transfers">{{ trans('cruds.weeklyVehicleExpense.fields.transfers') }}</label>
                            <input class="form-control" type="number" name="transfers" id="transfers" value="{{ old('transfers', '') }}" step="0.01">
                            @if($errors->has('transfers'))
                                <span class="help-block" role="alert">{{ $errors->first('transfers') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.transfers_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('deposit') ? 'has-error' : '' }}">
                            <label for="deposit">{{ trans('cruds.weeklyVehicleExpense.fields.deposit') }}</label>
                            <input class="form-control" type="number" name="deposit" id="deposit" value="{{ old('deposit', '') }}" step="0.01">
                            @if($errors->has('deposit'))
                                <span class="help-block" role="alert">{{ $errors->first('deposit') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.weeklyVehicleExpense.fields.deposit_helper') }}</span>
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