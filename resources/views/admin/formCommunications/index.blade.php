@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.formCommunication.title') }}
                </div>
            </div>
        </div>
    </div>
    @foreach ($form_names as $row)
    <div class="row">
        @foreach ($row as $form_name)
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>{{ $form_name->name }}</h4>
                </div>
                @if ($form_name->description)
                <div class="panel-body">
                    {!! $form_name->description !!}
                </div>
                @endif
                <div class="panel-footer">
                    <a href="/admin/form-communications/form/{{ $form_name->id }}" class="btn btn-success">Ir para
                        formulário</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endsection