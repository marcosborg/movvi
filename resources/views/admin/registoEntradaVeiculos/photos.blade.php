@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Viatura
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Marca</th>
                            <td>{{ $vehicle_item->vehicle_brand->name }}</td>
                        </tr>
                        <tr>
                            <th>Marca</th>
                            <td>{{ $vehicle_item->vehicle_model->name }}</td>
                        </tr>
                        <tr>
                            <th>Ano</th>
                            <td>{{ $vehicle_item->year }}</td>
                        </tr>
                        <tr>
                            <th>Matricula</th>
                            <td>{{ $vehicle_item->license_plate }}</td>
                        </tr>
                        <tr>
                            <th>Empresa</th>
                            <td>{{ $vehicle_item->company->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Registo de entradas
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Data</th>
                            <th>Técnico</th>
                            <th>Motorista</th>
                            <th></th>
                        </tr>
                        @foreach ($vehicle_item->registo_entrada_veiculos as $registo)
                            <tr>
                                <td>{{ $registo->data_e_horario }}</td>
                                <td>{{ $registo->user->name }}</td>
                                <td>{{ $registo->driver->name }}</td>
                                <td>
                                    <a href="/admin/registo-entrada-veiculos/{{ $registo->id }}" class="btn btn-success btn-sm">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Fotografias
                </div>
                <div class="panel-body">
                    @if ($medias->count() > 0)
                    <div class="row">
                        @foreach ($medias as $media)
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <img src="{{ $media->getUrl() }}" class="img-responsive">
                                    </div>
                                    <div class="panel-footer">
                                        <a href="/admin/registo-entrada-veiculos/delete-media/{{ $media->id }}" onclick="return confirm('Tem a certeza?')" class="btn btn-danger btn-sm">Apagar</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                        <div class="alert alert-info">
                            Não existem fotografias
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<script>console.log({
    vehicle_item: {!! $vehicle_item !!},
    medias: {!! $medias !!}
})</script>