@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.tvdeActivity.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.tvde-activities.update", [$tvdeActivity->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.tvdeActivity.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $tvdeActivity->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_operator') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_operator_id">{{ trans('cruds.tvdeActivity.fields.tvde_operator') }}</label>
                            <select class="form-control select2" name="tvde_operator_id" id="tvde_operator_id" required>
                                @foreach($tvde_operators as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_operator_id') ? old('tvde_operator_id') : $tvdeActivity->tvde_operator->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_operator'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_operator') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.tvde_operator_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.tvdeActivity.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('company_id') ? old('company_id') : $tvdeActivity->company->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('driver_code') ? 'has-error' : '' }}">
                            <label class="required" for="driver_code">{{ trans('cruds.tvdeActivity.fields.driver_code') }}</label>
                            <input class="form-control" type="text" name="driver_code" id="driver_code" value="{{ old('driver_code', $tvdeActivity->driver_code) }}" required>
                            @if($errors->has('driver_code'))
                                <span class="help-block" role="alert">{{ $errors->first('driver_code') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.driver_code_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('gross') ? 'has-error' : '' }}">
                            <label for="gross">{{ trans('cruds.tvdeActivity.fields.gross') }}</label>
                            <input class="form-control" type="number" name="gross" id="gross" value="{{ old('gross', $tvdeActivity->gross) }}" step="0.01">
                            @if($errors->has('gross'))
                                <span class="help-block" role="alert">{{ $errors->first('gross') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.gross_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('net') ? 'has-error' : '' }}">
                            <label for="net">{{ trans('cruds.tvdeActivity.fields.net') }}</label>
                            <input class="form-control" type="number" name="net" id="net" value="{{ old('net', $tvdeActivity->net) }}" step="0.01">
                            @if($errors->has('net'))
                                <span class="help-block" role="alert">{{ $errors->first('net') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.tvdeActivity.fields.net_helper') }}</span>
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