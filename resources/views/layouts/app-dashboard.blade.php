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

                           <!-- ACADEMIC -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{
                                        request()->routeIs('programs.*')
                                        || request()->routeIs('instructors.*')
                                        || request()->routeIs('batches.*')
                                        || request()->routeIs('students.*')
                                        || request()->routeIs('instructor-tracking.*')
                                        || request()->routeIs('trial-themes.*')
                                        || request()->routeIs('trial-schedules.*')
                                        || request()->routeIs('trial-participants.*')
                                            ? 'active' : ''
                                    }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-mortarboard"></i>
                                    <span>Academic</span>
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

                                    <li><hr class="dropdown-divider"></li>

                                    

                                   <li>
                                        <a class="dropdown-item {{ request()->routeIs('curriculum.*') ? 'active' : '' }}"
                                        href="{{ route('curriculum.index') }}">
                                            Manage Curriculum
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('instructor-tracking.*') ? 'active' : '' }}"
                                        href="{{ route('instructor-tracking.index') }}">
                                            Instructor Tracking
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>

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

                            <!-- FINANCE -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{
                                        request()->routeIs('orders.*')
                                        || request()->routeIs('payment-schedules.*')
                                        || request()->routeIs('payments.*')
                                            ? 'active' : ''
                                    }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-wallet2"></i>
                                    <span>Finance</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li><a class="dropdown-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">Orders</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('payment-schedules.*') ? 'active' : '' }}" href="{{ route('payment-schedules.index') }}">Payment Schedules</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a></li>
                                </ul>
                            </div>

                            <!-- SALES -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{
                                        request()->routeIs('sales-daily-reports.*')
                                        || request()->routeIs('sales-performance.*')
                                            ? 'active' : ''
                                    }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-graph-up-arrow"></i>
                                    <span>Sales</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li><a class="dropdown-item {{ request()->routeIs('sales-daily-reports.*') ? 'active' : '' }}" href="{{ route('sales-daily-reports.index') }}">Daily Reports</a></li>
                                    <li><a class="dropdown-item {{ request()->routeIs('sales-performance.*') ? 'active' : '' }}" href="{{ route('sales-performance.index') }}">Performance Dashboard</a></li>
                                </ul>
                            </div>

                            <!-- MARKETING -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('quiz.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-megaphone"></i>
                                    <span>Marketing</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li><a class="dropdown-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}" href="{{ route('quiz.index') }}">Quiz Management</a></li>
                                </ul>
                            </div>

                            <!-- INVENTORY -->
                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs('equipment.*') ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-box-seam"></i>
                                    <span>Inventory</span>
                                </button>

                                <ul class="dropdown-menu shadow-sm">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}"
                                        href="{{ route('equipment.index') }}">
                                            Master Equipment
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- 🔥 BORROW (STANDALONE) -->
                            <a href="{{ route('borrowings.index') }}"
                            class="nav-btn {{ request()->routeIs('borrowings.*') ? 'active' : '' }}">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Borrow Equipment</span>
                            </a>

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