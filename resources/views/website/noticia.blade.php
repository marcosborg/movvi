@extends('layouts.website')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase text-muted small mb-1">Notícias</p>
                <h1 class="h3 mb-0">{{ $newsArticle->title }}</h1>
                @if($newsArticle->created_at)
                    <small class="text-muted">{{ $newsArticle->created_at->format('d/m/Y') }}</small>
                @endif
            </div>
            <a class="btn btn-outline-secondary" href="{{ url('/#noticias') }}">Voltar</a>
        </div>

        @php
            $image = $newsArticle->image ? $newsArticle->image->getUrl() : null;
        @endphp

        <div class="clearfix">
            @if($image)
                <img src="{{ $image }}" class="img-fluid rounded shadow-sm float-md-end ms-md-4 mb-3" alt="{{ $newsArticle->title }}" style="max-width: 420px;">
            @endif

            <p class="lead">{{ $newsArticle->description }}</p>

            <div class="fs-6 lh-lg">
                {!! $newsArticle->content !!}
            </div>
        </div>
    </div>
</section>
@endsection
