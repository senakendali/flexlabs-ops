{{-- FlexOps AI Office Layout --}}

<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'AI Office') · FlexOps</title>

    <meta
        name="description"
        content="@yield(
            'meta_description',
            'FlexOps AI Office untuk kolaborasi dengan virtual AI team FlexLabs.'
        )"
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
                        sans: [
                            'Noto Sans',
                            'ui-sans-serif',
                            'system-ui',
                            'sans-serif',
                        ],
                    },

                    colors: {
                        office: {
                            primary: '#5B3E8E',
                            primaryDark: '#473073',
                            primarySoft: '#F0EAF8',
                            primarySoft2: '#F8F5FC',

                            yellow: '#FFBE04',
                            green: '#3B8E4D',

                            page: '#F2F4FA',
                            panel: '#FFFFFF',

                            ink: '#2D2938',
                            muted: '#737082',
                            line: '#E5E1EE',
                        },
                    },

                    boxShadow: {
                        shell: '0 28px 80px rgba(31, 27, 46, 0.18)',
                        panel: '0 16px 45px rgba(31, 27, 46, 0.07)',
                    },
                },
            },
        };
    </script>


    <style>
        /*
        |--------------------------------------------------------------------------
        | Base
        |--------------------------------------------------------------------------
        */

        html {
            width: 100%;
            min-height: 100%;
            scroll-behavior: smooth;
            background: #5B3E8E;
        }

        body,
        * {
            box-sizing: border-box;

            font-family:
                "Noto Sans",
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        body {
            width: 100%;
            min-height: 100vh;
            margin: 0;

            overflow-x: hidden;

            background: #5B3E8E;
        }

        [x-cloak] {
            display: none !important;
        }


        /*
        |--------------------------------------------------------------------------
        | Top Navigation
        |--------------------------------------------------------------------------
        */

        .ai-office-topnav-link {
            color: rgba(255, 255, 255, 0.68);

            transition:
                color 0.2s ease,
                background 0.2s ease,
                transform 0.2s ease;
        }

        .ai-office-topnav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.10);
        }

        .ai-office-topnav-link.is-active {
            color: #ffffff;
            background: transparent;
        }


        /*
        |--------------------------------------------------------------------------
        | Scrollbar
        |--------------------------------------------------------------------------
        */

        .ai-office-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #FFBE04 transparent;
        }

        .ai-office-scrollbar::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        .ai-office-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .ai-office-scrollbar::-webkit-scrollbar-thumb {
            background: #FFBE04;
            border-radius: 999px;
        }


        /*
        |--------------------------------------------------------------------------
        | Page Shell
        |--------------------------------------------------------------------------
        */

        .ai-office-page {
            min-height: 100vh;

            background: #5B3E8E;
        }

        .ai-office-layout {
            min-height: 100vh;
        }


        /*
        |--------------------------------------------------------------------------
        | Office Workspace
        |--------------------------------------------------------------------------
        |
        | Area ini sengaja TANPA padding.
        |
        | Child view bisa menggunakan seluruh area office.
        |--------------------------------------------------------------------------
        */

        .ai-office-shell {
            position: relative;

            width: 100%;

            overflow: hidden;

            background: #ffffff;

            border-radius: 2.25rem;

            box-shadow:
                0 28px 80px rgba(31, 27, 46, 0.18);
        }

        .ai-office-content {
            position: relative;

            width: 100%;

            /*
            |--------------------------------------------------------------------------
            | Tinggi mengikuti viewport
            |--------------------------------------------------------------------------
            |
            | Desktop:
            | viewport - topbar - footer/margins
            |
            */

            height: calc(100vh - 150px);

            min-height: 620px;

            overflow: hidden;

            background-color: #F6F5F8;
        }


        /*
        |--------------------------------------------------------------------------
        | Office Background
        |--------------------------------------------------------------------------
        */

        .ai-office-background {
            background-image:
                linear-gradient(
                    rgba(20, 15, 35, 0.02),
                    rgba(20, 15, 35, 0.02)
                ),
                url('{{ asset('images/office.png') }}');

            background-position: center center;

            background-repeat: no-repeat;

            background-size: cover;
        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1279px) {

            .ai-office-content {
                height: calc(100vh - 195px);
                min-height: 580px;
            }

        }

        @media (max-width: 767px) {

            .ai-office-shell {
                border-radius: 1.5rem;
            }

            .ai-office-content {
                height: calc(100vh - 180px);
                min-height: 560px;
            }

        }
    </style>

    @stack('styles')

</head>


<body class="min-h-screen bg-office-primary text-office-ink antialiased">

    @php

        /*
        |--------------------------------------------------------------------------
        | AI Office Division Navigation
        |--------------------------------------------------------------------------
        */

        $officeMenus = [

            [
                'label' => 'Academic',
                'route' => 'academic.ai-office.index',
                'url' => url('/academic/ai-office'),
                'pattern' => 'academic/ai-office*',
                'enabled' => true,
            ],

            [
                'label' => 'Sales',
                'route' => null,
                'url' => '#',
                'pattern' => null,
                'enabled' => false,
            ],

            [
                'label' => 'Marketing',
                'route' => null,
                'url' => '#',
                'pattern' => null,
                'enabled' => false,
            ],

            [
                'label' => 'Finance',
                'route' => null,
                'url' => '#',
                'pattern' => null,
                'enabled' => false,
            ],

            [
                'label' => 'HR',
                'route' => null,
                'url' => '#',
                'pattern' => null,
                'enabled' => false,
            ],

            [
                'label' => 'Operations',
                'route' => null,
                'url' => '#',
                'pattern' => null,
                'enabled' => false,
            ],

        ];


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        $mainDashboardUrl =
            \Illuminate\Support\Facades\Route::has('dashboard')
                ? route('dashboard')
                : url('/dashboard');


        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        $userName =
            auth()->user()?->name
            ?? 'User';


        $trimmedUserName =
            trim($userName);


        $userInitial =
            mb_strtoupper(
                mb_substr(
                    $trimmedUserName !== ''
                        ? $trimmedUserName
                        : 'U',
                    0,
                    1
                )
            );

    @endphp


    <div class="ai-office-page relative">

        <div
            class="
                ai-office-layout
                relative
                flex
                w-full
                flex-col
                px-4
                pb-3
                sm:px-6
                lg:px-8
            "
        >

            {{-- ============================================================
                TOP BAR
            ============================================================ --}}

            <header
                class="
                    sticky
                    top-0
                    z-50
                    -mx-4
                    bg-office-primary
                    px-4
                    py-5
                    sm:-mx-6
                    sm:px-6
                    lg:-mx-8
                    lg:px-8
                "
            >

                <div
                    class="
                        flex
                        w-full
                        items-center
                        justify-between
                        gap-4
                    "
                >

                    {{-- Logo --}}

                    <a
                        href="{{ route('academic.ai-office.index') }}"
                        class="inline-flex shrink-0 items-center"
                        aria-label="FlexOps AI Office"
                    >

                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="FlexLabs"
                            class="
                                w-[165px]
                                max-w-[165px]
                                object-contain
                                sm:w-[180px]
                                sm:max-w-[180px]
                            "
                        >

                    </a>


                    {{-- Desktop Navigation --}}

                    <nav
                        class="
                            hidden
                            min-w-0
                            flex-1
                            items-center
                            justify-center
                            gap-1
                            xl:flex
                        "
                        aria-label="AI Office Division Navigation"
                    >

                        @foreach ($officeMenus as $menu)

                            @php

                                $menuUrl = '#';


                                if ($menu['enabled']) {

                                    $menuUrl =
                                        $menu['route']
                                        &&
                                        \Illuminate\Support\Facades\Route::has(
                                            $menu['route']
                                        )

                                            ? route($menu['route'])

                                            : $menu['url'];

                                }


                                $isActive = false;


                                if ($menu['enabled']) {

                                    $isActive =

                                        (
                                            $menu['route']
                                            &&
                                            request()->routeIs(
                                                $menu['route']
                                            )
                                        )

                                        ||

                                        (
                                            $menu['pattern']
                                            &&
                                            request()->is(
                                                $menu['pattern']
                                            )
                                        );

                                }

                            @endphp


                            @if ($menu['enabled'])

                                <a
                                    href="{{ $menuUrl }}"

                                    class="
                                        ai-office-topnav-link

                                        {{ $isActive ? 'is-active' : '' }}

                                        relative
                                        whitespace-nowrap
                                        rounded-[1rem]
                                        px-4
                                        py-3
                                        text-center
                                        text-xs
                                        font-extrabold
                                    "

                                    @if ($isActive)
                                        aria-current="page"
                                    @endif
                                >

                                    {{ $menu['label'] }}


                                    @if ($isActive)

                                        <span
                                            class="
                                                absolute
                                                inset-x-4
                                                -bottom-1
                                                h-1
                                                rounded-full
                                                bg-office-yellow
                                            "
                                        ></span>

                                    @endif

                                </a>

                            @else

                                <div
                                    class="
                                        relative
                                        cursor-default
                                        whitespace-nowrap
                                        rounded-[1rem]
                                        px-4
                                        py-3
                                        text-center
                                        text-xs
                                        font-extrabold
                                        text-white/30
                                    "
                                >

                                    {{ $menu['label'] }}


                                    <span
                                        class="
                                            absolute
                                            -right-1
                                            top-0
                                            rounded-full
                                            bg-white/10
                                            px-1.5
                                            py-0.5
                                            text-[7px]
                                            font-black
                                            uppercase
                                            tracking-wide
                                            text-white/50
                                        "
                                    >
                                        Soon
                                    </span>

                                </div>

                            @endif

                        @endforeach

                    </nav>


                    {{-- Right Header --}}

                    <div
                        class="
                            flex
                            shrink-0
                            items-center
                            gap-2
                            sm:gap-3
                        "
                    >

                        <a
                            href="{{ $mainDashboardUrl }}"

                            class="
                                hidden
                                h-11
                                items-center
                                justify-center
                                gap-2
                                rounded-[1.1rem]
                                bg-white/10
                                px-4
                                text-xs
                                font-extrabold
                                text-white
                                transition

                                hover:bg-white
                                hover:text-office-primary

                                md:inline-flex
                            "
                        >

                            <i
                                data-lucide="arrow-left"
                                class="h-4 w-4"
                                aria-hidden="true"
                            ></i>

                            Main Dashboard

                        </a>


                        <div class="hidden text-right xl:block">

                            <p
                                class="
                                    text-xs
                                    font-extrabold
                                    text-white
                                "
                            >
                                {{ $userName }}
                            </p>

                            <p
                                class="
                                    mt-0.5
                                    text-[10px]
                                    font-semibold
                                    text-white/55
                                "
                            >
                                AI Office Access
                            </p>

                        </div>


                        <div
                            class="
                                flex
                                h-11
                                w-11
                                items-center
                                justify-center
                                rounded-[1.1rem]
                                bg-white
                                text-sm
                                font-black
                                text-office-primary
                                shadow-lg
                                shadow-black/10
                            "
                        >

                            {{ $userInitial }}

                        </div>

                    </div>

                </div>

            </header>


            {{-- ============================================================
                MOBILE NAVIGATION
            ============================================================ --}}

            <nav
                class="
                    ai-office-scrollbar
                    mb-4
                    flex
                    gap-2
                    overflow-x-auto
                    pb-1
                    xl:hidden
                "
                aria-label="AI Office Division Navigation"
            >

                @foreach ($officeMenus as $menu)

                    @php

                        $menuUrl = '#';


                        if ($menu['enabled']) {

                            $menuUrl =

                                $menu['route']

                                &&
                                \Illuminate\Support\Facades\Route::has(
                                    $menu['route']
                                )

                                    ? route($menu['route'])

                                    : $menu['url'];

                        }


                        $isActive = false;


                        if ($menu['enabled']) {

                            $isActive =

                                (
                                    $menu['route']

                                    &&
                                    request()->routeIs(
                                        $menu['route']
                                    )
                                )

                                ||

                                (
                                    $menu['pattern']

                                    &&
                                    request()->is(
                                        $menu['pattern']
                                    )
                                );

                        }

                    @endphp


                    @if ($menu['enabled'])

                        <a
                            href="{{ $menuUrl }}"

                            class="
                                ai-office-topnav-link

                                {{ $isActive ? 'is-active' : '' }}

                                relative
                                shrink-0
                                rounded-[1rem]
                                px-4
                                py-3
                                text-xs
                                font-extrabold
                            "
                        >

                            {{ $menu['label'] }}


                            @if ($isActive)

                                <span
                                    class="
                                        absolute
                                        inset-x-5
                                        -bottom-0.5
                                        h-1
                                        rounded-full
                                        bg-office-yellow
                                    "
                                ></span>

                            @endif

                        </a>

                    @else

                        <div
                            class="
                                shrink-0
                                cursor-default
                                rounded-[1rem]
                                px-4
                                py-3
                                text-xs
                                font-extrabold
                                text-white/30
                            "
                        >

                            {{ $menu['label'] }}

                        </div>

                    @endif

                @endforeach

            </nav>


            {{-- ============================================================
                OFFICE WORKSPACE
            ============================================================ --}}

            <div class="ai-office-shell">

                <main
                    class="
                        ai-office-content
                        ai-office-background
                        relative
                        w-full
                    "
                >

                    @yield('content')

                </main>

            </div>


            {{-- ============================================================
                FOOTER
            ============================================================ --}}

            <footer
                class="
                    mt-3
                    px-2
                    py-2
                    text-sm
                    font-semibold
                    text-white/70
                "
            >

                <div
                    class="
                        flex
                        flex-col
                        gap-2
                        sm:flex-row
                        sm:items-center
                        sm:justify-between
                    "
                >

                    <p>
                        © {{ date('Y') }} FlexLabs.
                        All rights reserved.
                    </p>

                    <p class="text-xs text-white/55">
                        FlexOps AI Office · Virtual Workforce
                    </p>

                </div>

            </footer>

        </div>

    </div>


    {{-- ================================================================
        LUCIDE ICON
    ================================================================ --}}

    <script
        src="https://unpkg.com/lucide@1.27.0/dist/umd/lucide.min.js"
    ></script>


    <script>

        window.renderLucideIcons = function (root = document) {

            if (
                !window.lucide
                ||
                typeof window.lucide.createIcons !== 'function'
            ) {
                return;
            }


            window.lucide.createIcons({

                root:
                    root instanceof Element
                    ||
                    root instanceof DocumentFragment

                        ? root

                        : document,

                attrs: {
                    'stroke-width': 1.8,
                },

            });

        };


        document.addEventListener(
            'DOMContentLoaded',

            function () {
                window.renderLucideIcons();
            }
        );


        document.addEventListener(
            'lucide:refresh',

            function (event) {

                window.renderLucideIcons(
                    event.detail?.root
                    ?? document
                );

            }
        );

    </script>


    @stack('scripts')

</body>

</html>