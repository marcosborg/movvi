@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.recruitmentForm.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.recruitment-forms.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.company') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->company->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.name') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.email') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->email }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.cv') }}
                                    </th>
                                    <td>
                                        @if($recruitmentForm->cv)
                                            <a href="{{ $recruitmentForm->cv->getUrl() }}" target="_blank">
                                                {{ trans('global.view_file') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.contact_successfully') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $recruitmentForm->contact_successfully ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.phone') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->phone }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.scheduled_interview') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $recruitmentForm->scheduled_interview ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.appointment') }}
                                    </th>
                                    <td>
                                        {{ $recruitmentForm->appointment }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.done') }}
                                    </th>
                                    <td>
                                        <input type="checkbox" disabled="disabled" {{ $recruitmentForm->done ? 'checked' : '' }}>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.recruitmentForm.fields.comments') }}
                                    </th>
                                    <td>
                                        {!! $recruitmentForm->comments !!}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.recruitment-forms.index') }}">
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