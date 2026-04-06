@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.driversBalance.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.drivers-balances.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.driver') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.tvde_week') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->tvde_week->start_date ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.value') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->value }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.last_balance') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->last_balance }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.new_balance') }}
                                    </th>
                                    <td>
                                        {{ $driversBalance->new_balance }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Estado semanal
                                    </th>
                                    <td>
                                        {{ $driversBalance->manual_status_label ?? 'Sem estado definido' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.drivers-balances.index') }}">
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
