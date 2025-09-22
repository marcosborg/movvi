@extends('layouts.admin')
@section('content')
<div class="content">
    @can('drivers_balance_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.drivers-balances.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.driversBalance.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.driversBalance.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-DriversBalance">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        Company
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.driver') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driver.fields.code') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.tvde_week') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.tvdeWeek.fields.end_date') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.value') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.balance') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.driversBalance.fields.drivers_balance') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($driversBalances as $key => $driversBalance)
                                    <tr data-entry-id="{{ $driversBalance->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $driversBalance->driver->company->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->driver->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->driver->code ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->tvde_week->start_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->tvde_week->end_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->value ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->balance ?? '' }}
                                        </td>
                                        <td>
                                            {{ $driversBalance->drivers_balance ?? '' }}
                                        </td>
                                        <td>
                                            @can('drivers_balance_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.drivers-balances.show', $driversBalance->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('drivers_balance_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.drivers-balances.edit', $driversBalance->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('drivers_balance_delete')
                                                <form action="{{ route('admin.drivers-balances.destroy', $driversBalance->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('drivers_balance_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.drivers-balances.massDestroy') }}",
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
  let table = $('.datatable-DriversBalance:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection