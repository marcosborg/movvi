<section class="py-5" id="noticias">
    <div class="container">
        <h3 class="mb-4 text-center">Últimas Notícias</h3>
        @if($newsArticles->isEmpty())
            <p class="text-center text-muted mb-0">Sem notícias no momento.</p>
        @else
            <div class="row g-4">
                @foreach($newsArticles as $article)
                    @php
                        $image = $article->image
                            ? $article->image->getUrl()
                            : 'https://via.placeholder.com/600x400?text=Noticia';
                    @endphp
                    <div class="col-md-4">
                        <div class="card h-100">
                            <img src="{{ $image }}" class="card-img-top" alt="{{ $article->title }}">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $article->title }}</h5>
                                <p class="card-text">{{ \Illuminate\Support\Str::limit($article->description, 140) }}</p>
                                <a href="{{ route('website.noticias.show', $article) }}" class="btn btn-outline-primary mt-auto">Ler mais</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
