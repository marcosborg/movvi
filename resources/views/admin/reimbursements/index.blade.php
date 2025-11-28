@extends('layouts.admin')
@section('content')
<div class="content">
    @can('reimbursement_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.reimbursements.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.reimbursement.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.reimbursement.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-Reimbursement">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.value') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.file') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.verified') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.driver') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.reimbursement.fields.tvde_week') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reimbursements as $key => $reimbursement)
                                    <tr data-entry-id="{{ $reimbursement->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $reimbursement->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $reimbursement->value ?? '' }}
                                        </td>
                                        <td>
                                            @if($reimbursement->file)
                                                <a href="{{ $reimbursement->file->getUrl() }}" target="_blank">
                                                    {{ trans('global.view_file') }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            <span style="display:none">{{ $reimbursement->verified ?? '' }}</span>
                                            <input type="checkbox" disabled="disabled" {{ $reimbursement->verified ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            {{ $reimbursement->driver->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $reimbursement->tvde_week->start_date ?? '' }}
                                        </td>
                                        <td>
                                            @can('reimbursement_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.reimbursements.show', $reimbursement->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('reimbursement_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.reimbursements.edit', $reimbursement->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('reimbursement_delete')
                                                <form action="{{ route('admin.reimbursements.destroy', $reimbursement->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('reimbursement_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.reimbursements.massDestroy') }}",
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
  let table = $('.datatable-Reimbursement:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection