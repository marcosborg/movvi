@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            {{ $form_name->name }}
        </div>
        <form action="/admin/form-assemblies/send-form-data" method="post">
            @csrf
            <input type="hidden" name="form_name_id" value="{{ $form_name->id }}">
            <div class="panel-body">
                <div class="row">
                    @if ($form_name->has_driver)
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Driver</label>
                            <select name="driver_id" class="form-control select2" required>
                                <option selected disabled>Select</option>
                                @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    @if ($form_name->has_license)
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>License</label>
                            <select name="vehicle_item_id" class="form-control select2" required>
                                <option selected disabled>Select</option>
                                @foreach ($vehicle_items as $vehicle_item)
                                <option value="{{ $vehicle_item->id }}">{{ $vehicle_item->license_plate }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    @if ($form_name->has_technician)
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Técnico</label>
                            <select name="user_id" class="form-control select2" required>
                                <option selected disabled>Select</option>
                                @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    @foreach ($form_name->form_inputs as $form_input)
                    <div class="col-md-6">
                        @switch($form_input->type)
                        @case('text')
                        <div class="form-group item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}</label>
                            <input type="text" class="form-control" name="{{ $form_input->name }}" {{
                                $form_input->required
                            ?
                            'required' : ''
                            }}>
                        </div>
                        @break
                        @case('number')
                        <div class="form-group item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}</label>
                            <input type="number" class="form-control" name="{{ $form_input->name }}" {{
                                $form_input->required ?
                            'required' : ''
                            }}>
                        </div>
                        @break
                        @case('date')
                        <div class="form-group item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}</label>
                            <input type="date" class="form-control" name="{{ $form_input->name }}" {{
                                $form_input->required
                            ?
                            'required' : ''
                            }}>
                        </div>
                        @break
                        @case('textarea')
                        <div class="form-group item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <label>{{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}</label>
                            <textarea class="form-control" name="{{ $form_input->name }}" {{
                                $form_input->required ? 'required' : '' }}></textarea>
                        </div>
                        @break
                        @case('checkbox')
                        <div class="checkbox item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <label>
                                <input type="checkbox" name="{{ $form_input->name }}" {{ $form_input->required ?
                                'required' : '' }}> {{ $form_input->label }}{{ $form_input->required ? ' *' : '' }}
                            </label>
                        </div>
                        @break
                        @case('radio')
                        <div class="row item" data-position="{{ $form_input->position }}"
                            data-form_input_id="{{ $form_input->id }}">
                            <div class="col-md-12">
                                <label>{{ $form_input->label }}</label>
                            </div>
                            <div class="col-md-12">
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="{{ $form_input->name }}"
                                            id="{{ $form_input->name }}_1" value="Sim" checked>
                                        Sim
                                    </label>
                                </div>
                                <div class="radio disabled">
                                    <label>
                                        <input type="radio" name="{{ $form_input->name }}"
                                            id="{{ $form_input->name }}_2" value="Não">
                                        Não
                                    </label>
                                </div>
                            </div>
                        </div>
                        @break
                        @case('photos')
                        <label>{{ $form_input->label }}</label>
                        <input type="file" id="photo-{{ $form_input->id }}"
                            onchange="submitPhoto('{{ $form_input->id }}')">
                        <input type="hidden" name="photos-{{ $form_input->id }}">
                        <ul id="photo-list-{{ $form_input->id }}"></ul>
                        @break
                        @default
                        @endswitch
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" class="btn btn-success">Gravar</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('styles')
<style>
    label {
        width: 100%;
    }
</style>
@endsection
@if ($form_name)
@section('scripts')
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
@endsection
@endif