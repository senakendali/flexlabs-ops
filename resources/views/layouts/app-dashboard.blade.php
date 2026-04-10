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
                                <i class="bi bi-house-door"></i>
                                <span>Dashboard</span>
                            </a>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('trial-classes.*') || request()->routeIs('trial-themes.*') || request()->routeIs('trial-schedules.*') || request()->routeIs('trial-participants.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-easel2"></i>
                                    <span>Trial Management</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('trial-classes.*') ? 'active' : '' }}"
                                           href="{{ route('trial-classes.index') }}">
                                            Trial Classes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('trial-themes.*') ? 'active' : '' }}"
                                           href="{{ route('trial-themes.index') }}">
                                            Trial Themes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('trial-schedules.*') ? 'active' : '' }}"
                                           href="{{ route('trial-schedules.index') }}">
                                            Trial Schedules
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('trial-participants.*') ? 'active' : '' }}"
                                           href="{{ route('trial-participants.index') }}">
                                            Trial Participants
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <a href="{{ route('enrollments.index') }}"
                               class="nav-btn {{ request()->routeIs('programs.*') || request()->routeIs('batches.*') || request()->routeIs('students.*') || request()->routeIs('enrollments.*') ? 'active' : '' }}">
                                <i class="bi bi-person-plus"></i>
                                <span>Enrollment</span>
                            </a>

                            <a href="{{ route('payments.index') }}"
                               class="nav-btn {{ request()->routeIs('orders.*') || request()->routeIs('payment-schedules.*') || request()->routeIs('payments.*') ? 'active' : '' }}">
                                <i class="bi bi-credit-card-2-front"></i>
                                <span>Payments</span>
                            </a>

                            <a href="{{ route('instructors.index') }}"
                               class="nav-btn {{ request()->routeIs('instructors.*') || request()->routeIs('sessions.*') || request()->routeIs('monitoring.*') ? 'active' : '' }}">
                                <i class="bi bi-person-video3"></i>
                                <span>Instructors</span>
                            </a>

                            <a href="{{ route('settings.index') }}"
                               class="nav-btn {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <i class="bi bi-gear"></i>
                                <span>Settings</span>
                            </a>
                        </nav>
                    </div>

                    <div class="col-12 col-xl-3">
                        <div class="dashboard-actions">
                            <button class="dashboard-icon-btn" type="button">
                                <i class="bi bi-bell"></i>
                            </button>

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
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="#">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>