<nav id="navbar" class="navbar">
    <ul>
        <li><a class="nav-link active" href="/">Home</a></li>
        <li><a class="nav-link" href="#contact">Contact</a></li>
        @auth
        <li><a class="getstarted" href="/admin">Dashboard</a></li>
        @else
        <li><a class="getstarted" href="/login">Login</a></li>
        @endauth
    </ul>
    <i class="bi bi-list mobile-nav-toggle"></i>
</nav><!-- .navbar -->