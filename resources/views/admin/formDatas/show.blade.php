@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.formData.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-datas.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $formData->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.form_name') }}
                                    </th>
                                    <td>
                                        {{ $formData->form_name->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.driver') }}
                                    </th>
                                    <td>
                                        {{ $formData->driver->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.vehicle_item') }}
                                    </th>
                                    <td>
                                        {{ $formData->vehicle_item->license_plate ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.user') }}
                                    </th>
                                    <td>
                                        {{ $formData->user->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.data') }}
                                    </th>
                                    <td>
                                        @foreach (json_decode($formData->data) as $key => $item)
                                        @if (strpos($key, 'photos') !== false)
                                        @php
                                        $item = json_decode($item, true);
                                        if (isset($item[1])) {
                                        $item = '<a href="https://Tribos&Montanhas.pt/storage/' . $item[1] . '" target="_new"><img src="https://Tribos&Montanhas.pt/storage/' . $item[1] . '" class="img-thumbnail" width="100"></a>';
                                        } else {
                                        $item = '';
                                        }
                                        @endphp
                                        {!! $item !!}<br>
                                        @else
                                        <strong>{{ $key }}: </strong>{{ $item }}<br>
                                        @endif
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.created_at') }}
                                    </th>
                                    <td>
                                        {{ $formData->created_at }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formData.fields.solved') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $formData->solved ? 'checked' : ''
                                        }}>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-datas.index') }}">
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