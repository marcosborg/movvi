@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.contractVat.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.contract-vats.update", [$contractVat->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.contractVat.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $contractVat->name) }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.contractVat.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('percent') ? 'has-error' : '' }}">
                            <label class="required" for="percent">{{ trans('cruds.contractVat.fields.percent') }}</label>
                            <input class="form-control" type="number" name="percent" id="percent" value="{{ old('percent', $contractVat->percent) }}" step="0.01" required>
                            @if($errors->has('percent'))
                                <span class="help-block" role="alert">{{ $errors->first('percent') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.contractVat.fields.percent_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('rf') ? 'has-error' : '' }}">
                            <label class="required" for="rf">{{ trans('cruds.contractVat.fields.rf') }}</label>
                            <input class="form-control" type="number" name="rf" id="rf" value="{{ old('rf', $contractVat->rf) }}" step="0.01" required>
                            @if($errors->has('rf'))
                                <span class="help-block" role="alert">{{ $errors->first('rf') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.contractVat.fields.rf_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('iva') ? 'has-error' : '' }}">
                            <label class="required" for="iva">{{ trans('cruds.contractVat.fields.iva') }}</label>
                            <input class="form-control" type="number" name="iva" id="iva" value="{{ old('iva', $contractVat->iva) }}" step="0.01" required>
                            @if($errors->has('iva'))
                                <span class="help-block" role="alert">{{ $errors->first('iva') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.contractVat.fields.iva_helper') }}</span>
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