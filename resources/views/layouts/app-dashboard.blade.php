<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }}</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="dashboard-page">
        <header class="dashboard-header">
            <div class="dashboard-header-inner">
                <div class="brand">
                    <div class="brand-logo">F</div>
                    <div class="brand-text">
                        <h1>FlexLabs</h1>
                    </div>
                </div>

                <nav class="top-nav">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('trial-classes.index') }}" class="{{ request()->routeIs('trial-classes.*') || request()->routeIs('trial-schedules.*') || request()->routeIs('trial-participants.*') ? 'active' : '' }}">
                        <i class="bi bi-easel2"></i>
                        <span>Trial Classes</span>
                    </a>

                    <a href="{{ route('enrollments.index') }}" class="{{ request()->routeIs('programs.*') || request()->routeIs('batches.*') || request()->routeIs('students.*') || request()->routeIs('enrollments.*') ? 'active' : '' }}">
                        <i class="bi bi-person-plus"></i>
                        <span>Enrollment</span>
                    </a>

                    <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('orders.*') || request()->routeIs('payment-schedules.*') || request()->routeIs('payments.*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>Payments</span>
                    </a>

                    <a href="{{ route('instructors.index') }}" class="{{ request()->routeIs('instructors.*') || request()->routeIs('sessions.*') || request()->routeIs('monitoring.*') ? 'active' : '' }}">
                        <i class="bi bi-person-video3"></i>
                        <span>Instructors</span>
                    </a>

                    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </nav>

                <div class="header-actions">
                    <button class="icon-btn" type="button">
                        <i class="bi bi-bell"></i>
                    </button>

                    <div class="user-dropdown">
                        <button class="user-box user-toggle" type="button" id="userDropdownToggle">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=5B3E8E&color=fff" alt="User">
                            <span>{{ auth()->user()->name ?? 'Admin' }}</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>

                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="#">
                                <i class="bi bi-person-circle"></i>
                                <span>Profile</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="logout-btn">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="dashboard-shell">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('userDropdownToggle');
            const menu = document.getElementById('userDropdownMenu');

            if (toggle && menu) {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menu.classList.toggle('show');
                });

                document.addEventListener('click', function (e) {
                    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>