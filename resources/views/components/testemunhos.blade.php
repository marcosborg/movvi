<section class="py-5 bg-light" id="testemunhos">
    <div class="container">
        <h3 class="mb-4 text-center">O que dizem os nossos clientes</h3>

        @if($testimonials->isEmpty())
            <p class="text-center text-muted mb-0">Sem testemunhos disponíveis.</p>
        @else
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($testimonials as $testimonial)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <div class="d-flex flex-column align-items-center">
                                <p class="lead text-center">“{{ $testimonial->message }}”</p>
                                <h5 class="fw-bold mb-0">{{ $testimonial->name }}</h5>
                                @if($testimonial->job_position)
                                    <span class="text-muted">{{ $testimonial->job_position }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Seguinte</span>
                </button>
            </div>
        @endif
    </div>
</section>
