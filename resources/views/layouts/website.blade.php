<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movvi.pt — Mobilidade à sua medida</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Swiper CSS for the image slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10.3.1/swiper-bundle.min.css" />
    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/website/assets/style.css">
    @yield('styles')
</head>
<body>

    <x-navbar />

    @yield('content')

    <!-- Footer -->
        <footer class="movvi-footer text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Movvi</h5>
                    <p>Servicos de mobilidade a sua medida: aluguer, stand e tours personalizados.</p>
                </div>
                <div class="col-md-8 mb-3">
                    <h5>Navegacao</h5>
                    @php($menuLinks = \App\Models\MenuLink::orderBy('position')->get())
                    <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0">
                        @forelse($menuLinks as $link)
                            <li><a href="{{ $link->url }}" class="text-white" @if($link->target) target="{{ $link->target }}" @endif>{{ $link->name }}</a></li>
                        @empty
                            <li><a href="{{ url('/') }}" class="text-white">Home</a></li>
                            <li><a href="{{ url('/quem-somos') }}" class="text-white">Quem Somos</a></li>
                            <li><a href="{{ route('website.stand') }}" class="text-white">Stand</a></li>
                            <li><a href="{{ route('website.rentals') }}" class="text-white">Aluguer</a></li>
                            <li><a href="{{ route('website.transfers') }}" class="text-white">Transfers & Tours</a></li>
                            <li><a href="{{ url('/contactos') }}" class="text-white">Contactos</a></li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="text-center mt-3">
                <small>2025 Movvi.pt - Todos os direitos reservados.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10.3.1/swiper-bundle.min.js"></script>
    <script>
        // Initialise Swiper slider
        const heroSwiper = new Swiper('.heroSwiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
    @yield('scripts')
</body>
</html>





