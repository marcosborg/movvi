@extends('layouts.admin')
@section('content')
<div class="content">
    @can('company_expense_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.company-expenses.create') }}">
                    <i class="fas fa-plus"></i> Registar despesa / conta a pagar
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Central de despesas da empresa e contas a pagar
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-CompanyExpense">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.name') }}
                                </th>
                                <th>Categoria</th>
                                <th>Fornecedor</th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.company') }}
                                </th>
                                <th>
                                    Valor (€)
                                </th>
                                <th>Tipo</th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.start_date') }}
                                </th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.end_date') }}
                                </th>
                                <th>
                                    {{ trans('cruds.companyExpense.fields.qty') }}
                                </th>
                                <th>Vencimento</th>
                                <th>Estado</th>
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
@can('company_expense_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.company-expenses.massDestroy') }}",
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
    ajax: "{{ route('admin.company-expenses.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'name', name: 'name' },
{ data: 'category', name: 'category' },
{ data: 'supplier', name: 'supplier' },
{ data: 'company_name', name: 'company.name' },
{ data: 'weekly_value', name: 'weekly_value' },
{ data: 'recurrence', name: 'recurrence' },
{ data: 'start_date', name: 'start_date' },
{ data: 'end_date', name: 'end_date' },
{ data: 'qty', name: 'qty' },
{ data: 'due_date', name: 'due_date' },
{ data: 'payment_status', name: 'payment_status' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-CompanyExpense').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection
