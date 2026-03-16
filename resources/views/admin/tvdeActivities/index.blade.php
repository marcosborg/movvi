@extends('layouts.admin')
@section('content')
<div class="content">
    @can('tvde_activity_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                @if (!$activeCompany)
                    <div class="alert alert-warning" style="margin-bottom: 10px;">
                        Nao existe nenhuma empresa ativa definida. Selecione uma empresa antes de importar ficheiros Uber ou Bolt.
                    </div>
                @endif
                <form id="platformCsvUploadForm" action="{{ route('admin.tvde-activities.uploadPlatformCsv') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                    @csrf
                    <div style="flex: 0 0 320px; min-width: 320px;">
                    <select name="tvde_week_id" id="platform_tvde_week_id" class="select2" style="width: 100%;" required>
                        <option value="" selected disabled>Semana</option>
                        @foreach ($tvde_weeks as $tvde_week)
                        <option value="{{ $tvde_week->id }}" {{ (string) old('tvde_week_id') === (string) $tvde_week->id ? 'selected' : '' }}>{{ $tvde_week->start_date }}</option>
                        @endforeach
                    </select>
                    </div>
                    <input type="hidden" name="platform" id="platformCsvType" value="">
                    <input type="file" name="csv_file" id="platformCsvFile" accept=".csv,.txt" style="display: none;" required>
                    <button type="button" class="btn btn-info js-platform-upload" data-platform="uber" style="flex: 0 0 auto;" {{ !$activeCompany ? 'disabled' : '' }}>UBER</button>
                    <button type="button" class="btn btn-primary js-platform-upload" data-platform="bolt" style="flex: 0 0 auto;" {{ !$activeCompany ? 'disabled' : '' }}>BOLT</button>
                </form>
                <form action="/admin/tvde-activities/delete-filter" method="post" style="margin-top: 10px;">
                @csrf
                <select name="week_filter" class="select2" style="max-width: 200px;">
                    <option selected disabled>Semana</option>
                    @foreach ($tvde_weeks as $tvde_week)
                    <option value="{{ $tvde_week->id }}">{{ $tvde_week->start_date }}</option>
                    @endforeach
                </select>
                <select name="company_filter" class="select2" style="max-width: 200px;">
                    <option selected disabled>Empresa</option>
                    @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                <button onclick="return confirm('Tem certeza que deseja eliminar os dados do filtro?')" class="btn btn-danger" data-toggle="modal" type="submit">
                    Eliminar seleção de filtro
                </button>
                </form>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.tvdeActivity.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-TvdeActivity">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.tvde_week') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.tvde_operator') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.company') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.driver_code') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.gross') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.net') }}
                                </th>
                                <th>
                                    {{ trans('cruds.tvdeActivity.fields.tips') }}
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
  const weekSelect = document.getElementById('platform_tvde_week_id');
  const platformInput = document.getElementById('platformCsvType');
  const fileInput = document.getElementById('platformCsvFile');
  const uploadForm = document.getElementById('platformCsvUploadForm');
  const hasActiveCompany = @json((bool) $activeCompany);

  if (weekSelect && platformInput && fileInput && uploadForm) {
    $('.js-platform-upload').on('click', function () {
      if (!hasActiveCompany) {
        alert('Nao existe nenhuma empresa ativa definida para esta importacao.');
        return;
      }

      if (!weekSelect.value) {
        alert('Selecione uma semana antes de importar.');
        return;
      }

      platformInput.value = $(this).data('platform');
      fileInput.click();
    });

    fileInput.addEventListener('change', function () {
      if (!fileInput.files.length || !platformInput.value) {
        return;
      }

      uploadForm.submit();
    });
  }

  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('tvde_activity_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.tvde-activities.massDestroy') }}",
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
    ajax: "{{ route('admin.tvde-activities.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'tvde_week_start_date', name: 'tvde_week.start_date' },
{ data: 'tvde_operator_name', name: 'tvde_operator.name' },
{ data: 'company_name', name: 'company.name' },
{ data: 'driver_code', name: 'driver_code' },
{ data: 'gross', name: 'gross' },
{ data: 'net', name: 'net' },
{ data: 'tips', name: 'tips' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-TvdeActivity').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection
