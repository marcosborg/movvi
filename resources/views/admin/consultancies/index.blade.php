@extends('layouts.admin')
@section('content')
<div class="content">
    @can('consultancy_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.consultancies.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.consultancy.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.consultancy.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-Consultancy">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.name') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.company') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.value') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.start_date') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.consultancy.fields.end_date') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consultancies as $key => $consultancy)
                                    <tr data-entry-id="{{ $consultancy->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $consultancy->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $consultancy->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $consultancy->company->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $consultancy->value ?? '' }}
                                        </td>
                                        <td>
                                            {{ $consultancy->start_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $consultancy->end_date ?? '' }}
                                        </td>
                                        <td>
                                            @can('consultancy_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.consultancies.show', $consultancy->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('consultancy_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.consultancies.edit', $consultancy->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('consultancy_delete')
                                                <form action="{{ route('admin.consultancies.destroy', $consultancy->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('consultancy_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.consultancies.massDestroy') }}",
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
  let table = $('.datatable-Consultancy:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection