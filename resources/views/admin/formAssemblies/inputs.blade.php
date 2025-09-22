@foreach ($form_inputs as $form_input)
@switch($form_input->type)
@case('text')
<div class="form-group item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}<a onclick="return confirm('Are you sure?')"
            href="{{ route("admin.form-assemblies.delete-form-input", ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a></label>
    <input type="text" class="form-control" name="{{ $form_input->name }}" {{ $form_input->required ? 'required' : ''
    }}>
</div>
@break
@case('number')
<div class="form-group item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}<a onclick="return confirm('Are you sure?')"
            href="{{ route("admin.form-assemblies.delete-form-input", ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a></label>
    <input type="number" class="form-control" name="{{ $form_input->name }}" {{ $form_input->required ? 'required' : ''
    }}>
</div>
@break
@case('date')
<div class="form-group item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}<a onclick="return confirm('Are you sure?')"
            href="{{ route("admin.form-assemblies.delete-form-input", ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a></label>
    <input type="date" class="form-control" name="{{ $form_input->name }}" {{ $form_input->required ? 'required' : ''
    }}>
</div>
@break
@case('textarea')
<div class="form-group item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}<a onclick="return confirm('Are you sure?')"
            href="{{ route("admin.form-assemblies.delete-form-input", ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a></label>
    <textarea class="form-control" name="{{ $form_input->name }}" {{
        $form_input->required ? 'required' : '' }}></textarea>
</div>
@break
@case('checkbox')
<div class="checkbox item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <label>
        <input type="checkbox" name="{{ $form_input->name }}" {{ $form_input->required ?
        'required' : '' }}> {{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}<a
            onclick="return confirm('Are you sure?')" href="{{ route("admin.form-assemblies.delete-form-input",
            ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a>
    </label>
</div>
@break
@case('radio')
<div class="row item" data-position="{{ $form_input->position }}" data-form_input_id="{{ $form_input->id }}">
    <div class="col-md-12">
        <label>{{ $form_input->label }}</label>
    </div>
    <div class="col-md-8">
        <div class="radio">
            <label>
                <input type="radio" name="{{ $form_input->name }}" id="{{ $form_input->name }}_1" value="Sim" checked>
                Sim
            </label>
        </div>
        <div class="radio disabled">
            <label>
                <input type="radio" name="{{ $form_input->name }}" id="{{ $form_input->name }}_2" value="Não">
                Não
            </label>
        </div>
    </div>
    <div class="col-md-4">
        <a onclick="return confirm('Are you sure?')" href="{{ route("admin.form-assemblies.delete-form-input",
            ['form_input_id'=> $form_input->id]) }}"
            class="btn btn-sm btn-link pull-right"><span class="glyphicon glyphicon-trash"
                aria-hidden="true"></span></a>
    </div>
</div>
@break
@case('photos')
<label>{{ $form_input->label }}</label>
<input type="file" id="photo-{{ $form_input->id }}" onchange="submitPhoto('{{ $form_input->id }}')">
<input type="hidden" name="photos-{{ $form_input->id }}">
<ul id="photo-list-{{ $form_input->id }}"></ul>
@break
@default
@endswitch
@endforeach
<script>
    submitPhoto = (form_input_id) => {
        var input = document.getElementById('photo-' + form_input_id);
        var fileList = document.getElementById('photo-list-' + form_input_id);
        var formData = new FormData();
        var files = input.files;
        var photos = $('input[name=photos-' + form_input_id + ']').val();

        for (var i = 0; i < files.length; i++) {
            var li = document.createElement('li');
            li.textContent = files[i].name;
            fileList.appendChild(li);
            formData.append('file', files[i]);
        }
        $.ajax({
            url: '{{ route('admin.form-assemblies.store-media') }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                console.log(data);
                photos = photos + ',' + data.name;
                $('input[name=photos-' + form_input_id + ']').val(photos);
            },
            error: function (error) {
                console.error('Erro no upload:', error);
            }
        });
    }
</script>