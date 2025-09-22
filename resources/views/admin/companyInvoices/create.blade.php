@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.companyInvoice.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.company-invoices.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                            <label class="required" for="company_id">{{ trans('cruds.companyInvoice.fields.company') }}</label>
                            <select class="form-control select2" name="company_id" id="company_id" required>
                                @foreach($companies as $id => $entry)
                                    <option value="{{ $id }}" {{ old('company_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('company'))
                                <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.company_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.companyInvoice.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ old('tvde_week_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('invoice') ? 'has-error' : '' }}">
                            <label for="invoice">{{ trans('cruds.companyInvoice.fields.invoice') }}</label>
                            <div class="needsclick dropzone" id="invoice-dropzone">
                            </div>
                            @if($errors->has('invoice'))
                                <span class="help-block" role="alert">{{ $errors->first('invoice') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.invoice_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('payment_receipt') ? 'has-error' : '' }}">
                            <label for="payment_receipt">{{ trans('cruds.companyInvoice.fields.payment_receipt') }}</label>
                            <div class="needsclick dropzone" id="payment_receipt-dropzone">
                            </div>
                            @if($errors->has('payment_receipt'))
                                <span class="help-block" role="alert">{{ $errors->first('payment_receipt') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.payment_receipt_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('info') ? 'has-error' : '' }}">
                            <label for="info">{{ trans('cruds.companyInvoice.fields.info') }}</label>
                            <textarea class="form-control" name="info" id="info">{{ old('info') }}</textarea>
                            @if($errors->has('info'))
                                <span class="help-block" role="alert">{{ $errors->first('info') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.info_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('payed') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="payed" value="0">
                                <input type="checkbox" name="payed" id="payed" value="1" {{ old('payed', 0) == 1 ? 'checked' : '' }}>
                                <label for="payed" style="font-weight: 400">{{ trans('cruds.companyInvoice.fields.payed') }}</label>
                            </div>
                            @if($errors->has('payed'))
                                <span class="help-block" role="alert">{{ $errors->first('payed') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.companyInvoice.fields.payed_helper') }}</span>
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
<script>
    var uploadedInvoiceMap = {}
Dropzone.options.invoiceDropzone = {
    url: '{{ route('admin.company-invoices.storeMedia') }}',
    maxFilesize: 2, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 2
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="invoice[]" value="' + response.name + '">')
      uploadedInvoiceMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedInvoiceMap[file.name]
      }
      $('form').find('input[name="invoice[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($companyInvoice) && $companyInvoice->invoice)
          var files =
            {!! json_encode($companyInvoice->invoice) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="invoice[]" value="' + file.file_name + '">')
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
<script>
    Dropzone.options.paymentReceiptDropzone = {
    url: '{{ route('admin.company-invoices.storeMedia') }}',
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
      $('form').find('input[name="payment_receipt"]').remove()
      $('form').append('<input type="hidden" name="payment_receipt" value="' + response.name + '">')
    },
    removedfile: function (file) {
      file.previewElement.remove()
      if (file.status !== 'error') {
        $('form').find('input[name="payment_receipt"]').remove()
        this.options.maxFiles = this.options.maxFiles + 1
      }
    },
    init: function () {
@if(isset($companyInvoice) && $companyInvoice->payment_receipt)
      var file = {!! json_encode($companyInvoice->payment_receipt) !!}
          this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      $('form').append('<input type="hidden" name="payment_receipt" value="' + file.file_name + '">')
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