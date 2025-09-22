@extends('layouts.admin')
@section('content')
<div class="content">
    @can('form_input_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.form-inputs.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.formInput.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.formInput.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-FormInput">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.label') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.type') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.form_name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.required') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.formInput.fields.position') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($formInputs as $key => $formInput)
                                    <tr data-entry-id="{{ $formInput->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $formInput->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $formInput->label ?? '' }}
                                        </td>
                                        <td>
                                            {{ $formInput->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ App\Models\FormInput::TYPE_RADIO[$formInput->type] ?? '' }}
                                        </td>
                                        <td>
                                            {{ $formInput->form_name->name ?? '' }}
                                        </td>
                                        <td>
                                            <span style="display:none">{{ $formInput->required ?? '' }}</span>
                                            <input type="checkbox" disabled="disabled" {{ $formInput->required ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            {{ $formInput->position ?? '' }}
                                        </td>
                                        <td>
                                            @can('form_input_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.form-inputs.show', $formInput->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('form_input_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.form-inputs.edit', $formInput->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('form_input_delete')
                                                <form action="{{ route('admin.form-inputs.destroy', $formInput->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                                </form>
                                            @endcan

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
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('form_input_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.form-inputs.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-FormInput:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection