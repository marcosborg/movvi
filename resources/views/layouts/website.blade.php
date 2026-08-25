<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/website/assets/favicon.ico" type="image/x-icon">
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
                    <p>Servicos de mobilidade a sua medida: cedência, stand e tours personalizados.</p>
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
                            <li><a href="{{ route('website.rentals') }}" class="text-white">Cedência</a></li>
                            <li><a href="{{ route('website.transfers') }}" class="text-white">Transfers & Tours</a></li>
                            <li><a href="{{ url('/contactos') }}" class="text-white">Contactos</a></li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 mb-3">
                    <h5>Apoio aos motoristas</h5>
                    <div class="row footer-support-list">
                        <div class="col-md-4 mb-3">
                            <a href="https://wa.me/351925120962" target="_blank" rel="noopener noreferrer" class="footer-support-card">
                                <div>
                                    <strong>Richard</strong>
                                    <span>Gestao de Frota &amp; Manutencao</span>
                                    <span>925 120 962</span>
                                </div>
                                <span class="footer-support-action" aria-hidden="true">
                                    <svg viewBox="0 0 16 16" focusable="false">
                                        <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326ZM7.994 14.521a6.57 6.57 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592Zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.066-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.65s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.473.205.842.327 1.13.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232Z"/>
                                    </svg>
                                </span>
                                <span class="visually-hidden">Contactar Richard por WhatsApp</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="https://wa.me/351913606801" target="_blank" rel="noopener noreferrer" class="footer-support-card">
                                <div>
                                    <strong>Karla</strong>
                                    <span>Apoio ao Sistema Movvi</span>
                                    <span>913 606 801</span>
                                </div>
                                <span class="footer-support-action" aria-hidden="true">
                                    <svg viewBox="0 0 16 16" focusable="false">
                                        <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326ZM7.994 14.521a6.57 6.57 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592Zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.066-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.65s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.473.205.842.327 1.13.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232Z"/>
                                    </svg>
                                </span>
                                <span class="visually-hidden">Contactar Karla por WhatsApp</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="https://wa.me/351913606800" target="_blank" rel="noopener noreferrer" class="footer-support-card">
                                <div>
                                    <strong>Adelmo</strong>
                                    <span>Outros Assuntos</span>
                                    <span>913 606 800</span>
                                </div>
                                <span class="footer-support-action" aria-hidden="true">
                                    <svg viewBox="0 0 16 16" focusable="false">
                                        <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326ZM7.994 14.521a6.57 6.57 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592Zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.066-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.65s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.473.205.842.327 1.13.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232Z"/>
                                    </svg>
                                </span>
                                <span class="visually-hidden">Contactar Adelmo por WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <small>
                    2026 Movvi.pt - Todos os direitos reservados.
                    <span class="mx-2">|</span>
                    <a href="{{ route('website.privacy-policy') }}" class="text-white">Política de Privacidade</a>
                </small>
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

        document.querySelectorAll('.movvi-navbar .dropdown-submenu > .dropdown-toggle').forEach((toggle) => {
            toggle.addEventListener('click', (event) => {
                if (!window.matchMedia('(max-width: 991.98px)').matches) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const submenu = toggle.closest('.dropdown-submenu');
                const parentMenu = submenu.closest('.dropdown-menu');

                parentMenu.querySelectorAll('.dropdown-submenu.is-open').forEach((openSubmenu) => {
                    if (openSubmenu !== submenu) {
                        openSubmenu.classList.remove('is-open');
                    }
                });

                submenu.classList.toggle('is-open');
            });
        });

        document.querySelectorAll('.movvi-navbar .dropdown-submenu .dropdown-menu').forEach((menu) => {
            menu.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        document.querySelectorAll('.movvi-navbar > .container .dropdown').forEach((dropdown) => {
            dropdown.addEventListener('hidden.bs.dropdown', () => {
                dropdown.querySelectorAll('.dropdown-submenu.is-open').forEach((submenu) => {
                    submenu.classList.remove('is-open');
                });
            });
        });
    </script>
    @yield('scripts')
</body>
</html>


