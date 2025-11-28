@extends('layouts.admin')
@section('content')
<div class="content">
    @can('weekly_vehicle_expense_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.weekly-vehicle-expenses.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.weeklyVehicleExpense.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'WeeklyVehicleExpense', 'route' => 'admin.weekly-vehicle-expenses.parseCsvImport'])
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.weeklyVehicleExpense.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-WeeklyVehicleExpense">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.vehicle_item') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.driver') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.tvde_week') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.total_km') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.weekly_km') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.extra_km') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.transfers') }}
                                </th>
                                <th>
                                    {{ trans('cruds.weeklyVehicleExpense.fields.deposit') }}
                                </th>
                                <th>
                                    &nbsp;
                                </th>
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
@can('weekly_vehicle_expense_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.weekly-vehicle-expenses.massDestroy') }}",
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
    ajax: "{{ route('admin.weekly-vehicle-expenses.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'vehicle_item_license_plate', name: 'vehicle_item.license_plate' },
{ data: 'driver_name', name: 'driver.name' },
{ data: 'tvde_week_start_date', name: 'tvde_week.start_date' },
{ data: 'total_km', name: 'total_km' },
{ data: 'weekly_km', name: 'weekly_km' },
{ data: 'extra_km', name: 'extra_km' },
{ data: 'transfers', name: 'transfers' },
{ data: 'deposit', name: 'deposit' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-WeeklyVehicleExpense').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection