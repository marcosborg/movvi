@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.show') }} {{ trans('cruds.newsArticle.title') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.news-articles.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.id') }}
                                    </th>
                                    <td>
                                        {{ $newsArticle->id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.title') }}
                                    </th>
                                    <td>
                                        {{ $newsArticle->title }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.description') }}
                                    </th>
                                    <td>
                                        {{ $newsArticle->description }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.image') }}
                                    </th>
                                    <td>
                                        @if($newsArticle->image)
                                            <a href="{{ $newsArticle->image->getUrl() }}" target="_blank" style="display: inline-block">
                                                <img src="{{ $newsArticle->image->getUrl('thumb') }}">
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.content') }}
                                    </th>
                                    <td>
                                        {!! $newsArticle->content !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        {{ trans('cruds.newsArticle.fields.position') }}
                                    </th>
                                    <td>
                                        {{ $newsArticle->position }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group">
                            <a class="btn btn-default" href="{{ route('admin.news-articles.index') }}">
                                {{ trans('global.back_to_list') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>
@endsection
