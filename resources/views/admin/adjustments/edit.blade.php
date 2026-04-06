@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.adjustment.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.adjustments.update", [$adjustment->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.adjustment.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $adjustment->name) }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('type') ? 'has-error' : '' }}">
                            <label class="required">{{ trans('cruds.adjustment.fields.type') }}</label>
                            @foreach(App\Models\Adjustment::TYPE_RADIO as $key => $label)
                                <div>
                                    <input type="radio" id="type_{{ $key }}" name="type" value="{{ $key }}" {{ old('type', $adjustment->type) === (string) $key ? 'checked' : '' }} required>
                                    <label for="type_{{ $key }}" style="font-weight: 400">{{ $label }}</label>
                                </div>
                            @endforeach
                            @if($errors->has('type'))
                                <span class="help-block" role="alert">{{ $errors->first('type') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.type_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                            <label for="amount">{{ trans('cruds.adjustment.fields.amount') }}</label>
                            <input class="form-control" type="number" name="amount" id="amount" value="{{ old('amount', $adjustment->amount) }}" step="0.01">
                            @if($errors->has('amount'))
                                <span class="help-block" role="alert">{{ $errors->first('amount') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.amount_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                            <label class="required" for="category">Categoria</label>
                            <select class="form-control" name="category" id="category" required>
                                @foreach(App\Models\Adjustment::CATEGORY_SELECT as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $adjustment->category ?? App\Models\Adjustment::CATEGORY_GENERAL) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('category'))
                                <span class="help-block" role="alert">{{ $errors->first('category') }}</span>
                            @endif
                            <span class="help-block">Define como este ajuste deve ser tratado no extrato e nos relatórios.</span>
                        </div>
                        <div class="form-group {{ $errors->has('percent') ? 'has-error' : '' }}">
                            <label for="percent">{{ trans('cruds.adjustment.fields.percent') }}</label>
                            <input class="form-control" type="text" name="percent" id="percent" value="{{ old('percent', $adjustment->percent) }}">
                            @if($errors->has('percent'))
                                <span class="help-block" role="alert">{{ $errors->first('percent') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.percent_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('start_date') ? 'has-error' : '' }}">
                            <label for="start_date">{{ trans('cruds.adjustment.fields.start_date') }}</label>
                            <input class="form-control date" type="text" name="start_date" id="start_date" value="{{ old('start_date', $adjustment->start_date) }}">
                            @if($errors->has('start_date'))
                                <span class="help-block" role="alert">{{ $errors->first('start_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.start_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('end_date') ? 'has-error' : '' }}">
                            <label for="end_date">{{ trans('cruds.adjustment.fields.end_date') }}</label>
                            <input class="form-control date" type="text" name="end_date" id="end_date" value="{{ old('end_date', $adjustment->end_date) }}">
                            @if($errors->has('end_date'))
                                <span class="help-block" role="alert">{{ $errors->first('end_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.end_date_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('drivers') ? 'has-error' : '' }}">
                            <label for="drivers">{{ trans('cruds.adjustment.fields.drivers') }}</label>
                            <div style="padding-bottom: 4px">
                                <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                                <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                            </div>
                            <select class="form-control select2" name="drivers[]" id="drivers" multiple>
                                @foreach($drivers as $id => $driver)
                                    <option value="{{ $id }}" {{ (in_array($id, old('drivers', [])) || $adjustment->drivers->contains($id)) ? 'selected' : '' }}>{{ $driver }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('drivers'))
                                <span class="help-block" role="alert">{{ $errors->first('drivers') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.drivers_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.adjustment.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('company_id') ? old('company_id') : $adjustment->company->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('affects_vehicle_profitability') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="affects_vehicle_profitability" value="0">
                                <input type="checkbox" name="affects_vehicle_profitability" id="affects_vehicle_profitability" value="1" {{ $adjustment->affects_vehicle_profitability || old('affects_vehicle_profitability', 0) === 1 ? 'checked' : '' }}>
                                <label for="affects_vehicle_profitability" style="font-weight: 400">{{ trans('cruds.adjustment.fields.affects_vehicle_profitability') }}</label>
                            </div>
                            @if($errors->has('affects_vehicle_profitability'))
                                <span class="help-block" role="alert">{{ $errors->first('affects_vehicle_profitability') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adjustment.fields.affects_vehicle_profitability_helper') }}</span>
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
