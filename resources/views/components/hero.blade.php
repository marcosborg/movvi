<!-- Image slider using Swiper -->
<section class="hero-section">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            @forelse($heroBanners ?? [] as $banner)
                @php
                    $hasTitle = filled($banner->title);
                    $hasSubtitle = filled($banner->subtitle);
                    $hasButton = filled($banner->button);
                    $hasCaption = $hasTitle || $hasSubtitle || $hasButton;
                @endphp
                <div class="swiper-slide">
                    <img src="{{ optional($banner->image)->getUrl() ?? 'https://picsum.photos/seed/movvi-default/1920/800' }}" class="d-block w-100" alt="{{ $banner->title ?? 'Hero banner' }}">
                    @if($hasCaption)
                        <div class="swiper-caption text-center">
                            @if($hasTitle)
                                <h2>{{ $banner->title }}</h2>
                            @endif
                            @if($hasSubtitle)
                                <p>{{ $banner->subtitle }}</p>
                            @endif
                            @if($hasButton)
                                <a class="btn btn-primary mt-3" href="{{ $banner->link ?? '#about-preview' }}">{{ $banner->button }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="swiper-slide">
                    <img src="https://picsum.photos/seed/movvi-fallback/1920/800" class="d-block w-100" alt="Mobilidade simplificada">
                    <div class="swiper-caption text-center">
                        <h2>Viagens que inspiram</h2>
                        <p>Do cedência ao tour, temos a solução perfeita para si.</p>
                        <a class="btn btn-primary mt-3" href="#about-preview">Saber mais</a>
                    </div>
                </div>
            @endforelse
        </div>
        <!-- If we need navigation buttons -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>
