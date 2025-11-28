@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.formName.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.form-names.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label class="required" for="name">{{ trans('cruds.formName.fields.name') }}</label>
                            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            @if($errors->has('name'))
                                <span class="help-block" role="alert">{{ $errors->first('name') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.name_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                            <label for="description">{{ trans('cruds.formName.fields.description') }}</label>
                            <textarea class="form-control ckeditor" name="description" id="description">{!! old('description') !!}</textarea>
                            @if($errors->has('description'))
                                <span class="help-block" role="alert">{{ $errors->first('description') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.description_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('has_driver') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="has_driver" value="0">
                                <input type="checkbox" name="has_driver" id="has_driver" value="1" {{ old('has_driver', 0) == 1 ? 'checked' : '' }}>
                                <label for="has_driver" style="font-weight: 400">{{ trans('cruds.formName.fields.has_driver') }}</label>
                            </div>
                            @if($errors->has('has_driver'))
                                <span class="help-block" role="alert">{{ $errors->first('has_driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.has_driver_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('has_license') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="has_license" value="0">
                                <input type="checkbox" name="has_license" id="has_license" value="1" {{ old('has_license', 0) == 1 ? 'checked' : '' }}>
                                <label for="has_license" style="font-weight: 400">{{ trans('cruds.formName.fields.has_license') }}</label>
                            </div>
                            @if($errors->has('has_license'))
                                <span class="help-block" role="alert">{{ $errors->first('has_license') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.has_license_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('has_technician') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="has_technician" value="0">
                                <input type="checkbox" name="has_technician" id="has_technician" value="1" {{ old('has_technician', 0) == 1 ? 'checked' : '' }}>
                                <label for="has_technician" style="font-weight: 400">{{ trans('cruds.formName.fields.has_technician') }}</label>
                            </div>
                            @if($errors->has('has_technician'))
                                <span class="help-block" role="alert">{{ $errors->first('has_technician') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.has_technician_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('roles') ? 'has-error' : '' }}">
                            <label for="roles">{{ trans('cruds.formName.fields.roles') }}</label>
                            <div style="padding-bottom: 4px">
                                <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                                <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                            </div>
                            <select class="form-control select2" name="roles[]" id="roles" multiple>
                                @foreach($roles as $id => $role)
                                    <option value="{{ $id }}" {{ in_array($id, old('roles', [])) ? 'selected' : '' }}>{{ $role }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('roles'))
                                <span class="help-block" role="alert">{{ $errors->first('roles') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.formName.fields.roles_helper') }}</span>
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
                xhr.open('POST', '{{ route('admin.form-names.storeCKEditorImages') }}', true);
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
                data.append('crud_id', '{{ $formName->id ?? 0 }}');
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