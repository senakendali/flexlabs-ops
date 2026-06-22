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
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <!-- Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        flex: {
                            primary: '#5B3E8E',
                            primaryDark: '#4b3178',
                            dark: '#2b1d48',
                            soft: '#F2F4FA',
                            line: '#e8e2f4',
                            muted: '#64748b',
                        },
                    },
                    fontFamily: {
                        sans: ['Noto Sans', 'sans-serif'],
                    },
                    boxShadow: {
                        flexSoft: '0 18px 45px rgba(43, 29, 72, 0.16)',
                        flexCard: '0 24px 70px rgba(20, 12, 38, 0.22)',
                    },
                },
            },
        };
    </script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GT-KTPL2PKG"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('set', 'linker', {
            domains: [
                'flexlabs.co.id',
                'workshop.flexlabs.co.id',
                'webinar.flexlabs.co.id',
                'event.flexlabs.co.id',
                'konsultasi.flexlabs.co.id'
            ]
        });

        gtag('config', 'GT-KTPL2PKG');
        gtag('config', 'AW-17838931358');
    </script>

    @stack('styles')
</head>

<body class="bg-white font-sans text-slate-900 antialiased">
    @php
        $currentHost = request()->getHost();
        $currentRouteName = (string) optional(request()->route())->getName();

        $workshopHomeUrl = 'https://workshop.flexlabs.co.id';
        $webinarHomeUrl = 'https://webinar.flexlabs.co.id';
        $eventHomeUrl = 'https://event.flexlabs.co.id';

        $brandUrlFromSection = trim($__env->yieldContent('brand_url', ''));

        $isEventPublicPage = $currentHost === 'event.flexlabs.co.id'
            || request()->is('event*')
            || str_starts_with($currentRouteName, 'events.')
            || str_starts_with($currentRouteName, 'local.events.');

        $defaultHomeUrl = match ($currentHost) {
            'workshop.flexlabs.co.id' => $workshopHomeUrl,
            'webinar.flexlabs.co.id' => $webinarHomeUrl,
            default => request()->is('workshop*')
                ? url('/workshop')
                : (request()->is('trial-class*') ? url('/trial-class') : url('/')),
        };

        /*
        |--------------------------------------------------------------------------
        | Logo home URL
        |--------------------------------------------------------------------------
        | On real subdomains, the logo must always go to the subdomain root.
        | This prevents workshop pages that define @section('brand_url', url('/workshop'))
        | from becoming https://workshop.flexlabs.co.id/workshop.
        |
        | For local/legacy URLs, the page-level brand_url is still respected.
        |--------------------------------------------------------------------------
        */
        $publicHomeUrl = match ($currentHost) {
            'workshop.flexlabs.co.id' => $workshopHomeUrl,
            'webinar.flexlabs.co.id' => $webinarHomeUrl,
            default => $brandUrlFromSection !== '' ? $brandUrlFromSection : $defaultHomeUrl,
        };

        $webinarNavUrl = $currentHost === 'webinar.flexlabs.co.id'
            ? url('/')
            : $webinarHomeUrl;

        $workshopNavUrl = $currentHost === 'workshop.flexlabs.co.id'
            ? url('/')
            : $workshopHomeUrl;
    @endphp

    <header
        id="publicNavbar"
        class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-flex-primary py-3 shadow-[0_16px_42px_rgba(43,29,72,0.22)] backdrop-blur-xl transition-all duration-300"
    >
        <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
            <div class="flex min-h-[70px] items-center justify-between gap-4">
                @if($isEventPublicPage)
                    <div
                        class="inline-flex shrink-0 cursor-default select-none items-center"
                        aria-label="FlexLabs Logo"
                    >
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="FlexLabs Logo"
                            class="h-auto w-[180px] max-w-[180px] object-contain brightness-0 invert transition duration-300"
                            id="navbarLogo"
                            draggable="false"
                        >
                    </div>
                @else
                    <a
                        href="{{ $publicHomeUrl }}"
                        class="inline-flex shrink-0 items-center"
                        aria-label="FlexLabs Home"
                    >
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="FlexLabs Logo"
                            class="h-auto w-[180px] max-w-[180px] object-contain brightness-0 invert transition duration-300"
                            id="navbarLogo"
                            draggable="false"
                        >
                    </a>
                @endif

                <div class="ml-auto flex items-center justify-end gap-3">
                    <a
                        href="https://wa.me/62811134759?text=Halo%20FlexLabs%2C%20saya%20ingin%20konsultasi%20program."
                        class="hidden min-h-11 items-center justify-center gap-2 rounded-full border border-white/20 bg-white px-4 py-2 text-sm font-extrabold text-flex-primary shadow-[0_14px_30px_rgba(21,12,36,0.18)] transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-flex-primaryDark hover:shadow-[0_18px_38px_rgba(21,12,36,0.24)] sm:inline-flex sm:px-5"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp text-base"></i>
                        <span>Konsultasi Gratis</span>
                    </a>

                    <button
                        type="button"
                        id="publicMobileMenuButton"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-xl text-white transition duration-200 hover:bg-white/15 md:hidden"
                        aria-label="Open menu"
                        aria-expanded="false"
                    >
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </div>

            <div
                id="publicMobileMenu"
                class="hidden border-t border-white/10 pb-4 pt-3 md:hidden"
            >
                <div class="grid gap-2">
                    <a
                        href="{{ $webinarNavUrl }}"
                        class="flex min-h-12 items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/15 {{ request()->is('trial-class*') || $currentHost === 'webinar.flexlabs.co.id' ? 'bg-white/15' : '' }}"
                    >
                        <span>Webinar</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    <a
                        href="{{ $workshopNavUrl }}"
                        class="flex min-h-12 items-center justify-between rounded-2xl bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/15 {{ request()->is('workshop*') || $currentHost === 'workshop.flexlabs.co.id' ? 'bg-white/15' : '' }}"
                    >
                        <span>Workshop</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    <a
                        href="https://wa.me/62811134759?text=Halo%20FlexLabs%2C%20saya%20ingin%20konsultasi%20program."
                        class="mt-2 inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-black text-flex-primary shadow-[0_14px_30px_rgba(21,12,36,0.18)] transition hover:-translate-y-0.5 hover:text-flex-primaryDark"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Konsultasi Gratis
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="min-h-screen">
        @yield('content')
    </main>

    <footer class="relative isolate mt-0 overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.12),transparent_34%),linear-gradient(135deg,#2b1d48_0%,#5B3E8E_48%,#3b2764_100%)] text-white">
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-45 [background-image:linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)] [background-size:44px_44px]"></div>

        <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
            <div class="py-14 lg:py-[72px] lg:pb-9">
                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-5">
                        <div class="h-full rounded-[1.75rem] border border-white/15 bg-white/10 p-6 shadow-flexCard backdrop-blur-xl sm:p-8">
                            <img
                                src="{{ asset('images/logo.png') }}"
                                alt="FlexLabs Logo"
                                class="mb-5 h-auto w-[180px] max-w-full brightness-0 invert"
                            >

                            <p class="max-w-[460px] text-[0.95rem] leading-7 text-white/80">
                                Flexlabs merupakan akademi digital pertama di Indonesia yang menghadirkan kurikulum rancangan khusus untuk mempersiapkan peserta didik agar kompetitif di industri Teknologi Informasi (TI). Selain itu, para peserta juga memiliki peluang untuk direkrut oleh PT System Ever Indonesia, sebuah anak perusahaan dari perusahaan ERP terkemuka di Asia, yakni YoungLimWon Soft Lab Co., Ltd.
                            </p>
                        </div>
                    </div>

                    <div class="sm:col-span-6 lg:col-span-2">
                        <div class="mb-5 flex items-center gap-3 text-sm font-black uppercase tracking-[0.08em] text-white">
                            <span class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white/15 text-white">
                                <i class="bi bi-mortarboard"></i>
                            </span>
                            Program
                        </div>

                        <div class="grid gap-3">
                            <a
                                href="https://flexlabs.co.id/software-engineer-program/"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex w-fit items-start gap-2.5 text-sm font-bold leading-6 text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right mt-0.5 text-xs text-white/55 transition group-hover:text-white"></i>
                                <span>AI-Powered Software Engineering</span>
                            </a>

                            <a
                                href="https://flexlabs.co.id/ui-ux-design-program/"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex w-fit items-start gap-2.5 text-sm font-bold leading-6 text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right mt-0.5 text-xs text-white/55 transition group-hover:text-white"></i>
                                <span>Augmented UI/UX Design</span>
                            </a>
                        </div>
                    </div>

                    <div class="sm:col-span-6 lg:col-span-2">
                        <div class="mb-5 flex items-center gap-3 text-sm font-black uppercase tracking-[0.08em] text-white">
                            <span class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white/15 text-white">
                                <i class="bi bi-compass"></i>
                            </span>
                            Explore
                        </div>

                        <div class="grid gap-3">
                            <a
                                href="{{ $webinarNavUrl }}"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right text-xs text-white/55 transition group-hover:text-white"></i>
                                Webinar
                            </a>

                            <a
                                href="{{ $workshopNavUrl }}"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right text-xs text-white/55 transition group-hover:text-white"></i>
                                Workshop
                            </a>

                            <a
                                href="{{ $eventHomeUrl }}"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right text-xs text-white/55 transition group-hover:text-white"></i>
                                Event
                            </a>

                            <a
                                href="https://flexlabs.co.id"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <i class="bi bi-arrow-right text-xs text-white/55 transition group-hover:text-white"></i>
                                About FlexLabs
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <div class="mb-5 flex items-center gap-3 text-sm font-black uppercase tracking-[0.08em] text-white">
                            <span class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white/15 text-white">
                                <i class="bi bi-chat-dots"></i>
                            </span>
                            Contact
                        </div>

                        <div class="rounded-3xl border border-white/15 bg-white/10 p-6">
                            <div class="flex gap-3.5 text-white/80">
                                <span class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-2xl bg-white/15 text-base text-white">
                                    <i class="bi bi-telephone"></i>
                                </span>

                                <div>
                                    <div class="mb-1 text-[0.78rem] font-black uppercase tracking-[0.08em] text-white/60">
                                        Call Admin
                                    </div>

                                    <p class="m-0 text-[0.95rem] font-extrabold leading-6 text-white">
                                        <a
                                            href="tel:+62811134759"
                                            class="text-white no-underline hover:underline"
                                        >
                                            0811134759
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-3.5 border-t border-white/15 pt-4 text-white/80">
                                <span class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-2xl bg-white/15 text-base text-white">
                                    <i class="bi bi-clock"></i>
                                </span>

                                <div>
                                    <div class="mb-1 text-[0.78rem] font-black uppercase tracking-[0.08em] text-white/60">
                                        Operational Hours
                                    </div>

                                    <p class="m-0 text-[0.95rem] font-extrabold leading-6 text-white">
                                        09:00 – 21:00 WIB
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-3.5 border-t border-white/15 pt-4 text-white/80">
                                <span class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-2xl bg-white/15 text-base text-white">
                                    <i class="bi bi-geo-alt"></i>
                                </span>

                                <div>
                                    <div class="mb-1 text-[0.78rem] font-black uppercase tracking-[0.08em] text-white/60">
                                        Location
                                    </div>

                                    <p class="m-0 text-sm leading-7 text-white/80">
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

                <div class="mt-8 flex flex-col gap-5 rounded-3xl border border-white/15 bg-white/10 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="m-0 text-base font-black text-white">
                            Mau mulai belajar bareng FlexLabs?
                        </p>

                        <p class="mt-1 text-sm leading-6 text-white/75">
                            Ikuti webinar, workshop, event, atau hubungi admin untuk konsultasi program yang paling cocok.
                        </p>
                    </div>

                    <a
                        href="https://wa.me/62811134759"
                        class="inline-flex min-h-[46px] w-full items-center justify-center gap-2 rounded-full bg-white px-5 text-sm font-black text-flex-primary no-underline shadow-[0_14px_30px_rgba(21,12,36,0.18)] transition duration-200 hover:-translate-y-0.5 hover:text-flex-primaryDark hover:shadow-[0_18px_38px_rgba(21,12,36,0.24)] sm:w-auto"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Chat Admin
                    </a>
                </div>
            </div>

            <div class="flex flex-col gap-4 border-t border-white/15 py-6 text-sm text-white/65 md:flex-row md:items-center md:justify-between">
                <div>
                    © {{ date('Y') }} FlexLabs. All rights reserved.
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <a
                        href="https://flexlabs.co.id"
                        target="_blank"
                        rel="noopener"
                        class="font-bold text-white/70 no-underline transition hover:text-white"
                    >
                        Flexlabs
                    </a>

                    <a
                        href="{{ $eventHomeUrl }}"
                        class="font-bold text-white/70 no-underline transition hover:text-white"
                    >
                        Event
                    </a>

                    <a
                        href="{{ $webinarNavUrl }}"
                        class="font-bold text-white/70 no-underline transition hover:text-white"
                    >
                        Webinar
                    </a>

                    <a
                        href="{{ $workshopNavUrl }}"
                        class="font-bold text-white/70 no-underline transition hover:text-white"
                    >
                        Workshop
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const navbar = document.getElementById('publicNavbar');
            const mobileButton = document.getElementById('publicMobileMenuButton');
            const mobileMenu = document.getElementById('publicMobileMenu');

            if (navbar) {
                const updateNavbarState = () => {
                    const isScrolled = window.scrollY > 24;

                    navbar.classList.toggle('shadow-[0_16px_42px_rgba(43,29,72,0.22)]', !isScrolled);
                    navbar.classList.toggle('shadow-[0_20px_55px_rgba(43,29,72,0.30)]', isScrolled);
                };

                updateNavbarState();
                window.addEventListener('scroll', updateNavbarState, { passive: true });
            }

            if (mobileButton && mobileMenu) {
                mobileButton.addEventListener('click', () => {
                    const isOpen = !mobileMenu.classList.contains('hidden');

                    mobileMenu.classList.toggle('hidden', isOpen);
                    mobileButton.setAttribute('aria-expanded', String(!isOpen));

                    const icon = mobileButton.querySelector('i');

                    if (icon) {
                        icon.classList.toggle('bi-list', isOpen);
                        icon.classList.toggle('bi-x-lg', !isOpen);
                    }
                });
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
