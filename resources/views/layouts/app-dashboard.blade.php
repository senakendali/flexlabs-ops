<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css?' . time()) }}">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                                        'batches.*',
                                        'enrollments.*',
                                        'curriculum.*',
                                        'assignments.*',
                                        'batch-assignments.*',
                                        'assignment-submissions.*',
                                        'learning-quizzes.*',
                                        'batch-learning-quizzes.*',
                                        'learning-quiz-attempts.*',
                                        'instructors.*',
                                        'instructor-schedules.*',
                                        'instructor-tracking.*',
                                        'academic.instructor-availability.*',
                                        'trial-themes.*',
                                        'trial-schedules.*',
                                        'trial-participants.*',
                                        'academic.workshops.*',
                                        'academic.mentoring-sessions.*',
                                        'academic.announcements.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <span>Academic</span>
                                </button>

                                <div class="dropdown-menu dropdown-menu-academic">
                                    <div class="dropdown-section-title">Core Setup</div>

                                    @if (Route::has('programs.index'))
                                        <a class="dropdown-item {{ request()->routeIs('programs.*') ? 'active' : '' }}"
                                        href="{{ route('programs.index') }}">
                                            <i class="bi bi-journal-bookmark-fill me-2"></i>Programs
                                        </a>
                                    @endif

                                    @if (Route::has('batches.index'))
                                        <a class="dropdown-item {{ request()->routeIs('batches.*') ? 'active' : '' }}"
                                        href="{{ route('batches.index') }}">
                                            <i class="bi bi-collection-play-fill me-2"></i>Batches
                                        </a>
                                    @endif

                                    @if (Route::has('curriculum.index'))
                                        <a class="dropdown-item {{ request()->routeIs('curriculum.*') ? 'active' : '' }}"
                                        href="{{ route('curriculum.index') }}">
                                            <i class="bi bi-diagram-3-fill me-2"></i>Curriculum
                                        </a>
                                    @endif

                                    @if (Route::has('enrollments.index'))
                                        <a class="dropdown-item {{ request()->routeIs('enrollments.*', 'batches.*', 'students.*') ? 'active' : '' }}"
                                        href="{{ route('enrollments.index') }}">
                                            <i class="bi bi-people me-2"></i>Student Enrollment
                                        </a>
                                    @endif

                                    @if (Route::has('academic.announcements.index'))
                                        <a
                                            class="dropdown-item {{ request()->routeIs('academic.announcements.*') ? 'active' : '' }}"
                                            href="{{ route('academic.announcements.index') }}"
                                        >
                                            <i class="bi bi-megaphone me-2"></i>
                                            Announcements
                                        </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Learning Activities</div>

                                    @if (Route::has('assignments.index'))
                                        <a class="dropdown-item {{ request()->routeIs('assignments.*') ? 'active' : '' }}"
                                        href="{{ route('assignments.index') }}">
                                            <i class="bi bi-journal-check me-2"></i>Assignments
                                        </a>
                                    @endif

                                    @if (Route::has('batch-assignments.index'))
                                        <a class="dropdown-item {{ request()->routeIs('batch-assignments.*') ? 'active' : '' }}"
                                        href="{{ route('batch-assignments.index') }}">
                                            <i class="bi bi-clipboard-check me-2"></i>Batch Assignments
                                        </a>
                                    @endif

                                    @if (Route::has('assignment-submissions.index'))
                                        <a class="dropdown-item {{ request()->routeIs('assignment-submissions.*') ? 'active' : '' }}"
                                        href="{{ route('assignment-submissions.index') }}">
                                            <i class="bi bi-inbox-fill me-2"></i>Assignment Submissions
                                        </a>
                                    @endif

                                    @if (Route::has('learning-quizzes.index'))
                                        <a class="dropdown-item {{ request()->routeIs('learning-quizzes.*') ? 'active' : '' }}"
                                        href="{{ route('learning-quizzes.index') }}">
                                            <i class="bi bi-patch-question-fill me-2"></i>Learning Quizzes
                                        </a>
                                    @endif

                                    @if (Route::has('batch-learning-quizzes.index'))
                                        <a class="dropdown-item {{ request()->routeIs('batch-learning-quizzes.*') ? 'active' : '' }}"
                                        href="{{ route('batch-learning-quizzes.index') }}">
                                            <i class="bi bi-ui-checks-grid me-2"></i>Batch Learning Quizzes
                                        </a>
                                    @endif

                                    @if (Route::has('learning-quiz-attempts.index'))
                                        <a class="dropdown-item {{ request()->routeIs('learning-quiz-attempts.*') ? 'active' : '' }}"
                                        href="{{ route('learning-quiz-attempts.index') }}">
                                            <i class="bi bi-activity me-2"></i>Quiz Attempts / Results
                                        </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Instructor Operations</div>

                                    @if (Route::has('instructors.index'))
                                        <a class="dropdown-item {{ request()->routeIs('instructors.*') ? 'active' : '' }}"
                                        href="{{ route('instructors.index') }}">
                                            <i class="bi bi-person-video3 me-2"></i>Instructors
                                        </a>
                                    @endif

                                    @if (Route::has('academic.instructor-availability.index'))
                                        <a
                                            class="dropdown-item {{ request()->routeIs('academic.instructor-availability.*') ? 'active' : '' }}"
                                            href="{{ route('academic.instructor-availability.index') }}"
                                        >
                                            <i class="bi bi-calendar2-week me-2"></i>
                                            Instructor Availability
                                        </a>
                                    @endif

                                    @if (Route::has('academic.mentoring-sessions.index'))
                                        <a
                                            class="dropdown-item {{ request()->routeIs('academic.mentoring-sessions.*') ? 'active' : '' }}"
                                            href="{{ route('academic.mentoring-sessions.index') }}"
                                        >
                                            <i class="bi bi-chat-square-text me-2"></i>
                                            Mentoring Sessions
                                        </a>
                                    @endif

                                    @if (Route::has('instructor-schedules.index'))
                                        <a class="dropdown-item {{ request()->routeIs('instructor-schedules.*') ? 'active' : '' }}"
                                        href="{{ route('instructor-schedules.index') }}">
                                            <i class="bi bi-calendar-check-fill me-2"></i>Instructor Schedules
                                        </a>
                                    @endif

                                    @if (Route::has('instructor-tracking.index'))
                                        <a class="dropdown-item {{ request()->routeIs('instructor-tracking.*') ? 'active' : '' }}"
                                        href="{{ route('instructor-tracking.index') }}">
                                            <i class="bi bi-clipboard-data-fill me-2"></i>Instructor Tracking
                                        </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Trial & Workshops</div>

                                    @if (Route::has('trial-themes.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-themes.*') ? 'active' : '' }}"
                                        href="{{ route('trial-themes.index') }}">
                                            <i class="bi bi-lightbulb-fill me-2"></i>Trial Themes
                                        </a>
                                    @endif

                                    @if (Route::has('trial-schedules.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-schedules.*') ? 'active' : '' }}"
                                        href="{{ route('trial-schedules.index') }}">
                                            <i class="bi bi-calendar2-week-fill me-2"></i>Trial Schedules
                                        </a>
                                    @endif

                                    @if (Route::has('trial-participants.index'))
                                        <a class="dropdown-item {{ request()->routeIs('trial-participants.*') ? 'active' : '' }}"
                                        href="{{ route('trial-participants.index') }}">
                                            <i class="bi bi-people-fill me-2"></i>Trial Participants
                                        </a>
                                    @endif

                                    @if (Route::has('academic.workshops.index'))
                                        <a class="dropdown-item {{ request()->routeIs('academic.workshops.*') ? 'active' : '' }}"
                                        href="{{ route('academic.workshops.index') }}">
                                            <i class="bi bi-easel2-fill me-2"></i>Workshops
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'sales.*',
                                        'sales-daily-reports.*',
                                        'sales-performance.*',
                                        'sales-orders.*',
                                        'orders.*',
                                        'batches.*',
                                        'students.*'
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
                                            <i class="bi bi-journal-text me-2"></i>Daily Report
                                        </a>
                                    @endif

                                    @if (Route::has('sales-performance.index'))
                                        <a class="dropdown-item {{ request()->routeIs('sales-performance.*') ? 'active' : '' }}"
                                        href="{{ route('sales-performance.index') }}">
                                            <i class="bi bi-bar-chart-line me-2"></i>Performance
                                        </a>
                                    @endif

                                    

                                    @if (Route::has('orders.index'))
                                        <a class="dropdown-item {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                                        href="{{ route('orders.index') }}">
                                            <i class="bi bi-receipt-cutoff me-2"></i>Sales Orders
                                        </a>
                                    @endif
                                </div>
                            </div>

                           <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'marketing.*',
                                        'marketing.setup.*',
                                        'quiz.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-megaphone-fill"></i>
                                    <span>Marketing</span>
                                </button>

                                <div class="dropdown-menu dropdown-menu-marketing">
                                    <div class="dropdown-section-title">Reporting</div>

                                    @if (Route::has('marketing.dashboard'))
                                        <a class="dropdown-item {{ request()->routeIs('marketing.dashboard') ? 'active' : '' }}"
                                        href="{{ route('marketing.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                        </a>
                                    @endif

                                    @if (Route::has('marketing.reports.index'))
                                        <a class="dropdown-item {{ request()->routeIs('marketing.reports.*') ? 'active' : '' }}"
                                        href="{{ route('marketing.reports.index') }}">
                                            <i class="bi bi-bar-chart-line me-2"></i>Reports
                                        </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Setup</div>

                                    @if (Route::has('marketing.setup.campaigns.index'))
                                        <a class="dropdown-item {{ request()->routeIs('marketing.setup.campaigns.*') ? 'active' : '' }}"
                                        href="{{ route('marketing.setup.campaigns.index') }}">
                                            <i class="bi bi-bullseye me-2"></i>Campaign Setup
                                        </a>
                                    @endif

                                    @if (Route::has('marketing.setup.ads.index'))
                                        <a class="dropdown-item {{ request()->routeIs('marketing.setup.ads.*') ? 'active' : '' }}"
                                        href="{{ route('marketing.setup.ads.index') }}">
                                            <i class="bi bi-badge-ad me-2"></i>Ads Setup
                                        </a>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <div class="dropdown-section-title">Tools</div>

                                    @if (Route::has('quiz.index'))
                                        <a class="dropdown-item {{ request()->routeIs('quiz.*') ? 'active' : '' }}"
                                        href="{{ route('quiz.index') }}">
                                            <i class="bi bi-ui-checks-grid me-2"></i>Quiz Management
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'finance.*',
                                        'payments.*',
                                        'invoices.*',
                                        'sales-orders.*',
                                        'payment-schedules.*'
                                    ) ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-wallet2"></i>
                                    <span>Finance</span>
                                </button>

                                <div class="dropdown-menu">

                                    {{-- Sales Order --}}
                                    @if (Route::has('sales-orders.index'))
                                        <a class="dropdown-item {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}"
                                        href="{{ route('sales-orders.index') }}">
                                            <i class="bi bi-receipt-cutoff me-2"></i>Sales Orders
                                        </a>
                                    @endif

                                    {{-- Payment Schedule --}}
                                    @if (Route::has('payment-schedules.index'))
                                        <a class="dropdown-item {{ request()->routeIs('payment-schedules.*') ? 'active' : '' }}"
                                        href="{{ route('payment-schedules.index') }}">
                                            <i class="bi bi-calendar-check me-2"></i>Payment Schedule
                                        </a>
                                    @endif

                                    {{-- Invoices --}}
                                    @if (Route::has('invoices.index'))
                                        <a class="dropdown-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                                        href="{{ route('invoices.index') }}">
                                            <i class="bi bi-file-earmark-text me-2"></i>Invoices
                                        </a>
                                    @endif

                                    {{-- Payments --}}
                                    @if (Route::has('payments.index'))
                                        <a class="dropdown-item {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                                        href="{{ route('payments.index') }}">
                                            <i class="bi bi-credit-card me-2"></i>Payments
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
                                        'inventory.atk-requests.*',
                                        'operation.meeting-minutes.*',
                                        'operation.meeting-minute-action-items.*'
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

                                    <div class="dropdown-section-title">Meeting & Documents</div>

                                    @if (Route::has('operation.meeting-minutes.index'))
                                        <a class="dropdown-item {{ request()->routeIs('operation.meeting-minutes.*', 'operation.meeting-minute-action-items.*') ? 'active' : '' }}"
                                        href="{{ route('operation.meeting-minutes.index') }}">
                                            <i class="bi bi-journal-text me-2"></i>Meeting Minutes / MOM
                                        </a>
                                    @else
                                        <span class="dropdown-item-text text-muted small px-3 py-2">
                                            Meeting Minutes belum tersedia
                                        </span>
                                    @endif

                                    <div class="dropdown-divider"></div>

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    @stack('scripts')

</body>
</html>