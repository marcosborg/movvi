@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.formInput.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.form-inputs.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('label') ? 'has-error' : '' }}">
                            <label class="required" for="label">{{ trans('cruds.formInput.fields.label') }}</label>
                            <input class="form-control" type="text" name="label" id="label" value="{{ old('label', '') }}" required>
                            @if($errors->has('label'))
                                <span class="help-block" role="alert">{{ $errors->first('label') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.label_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.formInput.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('type') ? 'has-error' : '' }}">
                            <label class="required">{{ trans('cruds.formInput.fields.type') }}</label>
                            @foreach(App\Models\FormInput::TYPE_RADIO as $key => $label)
                                <div>
                                    <input type="radio" id="type_{{ $key }}" name="type" value="{{ $key }}" {{ old('type', 'text') === (string) $key ? 'checked' : '' }} required>
                                    <label for="type_{{ $key }}" style="font-weight: 400">{{ $label }}</label>
                                </div>
                            @endforeach
                            @if($errors->has('type'))
                                <span class="help-block" role="alert">{{ $errors->first('type') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.type_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('form_name') ? 'has-error' : '' }}">
                            <label class="required" for="form_name_id">{{ trans('cruds.formInput.fields.form_name') }}</label>
                            <select class="form-control select2" name="form_name_id" id="form_name_id" required>
                                @foreach($form_names as $id => $entry)
                                    <option value="{{ $id }}" {{ old('form_name_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('form_name'))
                                <span class="help-block" role="alert">{{ $errors->first('form_name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.form_name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('required') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="required" value="0">
                                <input type="checkbox" name="required" id="required" value="1" {{ old('required', 0) == 1 ? 'checked' : '' }}>
                                <label for="required" style="font-weight: 400">{{ trans('cruds.formInput.fields.required') }}</label>
                            </div>
                            @if($errors->has('required'))
                                <span class="help-block" role="alert">{{ $errors->first('required') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.required_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('position') ? 'has-error' : '' }}">
                            <label class="required" for="position">{{ trans('cruds.formInput.fields.position') }}</label>
                            <input class="form-control" type="number" name="position" id="position" value="{{ old('position', '') }}" step="1" required>
                            @if($errors->has('position'))
                                <span class="help-block" role="alert">{{ $errors->first('position') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formInput.fields.position_helper') }}</span>
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