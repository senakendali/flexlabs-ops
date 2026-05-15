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
    <meta name="twitter:image" content="@yield('og_image', asset('og-image.jpg'))">

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
    <link rel="stylesheet" href="{{ asset('css/public.css?v=3') }}">
    <link rel="stylesheet" href="{{ asset('css/public-invoice.css?v=3') }}">

    <style>
        /*
        |--------------------------------------------------------------------------
        | Modern Public Footer
        |--------------------------------------------------------------------------
        */

        .modern-footer {
            position: relative;
            overflow: hidden;
            margin-top: 0;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.12), transparent 34%),
                linear-gradient(135deg, #2b1d48 0%, #5B3E8E 48%, #3b2764 100%);
            color: #ffffff;
        }

        .modern-footer::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 44px 44px;
            opacity: 0.45;
            pointer-events: none;
        }

        .modern-footer .container {
            position: relative;
            z-index: 1;
        }

        .footer-modern-main {
            padding: 72px 0 34px;
        }

        .footer-brand-card {
            height: 100%;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.10);
            box-shadow: 0 24px 70px rgba(20, 12, 38, 0.22);
            backdrop-filter: blur(14px);
        }

        .footer-modern-logo {
            width: 170px;
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }

        .footer-brand-text {
            max-width: 460px;
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.95rem;
            line-height: 1.75;
        }

        .footer-powered-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.86);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .footer-modern-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .footer-modern-title .title-icon {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
        }

        .footer-modern-links {
            display: grid;
            gap: 12px;
        }

        .footer-modern-links a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.94rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .footer-modern-links a i {
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .footer-modern-links a:hover {
            color: #ffffff;
            transform: translateX(4px);
        }

        .footer-modern-links a:hover i {
            color: #ffffff;
        }

        .footer-contact-card {
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.10);
        }

        .footer-contact-item {
            display: flex;
            gap: 14px;
            color: rgba(255, 255, 255, 0.78);
        }

        .footer-contact-item + .footer-contact-item {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .footer-contact-icon {
            display: inline-flex;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-size: 1.05rem;
        }

        .footer-contact-label {
            margin-bottom: 4px;
            color: rgba(255, 255, 255, 0.58);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .footer-contact-value {
            margin: 0;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.55;
        }

        .footer-contact-value a {
            color: #ffffff;
            text-decoration: none;
        }

        .footer-contact-value a:hover {
            text-decoration: underline;
        }

        .footer-address-text {
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .footer-cta-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: 34px;
            padding: 22px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.11);
        }

        .footer-cta-title {
            margin: 0;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
        }

        .footer-cta-text {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.9rem;
        }

        .footer-cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            background: #ffffff;
            color: #5B3E8E;
            font-size: 0.92rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 14px 30px rgba(21, 12, 36, 0.18);
            transition: all 0.2s ease;
        }

        .footer-cta-button:hover {
            color: #4b3178;
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(21, 12, 36, 0.24);
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 0 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.86rem;
        }

        .footer-bottom-links {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer-bottom-links a {
            color: rgba(255, 255, 255, 0.68);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-bottom-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 991.98px) {
            .footer-modern-main {
                padding-top: 54px;
            }

            .footer-cta-strip {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 767.98px) {
            .footer-brand-card {
                padding: 24px;
                border-radius: 24px;
            }

            .footer-modern-logo {
                width: 150px;
            }

            .footer-bottom {
                align-items: flex-start;
                flex-direction: column;
            }

            .footer-cta-button {
                width: 100%;
            }
        }
    </style>
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

                <nav class="d-flex align-items-center gap-3" aria-label="Public Navigation">
                    <a
                        href="https://wa.me/62811134759?text=Halo%20FlexLabs%2C%20saya%20ingin%20konsultasi%20program."
                        class="nav-consultation-button"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Konsultasi Gratis
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer modern-footer">
        <div class="container">
            <div class="footer-modern-main">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="footer-brand-card">
                            <img
                                src="{{ asset('images/logo.png') }}"
                                alt="FlexLabs Logo"
                                class="footer-modern-logo"
                            >

                            <p class="footer-brand-text">
                                Flexlabs merupakan akademi digital pertama di Indonesia yang menghadirkan kurikulum rancangan khusus untuk mempersiapkan peserta didik agar kompetitif di industri Teknologi Informasi (TI). Selain itu, para peserta juga memiliki peluang untuk direkrut oleh PT System Ever Indonesia, sebuah anak perusahaan dari perusahaan ERP terkemuka di Asia, yakni YoungLimWon Soft Lab Co., Ltd.
                            </p>

                           
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="footer-modern-title">
                            <span class="title-icon">
                                <i class="bi bi-mortarboard"></i>
                            </span>
                            Program
                        </div>

                        <div class="footer-modern-links">
                            <a href="https://flexlabs.co.id/software-engineer-program/" target="_blank">
                                <i class="bi bi-arrow-right"></i>
                                AI-Powered Software Engineering
                            </a>

                            <a href="https://flexlabs.co.id/ui-ux-design-program/" target="_blank">
                                <i class="bi bi-arrow-right"></i>
                                Augmented UI/UX Design
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="footer-modern-title">
                            <span class="title-icon">
                                <i class="bi bi-compass"></i>
                            </span>
                            Explore
                        </div>

                        <div class="footer-modern-links">
                            <a href="{{ url('/trial-class') }}">
                                <i class="bi bi-arrow-right"></i>
                                Trial Class
                            </a>

                            <a href="{{ url('/workshop') }}">
                                <i class="bi bi-arrow-right"></i>
                                Workshop
                            </a>

                            <a href="https://flexlabs.co.id">
                                <i class="bi bi-arrow-right"></i>
                                About FlexLabs
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="footer-modern-title">
                            <span class="title-icon">
                                <i class="bi bi-chat-dots"></i>
                            </span>
                            Contact
                        </div>

                        <div class="footer-contact-card">
                            <div class="footer-contact-item">
                                <span class="footer-contact-icon">
                                    <i class="bi bi-telephone"></i>
                                </span>

                                <div>
                                    <div class="footer-contact-label">Call Admin</div>
                                    <p class="footer-contact-value">
                                        <a href="tel:+62811134759">0811134759</a>
                                    </p>
                                </div>
                            </div>

                            <div class="footer-contact-item">
                                <span class="footer-contact-icon">
                                    <i class="bi bi-clock"></i>
                                </span>

                                <div>
                                    <div class="footer-contact-label">Operational Hours</div>
                                    <p class="footer-contact-value">
                                        09:00 – 21:00 WIB
                                    </p>
                                </div>
                            </div>

                            <div class="footer-contact-item">
                                <span class="footer-contact-icon">
                                    <i class="bi bi-geo-alt"></i>
                                </span>

                                <div>
                                    <div class="footer-contact-label">Location</div>
                                    <p class="footer-address-text">
                                        MyRepublic Plaza Wing B 2nd Floor<br>
                                        Jl. BSD Grand Boulevard<br>
                                        BSD Green Office Park BSD City<br>
                                        Sampora, Cisauk, Tangerang 15345
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer-cta-strip">
                    <div>
                        <p class="footer-cta-title">
                            Mau mulai belajar bareng FlexLabs?
                        </p>
                        <p class="footer-cta-text">
                            Ikuti trial class atau hubungi admin untuk konsultasi program yang paling cocok.
                        </p>
                    </div>

                    <a
                        href="https://wa.me/62811134759"
                        class="footer-cta-button"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Chat Admin
                    </a>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    © {{ date('Y') }} FlexLabs. All rights reserved.
                </div>

                <div class="footer-bottom-links">
                    <a href="https://flexlabs.co.id">Website</a>
                    <a href="{{ url('/trial-class') }}">Trial Class</a>
                    <a href="{{ url('/workshop') }}">Workshop</a>
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