@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.adminContract.title_singular') }}
                </div>
                <div class="panel-body">
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route("admin.admin-contracts.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="alert alert-info">Confirme os dados abaixo antes de gerar. O número será atribuído automaticamente, será usada a versão {{ $template->version }} do modelo ativo e ficará guardada uma cópia imutável.</div>
                        <input type="hidden" name="contract_template_id" value="{{ $template->id }}">
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{ trans('cruds.adminContract.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                <option selected disabled>{{ trans('global.pleaseSelect') }}</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ (string) old('driver_id', $selectedDriverId) === (string) $driver->id ? 'selected' : '' }}>{{ $driver->name }} - NIF: {{ $driver->driver_vat }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                                <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adminContract.fields.driver_helper') }}</span>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Condições financeiras e viatura</strong></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-4 form-group {{ $errors->has('weekly_cedencia') ? 'has-error' : '' }}">
                                        <label for="weekly_cedencia">Valor semanal da cedência (€)</label>
                                        <input class="form-control" type="number" step="0.01" min="0" name="weekly_cedencia" id="weekly_cedencia" value="{{ old('weekly_cedencia', $defaults['weekly_cedencia'] ?? '') }}">
                                        <span class="help-block">Deixe vazio nos contratos apenas por comissão.</span>
                                    </div>
                                    <div class="col-md-4 form-group {{ $errors->has('commission_percent') ? 'has-error' : '' }}">
                                        <label for="commission_percent">Percentagem de comissão (%)</label>
                                        <input class="form-control" type="number" step="0.01" min="0" max="100" name="commission_percent" id="commission_percent" value="{{ old('commission_percent', $defaults['commission_percent'] ?? '') }}">
                                        <span class="help-block">É preenchida a partir do contrato do motorista e pode ser confirmada aqui.</span>
                                    </div>
                                    <div class="col-md-4 form-group {{ $errors->has('vehicle_year') ? 'has-error' : '' }}">
                                        <label class="required" for="vehicle_year">Ano da viatura</label>
                                        <input class="form-control" type="number" min="1900" max="{{ now()->year + 1 }}" name="vehicle_year" id="vehicle_year" value="{{ old('vehicle_year', $defaults['vehicle_year'] ?? '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>Validade dos documentos</strong></div>
                            <div class="panel-body">
                                @php
                                    $expiryFields = [
                                        'citizen_card_expires_at' => ['Documento de identificação', true],
                                        'tvde_driver_certificate_expires_at' => ['Certificado de motorista TVDE', true],
                                        'driving_license_expires_at' => ['Carta de condução', true],
                                        'passport_expires_at' => ['Passaporte (quando aplicável)', false],
                                        'residence_permit_expires_at' => ['Autorização de residência (quando aplicável)', false],
                                    ];
                                @endphp
                                <div class="row">
                                    @foreach($expiryFields as $field => [$label, $required])
                                        <div class="col-md-4 form-group {{ $errors->has('document_expiries.'.$field) ? 'has-error' : '' }}">
                                            <label class="{{ $required ? 'required' : '' }}" for="{{ $field }}">{{ $label }}</label>
                                            <input class="form-control date" type="text" name="document_expiries[{{ $field }}]" id="{{ $field }}" value="{{ old('document_expiries.'.$field, data_get($defaults, 'document_expiries.'.$field)) }}" {{ $required ? 'required' : '' }}>
                                            @if($errors->has('document_expiries.'.$field))
                                                <span class="help-block" role="alert">{{ $errors->first('document_expiries.'.$field) }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('signed_at') ? 'has-error' : '' }}">
                            <label for="signed_at">{{ trans('cruds.adminContract.fields.signed_at') }}</label>
                            <input class="form-control date" type="text" name="signed_at" id="signed_at" value="{{ old('signed_at') }}">
                            @if($errors->has('signed_at'))
                                <span class="help-block" role="alert">{{ $errors->first('signed_at') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.adminContract.fields.signed_at_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                Gerar e arquivar contrato
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
@parent
<script>
    $('#driver_id').on('change', function () {
        if (this.value) {
            window.location = @json(route('admin.admin-contracts.create')) + '?driver_id=' + encodeURIComponent(this.value);
        }
    });
</script>
@endsection
