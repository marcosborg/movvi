@extends('layouts.admin')
@section('content')
<div class="content">
    @can('news_article_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.news-articles.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.newsArticle.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.newsArticle.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-NewsArticle">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.title') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.description') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.image') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.position') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newsArticles as $key => $newsArticle)
                                    <tr data-entry-id="{{ $newsArticle->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $newsArticle->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $newsArticle->title ?? '' }}
                                        </td>
                                        <td>
                                            {{ $newsArticle->description ?? '' }}
                                        </td>
                                        <td>
                                            @if($newsArticle->image)
                                                <a href="{{ $newsArticle->image->getUrl() }}" target="_blank" style="display: inline-block">
                                                    <img src="{{ $newsArticle->image->getUrl('thumb') }}">
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $newsArticle->position ?? '' }}
                                        </td>
                                        <td>
                                            @can('news_article_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.news-articles.show', $newsArticle->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('news_article_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.news-articles.edit', $newsArticle->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('news_article_delete')
                                                <form action="{{ route('admin.news-articles.destroy', $newsArticle->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
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
@can('news_article_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.news-articles.massDestroy') }}",
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
  let table = $('.datatable-NewsArticle:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection
