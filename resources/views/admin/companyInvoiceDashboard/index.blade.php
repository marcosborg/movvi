@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Faturas
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Semana</th>
                                    <th>Fatura</th>
                                    <th>Informação</th>
                                    <th>Comprovativo de pagamento</th>
                                    <th>Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($company->company_invoices as $company_invoice)
                                <tr>
                                    <td>{{ $company_invoice->id }}</td>
                                    <td>{{ $company_invoice->tvde_week->start_date }} a {{
                                        $company_invoice->tvde_week->end_date }}</td>
                                    <td>
                                        @foreach ($company_invoice->invoice as $invoice)
                                        <a href="{{ $invoice->getUrl() }}" target="_new">Download</a>
                                        @endforeach
                                    </td>
                                    <td>{{ $company_invoice->info }}</td>
                                    <td>
                                        @if ($company_invoice->payment_receipt)
                                        <a href="{{ $company_invoice->payment_receipt->getUrl() }}"
                                            target="_new">Download</a>
                                        @else
                                        <form action="{{ route('admin.company-invoice-upload-media') }}" method="post"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <input type="hidden" name="company_invoice_id"
                                                        value="{{ $company_invoice->id }}">
                                                    <input type="file" class="form-control" name="file" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="submit"
                                                        class="btn btn-success btn-sm btn-block">Enviar</button>
                                                </div>
                                            </div>
                                        </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($company_invoice->payed)
                                        <span class="badge badge-success">Pago</span>
                                        @else
                                        <span class="badge badge-danger">Aguarda pagamento</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('styles')
<style>
    .badge-success {
        background: green;
    }

    .badge-danger {
        background: red;
    }
</style>
@endsection