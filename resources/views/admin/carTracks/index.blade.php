@extends('layouts.admin')
@section('content')
<div class="content">
    @can('car_track_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <form id="viaVerdeUploadForm" action="{{ route('admin.car-tracks.uploadViaVerde') }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
                    @csrf
                    <div style="flex: 0 0 320px; min-width: 320px;">
                        <select name="tvde_week_id" id="via_verde_tvde_week_id" class="select2" style="width: 100%;" required>
                            <option value="" selected disabled>Semana</option>
                            @foreach ($tvde_weeks as $tvde_week)
                            <option value="{{ $tvde_week->id }}" {{ (string) old('tvde_week_id') === (string) $tvde_week->id ? 'selected' : '' }}>{{ $tvde_week->start_date }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="file" name="via_verde_file" id="viaVerdeFile" accept=".csv,.txt,.xlsx" style="display: none;" required>
                    <button type="button" class="btn btn-primary" id="viaVerdeUploadButton">Via Verde</button>
                </form>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.carTrack.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-CarTrack">
                        <thead>
                            <tr>
                                <th width="10">

                                </th>
                                <th>
                                    {{ trans('cruds.carTrack.fields.id') }}
                                </th>
                                <th>
                                    {{ trans('cruds.carTrack.fields.tvde_week') }}
                                </th>
                                <th>
                                    {{ trans('cruds.carTrack.fields.date') }}
                                </th>
                                <th>
                                    {{ trans('cruds.carTrack.fields.license_plate') }}
                                </th>
                                <th>
                                    {{ trans('cruds.carTrack.fields.value') }}
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
  const weekSelect = document.getElementById('via_verde_tvde_week_id');
  const fileInput = document.getElementById('viaVerdeFile');
  const uploadButton = document.getElementById('viaVerdeUploadButton');
  const uploadForm = document.getElementById('viaVerdeUploadForm');

  if (weekSelect && fileInput && uploadButton && uploadForm) {
    uploadButton.addEventListener('click', function () {
      if (!weekSelect.value) {
        alert('Selecione uma semana antes de importar.');
        return;
      }

      fileInput.click();
    });

    fileInput.addEventListener('change', function () {
      if (!fileInput.files.length) {
        return;
      }

      uploadForm.submit();
    });
  }

  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('car_track_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.car-tracks.massDestroy') }}",
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
    ajax: "{{ route('admin.car-tracks.index') }}",
   columns: [
  { data: 'placeholder', name: 'placeholder' },
  { data: 'id', name: 'car_tracks.id' },
  { data: 'tvde_week_start_date', name: 'tvde_weeks.start_date' },
  { data: 'date', name: 'car_tracks.date' },
  { data: 'license_plate', name: 'car_tracks.license_plate' },
  { data: 'value', name: 'car_tracks.value' },
  { data: 'actions', name: 'actions' }
],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-CarTrack').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection
