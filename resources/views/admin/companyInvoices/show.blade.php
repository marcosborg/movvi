@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.companyInvoice.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.company-invoices.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $companyInvoice->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.company') }}
                                    </th>
                                    <td>
                                        {{ $companyInvoice->company->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.tvde_week') }}
                                    </th>
                                    <td>
                                        {{ $companyInvoice->tvde_week->start_date ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.invoice') }}
                                    </th>
                                    <td>
                                        @foreach($companyInvoice->invoice as $key => $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.payment_receipt') }}
                                    </th>
                                    <td>
                                        @if($companyInvoice->payment_receipt)
                                            <a href="{{ $companyInvoice->payment_receipt->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.info') }}
                                    </th>
                                    <td>
                                        {{ $companyInvoice->info }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.companyInvoice.fields.payed') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $companyInvoice->payed ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.company-invoices.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection