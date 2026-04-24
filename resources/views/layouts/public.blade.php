<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <!-- Mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary SEO -->
    <title>@yield('title', 'FlexLabs - Digital Academy & Software Engineering Program')</title>
    <meta
        name="description"
        content="@yield('meta_description', 'FlexLabs adalah digital academy dengan kurikulum berbasis industri untuk Software Engineering dan UI/UX Design. Belajar dengan project nyata, AI-assisted learning, dan peluang karir di perusahaan teknologi.')"
    >
    <meta
        name="keywords"
        content="FlexLabs, Software Engineering, UI UX, Coding Bootcamp, Belajar Programming, Laravel, Web Development, AI Learning, Digital Academy Indonesia"
    >
    <meta name="author" content="FlexLabs">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta
        property="og:title"
        content="@yield('og_title', 'FlexLabs - Digital Academy & Software Engineering Program')"
    >
    <meta
        property="og:description"
        content="@yield('og_description', 'Belajar Software Engineering dengan pendekatan real project dan AI-assisted learning di FlexLabs.')"
    >
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="FlexLabs">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta
        name="twitter:title"
        content="@yield('og_title', 'FlexLabs - Digital Academy')"
    >
    <meta
        name="twitter:description"
        content="@yield('og_description', 'Belajar Software Engineering dengan pendekatan real project dan AI.')"
    >
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-image.jpg'))">

    <!-- Security -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Vendor CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
</head>
<body>
    <header class="public-navbar" id="publicNavbar">
        <div class="container">
            <div class="navbar-inner d-flex align-items-center justify-content-between">
                <a href="@yield('brand_url', url('/trial-class'))" class="brand-logo" aria-label="FlexLabs Home">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="FlexLabs Logo"
                        class="brand-logo-img"
                        id="navbarLogo"
                    >
                </a>

                <nav class="d-flex align-items-center gap-4" aria-label="Public Navigation">
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
                                title="FlexLabs Location Map"
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

            if (!navbar) {
                return;
            }

            const updateNavbarState = () => {
                const isScrolled = window.scrollY > 24;
                navbar.classList.toggle('scrolled', isScrolled);
            };

            updateNavbarState();
            window.addEventListener('scroll', updateNavbarState);
        })();
    </script>

    @stack('scripts')
</body>
</html>