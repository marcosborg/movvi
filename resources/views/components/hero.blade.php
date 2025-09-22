@php
    $hero = \App\Models\HeroBanner::first();
@endphp

<!-- ======= Hero Section ======= -->
<section id="hero" style="background-image: url('{{ $hero->image ? $hero->image->getUrl() : '' }}')">
    <div class="hero-container" data-aos="fade-up" data-aos-delay="150">
        <h1>{{ $hero->title ?? '' }}</h1>
        <h2>{{ $hero->subtitle ?? '' }}</h2>
        <div class="d-flex">
            <a href="{{ $hero->link }}" class="btn-get-started">{{ $hero->button ?? '' }}</a>&nbsp;&nbsp;
            @auth
            <a href="/admin" class="btn-get-started scrollto">Dashboard</a>
            @else
            <a href="/login" class="btn-get-started scrollto">Login</a>
            @endauth
        </div>
    </div>
</section><!-- End Hero -->