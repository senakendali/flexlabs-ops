{{-- Executive Center top navigation · yellow border active state --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Executive Center') · FlexOps</title>

    <meta
        name="description"
        content="@yield('meta_description', 'Executive Center FlexOps untuk monitoring KPI dan pengambilan keputusan manajemen.')"
    >
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800;900&display=swap"
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
                        executive: {
                            primary: '#5B3E8E',
                            primaryDark: '#473073',
                            primarySoft: '#F0EAF8',
                            primarySoft2: '#F8F5FC',
                            page: '#F2F4FA',
                            panel: '#FFFFFF',
                            ink: '#2D2938',
                            muted: '#737082',
                            line: '#E5E1EE',
                            orange: '#FFC316',
                        },
                    },
                    boxShadow: {
                        panel: '0 16px 45px rgba(31, 27, 46, 0.07)',
                        shell: '0 28px 80px rgba(31, 27, 46, 0.18)',
                        button: '0 14px 30px rgba(91, 62, 142, 0.22)',
                    },
                },
            },
        };
    </script>

    <style>
        html {
            scroll-behavior: smooth;
            background: #5B3E8E;
        }

        body,
        * {
            font-family: "Noto Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            overflow-x: hidden;
            background: #5B3E8E;
        }

        .lucide {
            flex-shrink: 0;
            stroke-width: 1.8;
            transition:
                color 0.2s ease,
                transform 0.2s ease;
        }

        a:hover > .lucide,
        button:hover > .lucide {
            transform: translateY(-1px);
        }

        [x-cloak] {
            display: none !important;
        }

        .executive-page-shell,
        .executive-layout-wrap,
        .executive-topbar {
            background: #5B3E8E !important;
            background-image: none !important;
        }

        .executive-topbar {
            border: 0 !important;
            box-shadow: none !important;
            isolation: isolate;
        }

        .executive-topnav-link {
            color: rgba(255, 255, 255, 0.72);
        }

        .executive-topnav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.10);
        }

        .executive-topnav-link.is-active {
            color: #ffffff;
            background: transparent;
        }

        .executive-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #FFC316 transparent;
        }

        .executive-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .executive-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .executive-scrollbar::-webkit-scrollbar-thumb {
            background: #FFC316;
            border: 2px solid #ffffff;
            border-radius: 999px;
        }

        .executive-content h1,
        .executive-content h2,
        .executive-content h3,
        .executive-content h4 {
            color: #2D2938;
        }

        @media print {
            .executive-topbar,
            .executive-navigation,
            .executive-page-header,
            .executive-footer {
                display: none !important;
            }

            html,
            body,
            .executive-page-shell,
            .executive-layout-wrap,
            .executive-shell-card,
            .executive-main-card {
                background: #ffffff !important;
            }

            .executive-layout-wrap {
                display: block !important;
                max-width: none !important;
                padding: 0 !important;
            }

            .executive-shell-card,
            .executive-main-card {
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen bg-executive-primary text-executive-ink antialiased">
    @php
        $executiveMenus = [
            [
                'label' => 'Executive Dashboard',
                'description' => 'Live KPI monitoring',
                'route' => 'executive-center.dashboard',
                'url' => url('/executive-center/dashboard'),
                'pattern' => 'executive-center/dashboard*',
            ],
            [
                'label' => 'AI Executive Brief',
                'description' => 'Insights and recommendations',
                'route' => 'executive-center.ai-executive-brief',
                'url' => url('/executive-center/ai-executive-brief'),
                'pattern' => 'executive-center/ai-executive-brief*',
            ],
            [
                'label' => 'KPI Scorecard',
                'description' => 'Target versus actual',
                'route' => 'executive-center.kpi-scorecard',
                'url' => url('/executive-center/kpi-scorecard'),
                'pattern' => 'executive-center/kpi-scorecard*',
            ],
            [
                'label' => 'Business Attention',
                'description' => 'Priority issues requiring action',
                'route' => 'executive-center.business-attention',
                'url' => url('/executive-center/business-attention'),
                'pattern' => 'executive-center/business-attention*',
            ],
            [
                'label' => 'Strategic Reports',
                'description' => 'Monthly and quarterly analysis',
                'route' => 'executive-center.strategic-reports.index',
                'url' => url('/executive-center/strategic-reports'),
                'pattern' => 'executive-center/strategic-reports*',
            ],
        ];

        $mainDashboardUrl = \Illuminate\Support\Facades\Route::has('management.dashboard')
            ? route('management.dashboard')
            : url('/dashboard');

        $userName = auth()->user()?->name ?? 'Executive';
        $trimmedUserName = trim($userName);
        $userInitial = mb_strtoupper(mb_substr($trimmedUserName !== '' ? $trimmedUserName : 'E', 0, 1));
    @endphp

    <div class="executive-page-shell relative min-h-screen overflow-visible bg-executive-primary">
        <div class="executive-layout-wrap relative flex min-h-screen w-full max-w-none flex-col px-4 pb-8 sm:px-6 lg:px-8">
            <header class="executive-topbar sticky top-0 z-50 -mx-4 bg-executive-primary px-4 py-5 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="flex w-full max-w-none items-center justify-between gap-4">
                    <a
                        href="{{ url('/executive-center') }}"
                        class="inline-flex shrink-0 items-center"
                        aria-label="Executive Center"
                    >
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="FlexLabs"
                            class="w-[170px] max-w-[170px] object-contain sm:w-[180px] sm:max-w-[180px]"
                        >
                    </a>

                    <nav class="hidden min-w-0 flex-1 items-center justify-center gap-1 xl:flex" aria-label="Executive Center Navigation">
                        @foreach ($executiveMenus as $menu)
                            @php
                                $menuUrl = \Illuminate\Support\Facades\Route::has($menu['route'])
                                    ? route($menu['route'])
                                    : $menu['url'];

                                $isActive = request()->routeIs($menu['route'])
                                    || request()->is($menu['pattern']);
                            @endphp

                            <a
                                href="{{ $menuUrl }}"
                                class="executive-topnav-link {{ $isActive ? 'is-active' : '' }} relative whitespace-nowrap rounded-[1rem] px-3 py-3 text-center text-[11px] font-extrabold transition 2xl:px-4 2xl:text-xs"
                                @if ($isActive) aria-current="page" @endif
                            >
                                {{ $menu['label'] }}

                                @if ($isActive)
                                    <span class="absolute inset-x-3 -bottom-1 h-1 rounded-full bg-executive-orange"></span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <a
                            href="{{ $mainDashboardUrl }}"
                            class="hidden h-11 items-center justify-center gap-2 rounded-[1.1rem] bg-white/12 px-4 text-xs font-extrabold text-white transition hover:bg-white hover:text-executive-primary md:inline-flex"
                        >
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>

                            Main Dashboard
                        </a>

                        <div class="hidden text-right xl:block">
                            <p class="text-xs font-extrabold text-white">
                                {{ $userName }}
                            </p>

                            <p class="mt-0.5 text-[10px] font-semibold text-white/60">
                                Management Access
                            </p>
                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-[1.1rem] bg-white text-sm font-black text-executive-primary shadow-lg shadow-black/10">
                            {{ $userInitial }}
                        </div>
                    </div>
                </div>
            </header>

            <nav
                class="executive-navigation executive-scrollbar mb-5 flex gap-2 overflow-x-auto pb-1 xl:hidden"
                aria-label="Executive Center Navigation"
            >
                @foreach ($executiveMenus as $menu)
                    @php
                        $menuUrl = \Illuminate\Support\Facades\Route::has($menu['route'])
                            ? route($menu['route'])
                            : $menu['url'];

                        $isActive = request()->routeIs($menu['route'])
                            || request()->is($menu['pattern']);
                    @endphp

                    <a
                        href="{{ $menuUrl }}"
                        class="executive-topnav-link {{ $isActive ? 'is-active' : '' }} relative shrink-0 rounded-[1rem] px-4 py-3 text-xs font-extrabold transition
                            {{ $isActive
                                ? 'text-white'
                                : 'text-white/70' }}"
                        @if ($isActive) aria-current="page" @endif
                    >
                        {{ $menu['label'] }}

                        @if ($isActive)
                            <span class="absolute inset-x-5 -bottom-0.5 h-1 rounded-full bg-executive-orange"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="executive-shell-card w-full max-w-none rounded-[2rem] bg-[#F7F6FB] p-4 shadow-shell sm:p-5 lg:rounded-[2.35rem] lg:p-7">
                <main class="min-w-0 w-full max-w-none">
                    <section class="executive-main-card min-h-[calc(100vh-11rem)] w-full max-w-none overflow-hidden rounded-[2rem] border border-white/90 bg-white shadow-panel">
                        <header class="executive-page-header border-b border-executive-line px-5 py-5 sm:px-6 lg:px-7">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-executive-primary">
                                            Executive Center
                                        </p>

                                        <span class="rounded-full bg-executive-primarySoft px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-[0.1em] text-executive-primary">
                                            Read only
                                        </span>
                                    </div>

                                    <h1 class="mt-1 text-2xl font-black tracking-[-0.045em] text-executive-ink sm:text-[1.75rem]">
                                        @yield('page_title', 'Executive Dashboard')
                                    </h1>

                                    @hasSection('page_description')
                                        <p class="mt-2 max-w-3xl text-xs font-medium leading-5 text-executive-muted sm:text-sm">
                                            @yield('page_description')
                                        </p>
                                    @endif
                                </div>

                                @hasSection('header_actions')
                                    <div class="flex shrink-0 flex-wrap items-center gap-3">
                                        @yield('header_actions')
                                    </div>
                                @endif
                            </div>
                        </header>

                        <div class="executive-content min-w-0 w-full max-w-none p-5 sm:p-6 lg:p-7">
                            @yield('content')
                        </div>
                    </section>
                </main>
            </div>

            <footer class="executive-footer mt-6 px-2 py-4 text-sm font-semibold text-white/70">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        © {{ date('Y') }} FlexLabs. All rights reserved.
                    </p>

                    <p class="text-xs text-white/60">
                        Executive Center · Management decision support
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@1.27.0/dist/umd/lucide.min.js"></script>

    <script>
        window.renderLucideIcons = function (root = document) {
            if (!window.lucide || typeof window.lucide.createIcons !== 'function') {
                return;
            }

            window.lucide.createIcons({
                root: root instanceof Element || root instanceof DocumentFragment
                    ? root
                    : document,
                attrs: {
                    'stroke-width': 1.8,
                },
            });
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.renderLucideIcons();
        });

        document.addEventListener('lucide:refresh', function (event) {
            window.renderLucideIcons(event.detail?.root ?? document);
        });
    </script>

    @stack('scripts')
</body>
</html>
