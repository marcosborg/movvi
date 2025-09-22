@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="form-group">
        <div class="row">
            <div class="col-md-6">
                <a class="btn btn-default" href="{{ route('admin.registo-entrada-veiculos.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
            <div class="col-md-6">
                <div class="pull-right">
                    <a class="btn btn-primary btn-sm"
                        href="{{ route('admin.registo-entrada-veiculos.photos', [$registoEntradaVeiculo->vehicle_item->id]) }}">
                        Todas as fotografias da viatura
                    </a>
                    <a class="btn btn-success btn-sm"
                        href="/admin/registo-entrada-veiculos/{{ $registoEntradaVeiculo->id }}/edit?step=1">
                        Editar
                    </a>
                </div>
            </div>
        </div>
    </div>
    <h3>{{ trans('cruds.registoEntradaVeiculo.title') }}</h3>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Dados gerais
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.data_e_horario') }}</strong><br>{{
                                $registoEntradaVeiculo->data_e_horario }}
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.user') }}</strong><br>{{
                                $registoEntradaVeiculo->user->name ?? '' }}
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.driver') }}</strong><br>{{
                                $registoEntradaVeiculo->driver->name ?? '' }}
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.vehicle_item') }}</strong><br>{{
                                $registoEntradaVeiculo->vehicle_item->license_plate ?? '' }}
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.bateria_a_chegada')
                                    }}</strong><br>{{ $registoEntradaVeiculo->bateria_a_chegada }}%
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.de_bateria_de_saida')
                                    }}</strong><br>{{ $registoEntradaVeiculo->de_bateria_de_saida }}
                            </td>
                            <td>
                                <strong>{{ trans('cruds.registoEntradaVeiculo.fields.km_atual') }}</strong><br>{{
                                $registoEntradaVeiculo->km_atual }} km
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    1.º Check de Danos Visiveis do Carro
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th colspan="3">Frente</th>
                        </tr>
                        @if ($registoEntradaVeiculo->frente_do_veiculo_teto)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_teto') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_teto ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->frente_do_veiculo_teto_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_new">
                                    <img src="{{ $media->getUrl() }}" style="max-width: 100px;">
                                    </img></a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_parabrisa)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parabrisa') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_parabrisa ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->frente_do_veiculo_parabrisa_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_new">
                                    <img src="{{ $media->getUrl() }}" style="max-width: 100px;">
                                    </img></a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_capo)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_capo') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_capo ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->frente_do_veiculo_capo_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_new">
                                    <img src="{{ $media->getUrl() }}" style="max-width: 100px;">
                                    </img></a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_parachoque)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_parachoque ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->frente_do_veiculo_parachoque_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_parachoque)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_parachoque') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_parachoque ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->frente_do_veiculo_parachoque_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_nada_consta)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_nada_consta') }}
                            </td>
                            <td colspan="2">
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->frente_do_veiculo_nada_consta ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->frente_do_veiculo_obs)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.frente_do_veiculo_obs') }}
                            </td>
                            <td colspan="2">
                                {{ $registoEntradaVeiculo->frente_do_veiculo_obs }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">Lateral esquerda</th>
                        </tr>
                        @if ($registoEntradaVeiculo->lateral_esquerda_paralama_diant)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_paralama_diant') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_paralama_diant ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_esquerda_paralama_diant_photos as $key
                                => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_retrovisor)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_retrovisor') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_retrovisor ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_esquerda_retrovisor_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_porta_diant)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_diant') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_porta_diant ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_esquerda_porta_diant_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_porta_tras)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_porta_tras') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_porta_tras ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_esquerda_porta_tras_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_lateral)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_lateral') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_lateral ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_esquerda_lateral_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_nada_consta)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_nada_consta') }}
                            </td>
                            <td colspan="2">
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_esquerda_nada_consta ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_esquerda_obs)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_esquerda_obs') }}
                            </td>
                            <td colspan="2">
                                {{ $registoEntradaVeiculo->lateral_esquerda_obs }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">
                                Traseira
                            </th>
                        </tr>
                        @if ($registoEntradaVeiculo->traseira_mala)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_mala') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->traseira_mala ?
                                'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_tampa_traseira_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_farol_dir)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_farol_dir') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->traseira_farol_dir
                                ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_lanternas_dir_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_farol_esq)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_farol_esq') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->traseira_farol_esq
                                ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_lanterna_esq_photos as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_parachoque_tras)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_parachoque_tras') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->traseira_parachoque_tras ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_parachoque_tras_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_pneu_reserva)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_pneu_reserva') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->traseira_pneu_reserva ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_estepe_photos as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_macaco)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_macaco') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->traseira_macaco ?
                                'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_macaco_photos as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_chave_de_roda)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_chave_de_roda') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->traseira_chave_de_roda ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_chave_de_roda_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_triangulo)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_triangulo') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->traseira_triangulo
                                ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->traseira_triangulo_photos as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_nada_consta)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_nada_consta') }}
                            </td>
                            <td colspan="2">
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->traseira_nada_consta ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->traseira_obs)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.traseira_obs') }}
                            </td>
                            <td colspan="2">
                                {{ $registoEntradaVeiculo->traseira_obs }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">Lateral direita</th>
                        </tr>
                        @if ($registoEntradaVeiculo->lateral_direita_lateral)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_lateral') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_lateral ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_direita_lateral_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_porta_tras)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_tras') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_porta_tras ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_direita_porta_tras_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_porta_diant)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_porta_diant') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_porta_diant ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_direita_porta_diant_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_retrovisor)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_retrovisor') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_retrovisor ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_direita_retrovisor_photos as $key =>
                                $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_paralama_diant)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_paralama_diant') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_paralama_diant ? 'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->lateral_direita_paralama_diant_photos as $key
                                => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_nada_consta)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_nada_consta') }}
                            </td>
                            <td colspan="2">
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->lateral_direita_nada_consta ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->lateral_direita_obs)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.lateral_direita_obs') }}
                            </td>
                            <td colspan="2">
                                {{ $registoEntradaVeiculo->lateral_direita_obs }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">Cinzeiro (vestígios de cinza)</th>
                        </tr>
                        @if ($registoEntradaVeiculo->cinzeiro_sim)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.cinzeiro_sim') }}
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->cinzeiro_sim ?
                                'checked' : '' }}>
                            </td>
                            <td>
                                @foreach($registoEntradaVeiculo->cinzeiro_photos as $key => $media)
                                <a href="{{ $media->getUrl() }}" target="_blank">
                                    <img src="{{ $media->getUrl() }}" width="100">
                                </a>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if ($registoEntradaVeiculo->cinzeiro_nada_consta)
                        <tr>
                            <td>
                                {{ trans('cruds.registoEntradaVeiculo.fields.cinzeiro_nada_consta') }}
                            </td>
                            <td colspan="2">
                                <input type="checkbox" disabled="disabled" {{
                                    $registoEntradaVeiculo->cinzeiro_nada_consta ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            2.º Checkagem de aspiração(10 minutos)
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_frente') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aspiracao_bancos_frente ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.aspiracao_bancos_tras') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aspiracao_bancos_tras ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_frente')
                                        }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aspiracao_tapetes_e_chao_frente ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.aspiracao_tapetes_e_chao_tras') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aspiracao_tapetes_e_chao_tras ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.limpeza_e_brilho_de_plasticos_carro')
                                        }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->limpeza_e_brilho_de_plasticos_carro ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.ambientador_de_carro') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->ambientador_de_carro ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.limpeza_vidros_interiores') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->limpeza_vidros_interiores ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.retirar_os_objetos_pessoais_existentes_no_carro')
                                        }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->retirar_os_objetos_pessoais_existentes_no_carro ?
                                        'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.verificacao_de_luzes_no_painel') }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->verificacao_de_luzes_no_painel ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.verificacao_de_necessidade_de_lavagem_estofos')
                                        }}
                                    </td>
                                    <td colspan="2">
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->verificacao_de_necessidade_de_lavagem_estofos ?
                                        'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.check_list_aspiracao_obs') }}
                                    </td>
                                    <td colspan="2">
                                        {{ $registoEntradaVeiculo->check_list_aspiracao_obs }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            3.º Documentação
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <tr>
                                    <th>Documento</th>
                                    <th>Verificado</th>
                                    <th>Validade</th>
                                    <th>Comentários</th>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.copia_de_licenca_de_tvde') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->copia_de_licenca_de_tvde ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->copia_de_licenca_de_tvde_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->copia_de_licenca_de_tvde_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.carta_verde_de_seguro_validade') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->carta_verde_de_seguro_validade ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->carta_verde_de_seguro_validade_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->carta_verde_de_seguro_validade_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.dua_do_veiculo') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->dua_do_veiculo ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->dua_do_veiculo_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->dua_do_veiculo_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.inspecao_do_veiculo_validade') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->inspecao_do_veiculo_validade ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->inspecao_do_veiculo_validade_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->inspecao_do_veiculo_validade_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.contratro_de_prestacao_de_servicos')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->contratro_de_prestacao_de_servicos ? 'checked' : ''
                                        }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->contratro_de_prestacao_de_servicos_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->contratro_de_prestacao_de_servicos_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.distico_tvde_colocado') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->distico_tvde_colocado ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->distico_tvde_colocado_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->distico_tvde_colocado_comentarios }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.declaracao_amigavel') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->declaracao_amigavel ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->declaracao_amigavel_data }}
                                    </td>
                                    <td>
                                        {{ $registoEntradaVeiculo->declaracao_amigavel_comentarios }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    4.º Checkagem de aspiração
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_agua_por_todo_o_carro')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aplicacao_de_agua_por_todo_o_carro ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.passagem_de_agua_em_todo_o_carro')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->passagem_de_agua_em_todo_o_carro ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.aplicacao_de_champo_em_todo_o_carro')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->aplicacao_de_champo_em_todo_o_carro ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.esfregar_todo_o_carro_com_a_escova')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->esfregar_todo_o_carro_com_a_escova ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.retirar_com_agua') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->retirar_com_agua ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{
                                        trans('cruds.registoEntradaVeiculo.fields.verificar_sujidades_ainda_existentes')
                                        }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->verificar_sujidades_ainda_existentes ? 'checked' :
                                        '' }}>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.limpeza_de_jantes') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->limpeza_de_jantes ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.possui_triangulo') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->possui_triangulo ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.possui_extintor') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->possui_extintor ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.banco_elevatorio_crianca') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{
                                            $registoEntradaVeiculo->banco_elevatorio_crianca ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {{ trans('cruds.registoEntradaVeiculo.fields.colete') }}
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $registoEntradaVeiculo->colete ?
                                        'checked' : '' }}>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection