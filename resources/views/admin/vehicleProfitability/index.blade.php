@extends('layouts.admin')

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <form method="GET" class="form-inline" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label for="vehicle_id">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @if($vehicleId === $vehicle->id) selected @endif>
                                {{ $vehicle->license_plate }} @if($vehicle->vehicle_model) ({{ $vehicle->vehicle_model->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label for="tvde_week_id">Week</label>
                    <select name="tvde_week_id" id="tvde_week_id" class="form-control">
                        <option value="">-- select --</option>
                        @foreach($weeks as $week)
                            <option value="{{ $week->id }}" @if($weekId === $week->id) selected @endif>
                                {{ $week->start_date }} → {{ $week->end_date }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-left: 10px;">Load</button>
                @php($canExport = $vehicleId && $weekId)
                <a class="btn btn-default"
                   style="margin-left: 10px; {{ $canExport ? '' : 'pointer-events: none; opacity: 0.6;' }}"
                   href="{{ $canExport ? url('admin/vehicle-profitability/pdf') . '?vehicle_id=' . $vehicleId . '&tvde_week_id=' . $weekId : '#' }}">
                    Exportar PDF
                </a>
            </form>
        </div>
    </div>

    @if(!empty($message))
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info" role="alert">{{ $message }}</div>
            </div>
        </div>
    @endif

    @if($result)
        <div class="row">
            <div class="col-lg-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Identification</h3>
                    </div>
                    <div class="box-body">
                        <p><strong>Vehicle:</strong> {{ $result['vehicle']['license_plate'] }}</p>
                        <p><strong>Model:</strong> {{ $result['vehicle']['model'] }}</p>
                        <p><strong>Week:</strong> {{ $result['week']['start_date'] }} → {{ $result['week']['end_date'] }}</p>
                        <p><strong>Driver:</strong> {{ $result['meta']['driver_id'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="box box-info">
                    <div class="box-body">
                        <h4>Revenue</h4>
                        <p>{{ $result['revenues']['total_revenue'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-warning">
                    <div class="box-body">
                        <h4>Total Costs</h4>
                        <p>{{ $result['totals']['total_costs'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-success">
                    <div class="box-body">
                        <h4>Final Result</h4>
                        <p>{{ $result['totals']['final_result'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-default">
                    <div class="box-body">
                        <h4>Status</h4>
                        <span class="label {{ $result['totals']['status_class'] }}">{{ $result['totals']['status'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Cost Breakdown</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Cost</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Car Hire</td><td>{{ $result['costs']['car_hire'] }}</td></tr>
                                <tr><td>Via Verde</td><td>{{ $result['costs']['via_verde'] }}</td></tr>
                                <tr><td>Fuel</td><td>{{ $result['costs']['fuel'] }}</td></tr>
                                <tr><td>Other Driver Costs</td><td>{{ $result['costs']['other_driver_costs'] }}</td></tr>
                                <tr><td>Vehicle Expenses</td><td>{{ $result['vehicle_costs']['expenses'] }}</td></tr>
                                <tr><td>Reimbursements</td><td>{{ $result['vehicle_costs']['reimbursements'] }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Car Hire Daily Breakdown</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($result['car_hire_breakdown']['days'] as $day)
                                    <tr>
                                        <td>{{ $day['date'] }}</td>
                                        <td>{{ $day['status'] }}</td>
                                        <td>{{ $day['discount'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p><strong>Weekly Value:</strong> {{ $result['car_hire_breakdown']['weekly_value'] }}</p>
                        <p><strong>Total Discount:</strong> {{ $result['car_hire_breakdown']['total_discount'] }}</p>
                        <p><strong>Final Value:</strong> {{ $result['car_hire_breakdown']['final_value'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
