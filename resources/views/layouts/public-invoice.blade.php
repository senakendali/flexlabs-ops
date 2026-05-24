<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'FlexLabs Payment Invoice')</title>
    <meta
        name="description"
        content="@yield('meta_description', 'FlexLabs payment invoice page for webinar, workshop, and learning program registration.')"
    >
    <meta name="author" content="FlexLabs">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('og_title', 'FlexLabs Payment Invoice')">
    <meta property="og:description" content="@yield('og_description', 'Complete your FlexLabs payment securely.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="FlexLabs">
    <meta property="og:locale" content="id_ID">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'FlexLabs Payment Invoice')">
    <meta name="twitter:description" content="@yield('og_description', 'Complete your FlexLabs payment securely.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-image.png'))">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        flex: {
                            primary: '#5B3E8E',
                            primaryDark: '#4b3178',
                            primarySoft: '#F1ECFA',
                            dark: '#2b1d48',
                            ink: '#171321',
                            soft: '#F2F4FA',
                            page: '#F6F3FA',
                            line: '#e8e2f4',
                            muted: '#64748b',
                            success: '#15803D',
                            warning: '#B45309',
                        },
                    },
                    fontFamily: {
                        sans: ['Noto Sans', 'sans-serif'],
                    },
                    boxShadow: {
                        flexSoft: '0 18px 45px rgba(43, 29, 72, 0.16)',
                        flexCard: '0 24px 70px rgba(20, 12, 38, 0.22)',
                        invoice: '0 30px 90px rgba(25, 18, 43, 0.12)',
                    },
                },
            },
        };
    </script>

    {{-- Invoice shape/style lama dipanggil dari CSS ini. --}}
    <link rel="stylesheet" href="{{ asset('css/invoice.css') }}">

    @stack('styles')
</head>

<body class="bg-flex-page font-sans text-slate-900 antialiased">
    <header
        id="invoiceNavbar"
        class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-flex-primary py-3 shadow-[0_16px_42px_rgba(43,29,72,0.22)] backdrop-blur-xl transition-all duration-300 print:hidden"
    >
        <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
            <div class="flex min-h-[70px] items-center justify-between gap-4">
                <a
                    href="https://flexlabs.co.id"
                    class="inline-flex shrink-0 items-center"
                    aria-label="FlexLabs Home"
                >
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="FlexLabs Logo"
                        class="h-auto w-[180px] max-w-[180px] object-contain brightness-0 invert transition duration-300"
                    >
                </a>

                <div class="ml-auto flex items-center justify-end gap-3">
                    

                    <a
                        href="https://wa.me/62811134759?text=Halo%20FlexLabs%2C%20saya%20ingin%20bertanya%20tentang%20invoice%20pembayaran."
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-white/20 bg-white px-4 py-2 text-sm font-extrabold text-flex-primary shadow-[0_14px_30px_rgba(21,12,36,0.18)] transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-flex-primaryDark hover:shadow-[0_18px_38px_rgba(21,12,36,0.24)] sm:px-5"
                        target="_blank"
                        rel="noopener"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.142-4.03 7.5-9 7.5a10.2 10.2 0 0 1-3.24-.52L3 21l1.56-4.18C3.58 15.48 3 13.83 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                        </svg>
                        <span class="hidden sm:inline">Butuh Bantuan?</span>
                        <span class="sm:hidden">Bantuan</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="min-h-screen pt-[94px] print:pt-0">
        @yield('content')
    </main>

    <footer class="relative isolate mt-0 overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.12),transparent_34%),linear-gradient(135deg,#2b1d48_0%,#5B3E8E_48%,#3b2764_100%)] text-white print:hidden">
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
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.15 12 5.25l7.74 4.9L12 15.05l-7.74-4.9Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 11.75v4.25c0 1.24 2.35 2.25 5.25 2.25s5.25-1.01 5.25-2.25v-4.25" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5v4.75" />
                                </svg>
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
                                <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-white/55 transition group-hover:text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                                <span>AI-Powered Software Engineering</span>
                            </a>

                            <a
                                href="https://flexlabs.co.id/ui-ux-design-program/"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex w-fit items-start gap-2.5 text-sm font-bold leading-6 text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-white/55 transition group-hover:text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                                <span>Augmented UI/UX Design</span>
                            </a>
                        </div>
                    </div>

                    <div class="sm:col-span-6 lg:col-span-2">
                        <div class="mb-5 flex items-center gap-3 text-sm font-black uppercase tracking-[0.08em] text-white">
                            <span class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white/15 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18.75 3.75 21V6.75L9 4.5l6 2.25L20.25 4.5v14.25L15 21l-6-2.25Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v14.25M15 6.75V21" />
                                </svg>
                            </span>
                            Explore
                        </div>

                        <div class="grid gap-3">
                            <a
                                href="{{ url('/trial-class') }}"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <svg class="h-3.5 w-3.5 text-white/55 transition group-hover:text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                                Webinar
                            </a>

                            <a
                                href="{{ url('/workshop') }}"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <svg class="h-3.5 w-3.5 text-white/55 transition group-hover:text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                                Workshop
                            </a>

                            <a
                                href="https://flexlabs.co.id"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex w-fit items-center gap-2.5 text-sm font-bold text-white/75 no-underline transition duration-200 hover:translate-x-1 hover:text-white"
                            >
                                <svg class="h-3.5 w-3.5 text-white/55 transition group-hover:text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h9.69L9.72 5.53a.75.75 0 0 1 1.06-1.06l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 1 1-1.06-1.06l3.72-3.72H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                                About FlexLabs
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <div class="mb-5 flex items-center gap-3 text-sm font-black uppercase tracking-[0.08em] text-white">
                            <span class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white/15 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9M7.5 12h5.25M21 12c0 4.142-4.03 7.5-9 7.5a10.2 10.2 0 0 1-3.24-.52L3 21l1.56-4.18C3.58 15.48 3 13.83 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                                </svg>
                            </span>
                            Contact
                        </div>

                        <div class="rounded-3xl border border-white/15 bg-white/10 p-6">
                            <div class="flex gap-3.5 text-white/80">
                                <span class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-2xl bg-white/15 text-base text-white">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.37a1.5 1.5 0 0 0-1.02-1.42l-3.43-1.14a1.5 1.5 0 0 0-1.6.45l-.86.96a11.25 11.25 0 0 1-5.82-5.82l.96-.86a1.5 1.5 0 0 0 .45-1.6L9.29 5.27A1.5 1.5 0 0 0 7.87 4.25H6.5A2.25 2.25 0 0 0 4.25 6.5v.25Z" />
                                    </svg>
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
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
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
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6.75-5.6 6.75-11.25a6.75 6.75 0 0 0-13.5 0C5.25 15.4 12 21 12 21Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    </svg>
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
                            Ikuti webinar, workshop, atau hubungi admin untuk konsultasi program yang paling cocok.
                        </p>
                    </div>

                    <a
                        href="https://wa.me/62811134759"
                        class="inline-flex min-h-[46px] w-full items-center justify-center gap-2 rounded-full bg-white px-5 text-sm font-black text-flex-primary no-underline shadow-[0_14px_30px_rgba(21,12,36,0.18)] transition duration-200 hover:-translate-y-0.5 hover:text-flex-primaryDark hover:shadow-[0_18px_38px_rgba(21,12,36,0.24)] sm:w-auto"
                        target="_blank"
                        rel="noopener"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12c0 4.142-4.03 7.5-9 7.5a10.2 10.2 0 0 1-3.24-.52L3 21l1.56-4.18C3.58 15.48 3 13.83 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                        </svg>
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
                        Website
                    </a>

                    <a
                        href="{{ url('/trial-class') }}"
                        class="font-bold text-white/70 no-underline transition hover:text-white"
                    >
                        Webinar
                    </a>

                    <a
                        href="{{ url('/workshop') }}"
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
            const navbar = document.getElementById('invoiceNavbar');

            if (!navbar) {
                return;
            }

            const updateNavbarState = () => {
                const isScrolled = window.scrollY > 24;

                navbar.classList.toggle('shadow-[0_16px_42px_rgba(43,29,72,0.22)]', !isScrolled);
                navbar.classList.toggle('shadow-[0_20px_55px_rgba(43,29,72,0.30)]', isScrolled);
            };

            updateNavbarState();
            window.addEventListener('scroll', updateNavbarState, { passive: true });
        })();
    </script>

    @stack('scripts')
</body>
</html>
