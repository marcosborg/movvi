<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyNewsArticleRequest;
use App\Http\Requests\StoreNewsArticleRequest;
use App\Http\Requests\UpdateNewsArticleRequest;
use App\Models\NewsArticle;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class NewsArticleController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('news_article_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $newsArticles = NewsArticle::orderBy('position')->get();

        return view('admin.newsArticles.index', compact('newsArticles'));
    }

    public function create()
    {
        abort_if(Gate::denies('news_article_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.newsArticles.create');
    }

    public function store(StoreNewsArticleRequest $request)
    {
        $newsArticle = NewsArticle::create($request->all());

        if ($request->input('image', false)) {
            $newsArticle->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $newsArticle->id]);
        }

        return redirect()->route('admin.news-articles.index');
    }

    public function edit(NewsArticle $newsArticle)
    {
        abort_if(Gate::denies('news_article_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.newsArticles.edit', compact('newsArticle'));
    }

    public function update(UpdateNewsArticleRequest $request, NewsArticle $newsArticle)
    {
        $newsArticle->update($request->all());

        if ($request->input('image', false)) {
            if (!$newsArticle->image || $request->input('image') !== $newsArticle->image->file_name) {
                if ($newsArticle->image) {
                    $newsArticle->image->delete();
                }
                $newsArticle->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
            }
        } elseif ($newsArticle->image) {
            $newsArticle->image->delete();
        }

        return redirect()->route('admin.news-articles.index');
    }

    public function show(NewsArticle $newsArticle)
    {
        abort_if(Gate::denies('news_article_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.newsArticles.show', compact('newsArticle'));
    }

    public function destroy(NewsArticle $newsArticle)
    {
        abort_if(Gate::denies('news_article_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $newsArticle->delete();

        return back();
    }

    public function massDestroy(MassDestroyNewsArticleRequest $request)
    {
        NewsArticle::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('news_article_create') && Gate::denies('news_article_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new NewsArticle();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
