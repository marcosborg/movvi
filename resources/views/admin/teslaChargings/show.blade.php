@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.teslaCharging.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.tesla-chargings.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $teslaCharging->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.value') }}
                                    </th>
                                    <td>
                                        {{ $teslaCharging->value }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.license') }}
                                    </th>
                                    <td>
                                        {{ $teslaCharging->license ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.datetime') }}
                                    </th>
                                    <td>
                                        {{ $teslaCharging->datetime ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.card_type') }}
                                    </th>
                                    <td>
                                        {{ \App\Models\TeslaCharging::CARD_TYPE_SELECT[$teslaCharging->card_type] ?? $teslaCharging->card_type }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.tesla-chargings.index') }}">
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
