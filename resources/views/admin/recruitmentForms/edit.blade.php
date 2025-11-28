@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.recruitmentForm.title_singular') }}
                </div>
                <form method="POST" action="{{ route("admin.recruitment-forms.update", [$recruitmentForm->id]) }}"
                    enctype="multipart/form-data">
                    <div class="panel-body">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('company') ? 'has-error' : '' }}">
                                    <label for="company_id">{{ trans('cruds.recruitmentForm.fields.company') }}</label>
                                    <select class="form-control select2" name="company_id" id="company_id">
                                        @foreach($companies as $id => $entry)
                                        <option value="{{ $id }}" {{ (old('company_id') ? old('company_id') :
                                            $recruitmentForm->
                                            company->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('company'))
                                    <span class="help-block" role="alert">{{ $errors->first('company') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.recruitmentForm.fields.company_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                                    <label class="required" for="name">{{ trans('cruds.recruitmentForm.fields.name')
                                        }}</label>
                                    <input class="form-control" type="text" name="name" id="name"
                                        value="{{ old('name', $recruitmentForm->name) }}" required>
                                    @if($errors->has('name'))
                                    <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.recruitmentForm.fields.name_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
                                    <label class="required" for="email">{{ trans('cruds.recruitmentForm.fields.email')
                                        }}</label>
                                    <input class="form-control" type="email" name="email" id="email"
                                        value="{{ old('email', $recruitmentForm->email) }}" required>
                                    @if($errors->has('email'))
                                    <span class="help-block" role="alert">{{ $errors->first('email') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.recruitmentForm.fields.email_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('cv') ? 'has-error' : '' }}">
                                    <label for="cv">{{ trans('cruds.recruitmentForm.fields.cv') }}</label>
                                    <div class="needsclick dropzone" id="cv-dropzone">
                                    </div>
                                    @if($errors->has('cv'))
                                    <span class="help-block" role="alert">{{ $errors->first('cv') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.recruitmentForm.fields.cv_helper')
                                        }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('comments') ? 'has-error' : '' }}">
                                    <label for="comments">{{ trans('cruds.recruitmentForm.fields.comments') }}</label>
                                    <textarea class="form-control ckeditor" name="comments"
                                        id="comments">{!! old('comments', $recruitmentForm->comments) !!}</textarea>
                                    @if($errors->has('comments'))
                                    <span class="help-block" role="alert">{{ $errors->first('comments') }}</span>
                                    @endif
                                    <span class="help-block">{{ trans('cruds.recruitmentForm.fields.comments_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Informação sobre a entrevista</label>
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div
                                            class="form-group {{ $errors->has('contact_successfully') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="contact_successfully" value="0">
                                                <input type="checkbox" name="contact_successfully"
                                                    id="contact_successfully" value="1" {{
                                                    $recruitmentForm->contact_successfully ||
                                                old('contact_successfully', 0) === 1 ?
                                                'checked' : '' }}>
                                                <label for="contact_successfully" style="font-weight: 400">{{
                                                    trans('cruds.recruitmentForm.fields.contact_successfully')
                                                    }}</label>
                                            </div>
                                            @if($errors->has('contact_successfully'))
                                            <span class="help-block" role="alert">{{
                                                $errors->first('contact_successfully') }}</span>
                                            @endif
                                            <span class="help-block">{{
                                                trans('cruds.recruitmentForm.fields.contact_successfully_helper')
                                                }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('phone') ? 'has-error' : '' }}">
                                            <label class="required" for="phone">{{
                                                trans('cruds.recruitmentForm.fields.phone')
                                                }}</label>
                                            <input class="form-control" type="text" name="phone" id="phone"
                                                value="{{ old('phone', $recruitmentForm->phone) }}" required>
                                            @if($errors->has('phone'))
                                            <span class="help-block" role="alert">{{ $errors->first('phone') }}</span>
                                            @endif
                                            <span class="help-block">{{
                                                trans('cruds.recruitmentForm.fields.phone_helper') }}</span>
                                        </div>
                                        <div
                                            class="form-group {{ $errors->has('scheduled_interview') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="scheduled_interview" value="0">
                                                <input type="checkbox" name="scheduled_interview"
                                                    id="scheduled_interview" value="1" {{
                                                    $recruitmentForm->scheduled_interview || old('scheduled_interview',
                                                0) === 1 ?
                                                'checked' : '' }}>
                                                <label for="scheduled_interview" style="font-weight: 400">{{
                                                    trans('cruds.recruitmentForm.fields.scheduled_interview') }}</label>
                                            </div>
                                            @if($errors->has('scheduled_interview'))
                                            <span class="help-block" role="alert">{{
                                                $errors->first('scheduled_interview') }}</span>
                                            @endif
                                            <span class="help-block">{{
                                                trans('cruds.recruitmentForm.fields.scheduled_interview_helper')
                                                }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('appointment') ? 'has-error' : '' }}">
                                            <label for="appointment">{{
                                                trans('cruds.recruitmentForm.fields.appointment') }}</label>
                                            <input class="form-control datetime" type="text" name="appointment"
                                                id="appointment"
                                                value="{{ old('appointment', $recruitmentForm->appointment) }}">
                                            @if($errors->has('appointment'))
                                            <span class="help-block" role="alert">{{ $errors->first('appointment')
                                                }}</span>
                                            @endif
                                            <span class="help-block">{{
                                                trans('cruds.recruitmentForm.fields.appointment_helper')
                                                }}</span>
                                        </div>
                                        <div class="form-group {{ $errors->has('done') ? 'has-error' : '' }}">
                                            <div>
                                                <input type="hidden" name="done" value="0">
                                                <input type="checkbox" name="done" id="done" value="1" {{
                                                    $recruitmentForm->done ||
                                                old('done', 0) === 1 ? 'checked' : '' }}>
                                                <label for="done" style="font-weight: 400">{{
                                                    trans('cruds.recruitmentForm.fields.done')
                                                    }}</label>
                                            </div>
                                            @if($errors->has('done'))
                                            <span class="help-block" role="alert">{{ $errors->first('done') }}</span>
                                            @endif
                                            <span class="help-block">{{
                                                trans('cruds.recruitmentForm.fields.done_helper') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    Dropzone.options.cvDropzone = {
    url: '{{ route('admin.recruitment-forms.storeMedia') }}',
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
      $('form').find('input[name="cv"]').remove()
      $('form').append('<input type="hidden" name="cv" value="' + response.name + '">')
    },
    removedfile: function (file) {
      file.previewElement.remove()
      if (file.status !== 'error') {
        $('form').find('input[name="cv"]').remove()
        this.options.maxFiles = this.options.maxFiles + 1
      }
    },
    init: function () {
@if(isset($recruitmentForm) && $recruitmentForm->cv)
      var file = {!! json_encode($recruitmentForm->cv) !!}
          this.options.addedfile.call(this, file)
      file.previewElement.classList.add('dz-complete')
      $('form').append('<input type="hidden" name="cv" value="' + file.file_name + '">')
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
<script>
    $(document).ready(function () {
  function SimpleUploadAdapter(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
      return {
        upload: function() {
          return loader.file
            .then(function (file) {
              return new Promise(function(resolve, reject) {
                // Init request
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route('admin.recruitment-forms.storeCKEditorImages') }}', true);
                xhr.setRequestHeader('x-csrf-token', window._token);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.responseType = 'json';

                // Init listeners
                var genericErrorText = `Couldn't upload file: ${ file.name }.`;
                xhr.addEventListener('error', function() { reject(genericErrorText) });
                xhr.addEventListener('abort', function() { reject() });
                xhr.addEventListener('load', function() {
                  var response = xhr.response;

                  if (!response || xhr.status !== 201) {
                    return reject(response && response.message ? `${genericErrorText}\n${xhr.status} ${response.message}` : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                  }

                  $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');

                  resolve({ default: response.url });
                });

                if (xhr.upload) {
                  xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                      loader.uploadTotal = e.total;
                      loader.uploaded = e.loaded;
                    }
                  });
                }

                // Send request
                var data = new FormData();
                data.append('upload', file);
                data.append('crud_id', '{{ $recruitmentForm->id ?? 0 }}');
                xhr.send(data);
              });
            })
        }
      };
    }
  }

  var allEditors = document.querySelectorAll('.ckeditor');
  for (var i = 0; i < allEditors.length; ++i) {
    ClassicEditor.create(
      allEditors[i], {
        extraPlugins: [SimpleUploadAdapter]
      }
    );
  }
});
</script>

@endsection