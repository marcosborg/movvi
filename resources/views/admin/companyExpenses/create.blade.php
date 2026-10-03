@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.companyExpense.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.company-expenses.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.companyExpense.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.name_helper') }}</span>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                                <label for="category">Categoria</label>
                                <input class="form-control" type="text" name="category" id="category" value="{{ old('category') }}" placeholder="Ex.: Impostos, seguros, contabilidade">
                            </div>
                            <div class="col-md-4 form-group {{ $errors->has('supplier') ? 'has-error' : '' }}">
                                <label for="supplier">Fornecedor / entidade</label>
                                <input class="form-control" type="text" name="supplier" id="supplier" value="{{ old('supplier') }}">
                            </div>
                            <div class="col-md-4 form-group {{ $errors->has('reference') ? 'has-error' : '' }}">
                                <label for="reference">Referência</label>
                                <input class="form-control" type="text" name="reference" id="reference" value="{{ old('reference') }}" placeholder="Fatura, imposto ou documento">
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.companyExpense.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ old('company_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('recurrence') ? 'has-error' : '' }}">
                            <label class="required" for="recurrence">Tipo de despesa</label>
                            <select class="form-control" name="recurrence" id="recurrence" required>
                                <option value="once" {{ old('recurrence', 'once') === 'once' ? 'selected' : '' }}>Pontual / conta a pagar</option>
                                <option value="weekly" {{ old('recurrence') === 'weekly' ? 'selected' : '' }}>Recorrente semanal</option>
                            </select>
                        </div>
                        <div class="form-group {{ $errors->has('weekly_value') ? 'has-error' : '' }}">
                            <label class="required" for="weekly_value">Valor (€)</label>
                            <input class="form-control" type="number" name="weekly_value" id="weekly_value" value="{{ old('weekly_value', '0') }}" step="0.01" required>
                            @if($errors->has('weekly_value'))
                                <span class="help-block" role="alert">{{ $errors->first('weekly_value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.weekly_value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('start_date') ? 'has-error' : '' }}">
                            <label class="required" for="start_date">{{ trans('cruds.companyExpense.fields.start_date') }}</label>
                            <input class="form-control date" type="text" name="start_date" id="start_date" value="{{ old('start_date') }}" required>
                            @if($errors->has('start_date'))
                                <span class="help-block" role="alert">{{ $errors->first('start_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.start_date_helper') }}</span>
                        </div>
                        <div class="form-group js-recurring-field {{ $errors->has('end_date') ? 'has-error' : '' }}">
                            <label class="required" for="end_date">{{ trans('cruds.companyExpense.fields.end_date') }}</label>
                            <input class="form-control date" type="text" name="end_date" id="end_date" value="{{ old('end_date') }}">
                            @if($errors->has('end_date'))
                                <span class="help-block" role="alert">{{ $errors->first('end_date') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.end_date_helper') }}</span>
                        </div>
                        <div class="form-group js-recurring-field {{ $errors->has('qty') ? 'has-error' : '' }}">
                            <label class="required" for="qty">{{ trans('cruds.companyExpense.fields.qty') }}</label>
                            <input class="form-control" type="number" name="qty" id="qty" value="{{ old('qty', '1') }}" min="1" step="1">
                            @if($errors->has('qty'))
                                <span class="help-block" role="alert">{{ $errors->first('qty') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyExpense.fields.qty_helper') }}</span>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group {{ $errors->has('due_date') ? 'has-error' : '' }}">
                                <label for="due_date">Data de vencimento</label>
                                <input class="form-control date" type="text" name="due_date" id="due_date" value="{{ old('due_date') }}">
                            </div>
                            <div class="col-md-4 form-group {{ $errors->has('payment_status') ? 'has-error' : '' }}">
                                <label class="required" for="payment_status">Estado do pagamento</label>
                                <select class="form-control" name="payment_status" id="payment_status" required>
                                    <option value="pending" {{ old('payment_status', 'pending') === 'pending' ? 'selected' : '' }}>Pendente</option>
                                    <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Pago</option>
                                    <option value="cancelled" {{ old('payment_status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group {{ $errors->has('paid_at') ? 'has-error' : '' }}">
                                <label for="paid_at">Data de pagamento</label>
                                <input class="form-control date" type="text" name="paid_at" id="paid_at" value="{{ old('paid_at') }}">
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <label for="notes">Notas</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
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
@section('scripts')
@parent
<script>
    (function () {
        const recurrence = document.getElementById('recurrence');
        const recurringFields = document.querySelectorAll('.js-recurring-field');
        const toggleRecurringFields = () => recurringFields.forEach((field) => {
            field.style.display = recurrence.value === 'weekly' ? '' : 'none';
        });
        recurrence.addEventListener('change', toggleRecurringFields);
        toggleRecurringFields();
    })();
</script>
@endsection
