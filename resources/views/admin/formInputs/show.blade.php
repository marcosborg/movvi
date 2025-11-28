@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.formInput.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-inputs.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $formInput->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.label') }}
                                    </th>
                                    <td>
                                        {{ $formInput->label }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $formInput->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.type') }}
                                    </th>
                                    <td>
                                        {{ App\Models\FormInput::TYPE_RADIO[$formInput->type] ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.form_name') }}
                                    </th>
                                    <td>
                                        {{ $formInput->form_name->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.required') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $formInput->required ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.formInput.fields.position') }}
                                    </th>
                                    <td>
                                        {{ $formInput->position }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.form-inputs.index') }}">
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