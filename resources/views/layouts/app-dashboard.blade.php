<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css?' . time()) }}">
</head>
<body>
    <div class="dashboard-layout">

        <!-- Header -->
        <header class="dashboard-topbar">
            <div class="container-fluid">
                <div class="row align-items-center g-3 py-3">

                    <div class="col-12 col-xl-2">
                        <a href="{{ route('dashboard') }}" class="dashboard-brand text-decoration-none">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="dashboard-logo me-2">
                            
                        </a>
                    </div>

                    <div class="col-12 col-xl-7">
                       <nav class="dashboard-nav">
                            <a href="{{ route('dashboard') }}"
                            class="nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </a>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'programs.*',
                                        'curriculum.*',
                                        'instructors.*',
                                        'instructor-tracking.*',
                                        'trial-themes.*',
                                        'trial-schedules.*',
                                        'trial-participants.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <span>Academic</span>
                                </button>

                                <div class="dropdown-menu">
                                    <a class="dropdown-item {{ request()->routeIs('programs.*') ? 'active' : '' }}"
                                    href="{{ route('programs.index') }}">
                                        Programs
                                    </a>

                                    <a class="dropdown-item {{ request()->routeIs('batches.*') ? 'active' : '' }}"
                                    href="{{ route('batches.index') }}">
                                        Batches
                                    </a>

                                    

                                    <a class="dropdown-item {{ request()->routeIs('curriculum.*') ? 'active' : '' }}"
                                    href="{{ route('curriculum.index') }}">
                                        Curriculum
                                    </a>

                                    @if (Route::has('instructors.index'))
                                        <a class="dropdown-item {{ request()->routeIs('instructors.*') ? 'active' : '' }}"
                                        href="{{ route('instructors.index') }}">
                                            Instructors
                                        </a>
                                    @endif

                                    @if (Route::has('instructor-tracking.index'))
                                        <a class="dropdown-item {{ request()->routeIs('instructor-tracking.*') ? 'active' : '' }}"
                                        href="{{ route('instructor-tracking.index') }}">
                                            Instructor Tracking
                                        </a>
                                    @endif

                                    @if (Route::has('trial-themes.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-themes.*') ? 'active' : '' }}"
                                        href="{{ route('trial-themes.index') }}">
                                            Trial Themes
                                        </a>
                                    @endif

                                    @if (Route::has('trial-schedules.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-schedules.*') ? 'active' : '' }}"
                                        href="{{ route('trial-schedules.index') }}">
                                            Trial Schedules
                                        </a>
                                    @endif

                                    @if (Route::has('trial-participants.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-participants.*') ? 'active' : '' }}"
                                        href="{{ route('trial-participants.index') }}">
                                            Trial Participants
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'sales.*',
                                        'sales-daily-reports.*',
                                        'sales-performance.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-graph-up-arrow"></i>
                                    <span>Sales</span>
                                </button>

                                <div class="dropdown-menu">
                                    @if (Route::has('sales-daily-reports.index'))
                                        <a class="dropdown-item {{ request()->routeIs('sales-daily-reports.*') ? 'active' : '' }}"
                                        href="{{ route('sales-daily-reports.index') }}">
                                            Daily Report
                                        </a>
                                    @endif

                                    @if (Route::has('sales-performance.index'))
                                        <a class="dropdown-item {{ request()->routeIs('sales-performance.*') ? 'active' : '' }}"
                                        href="{{ route('sales-performance.index') }}">
                                            Performance
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'marketing.*',
                                        'quiz.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-megaphone-fill"></i>
                                    <span>Marketing</span>
                                </button>

                                <div class="dropdown-menu">
                                    @if (Route::has('quiz.index'))
                                        <a class="dropdown-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}"
                                        href="{{ route('quiz.index') }}">
                                            Quiz Management
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'finance.*',
                                        'payments.*',
                                        'invoices.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-wallet2"></i>
                                    <span>Finance</span>
                                </button>

                                <div class="dropdown-menu">
                                    @if (Route::has('payments.index'))
                                        <a class="dropdown-item {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                                        href="{{ route('payments.index') }}">
                                            Payments
                                        </a>
                                    @endif

                                    @if (Route::has('invoices.index'))
                                        <a class="dropdown-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                                        href="{{ route('invoices.index') }}">
                                            Invoices
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'internal-memos.*',
                                        'equipment.*',
                                        'borrowings.*',
                                        'atk-items.*',
                                        'atk-requests.*',
                                        'inventory.atk-items.*',
                                        'inventory.atk-requests.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-building-gear"></i>
                                    <span>Operations</span>
                                </button>

                                <div class="dropdown-menu dropdown-menu-operations">
                                    <!--div class="dropdown-section-title">General Affairs</div>

                                    @if (Route::has('internal-memos.index'))
                                        <!--a class="dropdown-item {{ request()->routeIs('internal-memos.*') ? 'active' : '' }}"
                                        href="{{ route('internal-memos.index') }}">
                                            <i class="bi bi-journal-text me-2"></i>Internal Memo
                                        </a>
                                    @else
                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                            Internal Memo belum tersedia
                                        </span>
                                    @endif

                                    <div class="dropdown-divider"></div-->

                                    <div class="dropdown-section-title">Inventory</div>

                                    @if (Route::has('equipment.index'))
                                        <a class="dropdown-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}"
                                        href="{{ route('equipment.index') }}">
                                            <i class="bi bi-pc-display-horizontal me-2"></i>Equipment
                                        </a>
                                    @else
                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                            Equipment belum tersedia
                                        </span>
                                    @endif

                                    @if (Route::has('borrowings.index'))
                                        <a class="dropdown-item {{ request()->routeIs('borrowings.*') ? 'active' : '' }}"
                                        href="{{ route('borrowings.index') }}">
                                            <i class="bi bi-box-arrow-up-right me-2"></i>Borrow Equipment
                                        </a>
                                    @endif

                                    @if (Route::has('inventory.atk-items.index'))
                                        <a class="dropdown-item {{ request()->routeIs('inventory.atk-items.*', 'atk-items.*') ? 'active' : '' }}"
                                        href="{{ route('inventory.atk-items.index') }}">
                                            <i class="bi bi-box-seam me-2"></i>Master ATK
                                        </a>
                                    @elseif (Route::has('atk-items.index'))
                                        <a class="dropdown-item {{ request()->routeIs('atk-items.*') ? 'active' : '' }}"
                                        href="{{ route('atk-items.index') }}">
                                            <i class="bi bi-box-seam me-2"></i>Master ATK
                                        </a>
                                    @else
                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                            Master ATK belum tersedia
                                        </span>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Requests</div>

                                    @if (Route::has('inventory.atk-requests.index'))
                                        <a class="dropdown-item {{ request()->routeIs('inventory.atk-requests.*', 'atk-requests.*') ? 'active' : '' }}"
                                        href="{{ route('inventory.atk-requests.index') }}">
                                            <i class="bi bi-pencil-square me-2"></i>ATK Request
                                        </a>
                                    @elseif (Route::has('atk-requests.index'))
                                        <a class="dropdown-item {{ request()->routeIs('atk-requests.*') ? 'active' : '' }}"
                                        href="{{ route('atk-requests.index') }}">
                                            <i class="bi bi-pencil-square me-2"></i>ATK Request
                                        </a>
                                    @else
                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                            ATK Request belum tersedia
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </nav>
                    </div>

                    <div class="col-12 col-xl-3">
                        <div class="dashboard-actions">
                            <!--button class="dashboard-icon-btn" type="button">
                                <i class="bi bi-bell"></i>
                            </button-->

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
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                                            <i class="bi bi-person-circle"></i>
                                            <span>Profile</span>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
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
    @stack('scripts')

</body>
</html>