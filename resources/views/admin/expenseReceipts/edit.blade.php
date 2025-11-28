@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.expenseReceipt.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.expense-receipts.update", [$expenseReceipt->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.expenseReceipt.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') : $expenseReceipt->driver->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.expenseReceipt.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $expenseReceipt->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.tvde_week_helper') }}</span>
                        </div>
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
                            <input class="form-control" type="number" name="approved_value" id="approved_value" value="{{ old('approved_value', $expenseReceipt->approved_value) }}" step="0.01">
                            @if($errors->has('approved_value'))
                                <span class="help-block" role="alert">{{ $errors->first('approved_value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.approved_value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('verified') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="verified" value="0">
                                <input type="checkbox" name="verified" id="verified" value="1" {{ $expenseReceipt->verified || old('verified', 0) === 1 ? 'checked' : '' }}>
                                <label for="verified" style="font-weight: 400">{{ trans('cruds.expenseReceipt.fields.verified') }}</label>
                            </div>
                            @if($errors->has('verified'))
                                <span class="help-block" role="alert">{{ $errors->first('verified') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.expenseReceipt.fields.verified_helper') }}</span>
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
@endsection