@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Nova inspeção</div>
                <div class="panel-body">
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.inspections.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="required">Tipo</label>
                                <select name="type" class="form-control" required>
                                    @foreach(config('inspections.types', []) as $typeKey)
                                        <option value="{{ $typeKey }}" {{ old('type') === $typeKey ? 'selected' : '' }}>
                                            {{ config('inspections.type_labels.' . $typeKey, $typeKey) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label class="required">Viatura</label>
                                <select name="vehicle_id" id="vehicle_id" class="form-control select2" required>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}" data-driver="{{ $v->driver_id }}" {{ (string)old('vehicle_id') === (string)$v->id ? 'selected' : '' }}>
                                            {{ $v->license_plate }} - {{ $v->vehicle_brand->name ?? '' }} {{ $v->vehicle_model->name ?? '' }} ({{ $v->year }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Condutor</label>
                            <select name="driver_id" id="driver_id" class="form-control select2">
                                @foreach($drivers as $id => $name)
                                    <option value="{{ $id }}" {{ (string)old('driver_id') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Local (texto)</label>
                            <input type="text" id="location_text" name="location_text" class="form-control" value="{{ old('location_text') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-3"><div class="form-group"><label>Latitude</label><input type="text" id="location_lat" name="location_lat" class="form-control" value="{{ old('location_lat') }}"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Longitude</label><input type="text" id="location_lng" name="location_lng" class="form-control" value="{{ old('location_lng') }}"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Precisão (m)</label><input type="text" id="location_accuracy" name="location_accuracy" class="form-control" value="{{ old('location_accuracy') }}"></div></div>
                            <div class="col-md-3"><div class="form-group"><label>Timezone</label><input type="text" name="location_timezone" class="form-control" value="{{ old('location_timezone', 'Europe/Lisbon') }}"></div></div>
                        </div>

                        <button type="button" class="btn btn-default" id="btn-geolocation">Usar geolocalização do browser</button>
                        <button class="btn btn-danger" type="submit">Iniciar inspeção</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
(function() {
    const vehicle = document.getElementById('vehicle_id');
    const driver = document.getElementById('driver_id');
    const geoBtn = document.getElementById('btn-geolocation');

    if (vehicle && driver) {
        vehicle.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const driverId = selected ? selected.getAttribute('data-driver') : '';
            if (driverId) {
                driver.value = driverId;
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(driver).trigger('change');
                }
            }
        });
    }

    if (geoBtn && navigator.geolocation) {
        geoBtn.addEventListener('click', function () {
            navigator.geolocation.getCurrentPosition(function (position) {
                document.getElementById('location_lat').value = position.coords.latitude.toFixed(7);
                document.getElementById('location_lng').value = position.coords.longitude.toFixed(7);
                document.getElementById('location_accuracy').value = position.coords.accuracy.toFixed(2);
            }, function () {
                alert('Não foi possível obter geolocalização.');
            });
        });
    }
})();
</script>
@endsection
