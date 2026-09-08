@extends('layouts.admin')
@section('content')
<div class="content"><div class="panel panel-default">
<div class="panel-heading"><h3>Utilização da viatura - Visão Geral</h3></div>
<div class="panel-body">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('admin.vehicle-usage') }}" id="usage-filters">
        <div class="row">
            <div class="col-md-2 form-group"><label for="from">De</label><input class="form-control" type="date" id="from" name="from" value="{{ $from }}" max="{{ now()->toDateString() }}" required></div>
            <div class="col-md-2 form-group"><label for="to">Até</label><input class="form-control" type="date" id="to" name="to" value="{{ $to }}" max="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4 form-group"><label for="group">Grupo de viaturas</label><select class="form-control" id="group" name="group"><option value="">Todas as marcas e modelos</option>@foreach($groups as $key => $label)<option value="{{ $key }}" @selected($group === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4 form-group"><label for="selection">Viaturas a incluir</label><select class="form-control" id="selection" name="selection"><option value="all" @selected(request('selection') !== 'selected')>Todas as viaturas do grupo</option><option value="selected" @selected(request('selection') === 'selected')>Escolher matrículas</option></select></div>
        </div>
        <div id="vehicle-picker" style="display:{{ request('selection') === 'selected' ? 'block' : 'none' }};margin-bottom:15px">
            <label for="vehicle-search">Pesquisar matrícula ou modelo</label><input type="search" id="vehicle-search" class="form-control" placeholder="Ex.: CJ-17-XC ou Renault">
            <button type="button" class="btn btn-default btn-sm" id="pick-visible" style="margin:8px 0">Selecionar visíveis</button>
            <button type="button" class="btn btn-default btn-sm" id="clear-vehicles">Limpar seleção</button>
            <div style="max-height:220px;overflow:auto;border:1px solid #ddd;padding:10px" id="vehicle-options">
            @foreach($vehicles as $vehicle)
                <label style="display:inline-block;width:300px;font-weight:normal" data-active="{{ !$vehicle->suspended && $vehicle->first_usage_at && substr($vehicle->first_usage_at,0,10) <= now()->toDateString() && (!$vehicle->getRawOriginal('sale_date') || $vehicle->getRawOriginal('sale_date') > now()->toDateString()) ? '1' : '0' }}" data-brand="brand:{{ $vehicle->vehicle_brand_id }}" data-model="model:{{ $vehicle->vehicle_model_id }}"><input type="checkbox" name="vehicle_ids[]" value="{{ $vehicle->id }}" @checked(in_array($vehicle->id,$ids))> {{ $vehicle->license_plate }} - {{ $vehicle->vehicle_brand?->name }} {{ $vehicle->vehicle_model?->name }}</label>
            @endforeach
            </div>
        </div>
        <div class="form-inline">
            <label for="active_only">Estado</label>
            <select name="active_only" id="active_only" class="form-control"><option value="1" @selected($activeOnly)>Apenas viaturas ativas</option><option value="0" @selected(!$activeOnly)>Incluir histórico de viaturas inativas</option></select>
            <label for="breakdown">Detalhe por</label>
            <select name="breakdown" id="breakdown" class="form-control"><option value="years" @selected($breakdown === 'years')>Ano</option><option value="months" @selected($breakdown === 'months')>Mês</option><option value="weeks" @selected($breakdown === 'weeks')>Semana</option></select>
            <button type="submit" class="btn btn-primary">Aplicar filtros</button>
            <label for="section" style="margin-left:15px">Conteúdo do PDF</label>
            <select name="section" id="section" class="form-control"><option value="all">As três abas</option><option value="timeline">Linha do tempo</option><option value="chart">Gráfico de ocupação</option><option value="detail">Detalhe por viatura</option></select>
            <button type="submit" name="format" value="pdf" formtarget="_blank" class="btn btn-default">Imprimir PDF</button>
        </div>
    </form>
    <div class="usage-summary"><strong>{{ $companyName }} | {{ count($report['rows']) }} {{ count($report['rows']) === 1 ? 'viatura' : 'viaturas' }}</strong> &nbsp; {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} &nbsp; <strong>Ocupação do grupo: {{ number_format($report['fleet']['percent'],2,',','.') }}%</strong></div>
    @include('admin.vehicleUsages.usage-method')
@if($canViewRevenue)    <p><strong>Faturação total do grupo: {{ number_format($report['fleet']['revenue'],2,',','.') }} €</strong>{{ $report['fleet']['incomplete'] ? ' *' : '' }} | Média por viatura/dia: {{ number_format($report['fleet']['daily_average'],2,',','.') }} €</p>@endif
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#timeline" data-toggle="tab" role="tab">Linha do Tempo das Viaturas</a></li>
        <li><a href="#chart" data-toggle="tab" role="tab">Gráfico da Taxa de Ocupação</a></li>
        <li><a href="#detail" data-toggle="tab" role="tab">Detalhe da Ocupação por Viatura</a></li>
    </ul>
    <div class="tab-content">@include('admin.vehicleUsages.usage-report', ['section'=>'all', 'pdf'=>false])</div>
</div></div></div>
@endsection
@section('styles')
@include('admin.vehicleUsages.usage-style')
@endsection
@section('scripts')
@parent
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selection = document.getElementById('selection'), picker = document.getElementById('vehicle-picker');
    const labels = [...document.querySelectorAll('#vehicle-options label')];
    function filterVehicles() {
        const group = document.getElementById('group').value;
        const search = document.getElementById('vehicle-search').value.toLocaleLowerCase();
        const activeOnly = document.getElementById('active_only').value === '1';
        labels.forEach(label => { label.style.display = (!activeOnly || label.dataset.active === '1') && (!group || label.dataset.brand === group || label.dataset.model === group) && label.textContent.toLocaleLowerCase().includes(search) ? 'inline-block' : 'none'; });
    }
    selection.addEventListener('change', () => { picker.style.display = selection.value === 'selected' ? 'block' : 'none'; });
    document.getElementById('group').addEventListener('change', filterVehicles);
    document.getElementById('active_only').addEventListener('change', filterVehicles);
    document.getElementById('vehicle-search').addEventListener('input', filterVehicles);
    document.getElementById('pick-visible').addEventListener('click', () => labels.filter(l=>l.style.display !== 'none').forEach(l=>l.querySelector('input').checked=true));
    document.getElementById('clear-vehicles').addEventListener('click', () => labels.forEach(l=>l.querySelector('input').checked=false));
    document.getElementById('usage-filters').addEventListener('submit', event => {
        const from = document.getElementById('from'), to = document.getElementById('to');
        to.setCustomValidity(to.value < from.value ? 'A data final deve ser igual ou posterior à inicial.' : '');
        selection.setCustomValidity(selection.value === 'selected' && !labels.some(l=>l.querySelector('input').checked) ? 'Selecione pelo menos uma matrícula.' : '');
        if (!event.target.reportValidity()) event.preventDefault();
    });
    document.getElementById('to').addEventListener('input', e=>e.target.setCustomValidity(''));
    document.getElementById('vehicle-options').addEventListener('change', ()=>selection.setCustomValidity(''));
    filterVehicles();
});
</script>
@endsection
