@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default"><div class="panel-heading">QR da viatura {{ $vehicle->license_plate }}</div><div class="panel-body text-center">
    <div style="max-width:420px;margin:0 auto;">{!! $svg !!}</div>
    <h2>{{ $vehicle->license_plate }}</h2>
    <p>O motorista deve iniciar sessão na app, ler o código e confirmar a passagem.</p>
    <p><small>{{ $url }}</small></p>
    <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
</div></div></div>
@endsection
