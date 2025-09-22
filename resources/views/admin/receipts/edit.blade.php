@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.receipt.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.receipts.update", [$receipt->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.receipt.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') : $receipt->driver->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.receipt.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $receipt->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('file') ? 'has-error' : '' }}">
                            <label class="required" for="file">{{ trans('cruds.receipt.fields.file') }}</label>
                            <div class="needsclick dropzone" id="file-dropzone">
                            </div>
                            @if($errors->has('file'))
                                <span class="help-block" role="alert">{{ $errors->first('file') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.file_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('paid') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="paid" value="0">
                                <input type="checkbox" name="paid" id="paid" value="1" {{ $receipt->paid || old('paid', 0) === 1 ? 'checked' : '' }}>
                                <label for="paid" style="font-weight: 400">{{ trans('cruds.receipt.fields.paid') }}</label>
                            </div>
                            @if($errors->has('paid'))
                                <span class="help-block" role="alert">{{ $errors->first('paid') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.paid_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('verified') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="verified" value="0">
                                <input type="checkbox" name="verified" id="verified" value="1" {{ $receipt->verified || old('verified', 0) === 1 ? 'checked' : '' }}>
                                <label for="verified" style="font-weight: 400">{{ trans('cruds.receipt.fields.verified') }}</label>
                            </div>
                            @if($errors->has('verified'))
                                <span class="help-block" role="alert">{{ $errors->first('verified') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.verified_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('balance') ? 'has-error' : '' }}">
                            <label for="balance">{{ trans('cruds.receipt.fields.balance') }}</label>
                            <input class="form-control" type="number" name="balance" id="balance" value="{{ old('balance', $receipt->balance) }}" step="0.01">
                            @if($errors->has('balance'))
                                <span class="help-block" role="alert">{{ $errors->first('balance') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.balance_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('verified_value') ? 'has-error' : '' }}">
                            <label for="verified_value">{{ trans('cruds.receipt.fields.verified_value') }}</label>
                            <input class="form-control" type="number" name="verified_value" id="verified_value" value="{{ old('verified_value', $receipt->verified_value) }}" step="0.01">
                            @if($errors->has('verified_value'))
                                <span class="help-block" role="alert">{{ $errors->first('verified_value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.verified_value_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('amount_transferred') ? 'has-error' : '' }}">
                            <label for="amount_transferred">{{ trans('cruds.receipt.fields.amount_transferred') }}</label>
                            <input class="form-control" type="number" name="amount_transferred" id="amount_transferred" value="{{ old('amount_transferred', $receipt->amount_transferred) }}" step="0.01">
                            @if($errors->has('amount_transferred'))
                                <span class="help-block" role="alert">{{ $errors->first('amount_transferred') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.amount_transferred_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.receipt.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $receipt->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.receipt.fields.tvde_week_helper') }}</span>
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
    Dropzone.options.fileDropzone = {
    url: '{{ route('admin.receipts.storeMedia') }}',
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
@if(isset($receipt) && $receipt->file)
      var file = {!! json_encode($receipt->file) !!}
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
<script>console.log({!! $tvde_weeks !!})</script>