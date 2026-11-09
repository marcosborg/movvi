@php($jsName = \Illuminate\Support\Str::camel($field))
<div class="form-group {{ $errors->has($field) ? 'has-error' : '' }}">
    <label for="{{ $field }}">{{ $label }} <span class="text-muted">(opcional)</span></label>
    <div class="needsclick dropzone" id="{{ str_replace('_', '-', $field) }}-dropzone"></div>
    @if($errors->has($field))
        <span class="help-block" role="alert">{{ $errors->first($field) }}</span>
    @endif
</div>
@include('admin.documents.partials.expiryDate', ['field' => $field.'_expires_at', 'label' => 'Validade de '.$label, 'document' => $document ?? null])

@section('scripts')
@parent
<script>
    var uploaded{{ ucfirst($jsName) }}Map = {}
    Dropzone.options.{{ $jsName }}Dropzone = {
        url: '{{ route('admin.documents.storeMedia') }}',
        maxFilesize: 2,
        addRemoveLinks: true,
        headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
        params: {size: 2},
        success: function (file, response) {
            $('form').append('<input type="hidden" name="{{ $field }}[]" value="' + response.name + '">')
            uploaded{{ ucfirst($jsName) }}Map[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = typeof file.file_name !== 'undefined' ? file.file_name : uploaded{{ ucfirst($jsName) }}Map[file.name]
            $('form').find('input[name="{{ $field }}[]"][value="' + name + '"]').remove()
        },
        init: function () {
            @if(isset($document) && $document->{$field})
                var files = {!! json_encode($document->{$field}) !!}
                for (var i in files) {
                    var file = files[i]
                    this.options.addedfile.call(this, file)
                    file.previewElement.classList.add('dz-complete')
                    $('form').append('<input type="hidden" name="{{ $field }}[]" value="' + file.file_name + '">')
                }
            @endif
        },
        error: function (file, response) {
            var message = $.type(response) === 'string' ? response : response.errors.file
            file.previewElement.classList.add('dz-error')
            file.previewElement.querySelector('[data-dz-errormessage]').textContent = message
        }
    }
</script>
@endsection
