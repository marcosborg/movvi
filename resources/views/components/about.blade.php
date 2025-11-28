<!-- Quem somos block -->
@php($abouts = $abouts ?? collect())
@forelse($abouts as $about)
    <section class="py-5 bg-light" id="about-preview">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="{{ optional($about->image)->getUrl() ?? 'https://picsum.photos/seed/about/600/400' }}" class="img-fluid rounded" alt="{{ $about->title ?? 'Sobre a Movvi' }}">
                </div>
                <div class="col-lg-6">
                    <h3>{{ $about->title ?? 'Sobre a Movvi' }}</h3>
                    @if($about->description)
                        <h4 class="mb-3">{{ $about->description }}</h4>
                    @endif
                    @if($about->text)
                        {!! $about->text !!}
                    @endif
                    @if($about->button)
                        <a href="{{ $about->link ?? '#' }}" class="btn btn-primary mt-3">{{ $about->button }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@empty
    <section class="py-5 bg-light" id="about-preview">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://picsum.photos/seed/about/600/400" class="img-fluid rounded" alt="Sobre a Movvi">
                </div>
                <div class="col-lg-6">
                    <h3>Sobre a Movvi</h3>
                    <h4 class="mb-3">Ligando pessoas e lugares com inovação</h4>
                    <p>Somos apaixonados por mobilidade e tecnologia. A Movvi nasce com a missão de ligar pessoas e lugares através de soluções de transporte modernas e acessíveis. Desde o aluguer de viaturas até tours personalizados, trabalhamos todos os dias para melhorar a experiência de condução e viagem dos nossos clientes.</p>
                    <a href="quem-somos.html" class="btn btn-primary mt-3">Saber mais</a>
                </div>
            </div>
        </div>
    </section>
@endforelse
