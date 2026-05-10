<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $title ?? 'Dashboard')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css?' . time()) }}">
    <link rel="stylesheet" href="{{ asset('css/invoice.css?' . time()) }}">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('styles')
</head>
<body>
    <div class="dashboard-layout">

        <!-- Header -->
        <header class="dashboard-topbar dashboard-topbar-fixed">
            <div class="container-fluid">
                <div class="row align-items-center g-3 py-3">

                    <div class="col-12 col-xl-2">
                        <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="dashboard-brand text-decoration-none">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="dashboard-logo me-2">
                        </a>
                    </div>

                    <div class="col-12 col-xl-7">
                        @php
                            $currentUser = auth()->user();
                            $menus = config('flexops_access.menus', []);

                            $canAccess = function (?string $permission) use ($currentUser): bool {
                                if (! $permission) {
                                    return true;
                                }

                                if (! $currentUser) {
                                    return false;
                                }

                                if (method_exists($currentUser, 'canAccess')) {
                                    return $currentUser->canAccess($permission);
                                }

                                return ($currentUser->role ?? null) === 'admin';
                            };

                            $isActive = function (array $patterns = []): bool {
                                if (empty($patterns)) {
                                    return false;
                                }

                                return request()->routeIs(...$patterns);
                            };

                            $resolveRouteName = function (array $item): ?string {
                                $routeName = $item['route'] ?? null;

                                if ($routeName && Route::has($routeName)) {
                                    return $routeName;
                                }

                                $fallbackRouteName = $item['fallback_route'] ?? null;

                                if ($fallbackRouteName && Route::has($fallbackRouteName)) {
                                    return $fallbackRouteName;
                                }

                                return null;
                            };

                            $isVisibleItem = function (array $item) use ($canAccess, $resolveRouteName): bool {
                                if (! $canAccess($item['permission'] ?? null)) {
                                    return false;
                                }

                                if (! empty($item['disabled'])) {
                                    return true;
                                }

                                if ($resolveRouteName($item)) {
                                    return true;
                                }

                                return ! empty($item['missing_label']);
                            };

                            $visibleItems = function (array $items = []) use ($isVisibleItem) {
                                return collect($items)
                                    ->filter(fn ($item) => is_array($item) && $isVisibleItem($item))
                                    ->values();
                            };

                            $menuHasVisibleContent = function (array $menu) use ($canAccess, $resolveRouteName, $visibleItems): bool {
                                if (! $canAccess($menu['permission'] ?? null)) {
                                    return false;
                                }

                                $type = $menu['type'] ?? 'dropdown';

                                if ($type === 'link') {
                                    return (bool) $resolveRouteName($menu);
                                }

                                foreach (($menu['sections'] ?? []) as $section) {
                                    if ($visibleItems($section['items'] ?? [])->isNotEmpty()) {
                                        return true;
                                    }
                                }

                                $heroShortcut = $menu['hero']['shortcut'] ?? null;

                                if (is_array($heroShortcut)) {
                                    return $canAccess($heroShortcut['permission'] ?? null)
                                        && (bool) $resolveRouteName($heroShortcut);
                                }

                                return false;
                            };
                        @endphp

                        <nav class="dashboard-nav">
                            @foreach ($menus as $menu)
                                @php
                                    if (! is_array($menu) || ! $menuHasVisibleContent($menu)) {
                                        continue;
                                    }

                                    $type = $menu['type'] ?? 'dropdown';
                                    $menuActive = $isActive($menu['active'] ?? []);
                                    $menuRouteName = $resolveRouteName($menu);
                                    $dropdownClass = trim('dropdown-menu ' . ($menu['dropdown_class'] ?? ''));
                                @endphp

                                @if ($type === 'link')
                                    <a href="{{ $menuRouteName ? route($menuRouteName) : 'javascript:void(0)' }}"
                                       class="nav-btn {{ $menuActive ? 'active' : '' }}">
                                        <i class="{{ $menu['icon'] ?? 'bi bi-circle-fill' }}"></i>
                                        <span>{{ $menu['label'] ?? 'Menu' }}</span>
                                    </a>
                                @elseif ($type === 'mega')
                                    <div class="dropdown dashboard-mega-dropdown">
                                        <button
                                            class="dropdown-toggle nav-btn no-arrow {{ $menuActive ? 'active' : '' }}"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="outside"
                                            aria-expanded="false"
                                        >
                                            <i class="{{ $menu['icon'] ?? 'bi bi-grid-fill' }}"></i>
                                            <span>{{ $menu['label'] ?? 'Menu' }}</span>
                                        </button>

                                        <div class="{{ $dropdownClass }}">
                                            <div class="academic-mega-menu">
                                                @php
                                                    $hero = $menu['hero'] ?? [];
                                                    $heroShortcut = $hero['shortcut'] ?? null;
                                                    $heroShortcutRouteName = is_array($heroShortcut) ? $resolveRouteName($heroShortcut) : null;
                                                    $heroShortcutAllowed = is_array($heroShortcut) && $canAccess($heroShortcut['permission'] ?? null);
                                                    $heroShortcutActive = is_array($heroShortcut) ? $isActive($heroShortcut['active'] ?? []) : false;
                                                @endphp

                                                <aside class="academic-mega-hero">
                                                    <div class="academic-mega-hero-icon">
                                                        <i class="{{ $hero['icon'] ?? ($menu['icon'] ?? 'bi bi-grid-fill') }}"></i>
                                                    </div>

                                                    <div>
                                                        <div class="academic-mega-kicker">{{ $hero['kicker'] ?? ($menu['label'] ?? 'Menu Center') }}</div>
                                                        <h3>{{ $hero['title'] ?? ($menu['label'] ?? 'Menu') }}</h3>
                                                        <p>{{ $hero['description'] ?? '' }}</p>
                                                    </div>

                                                    @if ($heroShortcutAllowed && $heroShortcutRouteName)
                                                        <div class="academic-mega-hero-shortcut">
                                                            <a
                                                                href="{{ route($heroShortcutRouteName) }}"
                                                                class="academic-dashboard-shortcut {{ $heroShortcutActive ? 'active' : '' }}"
                                                            >
                                                                <span class="academic-dashboard-shortcut-icon">
                                                                    <i class="{{ $heroShortcut['icon'] ?? 'bi bi-arrow-right-circle' }}"></i>
                                                                </span>

                                                                <span class="academic-dashboard-shortcut-body">
                                                                    <span class="academic-dashboard-shortcut-title">
                                                                        {{ $heroShortcut['label'] ?? 'Open Dashboard' }}
                                                                    </span>
                                                                    <span class="academic-dashboard-shortcut-desc">
                                                                        {{ $heroShortcut['desc'] ?? '' }}
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </aside>

                                                <div class="academic-mega-content">
                                                    @foreach (($menu['sections'] ?? []) as $section)
                                                        @php
                                                            $sectionItems = $visibleItems($section['items'] ?? []);
                                                        @endphp

                                                        @continue($sectionItems->isEmpty())

                                                        <section class="academic-mega-group">
                                                            <div class="academic-mega-group-header">
                                                                <div class="academic-mega-group-icon">
                                                                    <i class="{{ $section['icon'] ?? 'bi bi-folder-fill' }}"></i>
                                                                </div>

                                                                <div>
                                                                    <h4>{{ $section['title'] ?? 'Menu Group' }}</h4>
                                                                    <p>{{ $section['subtitle'] ?? '' }}</p>
                                                                </div>
                                                            </div>

                                                            <div class="academic-mega-items">
                                                                @foreach ($sectionItems as $item)
                                                                    @php
                                                                        $itemRouteName = $resolveRouteName($item);
                                                                        $isDisabled = ($item['disabled'] ?? false) || ! $itemRouteName;
                                                                        $itemActive = $isActive($item['active'] ?? []);
                                                                    @endphp

                                                                    <a
                                                                        href="{{ $isDisabled ? 'javascript:void(0)' : route($itemRouteName) }}"
                                                                        class="academic-mega-item {{ $itemActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                                                        @if ($isDisabled) aria-disabled="true" tabindex="-1" @endif
                                                                    >
                                                                        <span class="academic-mega-item-icon">
                                                                            <i class="{{ $item['icon'] ?? 'bi bi-circle' }}"></i>
                                                                        </span>

                                                                        <span class="academic-mega-item-body">
                                                                            <span class="academic-mega-item-title">
                                                                                {{ $item['label'] ?? 'Menu Item' }}

                                                                                @if (! empty($item['badge']))
                                                                                    <span class="academic-mega-badge">
                                                                                        {{ $item['badge'] }}
                                                                                    </span>
                                                                                @endif
                                                                            </span>

                                                                            <span class="academic-mega-item-desc">
                                                                                {{ $item['desc'] ?? ($isDisabled ? ($item['missing_label'] ?? '') : '') }}
                                                                            </span>
                                                                        </span>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </section>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="dropdown">
                                        <button
                                            class="dropdown-toggle nav-btn no-arrow {{ $menuActive ? 'active' : '' }}"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                            <i class="{{ $menu['icon'] ?? 'bi bi-grid-fill' }}"></i>
                                            <span>{{ $menu['label'] ?? 'Menu' }}</span>
                                        </button>

                                        <div class="{{ $dropdownClass }}">
                                            @foreach (($menu['sections'] ?? []) as $section)
                                                @php
                                                    $sectionItems = $visibleItems($section['items'] ?? []);
                                                @endphp

                                                @continue($sectionItems->isEmpty())

                                                @if (! empty($section['title']))
                                                    <div class="dropdown-section-title">{{ $section['title'] }}</div>
                                                @endif

                                                @foreach ($sectionItems as $item)
                                                    @php
                                                        $itemRouteName = $resolveRouteName($item);
                                                        $itemActive = $isActive($item['active'] ?? []);
                                                    @endphp

                                                    @if ($itemRouteName)
                                                        <a class="dropdown-item {{ $itemActive ? 'active' : '' }}"
                                                           href="{{ route($itemRouteName) }}">
                                                            <i class="{{ $item['icon'] ?? 'bi bi-circle' }} me-2"></i>{{ $item['label'] ?? 'Menu Item' }}
                                                        </a>
                                                    @elseif (! empty($item['missing_label']))
                                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                                            <i class="{{ $item['icon'] ?? 'bi bi-circle' }} me-2"></i>{{ $item['missing_label'] }}
                                                        </span>
                                                    @endif
                                                @endforeach

                                                @if (! $loop->last)
                                                    <div class="dropdown-divider"></div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </nav>
                    </div>

                    <div class="col-12 col-xl-3">
                        <div class="dashboard-actions">
                            <div class="dropdown">
                                <button
                                    class="btn dropdown-toggle dashboard-user-toggle d-inline-flex align-items-center gap-2"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <img
                                        src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=5B3E8E&color=fff"
                                        alt="User"
                                    >
                                    <span class="dashboard-user-name">
                                        {{ auth()->user()->name ?? 'Admin' }}
                                    </span>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ Route::has('profile.edit') ? route('profile.edit') : 'javascript:void(0)' }}">
                                            <i class="bi bi-person-circle"></i>
                                            <span>Profile</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                <i class="bi bi-box-arrow-right"></i>
                                                <span>Logout</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="dashboard-body">
            <div class="container-fluid">
                <div class="dashboard-content-shell">
                    @yield('content')
                </div>
            </div>
        </main>

    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-semibold"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal').forEach(function (modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
