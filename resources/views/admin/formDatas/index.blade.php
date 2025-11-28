@extends('layouts.admin')
@section('content')
<div class="content">
    @can('form_data_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-md-6">
            <a class="btn btn-success" href="{{ route('admin.form-datas.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.formData.title_singular') }}
            </a>
        </div>
        <div class="col-md-6">
            <div class="pull-right">
                <a href="/admin/form-datas?status=unsolved" class="btn btn-{{ request()->query('status') == 'unsolved' ? 'primary' : 'default' }} btn-sm">Não tratado</a>
                <a href="/admin/form-datas?status=solved" class="btn btn-{{ request()->query('status') == 'solved' ? 'primary' : 'default' }} btn-sm">Tratado</a>
                <a href="/admin/form-datas?status=all" class="btn btn-{{ request()->query('status') == 'all' ? 'primary' : 'default' }} btn-sm">Todos</a>
            </div>
        </div>
    </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.formData.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table
                        class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-FormData">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.form_name') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.driver') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.vehicle_item') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.user') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.data') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.created_at') }}
                                </th>
                                <th>
                                    {{ trans('cruds.formData.fields.solved') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            <tr>
                                <td>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($form_names as $key => $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($drivers as $key => $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($vehicle_items as $key => $item)
                                        <option value="{{ $item->license_plate }}">{{ $item->license_plate }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="search">
                                        <option value>{{ trans('global.all') }}</option>
                                        @foreach($users as $key => $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                            </tr>
                        </thead>
                    </table>
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
@can('form_data_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.form-datas.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
          return entry.id
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

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "/admin/form-datas?status={{ request()->query('status') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'form_name_name', name: 'form_name.name' },
{ data: 'driver_name', name: 'driver.name' },
{ data: 'vehicle_item_license_plate', name: 'vehicle_item.license_plate' },
{ data: 'user_name', name: 'user.name' },
{ data: 'data', name: 'data' },
{ data: 'created_at', name: 'created_at' },
{ data: 'solved', name: 'solved' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-FormData').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
let visibleColumnsIndexes = null;
$('.datatable thead').on('input', '.search', function () {
      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value

      let index = $(this).parent().index()
      if (visibleColumnsIndexes !== null) {
        index = visibleColumnsIndexes[index]
      }

      table
        .column(index)
        .search(value, strict)
        .draw()
  });
table.on('column-visibility.dt', function(e, settings, column, state) {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) {
          visibleColumnsIndexes.push(colIdx);
      });
  })
});

</script>
@endsection