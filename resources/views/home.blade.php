@extends('layouts.admin')
@section('content')
<div class="content">
    @if ($company_id == 0)
    <div class="alert alert-info" role="alert">
        Selecione uma empresa para ver os seus extratos.
    </div>
    @else
    <div class="btn-group btn-group-justified" role="group">
        @foreach ($tvde_years as $tvde_year)
        <a href="/admin/financial-statements/year/{{ $tvde_year->id }}" class="btn btn-default {{ $tvde_year->id == $tvde_year_id ? 'disabled selected' : '' }}">{{ $tvde_year->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_months as $tvde_month)
        <a href="/admin/financial-statements/month/{{ $tvde_month->id }}" class="btn btn-default {{ $tvde_month->id == $tvde_month_id ? 'disabled selected' : '' }}">{{
            $tvde_month->name
            }}</a>
        @endforeach
    </div>
    <div class="btn-group btn-group-justified" role="group" style="margin-top: 5px;">
        @foreach ($tvde_weeks as $tvde_week)
        <a href="/admin/financial-statements/week/{{ $tvde_week->id }}" class="btn btn-default {{ $tvde_week->id == $tvde_week_id ? 'disabled selected' : '' }}">Semana de {{
            \Carbon\Carbon::parse($tvde_week->start_date)->format('d')
            }} a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d') }}</a>
        @endforeach
    </div>
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Atividades por operador
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Bruto</th>
                                <th style="text-align: right;">Líquido</th>
                            </tr>
                            <tr>
                                <th>UBER</th>
                                <td>{{ number_format($uber_gross, 2) }}€</td>
                                <td>{{ number_format($uber_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>BOLT</th>
                                <td>{{ number_format($bolt_gross, 2) }}€</td>
                                <td>{{ number_format($bolt_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Totais</th>
                                <td>{{ number_format($total_gross, 2) }}€</td>
                                <td>{{ number_format($total_net, 2) }}€</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    IVA das atividades por operador
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>IVA</th>
                            <td style="color: red;">- {{ number_format($vat_value, 2) }}€</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Totais
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Créditos</th>
                                <th style="text-align: right;">Débitos</th>
                                <th style="text-align: right;">Totais</th>
                            </tr>
                            <tr>
                                <th>Ganhos</th>
                                <td>{{ number_format($total_net, 2) }}€</td>
                                <td></td>
                                <td>{{ number_format($total_net, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Aluguer</th>
                                <td></td>
                                <td>- {{ number_format($car_hire, 2) }}€</td>
                                <td>- {{ number_format($car_hire, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Via Verde</th>
                                <td></td>
                                <td>- {{ number_format($car_track, 2) }}€</td>
                                <td>- {{ number_format($car_track, 2) }}€</td>
                            </tr>
                            <tr>
                                <th>Abastecimento</th>
                                <td></td>
                                <td>- {{ number_format($fuel_transactions, 2) }}€</td>
                                <td>- {{ number_format($fuel_transactions, 2) }}€</td>
                            </tr>
                            @if ($adjustments_array)
                            @foreach ($adjustments_array as $adjustment)
                            <tr>
                                <th>{{ $adjustment->name }}</th>
                                <td>{{ $adjustment->type == 'refund' ? number_format($adjustment->amount, 2) . '€' : '' }}</td>
                                <td>{{ $adjustment->type == 'deduct' ? '-' . number_format($adjustment->amount, 2) . '€' : '' }}</td>
                                <td>
                                    {{ $adjustment->type == 'refund' ? number_format($adjustment->amount, 2) . '€' : '' }}
                                    {{ $adjustment->type == 'deduct' ? '-' . number_format($adjustment->amount, 2) . '€' : '' }}
                                </td>
                            </tr>
                            @endforeach
                            @endif
                            <tr>
                                <th>IVA</th>
                                <td></td>
                                <td>- {{ number_format($vat_value, 2) }}€</td>
                                <td>- {{ number_format($vat_value, 2) }}€</td>
                            </tr>
                            @php
                                if ($adjustments && $adjustments > 0) {
                                    $total_net = $total_net + $adjustments;
                                }
                            @endphp
                            <tr>
                                <th>Totais</th>
                                <th style="text-align: right;">{{ number_format($total_net, 2) }}€</th>
                                <th style="text-align: right;">{{ number_format(($total - $total_net), 2) }}€</th>
                                <th style="text-align: right;">{{ number_format($total, 2) }}€</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="pull-left">
                    <h4>Valor da semana: <span style="font-weight: 800;">{{ number_format($total, 2) }}</span>€</h4>
                    <h3>Saldo atual: <span style="font-weight: 800;">{{ $driver_balance->balance ?? 0 }}</span>€</h3>
                    </div>
                    <div class="pull-right">
                        <a target="_new" href="/admin/financial-statements/pdf" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i></a>
                        <a href="/admin/financial-statements/pdf/1" class="btn btn-primary"><i class="fa fa-cloud-download"></i></a>
                    </div>
                </div>
                @if (auth()->user()->hasRole('Admin'))
                <div class="panel-footer">
                    <form action="/admin/financial-statements/update-balance" method="post" id="update-balance">
                        @csrf
                        <input type="hidden" name="driver_balance_id" value="{{ $driver_balance->id ?? 0 }}">
                        <div class="form-inline">
                            <div class="input-group">
                                <div class="input-group-addon">Saldo (€)</div>
                                <input type="text" class="form-control" value="{{ $driver_balance->drivers_balance ?? 0 }}" name="balance">
                            </div>
                            <button type="submit" class="btn btn-success">Atualizar saldo</button>
                    </form>
                </div>
                @endif
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Recibo
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            @if ($driver_balance)
                            <tr>
                                <th>Saldo transitado</th>
                                <td>{{ number_format($driver_balance_last_week->balance ?? 0, 2) }}€</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Saldo atual</th>
                                <td>{{ $driver_balance->balance ?? 0 }}€</td>
                            </tr>
                            <tr>
                                <th>IVA a devolver:</th>
                                <td>{{ $driver_balance->iva ?? 0 }}€</td>
                            </tr>
                            <tr>
                                <th>Retenção na fonte</th>
                                <td>{{ $driver_balance->rf ?? 0 }}€</td>
                            </tr>
                        </tbody>
                    </table>
                    @if ($driver_balance && $driver_balance->drivers_balance > 0)
                    <form method="POST" action="{{ route("admin.receipts.store") }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="driver_id" value="{{ $driver_id }}">
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <input type="hidden" name="balance" value="{{ $driver_balance->drivers_balance }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                                    <label class="required" for="value">Valor do recibo</label>
                                    <input class="form-control" type="hidden" name="value" id="value" value="{{ number_format((float)$driver_balance->final, 2, '.', '') }}">
                                    <input class="form-control" type="text" disabled value="{{ $driver_balance->final }}" placeholder="Verifique o seu recibo para confirmar o valor." required>
                                    @if($errors->has('value'))
                                    <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.receipt.fields.value_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6" style="display: none;">
                                <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                                    <label class="required" for="tvde_week_id">Recibo da semana</label>
                                    <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                        @foreach($tvde_weeks as $tvde_week)
                                            <option value="{{ $tvde_week->id }}" {{ $tvde_week_id == $tvde_week->id ? 'selected' : '' }}>{{ $tvde_week->start_date }} a {{ $tvde_week->end_date }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('tvde_week'))
                                        <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.receipt.fields.tvde_week_helper') }}</span>
                                </div>
                                <input type="hidden" name="tvde_week_id" id="tvde_week_id" value="{{ $tvde_week_id }}">
                            </div>
                        </div>
                        
                        <div class="form-group {{ $errors->has('file') ? 'has-error' : '' }}">
                            <label class="required" for="file">Recibo verde</label>
                            <div class="needsclick dropzone" id="file-dropzone">
                            </div>
                            @if($errors->has('file'))
                            <span class="help-block" role="alert">{{ $errors->first('file') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.file_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-success" type="submit">
                                Enviar recibo verde
                            </button>
                        </div>
                    </form>
                    @if (!$expenseReceipt)
                    <form method="POST" action="{{ route("admin.expense-receipts.store") }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="driver_id" value="{{ $driver_id }}">
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <div class="form-group {{ $errors->has('receipts') ? 'has-error' : '' }}">
                            <label for="receipts">Recibos de despesas</label>
                            <div class="needsclick dropzone" id="receipts-dropzone">
                            </div>
                            @if($errors->has('receipts'))
                                <span class="help-block" role="alert">{{ $errors->first('receipts') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.receipts_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('approved_value') ? 'has-error' : '' }}">
                            <label for="approved_value">Somatório das despesas</label>
                            <input class="form-control" type="number" name="approved_value" id="approved_value" value="{{ old('approved_value', '') }}" step="0.01">
                            @if($errors->has('approved_value'))
                                <span class="help-block" role="alert">{{ $errors->first('approved_value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.approved_value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('verified') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="verified" value="0">
                                <input type="checkbox" name="verified" id="verified" value="1" {{ old('verified', 0) == 1 ? 'checked' : '' }} disabled>
                                <label for="verified" style="font-weight: 400">{{ trans('cruds.expenseReceipt.fields.verified') }}</label>
                            </div>
                            @if($errors->has('verified'))
                                <span class="help-block" role="alert">{{ $errors->first('verified') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.verified_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                Enviar recibos de despesas
                            </button>
                        </div>
                    </form>
                    @else
                    <form method="POST" action="{{ route("admin.expense-receipts.update", [$expenseReceipt->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <input type="hidden" name="driver_id" value="{{ $driver_id }}">
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <div class="form-group {{ $errors->has('receipts') ? 'has-error' : '' }}">
                            <label for="receipts">{{ trans('cruds.expenseReceipt.fields.receipts') }}</label>
                            <div class="needsclick dropzone" id="receipts-dropzone">
                            </div>
                            @if($errors->has('receipts'))
                                <span class="help-block" role="alert">{{ $errors->first('receipts') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.receipts_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('approved_value') ? 'has-error' : '' }}">
                            <label for="approved_value">{{ trans('cruds.expenseReceipt.fields.approved_value') }}</label>
                            <input class="form-control" type="number" name="approved_value" id="approved_value" value="{{ old('approved_value', $expenseReceipt->approved_value) }}" step="0.01" {{ $expenseReceipt->verified ? 'disabled' : '' }}>
                            @if($errors->has('approved_value'))
                                <span class="help-block" role="alert">{{ $errors->first('approved_value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.approved_value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('verified') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="verified" value="0">
                                <input type="checkbox" name="verified" id="verified" value="1" disabled {{ $expenseReceipt->verified || old('verified', 0) === 1 ? 'checked' : '' }}>
                                <label for="verified" style="font-weight: 400">{{ trans('cruds.expenseReceipt.fields.verified') }}</label>
                            </div>
                            @if($errors->has('verified'))
                                <span class="help-block" role="alert">{{ $errors->first('verified') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.verified_helper') }}</span>
                        </div>
                        @if (!$expenseReceipt->verified)
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                        @endif
                    </form>
                    @endif
                    @else
                    <div class="alert alert-info">
                        O saldo não permite o envio de recibos.
                    </div>
                    @endif
                    <hr>
                    <form method="POST" action="{{ route("admin.reimbursements.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('file') ? 'has-error' : '' }}">
                            <label class="required" for="file">Devolução de valores</label>
                            <div class="needsclick dropzone" id="file-dropzone">
                            </div>
                            @if($errors->has('file'))
                                <span class="help-block" role="alert">{{ $errors->first('file') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.reimbursement.fields.file_helper') }}</span>
                        </div>
                        <input class="form-control" type="hidden" name="value" id="value" value="0" step="0.01">
                        <input type="hidden" name="driver_id" value="{{ $driver_id }}">
                        <input type="hidden" name="tvde_week_id" value="{{ $tvde_week_id }}">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                Gravar valores devolvidos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
</div>
@endsection
@section('styles')
<style>
    td {
        text-align: right;
    }

    table {
        font-size: 13px;
    }

    canvas#electric_racio {
        pointer-events: none;
    }

</style>
@endsection
@section('scripts')
@parent
<script src="https://malsup.github.io/jquery.form.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js">
</script>
<script>
    $(() => {
        $('#update-balance').ajaxForm({
            beforeSubmit: () => {
                $('#update-balance').LoadingOverlay('show');
            }
            , success: () => {
                $('#update-balance').LoadingOverlay('hide');
                Swal.fire({
                    title: 'Atualizado com sucesso'
                    , icon: 'success'
                , }).then(() => {
                    location.reload();
                });
            }
            , error: (error) => {
                $('#update-balance').LoadingOverlay('hide');
                var html = '';
                $.each(error.responseJSON.errors, (i, v) => {
                    $.each(v, (index, value) => {
                        html += value + '<br>'
                    });
                });
                Swal.fire({
                    title: 'Erro de validação'
                    , html: html
                    , icon: 'error'
                , }).then(() => {
                    location.reload();
                });
            }
        });
    });

</script>
<script>
    Dropzone.options.fileDropzone = {
        url: '{{ route('admin.receipts.storeMedia') }}'
        , maxFilesize: 2, // MB
        maxFiles: 1
        , addRemoveLinks: true
        , headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        }
        , params: {
            size: 2
        }
        , success: function(file, response) {
            $('form').find('input[name="file"]').remove()
            $('form').append('<input type="hidden" name="file" value="' + response.name + '">')
        }
        , removedfile: function(file) {
            file.previewElement.remove()
            if (file.status !== 'error') {
                $('form').find('input[name="file"]').remove()
                this.options.maxFiles = this.options.maxFiles + 1
            }
        }
        , init: function() {
            @if(isset($receipt) && $receipt->file)
            var file = {!! json_encode($receipt->file) !!}
            this.options.addedfile.call(this, file)
            file.previewElement.classList.add('dz-complete')
            $('form').append('<input type="hidden" name="file" value="' + file.file_name + '">')
            this.options.maxFiles = this.options.maxFiles - 1
            @endif
        }
        , error: function(file, response) {
            if ($.type(response) === 'string') {
                var message = response //dropzone sends it's own error messages in string
            } else {
                var message = response.errors.file
            }
            file.previewElement.classList.add('dz-error')
            _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
            _results = []
            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                node = _ref[_i]
                _results.push(node.textContent = message)
            }

            return _results
        }
    }

</script>
@if (!$expenseReceipt)
<script>
    var uploadedReceiptsMap = {}
Dropzone.options.receiptsDropzone = {
    url: '{{ route('admin.expense-receipts.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="receipts[]" value="' + response.name + '">')
      uploadedReceiptsMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedReceiptsMap[file.name]
      }
      $('form').find('input[name="receipts[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($expenseReceipt) && $expenseReceipt->receipts)
          var files =
            {!! json_encode($expenseReceipt->receipts) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="receipts[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
@else
<script>
    var uploadedReceiptsMap = {}
Dropzone.options.receiptsDropzone = {
    url: '{{ route('admin.expense-receipts.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: false,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="receipts[]" value="' + response.name + '">')
      uploadedReceiptsMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedReceiptsMap[file.name]
      }
      $('form').find('input[name="receipts[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($expenseReceipt) && $expenseReceipt->receipts)
          var files =
            {!! json_encode($expenseReceipt->receipts) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="receipts[]" value="' + file.file_name + '">')
            }
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
@endif
<script>
    Dropzone.options.fileDropzone = {
    url: '{{ route('admin.reimbursements.storeMedia') }}',
    maxFilesize: 2, // MB
    maxFiles: 1,
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2
    },
    success: function (file, response) {
      $('form').find('input[name="file"]').remove()
      $('form').append('<input type="hidden" name="file" value="' + response.name + '">')
    },
    removedfile: function (file) {
      file.previewElement.remove()
      if (file.status !== 'error') {
        $('form').find('input[name="file"]').remove()
        this.options.maxFiles = this.options.maxFiles + 1
      }
    },
    init: function () {
@if(isset($reimbursement) && $reimbursement->file)
      var file = {!! json_encode($reimbursement->file) !!}
          this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      $('form').append('<input type="hidden" name="file" value="' + file.file_name + '">')
      this.options.maxFiles = this.options.maxFiles - 1
@endif
    },
     error: function (file, response) {
         if ($.type(response) === 'string') {
             var message = response //dropzone sends it's own error messages in string
         } else {
             var message = response.errors.file
         }
         file.previewElement.classList.add('dz-error')
         _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
         _results = []
         for (_i = 0, _len = _ref.length; _i < _len; _i++) {
             node = _ref[_i]
             _results.push(node.textContent = message)
         }

         return _results
     }
}
</script>
@endsection