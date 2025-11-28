@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.driversBalance.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.drivers-balances.update", [$driversBalance->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.driversBalance.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') : $driversBalance->driver->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.driversBalance.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.driversBalance.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $driversBalance->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.driversBalance.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.driversBalance.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $driversBalance->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.driversBalance.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('last_balance') ? 'has-error' : '' }}">
                            <label class="required" for="last_balance">{{ trans('cruds.driversBalance.fields.last_balance') }}</label>
                            <input class="form-control" type="number" name="last_balance" id="last_balance" value="{{ old('last_balance', $driversBalance->last_balance) }}" step="0.01" required>
                            @if($errors->has('last_balance'))
                                <span class="help-block" role="alert">{{ $errors->first('last_balance') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.driversBalance.fields.last_balance_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('new_balance') ? 'has-error' : '' }}">
                            <label for="new_balance">{{ trans('cruds.driversBalance.fields.new_balance') }}</label>
                            <input class="form-control" type="number" name="new_balance" id="new_balance" value="{{ old('new_balance', $driversBalance->new_balance) }}" step="0.01">
                            @if($errors->has('new_balance'))
                                <span class="help-block" role="alert">{{ $errors->first('new_balance') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.driversBalance.fields.new_balance_helper') }}</span>
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