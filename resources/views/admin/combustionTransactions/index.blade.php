@extends('layouts.admin')
@section('content')
<div class="content">
    @can('combustion_transaction_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <form id="combustionSupplierUploadForm" action="{{ route('admin.combustion-transactions.uploadSupplierFile') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                    @csrf
                    <div style="flex: 0 0 320px; min-width: 320px;">
                        <select name="tvde_week_id" id="combustion_tvde_week_id" class="select2" style="width: 100%;" required>
                            <option value="" selected disabled>Semana</option>
                            @foreach ($tvde_weeks as $tvde_week)
                            <option value="{{ $tvde_week->id }}" {{ (string) old('tvde_week_id') === (string) $tvde_week->id ? 'selected' : '' }}>{{ $tvde_week->start_date }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="supplier" id="combustionSupplierType" value="">
                    <input type="file" name="supplier_file" id="combustionSupplierFile" accept=".csv,.txt,.xlsx" style="display: none;" required>
                    <button type="button" class="btn btn-danger js-combustion-upload" data-supplier="repsol">REPSOL</button>
                    <button type="button" class="btn btn-primary js-combustion-upload" data-supplier="prio">PRIO</button>
                    <button type="button" class="btn btn-info js-combustion-upload" data-supplier="prio_combustao">Prio Combustão</button>
                </form>
                <form action="{{ route('admin.combustion-transactions.deleteFilter') }}" method="post" style="margin-top: 10px;">
                    @csrf
                    <select name="week_filter" class="select2" style="max-width: 200px;" required>
                        <option value="" selected disabled>Semana</option>
                        @foreach ($tvde_weeks as $tvde_week)
                        <option value="{{ $tvde_week->id }}">{{ $tvde_week->start_date }}</option>
                        @endforeach
                    </select>
                    <button onclick="return confirm('Tem certeza que deseja eliminar os abastecimentos desta semana?')" class="btn btn-danger" type="submit">
                        Eliminar semana
                    </button>
                </form>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.combustionTransaction.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-CombustionTransaction">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.tvde_week') }}
                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.card') }}
                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.date') }}
                                </th>
                                <th>
                                    Existe
                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.amount') }}
                                </th>
                                <th>
                                    {{ trans('cruds.combustionTransaction.fields.total') }}
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
  const weekSelect = document.getElementById('combustion_tvde_week_id');
  const supplierInput = document.getElementById('combustionSupplierType');
  const fileInput = document.getElementById('combustionSupplierFile');
  const uploadForm = document.getElementById('combustionSupplierUploadForm');

  if (weekSelect && supplierInput && fileInput && uploadForm) {
    $('.js-combustion-upload').on('click', function () {
      if (!weekSelect.value) {
        alert('Selecione uma semana antes de importar.');
        return;
      }

      supplierInput.value = $(this).data('supplier');
      fileInput.click();
    });

    fileInput.addEventListener('change', function () {
      if (!fileInput.files.length || !supplierInput.value) {
        return;
      }

      uploadForm.submit();
    });
  }

  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('combustion_transaction_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.combustion-transactions.massDestroy') }}",
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
    ajax: "{{ route('admin.combustion-transactions.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'tvde_week_start_date', name: 'tvde_week.start_date' },
{ data: 'card', name: 'card' },
{ data: 'date', name: 'date' },
{ data: 'exist', name: 'exist', orderable: false, searchable: false },
{ data: 'amount', name: 'amount' },
{ data: 'total', name: 'total' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-CombustionTransaction').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection
