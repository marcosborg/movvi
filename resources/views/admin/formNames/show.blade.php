@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.formName.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-names.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $formName->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $formName->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.description') }}
                                    </th>
                                    <td>
                                        {!! $formName->description !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.has_driver') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $formName->has_driver ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.has_license') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $formName->has_license ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.has_technician') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $formName->has_technician ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formName.fields.roles') }}
                                    </th>
                                    <td>
                                        @foreach($formName->roles as $key => $roles)
                                            <span class="label label-info">{{ $roles->title }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-names.index') }}">
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