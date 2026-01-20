<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vehicle Profitability</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; font-size: 12px; }
        h1, h2 { margin: 0 0 8px 0; }
        .section { margin-bottom: 24px; }
        .badge { padding: 4px 8px; border-radius: 4px; color: #fff; font-weight: bold; }
        .badge.positive { background: #2d862d; }
        .badge.neutral { background: #999; }
        .badge.negative { background: #b30000; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .kpi { font-size: 16px; font-weight: bold; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="section">
        <h1>Weekly Vehicle Profitability</h1>
        <p><strong>Vehicle:</strong> {{ $result['vehicle']['license_plate'] }} @if($result['vehicle']['model']) ({{ $result['vehicle']['model'] }}) @endif</p>
        <p><strong>Week:</strong> {{ $result['week']['start_date'] }} → {{ $result['week']['end_date'] }}</p>
        <p><strong>Driver:</strong> {{ $result['meta']['driver_id'] }}</p>
        <p><strong>Final Result:</strong> <span class="kpi">{{ $result['totals']['final_result'] }}</span></p>
        <p><strong>Status:</strong>
            <span class="badge {{ $result['totals']['status'] }}">{{ $result['totals']['status'] }}</span>
        </p>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Financial Breakdown</h2>
        <table>
            <tbody>
                <tr><th>Revenues</th><td>{{ $result['revenues']['total_revenue'] }}</td></tr>
                <tr><th>Car Hire</th><td>{{ $result['costs']['car_hire'] }}</td></tr>
                <tr><th>Via Verde</th><td>{{ $result['costs']['via_verde'] }}</td></tr>
                <tr><th>Fuel</th><td>{{ $result['costs']['fuel'] }}</td></tr>
                <tr><th>Other driver costs</th><td>{{ $result['costs']['other_driver_costs'] }}</td></tr>
                <tr><th>Vehicle expenses</th><td>{{ $result['vehicle_costs']['expenses'] }}</td></tr>
                <tr><th>Reimbursements</th><td>{{ $result['vehicle_costs']['reimbursements'] }}</td></tr>
                <tr><th>Total costs</th><td>{{ $result['totals']['total_costs'] }}</td></tr>
                <tr><th>Final result</th><td>{{ $result['totals']['final_result'] }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Car Hire Daily Breakdown</h2>
        <table>
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
    </div>
</body>
</html>
