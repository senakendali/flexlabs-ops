<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FlexLabs')</title>

    <meta name="description" content="Trial Class FlexLabs">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
</head>
<body>
    <header class="public-navbar" id="publicNavbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between navbar-inner">
                <a href="{{ url('/trial-class') }}" class="brand-logo">
                    <img
                        src="{{ asset('images/logo-black.png') }}"
                        alt="FlexLabs Logo"
                        class="brand-logo-img"
                        id="navbarLogo"
                        data-logo-default="{{ asset('images/logo-black.png') }}"
                        data-logo-scrolled="{{ asset('images/logo.png') }}"
                    >
                </a>

                <nav class="d-flex align-items-center gap-4">
                    <a href="https://flexlabs.co.id" class="nav-public-link">
                        About FlexLabs
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="container">
            <div class="footer-main">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-brand-block">
                            <img
                                src="{{ asset('images/logo.png') }}"
                                alt="FlexLabs Logo"
                                class="footer-logo"
                            >

                            <div class="footer-address">
                                <div>MyRepublic Plaza Wing B 2nd Floor</div>
                                <div>Jl. BSD Grand Boulevard</div>
                                <div>BSD Green Office Park BSD City</div>
                                <div>Desa Sampora, Kec. Cisauk</div>
                                <div>Tangerang 15345</div>
                            </div>

                            <div class="footer-powered">
                                Powered by PT System Ever Indonesia
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="footer-title">Our Program</div>
                        <div class="footer-links">
                            <a href="javascript:void(0)">AI-Powered Software Engineering</a>
                            <a href="javascript:void(0)">Augmented UI/UX Design</a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="footer-title">Contact</div>
                        <div class="footer-contact">
                            <div>0811134759</div>
                            <div>(Call Admin)</div>
                            <div class="mt-3">Operational Hours:</div>
                            <div>09:00 – 21:00</div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="footer-map-wrap">
                            <iframe
                                src="https://www.google.com/maps?q=BSD%20Green%20Office%20Park%206&t=&z=15&ie=UTF8&iwloc=&output=embed"
                                width="100%"
                                height="245"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function () {
            const navbar = document.getElementById('publicNavbar');
            const logo = document.getElementById('navbarLogo');

            if (!navbar || !logo) return;

            const defaultLogo = logo.dataset.logoDefault;
            const scrolledLogo = logo.dataset.logoScrolled;

            function updateNavbarState() {
                const isScrolled = window.scrollY > 24;

                navbar.classList.toggle('scrolled', isScrolled);
                logo.src = isScrolled ? scrolledLogo : defaultLogo;
            }

            updateNavbarState();
            window.addEventListener('scroll', updateNavbarState);
        })();
    </script>

    @stack('scripts')
</body>
</html>