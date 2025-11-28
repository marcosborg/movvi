@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.teslaCharging.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.tesla-chargings.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.teslaCharging.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', '') }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                            <label for="license">{{ trans('cruds.teslaCharging.fields.license') }}</label>
                            <input class="form-control" type="text" name="license" id="license" value="{{ old('license', '') }}">
                            @if($errors->has('license'))
                                <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.license_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('datetime') ? 'has-error' : '' }}">
                            <label for="datetime">{{ trans('cruds.teslaCharging.fields.datetime') }}</label>
                            <input class="form-control datetime" type="text" name="datetime" id="datetime" value="{{ old('datetime', '') }}">
                            @if($errors->has('datetime'))
                                <span class="help-block" role="alert">{{ $errors->first('datetime') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.datetime_helper') }}</span>
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