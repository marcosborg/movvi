@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <a class="btn btn-primary btn-sm pull-right" href="{{ route('admin.registo-entrada-veiculos.photos', [$registoEntradaVeiculo->vehicle_item->id]) }}">
                Todas as fotografias da viatura
            </a>
        </div>
    </div>
    <form method="POST" action="{{ route("admin.registo-entrada-veiculos.update", [$registoEntradaVeiculo->id]) }}"
        enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Dados de entrada
                    </div>
                    <div class="panel-body">
                        @if (auth()->user()->hasRole('tecnico'))
                        <div class="form-group">
                            <label>{{ trans('cruds.registoEntradaVeiculo.fields.data_e_horario') }}</label>
                            <input class="form-control datetime" type="text" disabled
                                value="{{ $registoEntradaVeiculo->data_e_horario }}">
                            <input type="hidden" name="data_e_horario" id="data_e_horario"
                                value="{{ $registoEntradaVeiculo->data_e_horario }}">
                        </div>
                        <div class="form-group">
                            <label>{{ trans('cruds.registoEntradaVeiculo.fields.user')
                                }}</label>
                            <select class="form-control select2" disabled>
                                @foreach($users as $id => $entry)
                                <option value="{{ $id }}" {{ (old('user_id') ? old('user_id') : $registoEntradaVeiculo->
                                    user->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="user_id" id="user_id"
                                value="{{ $registoEntradaVeiculo->user->id }}">
                        </div>
                        @else
                        <div class="form-group {{ $errors->has('data_e_horario') ? 'has-error' : '' }}">
                            <label for="data_e_horario">{{ trans('cruds.registoEntradaVeiculo.fields.data_e_horario')
                                }}</label>
                            <input class="form-control datetime" type="text" name="data_e_horario" id="data_e_horario"
                                value="{{ old('data_e_horario', $registoEntradaVeiculo->data_e_horario) }}">
                            @if($errors->has('data_e_horario'))
                            <span class="help-block" role="alert">{{ $errors->first('data_e_horario') }}</span>
                            @endif
                            <span class="help-block">{{
                                trans('cruds.registoEntradaVeiculo.fields.data_e_horario_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('user') ? 'has-error' : '' }}">
                            <label class="required" for="user_id">{{ trans('cruds.registoEntradaVeiculo.fields.user')
                                }}</label>
                            <select class="form-control select2" name="user_id" id="user_id" required>
                                @foreach($users as $id => $entry)
                                <option value="{{ $id }}" {{ (old('user_id') ? old('user_id') : $registoEntradaVeiculo->
                                    user->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('user'))
                            <span class="help-block" role="alert">{{ $errors->first('user') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.user_helper')
                                }}</span>
                        </div>
                        @endif
                        <div class="form-group {{ $errors->has('driver') ? 'has-error' : '' }}">
                            <label class="required" for="driver_id">{{
                                trans('cruds.registoEntradaVeiculo.fields.driver') }}</label>
                            <select class="form-control select2" name="driver_id" id="driver_id" required>
                                @foreach($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') :
                                    $registoEntradaVeiculo->driver->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}
                                </option>
                                @endforeach
                            </select>
                            @if($errors->has('driver'))
                            <span class="help-block" role="alert">{{ $errors->first('driver') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.driver_helper')
                                }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('vehicle_item') ? 'has-error' : '' }}">
                            <label class="required" for="vehicle_item_id">{{
                                trans('cruds.registoEntradaVeiculo.fields.vehicle_item') }}</label>
                            <select class="form-control select2" name="vehicle_item_id" id="vehicle_item_id" required>
                                @foreach($vehicle_items as $id => $entry)
                                <option value="{{ $id }}" {{ (old('vehicle_item_id') ? old('vehicle_item_id') :
                                    $registoEntradaVeiculo->vehicle_item->id ?? '') == $id ? 'selected' : '' }}>{{
                                    $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('vehicle_item'))
                            <span class="help-block" role="alert">{{ $errors->first('vehicle_item') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.vehicle_item_helper')
                                }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('bateria_a_chegada') ? 'has-error' : '' }}">
                            <label class="required" for="bateria_a_chegada">{{
                                trans('cruds.registoEntradaVeiculo.fields.bateria_a_chegada') }}</label>
                            <input class="form-control" type="number" name="bateria_a_chegada" id="bateria_a_chegada"
                                value="{{ old('bateria_a_chegada', $registoEntradaVeiculo->bateria_a_chegada) }}"
                                step="1" required>
                            @if($errors->has('bateria_a_chegada'))
                            <span class="help-block" role="alert">{{ $errors->first('bateria_a_chegada') }}</span>
                            @endif
                            <span class="help-block">{{
                                trans('cruds.registoEntradaVeiculo.fields.bateria_a_chegada_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('de_bateria_de_saida') ? 'has-error' : '' }}">
                            <label class="required" for="de_bateria_de_saida">{{
                                trans('cruds.registoEntradaVeiculo.fields.de_bateria_de_saida') }}</label>
                            <input class="form-control" type="number" name="de_bateria_de_saida"
                                id="de_bateria_de_saida"
                                value="{{ old('de_bateria_de_saida', $registoEntradaVeiculo->de_bateria_de_saida) }}"
                                step="1" required>
                            @if($errors->has('de_bateria_de_saida'))
                            <span class="help-block" role="alert">{{ $errors->first('de_bateria_de_saida') }}</span>
                            @endif
                            <span class="help-block">{{
                                trans('cruds.registoEntradaVeiculo.fields.de_bateria_de_saida_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('km_atual') ? 'has-error' : '' }}">
                            <label class="required" for="km_atual">{{
                                trans('cruds.registoEntradaVeiculo.fields.km_atual') }}</label>
                            <input class="form-control" type="number" name="km_atual" id="km_atual"
                                value="{{ old('km_atual', $registoEntradaVeiculo->km_atual) }}" step="1" required>
                            @if($errors->has('km_atual'))
                            <span class="help-block" role="alert">{{ $errors->first('km_atual') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.km_atual_helper')
                                }}</span>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit" name="step" value="1">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </div>
                </div>
                @if (auth()->user()->hasRole('admin'))
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        Gestão
                    </div>
                    <div class="panel-body">
                        <div class="form-group {{ $errors->has('tratado') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="tratado" value="0">
                                <input type="checkbox" name="tratado" id="tratado" value="1" {{
                                    $registoEntradaVeiculo->tratado || old('tratado', 0) === 1 ? 'checked' : '' }}>
                                <label for="tratado" style="font-weight: 400">{{
                                    trans('cruds.registoEntradaVeiculo.fields.tratado') }}</label>
                            </div>
                            @if($errors->has('tratado'))
                            <span class="help-block" role="alert">{{ $errors->first('tratado') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.tratado_helper')
                                }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('comentarios') ? 'has-error' : '' }}">
                            <label for="comentarios">{{ trans('cruds.registoEntradaVeiculo.fields.comentarios')
                                }}</label>
                            <textarea class="form-control" name="comentarios"
                                id="comentarios">{{ old('comentarios', $registoEntradaVeiculo->comentarios) }}</textarea>
                            @if($errors->has('comentarios'))
                            <span class="help-block" role="alert">{{ $errors->first('comentarios') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.comentarios_helper')
                                }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('reparado') ? 'has-error' : '' }}">
                            <div>
                                <input type="hidden" name="reparado" value="0">
                                <input type="checkbox" name="reparado" id="reparado" value="1" {{
                                    $registoEntradaVeiculo->reparado || old('reparado', 0) === 1 ? 'checked' : '' }}>
                                <label for="reparado" style="font-weight: 400">{{
                                    trans('cruds.registoEntradaVeiculo.fields.reparado') }}</label>
                            </div>
                            @if($errors->has('reparado'))
                            <span class="help-block" role="alert">{{ $errors->first('reparado') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.registoEntradaVeiculo.fields.reparado_helper')
                                }}</span>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-lg-9">
                <ul class="nav nav-tabs">
                    <li role="presentation" {{ request()->query('step') == 1 ? 'class=active' : '' }}><a>1.º Check de
                            Danos Visiveis do Carro</a></li>
                    <li role="presentation" {{ request()->query('step') == 2 ? 'class=active' : '' }}><a>2.º Checkagem
                            de aspiração(10 minutos)</a></li>
                    <li role="presentation" {{ request()->query('step') == 3 ? 'class=active' : '' }}><a>3.º
                            Documentação</a></li>
                    <li role="presentation" {{ request()->query('step') == 4 ? 'class=active' : '' }}><a>4.º Checkagem
                            de lavagem</a></li>
                </ul>
                @if (request()->query('step') == 1)
                <input type="hidden" name="has_photos" value="true">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingOne">
                                    <h4 class="panel-title">
                                        <a role="button" data-toggle="collapse" data-parent="#accordion"
                                            href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            Frente
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseOne" class="panel-collapse collapse" role="tabpanel"
                                    aria-labelledby="headingOne">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_teto') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="frente_do_veiculo_teto" value="0">
                                                        <input type="checkbox" name="frente_do_veiculo_teto"
                                                            id="frente_do_veiculo_teto" value="1" {{
                                                            $registoEntradaVeiculo->frente_do_veiculo_teto ||
                                                        old('frente_do_veiculo_teto', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="frente_do_veiculo_teto" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_teto')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_teto'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_teto')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_teto_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_parabrisa') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="frente_do_veiculo_parabrisa"
                                                            value="0">
                                                        <input type="checkbox" name="frente_do_veiculo_parabrisa"
                                                            id="frente_do_veiculo_parabrisa" value="1" {{
                                                            $registoEntradaVeiculo->frente_do_veiculo_parabrisa ||
                                                        old('frente_do_veiculo_parabrisa', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="frente_do_veiculo_parabrisa"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parabrisa')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_parabrisa'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_parabrisa')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parabrisa_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_capo') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="frente_do_veiculo_capo" value="0">
                                                        <input type="checkbox" name="frente_do_veiculo_capo"
                                                            id="frente_do_veiculo_capo" value="1" {{
                                                            $registoEntradaVeiculo->frente_do_veiculo_capo ||
                                                        old('frente_do_veiculo_capo', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="frente_do_veiculo_capo" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_capo')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_capo'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_capo')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_capo_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_parachoque') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="frente_do_veiculo_parachoque"
                                                            value="0">
                                                        <input type="checkbox" name="frente_do_veiculo_parachoque"
                                                            id="frente_do_veiculo_parachoque" value="1" {{
                                                            $registoEntradaVeiculo->frente_do_veiculo_parachoque ||
                                                        old('frente_do_veiculo_parachoque', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="frente_do_veiculo_parachoque"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_parachoque'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_parachoque')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_nada_consta') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="frente_do_veiculo_nada_consta"
                                                            value="0">
                                                        <input type="checkbox" name="frente_do_veiculo_nada_consta"
                                                            id="frente_do_veiculo_nada_consta" value="1" {{
                                                            $registoEntradaVeiculo->frente_do_veiculo_nada_consta ||
                                                        old('frente_do_veiculo_nada_consta', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="frente_do_veiculo_nada_consta"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_nada_consta')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_nada_consta'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_nada_consta')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_nada_consta_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_obs') ? 'has-error' : '' }}">
                                                    <label for="frente_do_veiculo_obs">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_obs')
                                                        }}</label>
                                                    <textarea class="form-control" name="frente_do_veiculo_obs"
                                                        id="frente_do_veiculo_obs">{{ old('frente_do_veiculo_obs', $registoEntradaVeiculo->frente_do_veiculo_obs) }}</textarea>
                                                    @if($errors->has('frente_do_veiculo_obs'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_obs')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_obs_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_teto_photos') ? 'has-error' : '' }}">
                                                    <label for="frente_do_veiculo_teto_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_teto_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="frente_do_veiculo_teto_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_teto_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_teto_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_teto_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_parabrisa_photos') ? 'has-error' : '' }}">
                                                    <label for="frente_do_veiculo_parabrisa_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parabrisa_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="frente_do_veiculo_parabrisa_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_parabrisa_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_parabrisa_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parabrisa_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_capo_photos') ? 'has-error' : '' }}">
                                                    <label for="frente_do_veiculo_capo_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_capo_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="frente_do_veiculo_capo_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_capo_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_capo_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_capo_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('frente_do_veiculo_parachoque_photos') ? 'has-error' : '' }}">
                                                    <label for="frente_do_veiculo_parachoque_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="frente_do_veiculo_parachoque_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('frente_do_veiculo_parachoque_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('frente_do_veiculo_parachoque_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque_photos_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingTwo">
                                    <h4 class="panel-title">
                                        <a class="collapsed" role="button" data-toggle="collapse"
                                            data-parent="#accordion" href="#collapseTwo" aria-expanded="false"
                                            aria-controls="collapseTwo">
                                            Lateral esquerda
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel"
                                    aria-labelledby="headingTwo">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_paralama_diant') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_paralama_diant"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_esquerda_paralama_diant"
                                                            id="lateral_esquerda_paralama_diant" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_paralama_diant ||
                                                        old('lateral_esquerda_paralama_diant', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="lateral_esquerda_paralama_diant"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_paralama_diant')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_paralama_diant'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_paralama_diant')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_paralama_diant_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_retrovisor') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_retrovisor"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_esquerda_retrovisor"
                                                            id="lateral_esquerda_retrovisor" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_retrovisor ||
                                                        old('lateral_esquerda_retrovisor', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_esquerda_retrovisor"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_retrovisor')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_retrovisor'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_retrovisor')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_retrovisor_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_porta_diant') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_porta_diant"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_esquerda_porta_diant"
                                                            id="lateral_esquerda_porta_diant" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_porta_diant ||
                                                        old('lateral_esquerda_porta_diant', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="lateral_esquerda_porta_diant"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_diant')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_porta_diant'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_porta_diant')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_diant_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_porta_tras') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_porta_tras"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_esquerda_porta_tras"
                                                            id="lateral_esquerda_porta_tras" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_porta_tras ||
                                                        old('lateral_esquerda_porta_tras', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_esquerda_porta_tras"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_tras')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_porta_tras'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_porta_tras')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_tras_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_lateral') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_lateral" value="0">
                                                        <input type="checkbox" name="lateral_esquerda_lateral"
                                                            id="lateral_esquerda_lateral" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_lateral ||
                                                        old('lateral_esquerda_lateral', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_esquerda_lateral"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_lateral')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_lateral'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_lateral')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_lateral_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_nada_consta') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_esquerda_nada_consta"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_esquerda_nada_consta"
                                                            id="lateral_esquerda_nada_consta" value="1" {{
                                                            $registoEntradaVeiculo->lateral_esquerda_nada_consta ||
                                                        old('lateral_esquerda_nada_consta', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="lateral_esquerda_nada_consta"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_nada_consta')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_nada_consta'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_nada_consta')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_nada_consta_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_obs') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_obs">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_obs')
                                                        }}</label>
                                                    <textarea class="form-control" name="lateral_esquerda_obs"
                                                        id="lateral_esquerda_obs">{{ old('lateral_esquerda_obs', $registoEntradaVeiculo->lateral_esquerda_obs) }}</textarea>
                                                    @if($errors->has('lateral_esquerda_obs'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_obs') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_obs_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_paralama_diant_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_paralama_diant_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_paralama_diant_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_esquerda_paralama_diant_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_paralama_diant_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_paralama_diant_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_paralama_diant_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_retrovisor_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_retrovisor_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_retrovisor_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_esquerda_retrovisor_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_retrovisor_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_retrovisor_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_retrovisor_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_porta_diant_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_porta_diant_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_diant_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_esquerda_porta_diant_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_porta_diant_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_porta_diant_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_diant_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_porta_tras_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_porta_tras_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_tras_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_esquerda_porta_tras_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_porta_tras_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_porta_tras_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_tras_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_esquerda_lateral_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_esquerda_lateral_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_lateral_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_esquerda_lateral_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_esquerda_lateral_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_esquerda_lateral_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_lateral_photos_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingThree">
                                    <h4 class="panel-title">
                                        <a class="collapsed" role="button" data-toggle="collapse"
                                            data-parent="#accordion" href="#collapseThree" aria-expanded="false"
                                            aria-controls="collapseThree">
                                            Traseira
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseThree" class="panel-collapse collapse" role="tabpanel"
                                    aria-labelledby="headingThree">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('traseira_mala') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_mala" value="0">
                                                        <input type="checkbox" name="traseira_mala" id="traseira_mala"
                                                            value="1" {{ $registoEntradaVeiculo->traseira_mala ||
                                                        old('traseira_mala', 0) === 1 ? 'checked' :
                                                        '' }}>
                                                        <label for="traseira_mala" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_mala')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_mala'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_mala') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_mala_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_farol_dir') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_farol_dir" value="0">
                                                        <input type="checkbox" name="traseira_farol_dir"
                                                            id="traseira_farol_dir" value="1" {{
                                                            $registoEntradaVeiculo->traseira_farol_dir ||
                                                        old('traseira_farol_dir', 0) === 1 ?
                                                        'checked' : '' }}>
                                                        <label for="traseira_farol_dir" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_farol_dir')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_farol_dir'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_farol_dir') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_farol_dir_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_farol_esq') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_farol_esq" value="0">
                                                        <input type="checkbox" name="traseira_farol_esq"
                                                            id="traseira_farol_esq" value="1" {{
                                                            $registoEntradaVeiculo->traseira_farol_esq ||
                                                        old('traseira_farol_esq', 0) === 1 ?
                                                        'checked' : '' }}>
                                                        <label for="traseira_farol_esq" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_farol_esq')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_farol_esq'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_farol_esq') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_farol_esq_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_parachoque_tras') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_parachoque_tras" value="0">
                                                        <input type="checkbox" name="traseira_parachoque_tras"
                                                            id="traseira_parachoque_tras" value="1" {{
                                                            $registoEntradaVeiculo->traseira_parachoque_tras ||
                                                        old('traseira_parachoque_tras', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="traseira_parachoque_tras"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_parachoque_tras')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_parachoque_tras'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_parachoque_tras')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_parachoque_tras_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_pneu_reserva') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_pneu_reserva" value="0">
                                                        <input type="checkbox" name="traseira_pneu_reserva"
                                                            id="traseira_pneu_reserva" value="1" {{
                                                            $registoEntradaVeiculo->traseira_pneu_reserva ||
                                                        old('traseira_pneu_reserva', 0)
                                                        === 1 ? 'checked' : '' }}>
                                                        <label for="traseira_pneu_reserva" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_pneu_reserva')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_pneu_reserva'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_pneu_reserva') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_pneu_reserva_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_macaco') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_macaco" value="0">
                                                        <input type="checkbox" name="traseira_macaco"
                                                            id="traseira_macaco" value="1" {{
                                                            $registoEntradaVeiculo->traseira_macaco ||
                                                        old('traseira_macaco', 0) === 1 ?
                                                        'checked' : '' }}>
                                                        <label for="traseira_macaco" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_macaco')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_macaco'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_macaco') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_macaco_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_chave_de_roda') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_chave_de_roda" value="0">
                                                        <input type="checkbox" name="traseira_chave_de_roda"
                                                            id="traseira_chave_de_roda" value="1" {{
                                                            $registoEntradaVeiculo->traseira_chave_de_roda ||
                                                        old('traseira_chave_de_roda', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="traseira_chave_de_roda" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_chave_de_roda')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_chave_de_roda'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_chave_de_roda') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_chave_de_roda_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_triangulo') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_triangulo" value="0">
                                                        <input type="checkbox" name="traseira_triangulo"
                                                            id="traseira_triangulo" value="1" {{
                                                            $registoEntradaVeiculo->traseira_triangulo ||
                                                        old('traseira_triangulo', 0) === 1 ?
                                                        'checked' : '' }}>
                                                        <label for="traseira_triangulo" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_triangulo')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_triangulo'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_triangulo') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_triangulo_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_nada_consta') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="traseira_nada_consta" value="0">
                                                        <input type="checkbox" name="traseira_nada_consta"
                                                            id="traseira_nada_consta" value="1" {{
                                                            $registoEntradaVeiculo->traseira_nada_consta ||
                                                        old('traseira_nada_consta', 0)
                                                        === 1 ? 'checked' : '' }}>
                                                        <label for="traseira_nada_consta" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.traseira_nada_consta')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('traseira_nada_consta'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_nada_consta') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_nada_consta_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_obs') ? 'has-error' : '' }}">
                                                    <label for="traseira_obs">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_obs')
                                                        }}</label>
                                                    <textarea class="form-control" name="traseira_obs"
                                                        id="traseira_obs">{{ old('traseira_obs', $registoEntradaVeiculo->traseira_obs) }}</textarea>
                                                    @if($errors->has('traseira_obs'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_obs') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_obs_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('traseira_tampa_traseira_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_tampa_traseira_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_tampa_traseira_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_tampa_traseira_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_tampa_traseira_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_tampa_traseira_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_tampa_traseira_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_lanternas_dir_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_lanternas_dir_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_lanternas_dir_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_lanternas_dir_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_lanternas_dir_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_lanternas_dir_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_lanternas_dir_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_lanterna_esq_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_lanterna_esq_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_lanterna_esq_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_lanterna_esq_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_lanterna_esq_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_lanterna_esq_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_lanterna_esq_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_parachoque_tras_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_parachoque_tras_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_parachoque_tras_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_parachoque_tras_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_parachoque_tras_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_parachoque_tras_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_parachoque_tras_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_estepe_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_estepe_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_estepe_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_estepe_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_estepe_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_estepe_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_estepe_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_macaco_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_macaco_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_macaco_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_macaco_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_macaco_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_macaco_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_macaco_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_chave_de_roda_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_chave_de_roda_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_chave_de_roda_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_chave_de_roda_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_chave_de_roda_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_chave_de_roda_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_chave_de_roda_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('traseira_triangulo_photos') ? 'has-error' : '' }}">
                                                    <label for="traseira_triangulo_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_triangulo_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="traseira_triangulo_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('traseira_triangulo_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('traseira_triangulo_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.traseira_triangulo_photos_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingFour">
                                    <h4 class="panel-title">
                                        <a class="collapsed" role="button" data-toggle="collapse"
                                            data-parent="#accordion" href="#collapseFour" aria-expanded="false"
                                            aria-controls="collapseFour">
                                            Lateral direita
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseFour" class="panel-collapse collapse" role="tabpanel"
                                    aria-labelledby="headingFour">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_lateral') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_lateral" value="0">
                                                        <input type="checkbox" name="lateral_direita_lateral"
                                                            id="lateral_direita_lateral" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_lateral ||
                                                        old('lateral_direita_lateral', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_direita_lateral" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_lateral')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_lateral'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_lateral') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_lateral_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_porta_tras') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_porta_tras"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_direita_porta_tras"
                                                            id="lateral_direita_porta_tras" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_porta_tras ||
                                                        old('lateral_direita_porta_tras', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_direita_porta_tras"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_tras')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_porta_tras'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_porta_tras')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_tras_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_porta_diant') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_porta_diant"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_direita_porta_diant"
                                                            id="lateral_direita_porta_diant" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_porta_diant ||
                                                        old('lateral_direita_porta_diant', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_direita_porta_diant"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_diant')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_porta_diant'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_porta_diant')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_diant_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_retrovisor') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_retrovisor"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_direita_retrovisor"
                                                            id="lateral_direita_retrovisor" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_retrovisor ||
                                                        old('lateral_direita_retrovisor', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_direita_retrovisor"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_retrovisor')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_retrovisor'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_retrovisor')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_retrovisor_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_paralama_diant') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_paralama_diant"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_direita_paralama_diant"
                                                            id="lateral_direita_paralama_diant" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_paralama_diant ||
                                                        old('lateral_direita_paralama_diant', 0) === 1 ? 'checked' : ''
                                                        }}>
                                                        <label for="lateral_direita_paralama_diant"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_paralama_diant')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_paralama_diant'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_paralama_diant')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_paralama_diant_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_nada_consta') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="lateral_direita_nada_consta"
                                                            value="0">
                                                        <input type="checkbox" name="lateral_direita_nada_consta"
                                                            id="lateral_direita_nada_consta" value="1" {{
                                                            $registoEntradaVeiculo->lateral_direita_nada_consta ||
                                                        old('lateral_direita_nada_consta', 0) === 1 ? 'checked' : '' }}>
                                                        <label for="lateral_direita_nada_consta"
                                                            style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.lateral_direita_nada_consta')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('lateral_direita_nada_consta'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_nada_consta')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_nada_consta_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_obs') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_obs">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_obs')
                                                        }}</label>
                                                    <textarea class="form-control" name="lateral_direita_obs"
                                                        id="lateral_direita_obs">{{ old('lateral_direita_obs', $registoEntradaVeiculo->lateral_direita_obs) }}</textarea>
                                                    @if($errors->has('lateral_direita_obs'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_obs') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_obs_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_lateral_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_lateral_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_lateral_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_direita_lateral_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_direita_lateral_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_lateral_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_lateral_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_porta_tras_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_porta_tras_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_tras_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_direita_porta_tras_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_direita_porta_tras_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_porta_tras_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_tras_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_porta_diant_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_porta_diant_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_diant_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_direita_porta_diant_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_direita_porta_diant_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_porta_diant_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_diant_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_retrovisor_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_retrovisor_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_retrovisor_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_direita_retrovisor_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_direita_retrovisor_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_retrovisor_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_retrovisor_photos_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('lateral_direita_paralama_diant_photos') ? 'has-error' : '' }}">
                                                    <label for="lateral_direita_paralama_diant_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_paralama_diant_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone"
                                                        id="lateral_direita_paralama_diant_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('lateral_direita_paralama_diant_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('lateral_direita_paralama_diant_photos')
                                                        }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.lateral_direita_paralama_diant_photos_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingFive">
                                    <h4 class="panel-title">
                                        <a class="collapsed" role="button" data-toggle="collapse"
                                            data-parent="#accordion" href="#collapseFive" aria-expanded="false"
                                            aria-controls="collapseFive">
                                            Cinzeiro (vestígios de cinza)
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseFive" class="panel-collapse collapse" role="tabpanel"
                                    aria-labelledby="headingFive">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('cinzeiro_sim') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="cinzeiro_sim" value="0">
                                                        <input type="checkbox" name="cinzeiro_sim" id="cinzeiro_sim"
                                                            value="1" {{ $registoEntradaVeiculo->cinzeiro_sim ||
                                                        old('cinzeiro_sim', 0) === 1 ? 'checked' :
                                                        '' }}>
                                                        <label for="cinzeiro_sim" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.cinzeiro_sim')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('cinzeiro_sim'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('cinzeiro_sim') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.cinzeiro_sim_helper')
                                                        }}</span>
                                                </div>
                                                <div
                                                    class="form-group {{ $errors->has('cinzeiro_nada_consta') ? 'has-error' : '' }}">
                                                    <div>
                                                        <input type="hidden" name="cinzeiro_nada_consta" value="0">
                                                        <input type="checkbox" name="cinzeiro_nada_consta"
                                                            id="cinzeiro_nada_consta" value="1" {{
                                                            $registoEntradaVeiculo->cinzeiro_nada_consta ||
                                                        old('cinzeiro_nada_consta', 0)
                                                        === 1 ? 'checked' : '' }}>
                                                        <label for="cinzeiro_nada_consta" style="font-weight: 400">{{
                                                            trans('cruds.registoEntradaVeiculo.fields.cinzeiro_nada_consta')
                                                            }}</label>
                                                    </div>
                                                    @if($errors->has('cinzeiro_nada_consta'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('cinzeiro_nada_consta') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.cinzeiro_nada_consta_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group {{ $errors->has('cinzeiro_photos') ? 'has-error' : '' }}">
                                                    <label for="cinzeiro_photos">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.cinzeiro_photos')
                                                        }}</label>
                                                    <div class="needsclick dropzone" id="cinzeiro_photos-dropzone">
                                                    </div>
                                                    @if($errors->has('cinzeiro_photos'))
                                                    <span class="help-block" role="alert">{{
                                                        $errors->first('cinzeiro_photos') }}</span>
                                                    @endif
                                                    <span class="help-block">{{
                                                        trans('cruds.registoEntradaVeiculo.fields.cinzeiro_photos_helper')
                                                        }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit" name="step" value="2">
                                Avançar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                @if (request()->query('step') == 2)
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div
                                    class="form-group {{ $errors->has('aspiracao_bancos_frente') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aspiracao_bancos_frente" value="0">
                                        <input type="checkbox" name="aspiracao_bancos_frente"
                                            id="aspiracao_bancos_frente" value="1" {{
                                            $registoEntradaVeiculo->aspiracao_bancos_frente ||
                                        old('aspiracao_bancos_frente', 0) === 1 ? 'checked' : '' }}>
                                        <label for="aspiracao_bancos_frente" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_frente')
                                            }}</label>
                                    </div>
                                    @if($errors->has('aspiracao_bancos_frente'))
                                    <span class="help-block" role="alert">{{ $errors->first('aspiracao_bancos_frente')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_frente_helper')
                                        }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('aspiracao_bancos_tras') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aspiracao_bancos_tras" value="0">
                                        <input type="checkbox" name="aspiracao_bancos_tras" id="aspiracao_bancos_tras"
                                            value="1" {{ $registoEntradaVeiculo->aspiracao_bancos_tras ||
                                        old('aspiracao_bancos_tras', 0)
                                        === 1 ? 'checked' : '' }}>
                                        <label for="aspiracao_bancos_tras" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_tras') }}</label>
                                    </div>
                                    @if($errors->has('aspiracao_bancos_tras'))
                                    <span class="help-block" role="alert">{{ $errors->first('aspiracao_bancos_tras')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_tras_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('aspiracao_tapetes_e_chao_frente') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aspiracao_tapetes_e_chao_frente" value="0">
                                        <input type="checkbox" name="aspiracao_tapetes_e_chao_frente"
                                            id="aspiracao_tapetes_e_chao_frente" value="1" {{
                                            $registoEntradaVeiculo->aspiracao_tapetes_e_chao_frente ||
                                        old('aspiracao_tapetes_e_chao_frente', 0) === 1 ? 'checked' : '' }}>
                                        <label for="aspiracao_tapetes_e_chao_frente" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_frente')
                                            }}</label>
                                    </div>
                                    @if($errors->has('aspiracao_tapetes_e_chao_frente'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('aspiracao_tapetes_e_chao_frente')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_frente_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('aspiracao_tapetes_e_chao_tras') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aspiracao_tapetes_e_chao_tras" value="0">
                                        <input type="checkbox" name="aspiracao_tapetes_e_chao_tras"
                                            id="aspiracao_tapetes_e_chao_tras" value="1" {{
                                            $registoEntradaVeiculo->aspiracao_tapetes_e_chao_tras ||
                                        old('aspiracao_tapetes_e_chao_tras', 0) === 1 ? 'checked' : '' }}>
                                        <label for="aspiracao_tapetes_e_chao_tras" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_tras')
                                            }}</label>
                                    </div>
                                    @if($errors->has('aspiracao_tapetes_e_chao_tras'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('aspiracao_tapetes_e_chao_tras')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_tras_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('limpeza_e_brilho_de_plasticos_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="limpeza_e_brilho_de_plasticos_carro" value="0">
                                        <input type="checkbox" name="limpeza_e_brilho_de_plasticos_carro"
                                            id="limpeza_e_brilho_de_plasticos_carro" value="1" {{
                                            $registoEntradaVeiculo->limpeza_e_brilho_de_plasticos_carro ||
                                        old('limpeza_e_brilho_de_plasticos_carro', 0) === 1 ? 'checked' : '' }}>
                                        <label for="limpeza_e_brilho_de_plasticos_carro" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.limpeza_e_brilho_de_plasticos_carro')
                                            }}</label>
                                    </div>
                                    @if($errors->has('limpeza_e_brilho_de_plasticos_carro'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('limpeza_e_brilho_de_plasticos_carro') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.limpeza_e_brilho_de_plasticos_carro_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('ambientador_de_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="ambientador_de_carro" value="0">
                                        <input type="checkbox" name="ambientador_de_carro" id="ambientador_de_carro"
                                            value="1" {{ $registoEntradaVeiculo->ambientador_de_carro ||
                                        old('ambientador_de_carro', 0)
                                        === 1 ? 'checked' : '' }}>
                                        <label for="ambientador_de_carro" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.ambientador_de_carro') }}</label>
                                    </div>
                                    @if($errors->has('ambientador_de_carro'))
                                    <span class="help-block" role="alert">{{ $errors->first('ambientador_de_carro')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.ambientador_de_carro_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('limpeza_vidros_interiores') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="limpeza_vidros_interiores" value="0">
                                        <input type="checkbox" name="limpeza_vidros_interiores"
                                            id="limpeza_vidros_interiores" value="1" {{
                                            $registoEntradaVeiculo->limpeza_vidros_interiores ||
                                        old('limpeza_vidros_interiores', 0) === 1 ? 'checked' : '' }}>
                                        <label for="limpeza_vidros_interiores" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.limpeza_vidros_interiores')
                                            }}</label>
                                    </div>
                                    @if($errors->has('limpeza_vidros_interiores'))
                                    <span class="help-block" role="alert">{{ $errors->first('limpeza_vidros_interiores')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.limpeza_vidros_interiores_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('retirar_os_objetos_pessoais_existentes_no_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="retirar_os_objetos_pessoais_existentes_no_carro"
                                            value="0">
                                        <input type="checkbox" name="retirar_os_objetos_pessoais_existentes_no_carro"
                                            id="retirar_os_objetos_pessoais_existentes_no_carro" value="1" {{
                                            $registoEntradaVeiculo->retirar_os_objetos_pessoais_existentes_no_carro ||
                                        old('retirar_os_objetos_pessoais_existentes_no_carro', 0) === 1 ? 'checked' : ''
                                        }}>
                                        <label for="retirar_os_objetos_pessoais_existentes_no_carro"
                                            style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.retirar_os_objetos_pessoais_existentes_no_carro')
                                            }}</label>
                                    </div>
                                    @if($errors->has('retirar_os_objetos_pessoais_existentes_no_carro'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('retirar_os_objetos_pessoais_existentes_no_carro') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.retirar_os_objetos_pessoais_existentes_no_carro_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('verificacao_de_luzes_no_painel') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="verificacao_de_luzes_no_painel" value="0">
                                        <input type="checkbox" name="verificacao_de_luzes_no_painel"
                                            id="verificacao_de_luzes_no_painel" value="1" {{
                                            $registoEntradaVeiculo->verificacao_de_luzes_no_painel ||
                                        old('verificacao_de_luzes_no_painel', 0) === 1 ? 'checked' : '' }}>
                                        <label for="verificacao_de_luzes_no_painel" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.verificacao_de_luzes_no_painel')
                                            }}</label>
                                    </div>
                                    @if($errors->has('verificacao_de_luzes_no_painel'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('verificacao_de_luzes_no_painel')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.verificacao_de_luzes_no_painel_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('verificacao_de_necessidade_de_lavagem_estofos') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="verificacao_de_necessidade_de_lavagem_estofos"
                                            value="0">
                                        <input type="checkbox" name="verificacao_de_necessidade_de_lavagem_estofos"
                                            id="verificacao_de_necessidade_de_lavagem_estofos" value="1" {{
                                            $registoEntradaVeiculo->verificacao_de_necessidade_de_lavagem_estofos ||
                                        old('verificacao_de_necessidade_de_lavagem_estofos', 0) === 1 ? 'checked' : ''
                                        }}>
                                        <label for="verificacao_de_necessidade_de_lavagem_estofos"
                                            style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.verificacao_de_necessidade_de_lavagem_estofos')
                                            }}</label>
                                    </div>
                                    @if($errors->has('verificacao_de_necessidade_de_lavagem_estofos'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('verificacao_de_necessidade_de_lavagem_estofos') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.verificacao_de_necessidade_de_lavagem_estofos_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('check_list_aspiracao_obs') ? 'has-error' : '' }}">
                            <label for="check_list_aspiracao_obs">{{
                                trans('cruds.registoEntradaVeiculo.fields.check_list_aspiracao_obs') }}</label>
                            <textarea class="form-control" name="check_list_aspiracao_obs"
                                id="check_list_aspiracao_obs">{{ old('check_list_aspiracao_obs', $registoEntradaVeiculo->check_list_aspiracao_obs) }}</textarea>
                            @if($errors->has('check_list_aspiracao_obs'))
                            <span class="help-block" role="alert">{{ $errors->first('check_list_aspiracao_obs')
                                }}</span>
                            @endif
                            <span class="help-block">{{
                                trans('cruds.registoEntradaVeiculo.fields.check_list_aspiracao_obs_helper') }}</span>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit" name="step" value="1">
                                Recuar
                            </button>
                            <button class="btn btn-danger" type="submit" name="step" value="3">
                                Avançar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                @if (request()->query('step') == 3)
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('copia_de_licenca_de_tvde') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="copia_de_licenca_de_tvde" value="0">
                                        <input type="checkbox" name="copia_de_licenca_de_tvde"
                                            id="copia_de_licenca_de_tvde" value="1" {{
                                            $registoEntradaVeiculo->copia_de_licenca_de_tvde ||
                                        old('copia_de_licenca_de_tvde', 0) === 1 ? 'checked' : '' }}>
                                        <label for="copia_de_licenca_de_tvde" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde')
                                            }}</label>
                                    </div>
                                    @if($errors->has('copia_de_licenca_de_tvde'))
                                    <span class="help-block" role="alert">{{ $errors->first('copia_de_licenca_de_tvde')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('copia_de_licenca_de_tvde_data') ? 'has-error' : '' }}">
                                    <label for="copia_de_licenca_de_tvde_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde_data')
                                        }}</label>
                                    <input class="form-control date" type="text" name="copia_de_licenca_de_tvde_data"
                                        id="copia_de_licenca_de_tvde_data"
                                        value="{{ old('copia_de_licenca_de_tvde_data', $registoEntradaVeiculo->copia_de_licenca_de_tvde_data) }}">
                                    @if($errors->has('copia_de_licenca_de_tvde_data'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('copia_de_licenca_de_tvde_data')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde_data_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('copia_de_licenca_de_tvde_comentarios') ? 'has-error' : '' }}">
                                    <label for="copia_de_licenca_de_tvde_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text" name="copia_de_licenca_de_tvde_comentarios"
                                        id="copia_de_licenca_de_tvde_comentarios"
                                        value="{{ old('copia_de_licenca_de_tvde_comentarios', $registoEntradaVeiculo->copia_de_licenca_de_tvde_comentarios) }}">
                                    @if($errors->has('copia_de_licenca_de_tvde_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('copia_de_licenca_de_tvde_comentarios') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('carta_verde_de_seguro_validade') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="carta_verde_de_seguro_validade" value="0">
                                        <input type="checkbox" name="carta_verde_de_seguro_validade"
                                            id="carta_verde_de_seguro_validade" value="1" {{
                                            $registoEntradaVeiculo->carta_verde_de_seguro_validade ||
                                        old('carta_verde_de_seguro_validade', 0) === 1 ? 'checked' : '' }}>
                                        <label for="carta_verde_de_seguro_validade" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade')
                                            }}</label>
                                    </div>
                                    @if($errors->has('carta_verde_de_seguro_validade'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('carta_verde_de_seguro_validade')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('carta_verde_de_seguro_validade_data') ? 'has-error' : '' }}">
                                    <label for="carta_verde_de_seguro_validade_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade_data')
                                        }}</label>
                                    <input class="form-control date" type="text"
                                        name="carta_verde_de_seguro_validade_data"
                                        id="carta_verde_de_seguro_validade_data"
                                        value="{{ old('carta_verde_de_seguro_validade_data', $registoEntradaVeiculo->carta_verde_de_seguro_validade_data) }}">
                                    @if($errors->has('carta_verde_de_seguro_validade_data'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('carta_verde_de_seguro_validade_data') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade_data_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('carta_verde_de_seguro_validade_comentarios') ? 'has-error' : '' }}">
                                    <label for="carta_verde_de_seguro_validade_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text"
                                        name="carta_verde_de_seguro_validade_comentarios"
                                        id="carta_verde_de_seguro_validade_comentarios"
                                        value="{{ old('carta_verde_de_seguro_validade_comentarios', $registoEntradaVeiculo->carta_verde_de_seguro_validade_comentarios) }}">
                                    @if($errors->has('carta_verde_de_seguro_validade_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('carta_verde_de_seguro_validade_comentarios') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('dua_do_veiculo') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="dua_do_veiculo" value="0">
                                        <input type="checkbox" name="dua_do_veiculo" id="dua_do_veiculo" value="1" {{
                                            $registoEntradaVeiculo->dua_do_veiculo || old('dua_do_veiculo', 0) === 1 ?
                                        'checked'
                                        : '' }}>
                                        <label for="dua_do_veiculo" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo') }}</label>
                                    </div>
                                    @if($errors->has('dua_do_veiculo'))
                                    <span class="help-block" role="alert">{{ $errors->first('dua_do_veiculo') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('dua_do_veiculo_data') ? 'has-error' : '' }}">
                                    <label for="dua_do_veiculo_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo_data') }}</label>
                                    <input class="form-control date" type="text" name="dua_do_veiculo_data"
                                        id="dua_do_veiculo_data"
                                        value="{{ old('dua_do_veiculo_data', $registoEntradaVeiculo->dua_do_veiculo_data) }}">
                                    @if($errors->has('dua_do_veiculo_data'))
                                    <span class="help-block" role="alert">{{ $errors->first('dua_do_veiculo_data')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo_data_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('dua_do_veiculo_comentarios') ? 'has-error' : '' }}">
                                    <label for="dua_do_veiculo_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text" name="dua_do_veiculo_comentarios"
                                        id="dua_do_veiculo_comentarios"
                                        value="{{ old('dua_do_veiculo_comentarios', $registoEntradaVeiculo->dua_do_veiculo_comentarios) }}">
                                    @if($errors->has('dua_do_veiculo_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('dua_do_veiculo_comentarios')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('contratro_de_prestacao_de_servicos') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="contratro_de_prestacao_de_servicos" value="0">
                                        <input type="checkbox" name="contratro_de_prestacao_de_servicos"
                                            id="contratro_de_prestacao_de_servicos" value="1" {{
                                            $registoEntradaVeiculo->contratro_de_prestacao_de_servicos ||
                                        old('contratro_de_prestacao_de_servicos', 0) === 1 ? 'checked' : '' }}>
                                        <label for="contratro_de_prestacao_de_servicos" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos')
                                            }}</label>
                                    </div>
                                    @if($errors->has('contratro_de_prestacao_de_servicos'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('contratro_de_prestacao_de_servicos') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('contratro_de_prestacao_de_servicos_data') ? 'has-error' : '' }}">
                                    <label for="contratro_de_prestacao_de_servicos_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos_data')
                                        }}</label>
                                    <input class="form-control date" type="text"
                                        name="contratro_de_prestacao_de_servicos_data"
                                        id="contratro_de_prestacao_de_servicos_data"
                                        value="{{ old('contratro_de_prestacao_de_servicos_data', $registoEntradaVeiculo->contratro_de_prestacao_de_servicos_data) }}">
                                    @if($errors->has('contratro_de_prestacao_de_servicos_data'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('contratro_de_prestacao_de_servicos_data') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos_data_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('contratro_de_prestacao_de_servicos_comentarios') ? 'has-error' : '' }}">
                                    <label for="contratro_de_prestacao_de_servicos_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text"
                                        name="contratro_de_prestacao_de_servicos_comentarios"
                                        id="contratro_de_prestacao_de_servicos_comentarios"
                                        value="{{ old('contratro_de_prestacao_de_servicos_comentarios', $registoEntradaVeiculo->contratro_de_prestacao_de_servicos_comentarios) }}">
                                    @if($errors->has('contratro_de_prestacao_de_servicos_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('contratro_de_prestacao_de_servicos_comentarios') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('distico_tvde_colocado') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="distico_tvde_colocado" value="0">
                                        <input type="checkbox" name="distico_tvde_colocado" id="distico_tvde_colocado"
                                            value="1" {{ $registoEntradaVeiculo->distico_tvde_colocado ||
                                        old('distico_tvde_colocado', 0)
                                        === 1 ? 'checked' : '' }}>
                                        <label for="distico_tvde_colocado" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado') }}</label>
                                    </div>
                                    @if($errors->has('distico_tvde_colocado'))
                                    <span class="help-block" role="alert">{{ $errors->first('distico_tvde_colocado')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('distico_tvde_colocado_data') ? 'has-error' : '' }}">
                                    <label for="distico_tvde_colocado_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado_data')
                                        }}</label>
                                    <input class="form-control date" type="text" name="distico_tvde_colocado_data"
                                        id="distico_tvde_colocado_data"
                                        value="{{ old('distico_tvde_colocado_data', $registoEntradaVeiculo->distico_tvde_colocado_data) }}">
                                    @if($errors->has('distico_tvde_colocado_data'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('distico_tvde_colocado_data')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado_data_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('distico_tvde_colocado_comentarios') ? 'has-error' : '' }}">
                                    <label for="distico_tvde_colocado_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text" name="distico_tvde_colocado_comentarios"
                                        id="distico_tvde_colocado_comentarios"
                                        value="{{ old('distico_tvde_colocado_comentarios', $registoEntradaVeiculo->distico_tvde_colocado_comentarios) }}">
                                    @if($errors->has('distico_tvde_colocado_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('distico_tvde_colocado_comentarios')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group {{ $errors->has('declaracao_amigavel') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="declaracao_amigavel" value="0">
                                        <input type="checkbox" name="declaracao_amigavel" id="declaracao_amigavel"
                                            value="1" {{ $registoEntradaVeiculo->declaracao_amigavel ||
                                        old('declaracao_amigavel', 0) === 1 ?
                                        'checked' : '' }}>
                                        <label for="declaracao_amigavel" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel') }}</label>
                                    </div>
                                    @if($errors->has('declaracao_amigavel'))
                                    <span class="help-block" role="alert">{{ $errors->first('declaracao_amigavel')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel_helper') }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('declaracao_amigavel_data') ? 'has-error' : '' }}">
                                    <label for="declaracao_amigavel_data">{{
                                        trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel_data') }}</label>
                                    <input class="form-control date" type="text" name="declaracao_amigavel_data"
                                        id="declaracao_amigavel_data"
                                        value="{{ old('declaracao_amigavel_data', $registoEntradaVeiculo->declaracao_amigavel_data) }}">
                                    @if($errors->has('declaracao_amigavel_data'))
                                    <span class="help-block" role="alert">{{ $errors->first('declaracao_amigavel_data')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel_data_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div
                                    class="form-group {{ $errors->has('declaracao_amigavel_comentarios') ? 'has-error' : '' }}">
                                    <label for="declaracao_amigavel_comentarios">{{
                                        trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel_comentarios')
                                        }}</label>
                                    <input class="form-control" type="text" name="declaracao_amigavel_comentarios"
                                        id="declaracao_amigavel_comentarios"
                                        value="{{ old('declaracao_amigavel_comentarios', $registoEntradaVeiculo->declaracao_amigavel_comentarios) }}">
                                    @if($errors->has('declaracao_amigavel_comentarios'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('declaracao_amigavel_comentarios')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel_comentarios_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-danger" type="submit" name="step" value="2">
                            Recuar
                        </button>
                        <button class="btn btn-danger" type="submit" name="step" value="4">
                            Avançar
                        </button>
                    </div>
                </div>
                @endif
                @if (request()->query('step') == 4)
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div
                                    class="form-group {{ $errors->has('aplicacao_de_agua_por_todo_o_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aplicacao_de_agua_por_todo_o_carro" value="0">
                                        <input type="checkbox" name="aplicacao_de_agua_por_todo_o_carro"
                                            id="aplicacao_de_agua_por_todo_o_carro" value="1" {{
                                            $registoEntradaVeiculo->aplicacao_de_agua_por_todo_o_carro ||
                                        old('aplicacao_de_agua_por_todo_o_carro', 0) === 1 ? 'checked' : '' }}>
                                        <label for="aplicacao_de_agua_por_todo_o_carro" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_agua_por_todo_o_carro')
                                            }}</label>
                                    </div>
                                    @if($errors->has('aplicacao_de_agua_por_todo_o_carro'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('aplicacao_de_agua_por_todo_o_carro') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_agua_por_todo_o_carro_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('passagem_de_agua_em_todo_o_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="passagem_de_agua_em_todo_o_carro" value="0">
                                        <input type="checkbox" name="passagem_de_agua_em_todo_o_carro"
                                            id="passagem_de_agua_em_todo_o_carro" value="1" {{
                                            $registoEntradaVeiculo->passagem_de_agua_em_todo_o_carro ||
                                        old('passagem_de_agua_em_todo_o_carro', 0) === 1 ? 'checked' : '' }}>
                                        <label for="passagem_de_agua_em_todo_o_carro" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.passagem_de_agua_em_todo_o_carro')
                                            }}</label>
                                    </div>
                                    @if($errors->has('passagem_de_agua_em_todo_o_carro'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('passagem_de_agua_em_todo_o_carro')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.passagem_de_agua_em_todo_o_carro_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('aplicacao_de_champo_em_todo_o_carro') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="aplicacao_de_champo_em_todo_o_carro" value="0">
                                        <input type="checkbox" name="aplicacao_de_champo_em_todo_o_carro"
                                            id="aplicacao_de_champo_em_todo_o_carro" value="1" {{
                                            $registoEntradaVeiculo->aplicacao_de_champo_em_todo_o_carro ||
                                        old('aplicacao_de_champo_em_todo_o_carro', 0) === 1 ? 'checked' : '' }}>
                                        <label for="aplicacao_de_champo_em_todo_o_carro" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_champo_em_todo_o_carro')
                                            }}</label>
                                    </div>
                                    @if($errors->has('aplicacao_de_champo_em_todo_o_carro'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('aplicacao_de_champo_em_todo_o_carro') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_champo_em_todo_o_carro_helper')
                                        }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('esfregar_todo_o_carro_com_a_escova') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="esfregar_todo_o_carro_com_a_escova" value="0">
                                        <input type="checkbox" name="esfregar_todo_o_carro_com_a_escova"
                                            id="esfregar_todo_o_carro_com_a_escova" value="1" {{
                                            $registoEntradaVeiculo->esfregar_todo_o_carro_com_a_escova ||
                                        old('esfregar_todo_o_carro_com_a_escova', 0) === 1 ? 'checked' : '' }}>
                                        <label for="esfregar_todo_o_carro_com_a_escova" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.esfregar_todo_o_carro_com_a_escova')
                                            }}</label>
                                    </div>
                                    @if($errors->has('esfregar_todo_o_carro_com_a_escova'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('esfregar_todo_o_carro_com_a_escova') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.esfregar_todo_o_carro_com_a_escova_helper')
                                        }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('retirar_com_agua') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="retirar_com_agua" value="0">
                                        <input type="checkbox" name="retirar_com_agua" id="retirar_com_agua" value="1"
                                            {{ $registoEntradaVeiculo->retirar_com_agua || old('retirar_com_agua', 0)
                                        === 1 ?
                                        'checked' : '' }}>
                                        <label for="retirar_com_agua" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.retirar_com_agua') }}</label>
                                    </div>
                                    @if($errors->has('retirar_com_agua'))
                                    <span class="help-block" role="alert">{{ $errors->first('retirar_com_agua')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.retirar_com_agua_helper') }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('verificar_sujidades_ainda_existentes') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="verificar_sujidades_ainda_existentes" value="0">
                                        <input type="checkbox" name="verificar_sujidades_ainda_existentes"
                                            id="verificar_sujidades_ainda_existentes" value="1" {{
                                            $registoEntradaVeiculo->verificar_sujidades_ainda_existentes ||
                                        old('verificar_sujidades_ainda_existentes', 0) === 1 ? 'checked' : '' }}>
                                        <label for="verificar_sujidades_ainda_existentes" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.verificar_sujidades_ainda_existentes')
                                            }}</label>
                                    </div>
                                    @if($errors->has('verificar_sujidades_ainda_existentes'))
                                    <span class="help-block" role="alert">{{
                                        $errors->first('verificar_sujidades_ainda_existentes') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.verificar_sujidades_ainda_existentes_helper')
                                        }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group {{ $errors->has('limpeza_de_jantes') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="limpeza_de_jantes" value="0">
                                        <input type="checkbox" name="limpeza_de_jantes" id="limpeza_de_jantes" value="1"
                                            {{ $registoEntradaVeiculo->limpeza_de_jantes || old('limpeza_de_jantes', 0)
                                        === 1 ?
                                        'checked' : '' }}>
                                        <label for="limpeza_de_jantes" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.limpeza_de_jantes') }}</label>
                                    </div>
                                    @if($errors->has('limpeza_de_jantes'))
                                    <span class="help-block" role="alert">{{ $errors->first('limpeza_de_jantes')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.limpeza_de_jantes_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('possui_triangulo') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="possui_triangulo" value="0">
                                        <input type="checkbox" name="possui_triangulo" id="possui_triangulo" value="1"
                                            {{ $registoEntradaVeiculo->possui_triangulo || old('possui_triangulo', 0)
                                        === 1 ? 'checked' : '' }}>
                                        <label for="possui_triangulo" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.possui_triangulo') }}</label>
                                    </div>
                                    @if($errors->has('possui_triangulo'))
                                    <span class="help-block" role="alert">{{ $errors->first('possui_triangulo')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.possui_triangulo_helper') }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('possui_extintor') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="possui_extintor" value="0">
                                        <input type="checkbox" name="possui_extintor" id="possui_extintor" value="1" {{
                                            $registoEntradaVeiculo->possui_extintor || old('possui_extintor', 0) === 1 ?
                                        'checked' : '' }}>
                                        <label for="possui_extintor" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.possui_extintor') }}</label>
                                    </div>
                                    @if($errors->has('possui_extintor'))
                                    <span class="help-block" role="alert">{{ $errors->first('possui_extintor') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.possui_extintor_helper') }}</span>
                                </div>
                                <div
                                    class="form-group {{ $errors->has('banco_elevatorio_crianca') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="banco_elevatorio_crianca" value="0">
                                        <input type="checkbox" name="banco_elevatorio_crianca"
                                            id="banco_elevatorio_crianca" value="1" {{
                                            $registoEntradaVeiculo->banco_elevatorio_crianca ||
                                        old('banco_elevatorio_crianca', 0) === 1 ? 'checked' : '' }}>
                                        <label for="banco_elevatorio_crianca" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.banco_elevatorio_crianca')
                                            }}</label>
                                    </div>
                                    @if($errors->has('banco_elevatorio_crianca'))
                                    <span class="help-block" role="alert">{{ $errors->first('banco_elevatorio_crianca')
                                        }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.banco_elevatorio_crianca_helper')
                                        }}</span>
                                </div>
                                <div class="form-group {{ $errors->has('colete') ? 'has-error' : '' }}">
                                    <div>
                                        <input type="hidden" name="colete" value="0">
                                        <input type="checkbox" name="colete" id="colete" value="1" {{
                                            $registoEntradaVeiculo->colete || old('colete', 0) === 1 ? 'checked' : ''
                                        }}>
                                        <label for="colete" style="font-weight: 400">{{
                                            trans('cruds.registoEntradaVeiculo.fields.colete') }}</label>
                                    </div>
                                    @if($errors->has('colete'))
                                    <span class="help-block" role="alert">{{ $errors->first('colete') }}</span>
                                    @endif
                                    <span class="help-block">{{
                                        trans('cruds.registoEntradaVeiculo.fields.colete_helper')
                                        }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-danger" type="submit" name="step" value="3">
                            Recuar
                        </button>
                        <button class="btn btn-success" type="submit">
                            Concluir
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
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