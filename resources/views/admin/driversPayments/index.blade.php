@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">
                    {{ trans('cruds.driversPayment.title') }}
                </div>
                <div class="col-md-6">
                    <a href="https://tribosemontanhas.gestvde.pt/assets/payment.xml" target="_new" id="download-link" class="btn btn-success btn-sm" download>Download</a>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <td><input type="checkbox" id="all-checkbox"> <label for="all-checkbox">Todos</label></td>
                        <th>Driver</th>
                        <th>Email</th>
                        <th>IBAN</th>
                        <th>VAT</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receipts as $receipt)
                    <tr>
                        <!-- Adiciona data-id com o ID do recibo para capturar depois -->
                        <td><input type="checkbox" class="receipt-checkbox" data-id="{{ $receipt->id }}"></td>
                        <td>{{ $receipt->driver->name }}</td>
                        <td>{{ $receipt->driver->email }}</td>
                        <td>{{ $receipt->driver->iban }}</td>
                        <td>{{ $receipt->driver->payment_vat }}</td>
                        <td>{{ $receipt->amount_transferred }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <a href="https://tribosemontanhas.gestvde.pt/assets/payment.xml" target="_new" id="download-link" class="btn btn-success btn-sm" download>Download</a>
        </div>
    </div>
</div>
@endsection
