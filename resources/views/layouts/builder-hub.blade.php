<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'FlexLabs Builder Hub')</title>

    <meta
        name="description"
        content="@yield('meta_description', 'Trial class, workshop, dan materi praktik FlexLabs untuk belajar teknologi berbasis project nyata.')"
    >
    <meta name="author" content="FlexLabs">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('og_title', 'FlexLabs Builder Hub')">
    <meta property="og:description" content="@yield('og_description', 'Belajar teknologi lewat trial class dan workshop berbasis project nyata di FlexLabs.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="FlexLabs">
    <meta property="og:locale" content="id_ID">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'FlexLabs Builder Hub')">
    <meta name="twitter:description" content="@yield('og_description', 'Belajar teknologi lewat trial class dan workshop berbasis project nyata di FlexLabs.')">
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
                    fontFamily: {
                        sans: ['Noto Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        flex: {
                            primary: '#5B3E8E',
                            primaryDark: '#473073',
                            primarySoft: '#F0EAF8',
                            primarySoft2: '#F8F5FC',
                            dark: '#1F1B2E',
                            muted: '#737082',
                            soft: '#F5F3FA',
                            line: '#E5E1EE',
                            page: '#F2F4FA',
                            panel: '#FFFFFF',
                            ink: '#2D2938',
                        },
                    },
                    boxShadow: {
                        soft: '0 22px 70px rgba(31, 27, 46, 0.08)',
                        card: '0 16px 45px rgba(31, 27, 46, 0.07)',
                        button: '0 14px 30px rgba(91, 62, 142, 0.22)',
                    },
                },
            },
        };
    </script>

    <style>
        html {
            scroll-behavior: smooth;
            background: #F2F4FA;
        }

        body {
            font-family: "Noto Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
            background: #F2F4FA;
        }

        [x-cloak] {
            display: none !important;
        }

        .builder-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .builder-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .builder-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(91, 62, 142, 0.22);
            border-radius: 999px;
        }

        .builder-content h1,
        .builder-content h2,
        .builder-content h3,
        .builder-content h4 {
            color: #1F1B2E;
            font-weight: 900;
            letter-spacing: -0.045em;
        }

        .builder-content p {
            color: #4F4A5E;
            line-height: 1.82;
        }

        .builder-content .builder-rich a {
            color: #5B3E8E;
            font-weight: 800;
            text-decoration: none;
        }

        .builder-content .builder-rich a:hover {
            text-decoration: underline;
        }

        .builder-content img {
            max-width: 100%;
            height: auto;
        }

        .builder-header {
            isolation: isolate;
            background: #F2F4FA;
        }

        .builder-main-card {
            background: #ffffff !important;
            background-image: none !important;
        }

        .builder-body-grid {
            align-items: start;
        }

        .builder-sidebar,
        .builder-body-grid > main {
            margin-top: 0;
        }

        .builder-sidebar > div,
        .builder-main-card {
            margin-top: 0;
        }

        @media print {
            .builder-header,
            .builder-sidebar,
            .builder-mobile-menu,
            .builder-footer,
            .builder-before-content {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .builder-main-card {
                box-shadow: none !important;
                border: 0 !important;
                padding: 0 !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen bg-flex-page text-flex-dark antialiased">
    @php
        $whatsappNumber = '62811134759';
        $whatsappText = trim($__env->yieldContent('whatsapp_text', 'Halo FlexLabs, saya ingin konsultasi trial class atau workshop.'));
        $whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . urlencode($whatsappText);

        $builderMenus = [
            [
                'label' => 'Trial Class',
                'description' => 'Coba dulu sebelum join program',
                'url' => url('/trial-class'),
                'active' => request()->is('trial-class*'),
                'icon' => 'play',
            ],
            [
                'label' => 'Workshop',
                'description' => 'Kelas singkat berbasis praktik',
                'url' => url('/workshop'),
                'active' => request()->is('workshop*'),
                'icon' => 'academic',
            ],
            [
                'label' => 'Program FlexLabs',
                'description' => 'Lihat program utama FlexLabs',
                'url' => 'https://flexlabs.co.id',
                'active' => false,
                'external' => true,
                'icon' => 'sparkles',
            ],
        ];
    @endphp

    <div class="relative min-h-screen overflow-visible bg-flex-page">
        <div class="relative mx-auto flex min-h-screen w-full max-w-[1780px] flex-col px-4 pb-6 sm:px-6 lg:px-8">
            <header class="builder-header sticky top-0 z-50 -mx-4 bg-flex-page px-4 py-5 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="mx-auto flex w-full max-w-[1780px] items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-5">
                        <a
                            href="@yield('brand_url', url('/trial-class'))"
                            class="inline-flex shrink-0 items-center"
                            aria-label="FlexLabs Home"
                        >
                            <img
                                src="{{ asset('images/logo-black.png') }}"
                                alt="FlexLabs Logo"
                                class="w-[180px] max-w-[180px] object-contain"
                            >
                        </a>

                        <div class="hidden h-9 w-px bg-flex-line md:block"></div>

                        <a
                            href="javascript:history.back()"
                            class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-flex-dark shadow-sm ring-1 ring-flex-line transition hover:-translate-y-0.5 hover:bg-flex-primary hover:text-white md:inline-flex"
                            aria-label="Kembali"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

                        <div class="hidden min-w-0 lg:block">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-flex-primary">
                                @yield('page_kicker', 'Builder Hub')
                            </p>

                            <h1 class="mt-0.5 truncate text-xl font-black tracking-[-0.04em] text-flex-dark">
                                @yield('page_title', 'FlexLabs Builder Hub')
                            </h1>
                        </div>
                    </div>

                    <nav class="hidden items-center gap-2 xl:flex" aria-label="Builder Hub Navigation">
                        <a
                            href="{{ url('/trial-class') }}"
                            class="rounded-full px-4 py-2.5 text-sm font-black transition {{ request()->is('trial-class*') ? 'bg-flex-primary text-white shadow-button' : 'text-flex-muted hover:bg-white hover:text-flex-primary hover:shadow-sm' }}"
                        >
                            Trial Class
                        </a>

                        <a
                            href="{{ url('/workshop') }}"
                            class="rounded-full px-4 py-2.5 text-sm font-black transition {{ request()->is('workshop*') ? 'bg-flex-primary text-white shadow-button' : 'text-flex-muted hover:bg-white hover:text-flex-primary hover:shadow-sm' }}"
                        >
                            Workshop
                        </a>
                    </nav>

                    <div class="flex shrink-0 items-center gap-3">
                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-flex-primary px-5 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.52 0 .19 5.32.19 11.87c0 2.09.55 4.14 1.6 5.94L.09 24l6.34-1.66a11.9 11.9 0 0 0 5.63 1.43h.01c6.55 0 11.88-5.32 11.88-11.87 0-3.17-1.24-6.15-3.43-8.42Zm-8.46 18.28h-.01a9.86 9.86 0 0 1-5.03-1.38l-.36-.22-3.76.99 1-3.66-.24-.38a9.82 9.82 0 0 1-1.5-5.24c0-5.45 4.44-9.88 9.9-9.88a9.84 9.84 0 0 1 6.99 2.9 9.82 9.82 0 0 1 2.9 7c0 5.44-4.44 9.87-9.89 9.87Zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.04-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.08-.79.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/>
                            </svg>

                            <span class="hidden sm:inline">
                                WhatsApp
                            </span>
                        </a>
                    </div>
                </div>
            </header>

            <div class="builder-mobile-menu mb-5 grid gap-3 lg:hidden">
                <div class="rounded-[1.75rem] border border-white/80 bg-white p-5 shadow-card">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-flex-primary">
                        @yield('page_kicker', 'Builder Hub')
                    </p>

                    <h2 class="mt-1 text-2xl font-black tracking-[-0.05em] text-flex-dark">
                        @yield('page_title', 'FlexLabs Builder Hub')
                    </h2>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1 builder-scrollbar">
                    @foreach ($builderMenus as $menu)
                        <a
                            href="{{ $menu['url'] }}"
                            @if (! empty($menu['external'])) target="_blank" rel="noopener" @endif
                            class="flex shrink-0 items-center gap-2 rounded-full px-4 py-3 text-sm font-black shadow-sm transition {{ $menu['active'] ? 'bg-flex-primary text-white' : 'bg-white text-flex-dark hover:text-flex-primary' }}"
                        >
                            {{ $menu['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="builder-body-grid grid flex-1 grid-cols-1 items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)] xl:grid-cols-[390px_minmax(0,1fr)]">
                <aside class="builder-sidebar hidden self-start lg:block">
                    <div class="rounded-[2rem] border border-white/80 bg-white p-6 shadow-soft">
                        @hasSection('sidebar')
                            @yield('sidebar')
                        @else
                            <div class="mb-7 flex items-center gap-4">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h10.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-flex-primary">
                                        Explore
                                    </p>

                                    <h2 class="text-lg font-black tracking-[-0.04em] text-flex-dark">
                                        Builder Hub
                                    </h2>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach ($builderMenus as $menu)
                                    <a
                                        href="{{ $menu['url'] }}"
                                        @if (! empty($menu['external'])) target="_blank" rel="noopener" @endif
                                        class="group flex items-center gap-4 rounded-[1.5rem] px-4 py-4 transition {{ $menu['active'] ? 'bg-flex-primary text-white shadow-button' : 'bg-flex-soft text-flex-dark hover:bg-flex-primarySoft hover:text-flex-primary' }}"
                                    >
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $menu['active'] ? 'bg-white/20 text-white' : 'bg-white text-flex-primary shadow-sm' }}">
                                            @if ($menu['icon'] === 'play')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd"/>
                                                </svg>
                                            @elseif ($menu['icon'] === 'academic')
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347M4.26 10.147A59.433 59.433 0 0 1 12 3.493a59.433 59.433 0 0 1 7.74 6.654M4.26 10.147 12 14.625l7.74-4.478" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.25 8.25 17.75 10l-.5-1.75a2.7 2.7 0 0 0-1.85-1.85L13.65 6l1.75-.5a2.7 2.7 0 0 0 1.85-1.85l.5-1.75.5 1.75a2.7 2.7 0 0 0 1.85 1.85l1.75.5-1.75.5a2.7 2.7 0 0 0-1.85 1.85Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block truncate text-base font-black">
                                                {{ $menu['label'] }}
                                            </span>

                                            <span class="{{ $menu['active'] ? 'text-white/75' : 'text-flex-muted' }} mt-0.5 block truncate text-sm font-semibold">
                                                {{ $menu['description'] }}
                                            </span>
                                        </span>

                                        @if ($menu['active'])
                                            <span class="ml-auto flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-flex-primary">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="m4.5 12.75 6 6 9-13.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>

                            <div class="mt-6 rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                                    Need Help?
                                </p>

                                <h3 class="mt-2 text-lg font-black tracking-[-0.04em] text-flex-dark">
                                    Bingung pilih kelas?
                                </h3>

                                <p class="mt-2 text-sm font-semibold leading-6 text-flex-muted">
                                    Chat admin FlexLabs buat tanya trial, workshop, atau program yang paling cocok.
                                </p>

                                <a
                                    href="{{ $whatsappUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-5 py-3 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
                                >
                                    Chat WhatsApp
                                </a>
                            </div>
                        @endif
                    </div>
                </aside>

                <main class="min-w-0 self-start">
                    @hasSection('before_content')
                        <div class="builder-before-content mb-6">
                            @yield('before_content')
                        </div>
                    @endif

                    <section class="builder-main-card min-h-[calc(100vh-9rem)] rounded-[2rem] border border-white/90 bg-white p-5 shadow-soft sm:p-7 lg:rounded-[2.25rem] lg:p-10 xl:p-14">
                        <div class="builder-content min-w-0">
                            @yield('content')
                        </div>
                    </section>

                    <footer class="builder-footer mt-6 rounded-[2rem] border border-white/80 bg-white px-6 py-5 text-sm font-semibold text-flex-muted shadow-card">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <p>
                                © {{ date('Y') }} FlexLabs. All rights reserved.
                            </p>

                            <div class="flex flex-wrap items-center gap-4">
                                <a href="https://flexlabs.co.id" class="font-black text-flex-dark transition hover:text-flex-primary">
                                    Website
                                </a>

                                <a href="{{ url('/trial-class') }}" class="font-black text-flex-dark transition hover:text-flex-primary">
                                    Trial Class
                                </a>

                                <a href="{{ url('/workshop') }}" class="font-black text-flex-dark transition hover:text-flex-primary">
                                    Workshop
                                </a>
                            </div>
                        </div>
                    </footer>
                </main>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>