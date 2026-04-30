@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Controlo de recibos
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.driver-alerts.index') }}" class="form-inline" style="margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="driver_id" style="margin-right: 8px;">Motorista</label>
                            <select name="driver_id" id="driver_id" class="form-control select2" style="min-width: 260px;">
                                @foreach($drivers as $id => $name)
                                    <option value="{{ $id }}" {{ (string) $selectedDriverId === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-left: 8px;">
                            <label for="tvde_week_id" style="margin-right: 8px;">Semana</label>
                            <select name="tvde_week_id" id="tvde_week_id" class="form-control select2" style="min-width: 160px;">
                                @foreach($tvdeWeeks as $id => $startDate)
                                    <option value="{{ $id }}" {{ (string) $selectedWeekId === (string) $id ? 'selected' : '' }}>{{ $startDate }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-left: 8px;">
                            <label for="status" style="margin-right: 8px;">Estado</label>
                            <select name="status" id="status" class="form-control" style="min-width: 190px;">
                                @foreach($statuses as $status => $label)
                                    <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-left: 8px;">Filtrar</button>
                        @if($selectedDriverId || $selectedWeekId || $selectedStatus !== \App\Services\ReceiptControlService::STATUS_ACTIVE)
                            <a href="{{ route('admin.driver-alerts.index') }}" class="btn btn-default" style="margin-left: 4px;">Limpar</a>
                        @endif
                    </form>

                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Condutor</th>
                                <th>Empresa</th>
                                <th>Semana</th>
                                <th>Valor do recibo</th>
                                <th>Estado</th>
                                <th>Recibo</th>
                                <th>Valor colocado</th>
                                <th>Valor em aberto</th>
                                <th>Verificado</th>
                                <th>Pago</th>
                                <th>Ficheiro</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receiptRows as $row)
                                @php
                                    $driver = $row['driver'];
                                    $week = $row['week'];
                                    $receipt = $row['receipt'];
                                @endphp
                                <tr>
                                    <td>{{ $driver->name ?? '-' }}</td>
                                    <td>{{ $driver->company->name ?? '-' }}</td>
                                    <td>{{ $week ? $week->start_date . ' a ' . $week->end_date : '-' }}</td>
                                    <td>{{ number_format((float) $row['expected_value'], 2, ',', '.') }}&euro;</td>
                                    <td>
                                        @if($row['status'] === \App\Services\ReceiptControlService::STATUS_MISSING)
                                            <span class="label label-danger">{{ $row['status_label'] }}</span>
                                        @elseif($row['status'] === \App\Services\ReceiptControlService::STATUS_NOT_REQUIRED)
                                            <span class="label label-default">{{ $row['status_label'] }}</span>
                                        @elseif($row['status'] === \App\Services\ReceiptControlService::STATUS_PAID)
                                            <span class="label label-primary">{{ $row['status_label'] }}</span>
                                        @elseif($row['status'] === \App\Services\ReceiptControlService::STATUS_VERIFIED)
                                            <span class="label label-info">{{ $row['status_label'] }}</span>
                                        @else
                                            <span class="label label-success">{{ $row['status_label'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($receipt)
                                            <a href="{{ route('admin.receipts.show', $receipt->id) }}">#{{ $receipt->id }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{!! $receipt ? number_format((float) $receipt->value, 2, ',', '.') . '&euro;' : '-' !!}</td>
                                    <td>{{ number_format((float) $row['required_value'], 2, ',', '.') }}&euro;</td>
                                    <td>{{ $receipt ? ($receipt->verified ? 'Sim' : 'Nao') : '-' }}</td>
                                    <td>{{ $receipt ? ($receipt->paid ? 'Sim' : 'Nao') : '-' }}</td>
                                    <td>
                                        @if($receipt && $receipt->file)
                                            <a href="{{ $receipt->file->getUrl() }}" target="_blank">Abrir</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $receipt ? $receipt->created_at : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">Sem recibos para os filtros escolhidos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
