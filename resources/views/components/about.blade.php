@php
    $about = \App\Models\HomeInfo::first();
@endphp

<!-- ======= About Section ======= -->
<section id="about" class="about">
    <div class="container" data-aos="fade-up">

        <div class="row">

            <div class="col-lg-6 align-self-baseline" data-aos="zoom-in" data-aos-delay="100">
                @if ($about->image)
                <img src="{{ $about->image ? $about->image->getUrl() : '' }}" class="img-fluid" alt="">
                @endif

            </div>

            <div class="col-lg-6 pt-3 pt-lg-0 content">
                <h3 class="mt-4">{{ $about->title ?? '' }}</h3>
                <p class="fst-italic">
                    {{ $about->description ?? '' }}
                </p>
                {!! $about->text ?? '' !!}
                @if ($about->button && $about->link)
                <p>&nbsp;</p>
                <a href="{{ $about->link ?? '' }}" class="btn-theme mt-4">{{ $about->button ?? '' }}</a>
                @endif
            </div>

        </div>

    </div>
</section><!-- End About Section -->