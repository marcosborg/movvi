@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.formData.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.form-datas.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('form_name') ? 'has-error' : '' }}">
                            <label class="required" for="form_name_id">{{ trans('cruds.formData.fields.form_name') }}</label>
                            <select class="form-control select2" name="form_name_id" id="form_name_id" required>
                                @foreach($form_names as $id => $entry)
                                    <option value="{{ $id }}" {{ old('form_name_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('form_name'))
                                <span class="help-block" role="alert">{{ $errors->first('form_name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.form_name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label for="driver_id">{{ trans('cruds.formData.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id">
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vehicle_item') ? 'has-error' : '' }}">
                            <label for="vehicle_item_id">{{ trans('cruds.formData.fields.vehicle_item') }}</label>
                            <select class="form-control select2" name="vehicle_item_id" id="vehicle_item_id">
                                @foreach($vehicle_items as $id => $entry)
                                    <option value="{{ $id }}" {{ old('vehicle_item_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_item'))
                                <span class="help-block" role="alert">{{ $errors->first('vehicle_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.vehicle_item_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('user') ? 'has-error' : '' }}">
                            <label for="user_id">{{ trans('cruds.formData.fields.user') }}</label>
                            <select class="form-control select2" name="user_id" id="user_id">
                                @foreach($users as $id => $entry)
                                    <option value="{{ $id }}" {{ old('user_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('user'))
                                <span class="help-block" role="alert">{{ $errors->first('user') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.user_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('data') ? 'has-error' : '' }}">
                            <label class="required" for="data">{{ trans('cruds.formData.fields.data') }}</label>
                            <textarea class="form-control" name="data" id="data" required>{{ old('data') }}</textarea>
                            @if($errors->has('data'))
                                <span class="help-block" role="alert">{{ $errors->first('data') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.data_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('solved') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="solved" value="0">
                                <input type="checkbox" name="solved" id="solved" value="1" {{ old('solved', 0) == 1 ? 'checked' : '' }}>
                                <label for="solved" style="font-weight: 400">{{ trans('cruds.formData.fields.solved') }}</label>
                            </div>
                            @if($errors->has('solved'))
                                <span class="help-block" role="alert">{{ $errors->first('solved') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formData.fields.solved_helper') }}</span>
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