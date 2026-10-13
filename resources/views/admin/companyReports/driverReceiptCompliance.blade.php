@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">Recibos dos motoristas ativos</div>
        <div class="panel-body">
            <form method="GET" class="form-inline">
                <label for="tvde_week_id">Semana</label>
                <select id="tvde_week_id" name="tvde_week_id" class="form-control"><option value="">Todas</option>@foreach($weeks as $week)<option value="{{ $week->id }}" {{ (int) request('tvde_week_id') === $week->id ? 'selected' : '' }}>{{ $week->start_date }} a {{ $week->end_date }}</option>@endforeach</select>
                <label for="month">ou mês</label><input type="month" id="month" name="month" value="{{ request('month') }}" class="form-control">
                <button class="btn btn-primary">Consultar</button>
                <a class="btn btn-default" href="{{ route('admin.company-reports.driver-receipt-compliance-pdf', request()->query()) }}" target="_blank">PDF</a>
                <a class="btn btn-success" href="{{ route('admin.company-reports.driver-receipt-compliance-excel', request()->query()) }}">Excel</a>
            </form>
        </div>
        <div class="table-responsive">
            @include('admin.companyReports.driverReceiptComplianceTable')
        </div>
    </div>
</div>
@endsection
