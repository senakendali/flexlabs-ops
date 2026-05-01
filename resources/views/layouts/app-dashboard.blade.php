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
        <header class="dashboard-topbar dashboard-topbar-fixed">
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

                            @php
                                $academicActive = request()->routeIs(
                                    'programs.*',
                                    'batches.*',
                                    'enrollments.*',
                                    'students.*',
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
                                    'academic.announcements.*',
                                    'academic.assessment-templates.*',
                                    'academic.assessment-scores.*',
                                    'academic.report-cards.*',
                                    'academic.certificates.*',
                                    'academic.attendances.*'
                                );

                                $academicMenuGroups = [
                                    [
                                        'title' => 'Core Setup',
                                        'subtitle' => 'Master data akademik utama.',
                                        'icon' => 'bi bi-layers-fill',
                                        'items' => [
                                            [
                                                'label' => 'Programs',
                                                'route' => 'programs.index',
                                                'active' => ['programs.*'],
                                                'icon' => 'bi bi-journal-bookmark-fill',
                                                'desc' => 'Program belajar, pricing, dan basic setup.',
                                            ],
                                            [
                                                'label' => 'Batches',
                                                'route' => 'batches.index',
                                                'active' => ['batches.*'],
                                                'icon' => 'bi bi-collection-play-fill',
                                                'desc' => 'Batch, kapasitas, tanggal mulai, dan status.',
                                            ],
                                            [
                                                'label' => 'Curriculum',
                                                'route' => 'curriculum.index',
                                                'active' => ['curriculum.*'],
                                                'icon' => 'bi bi-diagram-3-fill',
                                                'desc' => 'Stage, module, topic, dan sub topic.',
                                            ],
                                            [
                                                'label' => 'Student Enrollment',
                                                'route' => 'enrollments.index',
                                                'active' => ['enrollments.*', 'students.*'],
                                                'icon' => 'bi bi-people-fill',
                                                'desc' => 'Data student, enrollment, dan batch student.',
                                            ],
                                            [
                                                'label' => 'Announcements',
                                                'route' => 'academic.announcements.index',
                                                'active' => ['academic.announcements.*'],
                                                'icon' => 'bi bi-megaphone-fill',
                                                'desc' => 'Pengumuman untuk student dan batch.',
                                            ],
                                        ],
                                    ],
                                    [
                                        'title' => 'Learning Activities',
                                        'subtitle' => 'Tugas, quiz, dan aktivitas belajar.',
                                        'icon' => 'bi bi-journal-check',
                                        'items' => [
                                            [
                                                'label' => 'Assignments',
                                                'route' => 'assignments.index',
                                                'active' => ['assignments.*'],
                                                'icon' => 'bi bi-journal-check',
                                                'desc' => 'Master tugas dan aktivitas mandiri.',
                                            ],
                                            [
                                                'label' => 'Batch Assignments',
                                                'route' => 'batch-assignments.index',
                                                'active' => ['batch-assignments.*'],
                                                'icon' => 'bi bi-clipboard-check-fill',
                                                'desc' => 'Assign tugas ke batch tertentu.',
                                            ],
                                            [
                                                'label' => 'Assignment Submissions',
                                                'route' => 'assignment-submissions.index',
                                                'active' => ['assignment-submissions.*'],
                                                'icon' => 'bi bi-inbox-fill',
                                                'desc' => 'Review hasil pengumpulan tugas student.',
                                            ],
                                            [
                                                'label' => 'Learning Quizzes',
                                                'route' => 'learning-quizzes.index',
                                                'active' => ['learning-quizzes.*'],
                                                'icon' => 'bi bi-patch-question-fill',
                                                'desc' => 'Quiz pembelajaran dan bank pertanyaan.',
                                            ],
                                            [
                                                'label' => 'Batch Learning Quizzes',
                                                'route' => 'batch-learning-quizzes.index',
                                                'active' => ['batch-learning-quizzes.*'],
                                                'icon' => 'bi bi-ui-checks-grid',
                                                'desc' => 'Assign quiz ke batch tertentu.',
                                            ],
                                            [
                                                'label' => 'Quiz Attempts / Results',
                                                'route' => 'learning-quiz-attempts.index',
                                                'active' => ['learning-quiz-attempts.*'],
                                                'icon' => 'bi bi-activity',
                                                'desc' => 'Hasil pengerjaan quiz dan score student.',
                                            ],
                                        ],
                                    ],
                                    [
                                        'title' => 'Evaluation',
                                        'subtitle' => 'Assessment, nilai, dan report.',
                                        'icon' => 'bi bi-clipboard-data-fill',
                                        'items' => [
                                            [
                                                'label' => 'Assessment Templates',
                                                'route' => 'academic.assessment-templates.index',
                                                'active' => ['academic.assessment-templates.*'],
                                                'icon' => 'bi bi-sliders2-vertical',
                                                'desc' => 'Rubrik dan template penilaian.',
                                            ],
                                            [
                                                'label' => 'Assessment Scores',
                                                'route' => 'academic.assessment-scores.index',
                                                'active' => ['academic.assessment-scores.*'],
                                                'icon' => 'bi bi-pencil-square',
                                                'desc' => 'Input dan review score assessment.',
                                            ],
                                            [
                                                'label' => 'Report Cards',
                                                'route' => 'academic.report-cards.index',
                                                'active' => ['academic.report-cards.*'],
                                                'icon' => 'bi bi-file-earmark-text-fill',
                                                'desc' => 'Report card, grade, dan final evaluation.',
                                            ],
                                            [
                                                'label' => 'Certificates',
                                                'route' => 'academic.certificates.index',
                                                'active' => ['academic.certificates.*'],
                                                'icon' => 'bi bi-award-fill',
                                                'desc' => 'Sementara dipending sampai QR/verifikasi stabil.',
                                                'badge' => 'Pending',
                                                'disabled' => true,
                                            ],
                                        ],
                                    ],
                                    [
                                        'title' => 'Instructor Ops',
                                        'subtitle' => 'Pengajar, jadwal, attendance, dan tracking kelas.',
                                        'icon' => 'bi bi-person-video3',
                                        'items' => [
                                            [
                                                'label' => 'Instructors',
                                                'route' => 'instructors.index',
                                                'active' => ['instructors.*'],
                                                'icon' => 'bi bi-person-video3',
                                                'desc' => 'Master data instructor.',
                                            ],
                                            [
                                                'label' => 'Instructor Availability',
                                                'route' => 'academic.instructor-availability.index',
                                                'active' => ['academic.instructor-availability.*'],
                                                'icon' => 'bi bi-calendar2-week-fill',
                                                'desc' => 'Ketersediaan jadwal instructor.',
                                            ],
                                            [
                                                'label' => 'Mentoring Sessions',
                                                'route' => 'academic.mentoring-sessions.index',
                                                'active' => ['academic.mentoring-sessions.*'],
                                                'icon' => 'bi bi-chat-square-text-fill',
                                                'desc' => 'Jadwal mentoring student.',
                                            ],
                                            [
                                                'label' => 'Instructor Schedules',
                                                'route' => 'instructor-schedules.index',
                                                'active' => ['instructor-schedules.*'],
                                                'icon' => 'bi bi-calendar-check-fill',
                                                'desc' => 'Jadwal mengajar dan replacement.',
                                            ],
                                            [
                                                'label' => 'Student Attendance',
                                                'route' => 'academic.attendances.index',
                                                'active' => ['academic.attendances.*'],
                                                'icon' => 'bi bi-check2-square',
                                                'desc' => 'Catat kehadiran student online/offline per live session.',
                                            ],
                                            [
                                                'label' => 'Instructor Tracking',
                                                'route' => 'instructor-tracking.index',
                                                'active' => ['instructor-tracking.*'],
                                                'icon' => 'bi bi-clipboard-data-fill',
                                                'desc' => 'Tracking coverage materi per sesi.',
                                            ],
                                        ],
                                    ],
                                    [
                                        'title' => 'Trial & Workshops',
                                        'subtitle' => 'Trial class dan event akademik.',
                                        'icon' => 'bi bi-easel2-fill',
                                        'items' => [
                                            [
                                                'label' => 'Trial Themes',
                                                'route' => 'trial-themes.index',
                                                'active' => ['trial-themes.*'],
                                                'icon' => 'bi bi-lightbulb-fill',
                                                'desc' => 'Tema trial class.',
                                            ],
                                            [
                                                'label' => 'Trial Schedules',
                                                'route' => 'trial-schedules.index',
                                                'active' => ['trial-schedules.*'],
                                                'icon' => 'bi bi-calendar2-week-fill',
                                                'desc' => 'Jadwal trial class.',
                                            ],
                                            [
                                                'label' => 'Trial Participants',
                                                'route' => 'trial-participants.index',
                                                'active' => ['trial-participants.*'],
                                                'icon' => 'bi bi-people-fill',
                                                'desc' => 'Peserta trial dan follow up.',
                                            ],
                                            [
                                                'label' => 'Workshops',
                                                'route' => 'academic.workshops.index',
                                                'active' => ['academic.workshops.*'],
                                                'icon' => 'bi bi-easel2-fill',
                                                'desc' => 'Workshop public dan internal.',
                                            ],
                                        ],
                                    ],
                                ];
                            @endphp

                            <div class="dropdown dashboard-mega-dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ $academicActive ? 'active' : '' }}"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                >
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <span>Academic</span>
                                </button>

                                <div class="dropdown-menu dropdown-menu-academic dropdown-menu-mega">
                                    <div class="academic-mega-menu">
                                        <aside class="academic-mega-hero">
                                            <div class="academic-mega-hero-icon">
                                                <i class="bi bi-mortarboard-fill"></i>
                                            </div>

                                            <div>
                                                <div class="academic-mega-kicker">Academic Center</div>
                                                <h3>Learning Operations</h3>
                                                <p>
                                                   Centralized access to academic operations, learning management, assessment, and student development workflows.
                                                </p>
                                            </div>

                                            
                                        </aside>

                                        <div class="academic-mega-content">
                                            @foreach ($academicMenuGroups as $group)
                                                <section class="academic-mega-group">
                                                    <div class="academic-mega-group-header">
                                                        <div class="academic-mega-group-icon">
                                                            <i class="{{ $group['icon'] }}"></i>
                                                        </div>

                                                        <div>
                                                            <h4>{{ $group['title'] }}</h4>
                                                            <p>{{ $group['subtitle'] }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="academic-mega-items">
                                                        @foreach ($group['items'] as $item)
                                                            @php
                                                                $routeExists = Route::has($item['route']);
                                                                $isDisabled = ($item['disabled'] ?? false) || ! $routeExists;
                                                                $itemActive = request()->routeIs(...$item['active']);
                                                            @endphp

                                                            @if ($routeExists || ($item['disabled'] ?? false))
                                                                <a
                                                                    href="{{ $isDisabled ? 'javascript:void(0)' : route($item['route']) }}"
                                                                    class="academic-mega-item {{ $itemActive ? 'active' : '' }} {{ $isDisabled ? 'disabled' : '' }}"
                                                                    @if ($isDisabled) aria-disabled="true" tabindex="-1" @endif
                                                                >
                                                                    <span class="academic-mega-item-icon">
                                                                        <i class="{{ $item['icon'] }}"></i>
                                                                    </span>

                                                                    <span class="academic-mega-item-body">
                                                                        <span class="academic-mega-item-title">
                                                                            {{ $item['label'] }}

                                                                            @if (! empty($item['badge']))
                                                                                <span class="academic-mega-badge">
                                                                                    {{ $item['badge'] }}
                                                                                </span>
                                                                            @endif
                                                                        </span>

                                                                        <span class="academic-mega-item-desc">
                                                                            {{ $item['desc'] }}
                                                                        </span>
                                                                    </span>
                                                                </a>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </section>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="dropdown-toggle nav-btn no-arrow {{ request()->routeIs(
                                        'sales.*',
                                        'sales-daily-reports.*',
                                        'sales-performance.*',
                                        'sales-orders.*',
                                        'orders.*'
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
                                    @if (Route::has('sales-orders.index'))
                                        <a class="dropdown-item {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}"
                                           href="{{ route('sales-orders.index') }}">
                                            <i class="bi bi-receipt-cutoff me-2"></i>Sales Orders
                                        </a>
                                    @endif

                                    @if (Route::has('payment-schedules.index'))
                                        <a class="dropdown-item {{ request()->routeIs('payment-schedules.*') ? 'active' : '' }}"
                                           href="{{ route('payment-schedules.index') }}">
                                            <i class="bi bi-calendar-check me-2"></i>Payment Schedule
                                        </a>
                                    @endif

                                    @if (Route::has('invoices.index'))
                                        <a class="dropdown-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                                           href="{{ route('invoices.index') }}">
                                            <i class="bi bi-file-earmark-text me-2"></i>Invoices
                                        </a>
                                    @endif

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