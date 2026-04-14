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

                            <!-- MASTER DATA -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('programs.*') || request()->routeIs('instructors.*') || request()->routeIs('equipment.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-diagram-3"></i>
                                    <span>Master Data</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('programs.*') ? 'active' : '' }}"
                                        href="{{ route('programs.index') }}">
                                            Programs
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('instructors.*') ? 'active' : '' }}"
                                        href="{{ route('instructors.index') }}">
                                            Instructors
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}"
                                        href="{{ route('equipment.index') }}">
                                            Equipment
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- TRIAL MANAGEMENT -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('trial-themes.*') || request()->routeIs('trial-schedules.*') || request()->routeIs('trial-participants.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-easel2"></i>
                                    <span>Trial Management</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
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

                            <!-- ENROLLMENT -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('batches.*') || request()->routeIs('students.*') || request()->routeIs('enrollments.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-person-plus"></i>
                                    <span>Enrollment</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('batches.*') ? 'active' : '' }}"
                                        href="{{ route('batches.index') }}">
                                            Batches
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('students.*') ? 'active' : '' }}"
                                        href="{{ route('students.index') }}">
                                            Students
                                        </a>
                                    </li>
                                    <!--li>
                                        <a class="dropdown-item {{ request()->routeIs('enrollments.*') ? 'active' : '' }}"
                                        href="{{ route('enrollments.index') }}">
                                            Enrollments
                                        </a>
                                    </li-->
                                </ul>
                            </div>

                            <!-- PAYMENTS -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('orders.*') || request()->routeIs('payment-schedules.*') || request()->routeIs('payments.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-credit-card-2-front"></i>
                                    <span>Payments</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                                        href="{{ route('orders.index') }}">
                                            Orders
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('payment-schedules.*') ? 'active' : '' }}"
                                        href="{{ route('payment-schedules.index') }}">
                                            Payment Schedules
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                                        href="{{ route('payments.index') }}">
                                            Payments
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- 🔥 NEW: OPERATIONS -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('borrowings.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-box-arrow-in-down"></i>
                                    <span>Operations</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('borrowings.*') ? 'active' : '' }}"
                                        href="{{ route('borrowings.index') }}">
                                            Gear Borrowing
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}"
                                        href="{{ route('quiz.index') }}">
                                            Quiz Management
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- MONITORING -->
                            <!-- <a href="{{ route('monitoring.index') }}"
                            class="nav-btn {{ request()->routeIs('monitoring.*') || request()->routeIs('sessions.*') ? 'active' : '' }}">
                                <i class="bi bi-clipboard-data"></i>
                                <span>Monitoring</span>
                            </a> -->
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