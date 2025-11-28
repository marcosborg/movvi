@extends('layouts.admin')
@section('content')
<div class="content">

  <div class="row">
    <div class="col-md-offset-4 col-lg-4">
      <div class="panel panel-default">
        <div class="panel-heading">
          {{ trans('global.create') }} {{ trans('cruds.registoEntradaVeiculo.title_singular') }}
        </div>
        <div class="panel-body">
          <form method="POST" action="{{ route("admin.registo-entrada-veiculos.store") }}"
            enctype="multipart/form-data">
            @csrf
            <div class="form-group {{ $errors->has('data_e_horario') ? 'has-error' : '' }}">
              <label for="data_e_horario">{{ trans('cruds.registoEntradaVeiculo.fields.data_e_horario') }}</label>
              <input class="form-control datetime" type="text" name="data_e_horario" id="data_e_horario"
                value="{{ old('data_e_horario') }}">
              @if($errors->has('data_e_horario'))
              <span class="help-block" role="alert">{{ $errors->first('data_e_horario') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.data_e_horario_helper') }}</span>
            </div>
            @if (auth()->user()->hasRole('tecnico'))
            <div class="form-group">
              <label>{{ trans('cruds.registoEntradaVeiculo.fields.user') }}</label>
              <select class="form-control select2" disabled>
                @foreach($users as $id => $entry)
                <option value="{{ $id }}" {{ auth()->user()->id == $id ? 'selected' : '' }}>{{ $entry }}</option>
                @endforeach
              </select>
              <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
            </div>
            @else
            <div class="form-group {{ $errors->has('user') ? 'has-error' : '' }}">
              <label class="required" for="user_id">{{ trans('cruds.registoEntradaVeiculo.fields.user') }}</label>
              <select class="form-control select2" name="user_id" id="user_id" required>
                @foreach($users as $id => $entry)
                <option value="{{ $id }}" {{ old('user_id')==$id ? 'selected' : '' }}>{{ $entry }}</option>
                @endforeach
              </select>
              @if($errors->has('user'))
              <span class="help-block" role="alert">{{ $errors->first('user') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.user_helper') }}</span>
            </div>
            @endif
            <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
              <label class="required" for="driver_id">{{ trans('cruds.registoEntradaVeiculo.fields.driver') }}</label>
              <select class="form-control select2" name="driver_id" id="driver_id" required>
                @foreach($drivers as $id => $entry)
                <option value="{{ $id }}" {{ old('driver_id')==$id ? 'selected' : '' }}>{{ $entry }}</option>
                @endforeach
              </select>
              @if($errors->has('driver'))
              <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.driver_helper') }}</span>
            </div>
            <div class="form-group {{ $errors->has('vehicle_item') ? 'has-error' : '' }}">
              <label class="required" for="vehicle_item_id">{{ trans('cruds.registoEntradaVeiculo.fields.vehicle_item')
                }}</label>
              <select class="form-control select2" name="vehicle_item_id" id="vehicle_item_id" required>
                @foreach($vehicle_items as $id => $entry)
                <option value="{{ $id }}" {{ old('vehicle_item_id')==$id ? 'selected' : '' }}>{{ $entry }}</option>
                @endforeach
              </select>
              @if($errors->has('vehicle_item'))
              <span class="help-block" role="alert">{{ $errors->first('vehicle_item') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.vehicle_item_helper') }}</span>
            </div>
            <div class="form-group {{ $errors->has('bateria_a_chegada') ? 'has-error' : '' }}">
              <label class="required" for="bateria_a_chegada">{{
                trans('cruds.registoEntradaVeiculo.fields.bateria_a_chegada') }}</label>
              <input class="form-control" type="number" name="bateria_a_chegada" id="bateria_a_chegada"
                value="{{ old('bateria_a_chegada', '') }}" step="1" required>
              @if($errors->has('bateria_a_chegada'))
              <span class="help-block" role="alert">{{ $errors->first('bateria_a_chegada') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.bateria_a_chegada_helper') }}</span>
            </div>
            <div class="form-group {{ $errors->has('de_bateria_de_saida') ? 'has-error' : '' }}">
              <label class="required" for="de_bateria_de_saida">{{
                trans('cruds.registoEntradaVeiculo.fields.de_bateria_de_saida') }}</label>
              <input class="form-control" type="number" name="de_bateria_de_saida" id="de_bateria_de_saida"
                value="{{ old('de_bateria_de_saida', '') }}" step="1" required>
              @if($errors->has('de_bateria_de_saida'))
              <span class="help-block" role="alert">{{ $errors->first('de_bateria_de_saida') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.de_bateria_de_saida_helper')
                }}</span>
            </div>
            <div class="form-group {{ $errors->has('km_atual') ? 'has-error' : '' }}">
              <label class="required" for="km_atual">{{ trans('cruds.registoEntradaVeiculo.fields.km_atual') }}</label>
              <input class="form-control" type="number" name="km_atual" id="km_atual" value="{{ old('km_atual', '') }}"
                step="1" required>
              @if($errors->has('km_atual'))
              <span class="help-block" role="alert">{{ $errors->first('km_atual') }}</span>
              @endif
              <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.km_atual_helper') }}</span>
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
  var uploadedFrenteDoVeiculoTetoPhotosMap = {}
Dropzone.options.frenteDoVeiculoTetoPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="frente_do_veiculo_teto_photos[]" value="' + response.name + '">')
      uploadedFrenteDoVeiculoTetoPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedFrenteDoVeiculoTetoPhotosMap[file.name]
      }
      $('form').find('input[name="frente_do_veiculo_teto_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->frente_do_veiculo_teto_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->frente_do_veiculo_teto_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="frente_do_veiculo_teto_photos[]" value="' + file.file_name + '">')
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
  var uploadedFrenteDoVeiculoParabrisaPhotosMap = {}
Dropzone.options.frenteDoVeiculoParabrisaPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="frente_do_veiculo_parabrisa_photos[]" value="' + response.name + '">')
      uploadedFrenteDoVeiculoParabrisaPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedFrenteDoVeiculoParabrisaPhotosMap[file.name]
      }
      $('form').find('input[name="frente_do_veiculo_parabrisa_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="frente_do_veiculo_parabrisa_photos[]" value="' + file.file_name + '">')
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
  var uploadedFrenteDoVeiculoCapoPhotosMap = {}
Dropzone.options.frenteDoVeiculoCapoPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="frente_do_veiculo_capo_photos[]" value="' + response.name + '">')
      uploadedFrenteDoVeiculoCapoPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedFrenteDoVeiculoCapoPhotosMap[file.name]
      }
      $('form').find('input[name="frente_do_veiculo_capo_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->frente_do_veiculo_capo_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->frente_do_veiculo_capo_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="frente_do_veiculo_capo_photos[]" value="' + file.file_name + '">')
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
  var uploadedFrenteDoVeiculoParachoquePhotosMap = {}
Dropzone.options.frenteDoVeiculoParachoquePhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="frente_do_veiculo_parachoque_photos[]" value="' + response.name + '">')
      uploadedFrenteDoVeiculoParachoquePhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedFrenteDoVeiculoParachoquePhotosMap[file.name]
      }
      $('form').find('input[name="frente_do_veiculo_parachoque_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->frente_do_veiculo_parachoque_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->frente_do_veiculo_parachoque_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="frente_do_veiculo_parachoque_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralEsquerdaParalamaDiantPhotosMap = {}
Dropzone.options.lateralEsquerdaParalamaDiantPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_esquerda_paralama_diant_photos[]" value="' + response.name + '">')
      uploadedLateralEsquerdaParalamaDiantPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralEsquerdaParalamaDiantPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_esquerda_paralama_diant_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_esquerda_paralama_diant_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralEsquerdaRetrovisorPhotosMap = {}
Dropzone.options.lateralEsquerdaRetrovisorPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_esquerda_retrovisor_photos[]" value="' + response.name + '">')
      uploadedLateralEsquerdaRetrovisorPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralEsquerdaRetrovisorPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_esquerda_retrovisor_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_esquerda_retrovisor_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_esquerda_retrovisor_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_esquerda_retrovisor_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralEsquerdaPortaDiantPhotosMap = {}
Dropzone.options.lateralEsquerdaPortaDiantPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_esquerda_porta_diant_photos[]" value="' + response.name + '">')
      uploadedLateralEsquerdaPortaDiantPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralEsquerdaPortaDiantPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_esquerda_porta_diant_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_esquerda_porta_diant_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_esquerda_porta_diant_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_esquerda_porta_diant_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralEsquerdaPortaTrasPhotosMap = {}
Dropzone.options.lateralEsquerdaPortaTrasPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_esquerda_porta_tras_photos[]" value="' + response.name + '">')
      uploadedLateralEsquerdaPortaTrasPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralEsquerdaPortaTrasPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_esquerda_porta_tras_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_esquerda_porta_tras_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_esquerda_porta_tras_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_esquerda_porta_tras_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralEsquerdaLateralPhotosMap = {}
Dropzone.options.lateralEsquerdaLateralPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_esquerda_lateral_photos[]" value="' + response.name + '">')
      uploadedLateralEsquerdaLateralPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralEsquerdaLateralPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_esquerda_lateral_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_esquerda_lateral_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_esquerda_lateral_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_esquerda_lateral_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraTampaTraseiraPhotosMap = {}
Dropzone.options.traseiraTampaTraseiraPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_tampa_traseira_photos[]" value="' + response.name + '">')
      uploadedTraseiraTampaTraseiraPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraTampaTraseiraPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_tampa_traseira_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_tampa_traseira_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_tampa_traseira_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_tampa_traseira_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraLanternasDirPhotosMap = {}
Dropzone.options.traseiraLanternasDirPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_lanternas_dir_photos[]" value="' + response.name + '">')
      uploadedTraseiraLanternasDirPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraLanternasDirPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_lanternas_dir_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_lanternas_dir_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_lanternas_dir_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_lanternas_dir_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraLanternaEsqPhotosMap = {}
Dropzone.options.traseiraLanternaEsqPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_lanterna_esq_photos[]" value="' + response.name + '">')
      uploadedTraseiraLanternaEsqPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraLanternaEsqPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_lanterna_esq_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_lanterna_esq_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_lanterna_esq_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_lanterna_esq_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraParachoqueTrasPhotosMap = {}
Dropzone.options.traseiraParachoqueTrasPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_parachoque_tras_photos[]" value="' + response.name + '">')
      uploadedTraseiraParachoqueTrasPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraParachoqueTrasPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_parachoque_tras_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_parachoque_tras_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_parachoque_tras_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_parachoque_tras_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraEstepePhotosMap = {}
Dropzone.options.traseiraEstepePhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_estepe_photos[]" value="' + response.name + '">')
      uploadedTraseiraEstepePhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraEstepePhotosMap[file.name]
      }
      $('form').find('input[name="traseira_estepe_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_estepe_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_estepe_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_estepe_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraMacacoPhotosMap = {}
Dropzone.options.traseiraMacacoPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_macaco_photos[]" value="' + response.name + '">')
      uploadedTraseiraMacacoPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraMacacoPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_macaco_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_macaco_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_macaco_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_macaco_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraChaveDeRodaPhotosMap = {}
Dropzone.options.traseiraChaveDeRodaPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_chave_de_roda_photos[]" value="' + response.name + '">')
      uploadedTraseiraChaveDeRodaPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraChaveDeRodaPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_chave_de_roda_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_chave_de_roda_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_chave_de_roda_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_chave_de_roda_photos[]" value="' + file.file_name + '">')
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
  var uploadedTraseiraTrianguloPhotosMap = {}
Dropzone.options.traseiraTrianguloPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="traseira_triangulo_photos[]" value="' + response.name + '">')
      uploadedTraseiraTrianguloPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedTraseiraTrianguloPhotosMap[file.name]
      }
      $('form').find('input[name="traseira_triangulo_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->traseira_triangulo_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->traseira_triangulo_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="traseira_triangulo_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralDireitaLateralPhotosMap = {}
Dropzone.options.lateralDireitaLateralPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_direita_lateral_photos[]" value="' + response.name + '">')
      uploadedLateralDireitaLateralPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralDireitaLateralPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_direita_lateral_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_direita_lateral_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_direita_lateral_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_direita_lateral_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralDireitaPortaTrasPhotosMap = {}
Dropzone.options.lateralDireitaPortaTrasPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_direita_porta_tras_photos[]" value="' + response.name + '">')
      uploadedLateralDireitaPortaTrasPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralDireitaPortaTrasPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_direita_porta_tras_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_direita_porta_tras_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_direita_porta_tras_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_direita_porta_tras_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralDireitaPortaDiantPhotosMap = {}
Dropzone.options.lateralDireitaPortaDiantPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_direita_porta_diant_photos[]" value="' + response.name + '">')
      uploadedLateralDireitaPortaDiantPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralDireitaPortaDiantPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_direita_porta_diant_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_direita_porta_diant_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_direita_porta_diant_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_direita_porta_diant_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralDireitaRetrovisorPhotosMap = {}
Dropzone.options.lateralDireitaRetrovisorPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_direita_retrovisor_photos[]" value="' + response.name + '">')
      uploadedLateralDireitaRetrovisorPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralDireitaRetrovisorPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_direita_retrovisor_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_direita_retrovisor_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_direita_retrovisor_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_direita_retrovisor_photos[]" value="' + file.file_name + '">')
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
  var uploadedLateralDireitaParalamaDiantPhotosMap = {}
Dropzone.options.lateralDireitaParalamaDiantPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="lateral_direita_paralama_diant_photos[]" value="' + response.name + '">')
      uploadedLateralDireitaParalamaDiantPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedLateralDireitaParalamaDiantPhotosMap[file.name]
      }
      $('form').find('input[name="lateral_direita_paralama_diant_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->lateral_direita_paralama_diant_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->lateral_direita_paralama_diant_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="lateral_direita_paralama_diant_photos[]" value="' + file.file_name + '">')
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
  var uploadedCinzeiroPhotosMap = {}
Dropzone.options.cinzeiroPhotosDropzone = {
    url: '{{ route('admin.registo-entrada-veiculos.storeMedia') }}',
    maxFilesize: 5, // MB
    addRemoveLinks: true,
    headers: {
      'X-CSRF-TOKEN': "{{ csrf_token() }}"
    },
    params: {
      size: 5
    },
    success: function (file, response) {
      $('form').append('<input type="hidden" name="cinzeiro_photos[]" value="' + response.name + '">')
      uploadedCinzeiroPhotosMap[file.name] = response.name
    },
    removedfile: function (file) {
      file.previewElement.remove()
      var name = ''
      if (typeof file.file_name !== 'undefined') {
        name = file.file_name
      } else {
        name = uploadedCinzeiroPhotosMap[file.name]
      }
      $('form').find('input[name="cinzeiro_photos[]"][value="' + name + '"]').remove()
    },
    init: function () {
@if(isset($registoEntradaVeiculo) && $registoEntradaVeiculo->cinzeiro_photos)
          var files =
            {!! json_encode($registoEntradaVeiculo->cinzeiro_photos) !!}
              for (var i in files) {
              var file = files[i]
              this.options.addedfile.call(this, file)
              file.previewElement.classList.add('dz-complete')
              $('form').append('<input type="hidden" name="cinzeiro_photos[]" value="' + file.file_name + '">')
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