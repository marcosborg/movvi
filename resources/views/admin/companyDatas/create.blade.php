@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.companyData.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.company-datas.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label for="company_id">{{ trans('cruds.companyData.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id">
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ old('company_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyData.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label for="tvde_week_id">{{ trans('cruds.companyData.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id">
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ old('tvde_week_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyData.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('data') ? 'has-error' : '' }}">
                            <label for="data">{{ trans('cruds.companyData.fields.data') }}</label>
                            <textarea class="form-control" name="data" id="data">{{ old('data') }}</textarea>
                            @if($errors->has('data'))
                                <span class="help-block" role="alert">{{ $errors->first('data') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyData.fields.data_helper') }}</span>
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