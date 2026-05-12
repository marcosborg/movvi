<!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg movvi-navbar navbar-light fixed-top py-3">
        <div class="container">
            <a class="navbar-brand" href="/"><img src="/assets/website/assets/img/logo.png" alt=""></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                @php($menuLinks = \App\Models\MenuLink::orderBy('position')->get())
                <ul class="navbar-nav ms-auto">
                    @forelse($menuLinks as $link)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $link->url }}" @if($link->target) target="{{ $link->target }}" @endif>
                                {{ $link->name }}
                            </a>
                        </li>
                    @empty
                        <li class="nav-item"><a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/quem-somos') }}">Quem Somos</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('website.stand') }}">Stand</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('website.rentals') }}">Aluguer</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('website.transfers') }}">Transfers & Tours</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/contactos') }}">Contactos</a></li>
                    @endforelse
                    <li class="nav-item ms-lg-3 dropdown">
                        @auth
                            <a class="btn btn-outline-movvi dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 1a3 3 0 0 0-3 3v3H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1V4a3 3 0 0 0-3-3zm1 6V4a1 1 0 1 0-2 0v3z"/>
                                    </svg>
                                </span>
                                <span class="fw-semibold">Area Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" role="button">Gestão</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.home') }}">Acesso ao MOVVI</a></li>
                                        <li><a class="dropdown-item" href="https://dashboard.movvi.com.pt">Dashboard de gestão</a></li>
                                        <li><a class="dropdown-item disabled" href="#" aria-disabled="true">App de Agenda</a></li>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" role="button">Motoristas</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.home') }}">Acesso ao MOVVI</a></li>
                                        <li><a class="dropdown-item" href="https://dashboard.movvi.com.pt/motorista/login">Dashboard de Motorista</a></li>
                                    </ul>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();">
                                        <span aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M8 1a3 3 0 0 0-3 3v3H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1V4a3 3 0 0 0-3-3zm1 6V4a1 1 0 1 0-2 0v3z"/>
                                            </svg>
                                        </span>
                                        Logout
                                    </a>
                                </li>
                            </ul>
                        @else
                            <a class="btn btn-outline-movvi dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 1a3 3 0 0 0-3 3v3H4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1V4a3 3 0 0 0-3-3zm1 6V4a1 1 0 1 0-2 0v3z"/>
                                    </svg>
                                </span>
                                <span class="fw-semibold">Area Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" role="button">Gestão</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('login') }}">Acesso ao MOVVI</a></li>
                                        <li><a class="dropdown-item" href="https://dashboard.movvi.com.pt">Dashboard de gestão</a></li>
                                        <li><a class="dropdown-item disabled" href="#" aria-disabled="true">App de Agenda</a></li>
                                    </ul>
                                </li>
                                <li class="dropdown-submenu">
                                    <a class="dropdown-item dropdown-toggle" href="#" role="button">Motoristas</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('login') }}">Acesso ao MOVVI</a></li>
                                        <li><a class="dropdown-item" href="https://dashboard.movvi.com.pt/motorista/login">Dashboard de Motorista</a></li>
                                    </ul>
                                </li>
                            </ul>
                        @endauth
                    </li>
                </ul>
                @auth
                    <form id="navbar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endauth
            </div>
        </div>
    </nav>
