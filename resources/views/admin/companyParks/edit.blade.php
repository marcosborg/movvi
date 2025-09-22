@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.companyPark.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.company-parks.update", [$companyPark->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.companyPark.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $companyPark->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyPark.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.companyPark.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('company_id') ? old('company_id') : $companyPark->company->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyPark.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.companyPark.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $companyPark->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyPark.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('fleet_management') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="fleet_management" value="0">
                                <input type="checkbox" name="fleet_management" id="fleet_management" value="1" {{ $companyPark->fleet_management || old('fleet_management', 0) === 1 ? 'checked' : '' }}>
                                <label for="fleet_management" style="font-weight: 400">{{ trans('cruds.companyPark.fields.fleet_management') }}</label>
                            </div>
                            @if($errors->has('fleet_management'))
                                <span class="help-block" role="alert">{{ $errors->first('fleet_management') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyPark.fields.fleet_management_helper') }}</span>
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