@extends('layouts.app-dashboard')

@section('title', 'Marketing Dashboard')

@section('content')
@php
    $mode = request('mode', 'monthly');

    $periodLabel = $mode === 'weekly' ? 'Week 3 - April 2026' : 'April 2026';

    $summary = $mode === 'weekly'
        ? [
            'active_campaigns' => 3,
            'ads_running' => 5,
            'upcoming_events' => 2,
            'total_budget' => 12500000,
            'budget_used' => 7600000,
            'budget_remaining' => 4900000,
            'leads_snapshot' => 38,
            'registrants' => 19,
            'attendees' => 11,
            'conversions' => 4,
        ]
        : [
            'active_campaigns' => 5,
            'ads_running' => 8,
            'upcoming_events' => 4,
            'total_budget' => 42000000,
            'budget_used' => 24750000,
            'budget_remaining' => 17250000,
            'leads_snapshot' => 186,
            'registrants' => 94,
            'attendees' => 61,
            'conversions' => 21,
        ];

    $budgetUtilization = $summary['total_budget'] > 0
        ? ($summary['budget_used'] / $summary['total_budget']) * 100
        : 0;

    $campaignPlans = $mode === 'weekly'
        ? [
            [
                'name' => 'Retargeting Leads April',
                'objective' => 'Mendorong leads yang belum closing agar kembali engaged',
                'period' => '21 Apr 2026 - 27 Apr 2026',
                'budget' => 2500000,
                'used' => 1400000,
                'owner' => 'Marketing Team',
                'status' => 'On Progress',
                'note' => 'Fokus pada leads yang sudah sempat interaksi namun belum daftar.',
            ],
            [
                'name' => 'Landing Page Optimization',
                'objective' => 'Meningkatkan conversion dari traffic iklan ke form',
                'period' => '22 Apr 2026 - 25 Apr 2026',
                'budget' => 1000000,
                'used' => 400000,
                'owner' => 'Marketing + Design',
                'status' => 'Planned',
                'note' => 'Perbaikan headline, CTA, dan susunan section utama.',
            ],
            [
                'name' => 'Referral Push',
                'objective' => 'Menambah lead dari jaringan student & alumni',
                'period' => '20 Apr 2026 - 30 Apr 2026',
                'budget' => 500000,
                'used' => 150000,
                'owner' => 'Community Team',
                'status' => 'Review',
                'note' => 'Masih kecil secara volume, tetapi sangat murah secara biaya.',
            ],
        ]
        : [
            [
                'name' => 'May Intake Push',
                'objective' => 'Drive enrollment untuk batch baru',
                'period' => '1 Apr 2026 - 30 Apr 2026',
                'budget' => 12000000,
                'used' => 7500000,
                'owner' => 'Marketing Team',
                'status' => 'On Progress',
                'note' => 'Campaign utama untuk mendorong pendaftaran batch berjalan.',
            ],
            [
                'name' => 'Brand Awareness Campus',
                'objective' => 'Meningkatkan awareness di kampus target',
                'period' => '5 Apr 2026 - 5 May 2026',
                'budget' => 8000000,
                'used' => 3200000,
                'owner' => 'Partnership & Marketing',
                'status' => 'Planned',
                'note' => 'Difokuskan untuk exposure awal dan membangun top of mind.',
            ],
            [
                'name' => 'Referral Activation',
                'objective' => 'Mendorong lead masuk dari komunitas internal',
                'period' => '10 Apr 2026 - 30 Apr 2026',
                'budget' => 2000000,
                'used' => 900000,
                'owner' => 'Community Team',
                'status' => 'Review',
                'note' => 'Perlu diperkuat dari sisi incentive dan materi komunikasi.',
            ],
        ];

    $adsRunning = $mode === 'weekly'
        ? [
            [
                'name' => 'Meta Retargeting - Batch April',
                'platform' => 'Meta Ads',
                'campaign' => 'Retargeting Leads April',
                'objective' => 'Retargeting',
                'budget' => 1500000,
                'used' => 950000,
                'period' => '21 Apr - 27 Apr 2026',
                'status' => 'Active',
            ],
            [
                'name' => 'TikTok Awareness - Tech Career',
                'platform' => 'TikTok Ads',
                'campaign' => 'Brand Awareness',
                'objective' => 'Awareness',
                'budget' => 1000000,
                'used' => 500000,
                'period' => '20 Apr - 27 Apr 2026',
                'status' => 'Paused',
            ],
            [
                'name' => 'Meta Traffic - Trial Landing Page',
                'platform' => 'Meta Ads',
                'campaign' => 'Landing Page Optimization',
                'objective' => 'Traffic',
                'budget' => 800000,
                'used' => 350000,
                'period' => '22 Apr - 25 Apr 2026',
                'status' => 'Active',
            ],
        ]
        : [
            [
                'name' => 'IG Lead Form - Intro Class',
                'platform' => 'Instagram',
                'campaign' => 'May Intake Push',
                'objective' => 'Lead Generation',
                'budget' => 3500000,
                'used' => 2200000,
                'period' => '1 Apr - 15 Apr 2026',
                'status' => 'Active',
            ],
            [
                'name' => 'Meta Traffic - Landing Page',
                'platform' => 'Meta Ads',
                'campaign' => 'May Intake Push',
                'objective' => 'Traffic',
                'budget' => 4000000,
                'used' => 2600000,
                'period' => '3 Apr - 30 Apr 2026',
                'status' => 'Active',
            ],
            [
                'name' => 'TikTok Video Ads - Awareness',
                'platform' => 'TikTok',
                'campaign' => 'Brand Awareness Campus',
                'objective' => 'Reach',
                'budget' => 2500000,
                'used' => 1400000,
                'period' => '10 Apr - 25 Apr 2026',
                'status' => 'Paused',
            ],
            [
                'name' => 'Google Search - Coding Class',
                'platform' => 'Google Ads',
                'campaign' => 'Referral Activation',
                'objective' => 'Intent Capture',
                'budget' => 1500000,
                'used' => 900000,
                'period' => '5 Apr - 30 Apr 2026',
                'status' => 'Review',
            ],
        ];

    $upcomingEvents = $mode === 'weekly'
        ? [
            [
                'name' => 'Tech Career Expo Binus',
                'date' => '24 Apr 2026',
                'type' => 'External Event',
                'location' => 'Jakarta',
                'target' => 80,
                'budget' => 3000000,
                'status' => 'Confirmed',
            ],
            [
                'name' => 'FlexLabs Open House',
                'date' => '26 Apr 2026',
                'type' => 'Owned Event',
                'location' => 'Online',
                'target' => 40,
                'budget' => 1500000,
                'status' => 'Open Registration',
            ],
        ]
        : [
            [
                'name' => 'Webinar - Career in Tech for Beginners',
                'date' => '22 Apr 2026',
                'type' => 'Owned Event',
                'location' => 'Online',
                'target' => 50,
                'budget' => 1000000,
                'status' => 'Scheduled',
            ],
            [
                'name' => 'Free Trial Class - AI Software Engineering',
                'date' => '25 Apr 2026',
                'type' => 'Trial Class',
                'location' => 'Online',
                'target' => 30,
                'budget' => 1500000,
                'status' => 'Open Registration',
            ],
            [
                'name' => 'Campus Tech Expo',
                'date' => '27 Apr 2026',
                'type' => 'Participated Event',
                'location' => 'Bandung',
                'target' => 100,
                'budget' => 5000000,
                'status' => 'Confirmed',
            ],
            [
                'name' => 'Mini Info Session - FlexLabs Program Overview',
                'date' => '29 Apr 2026',
                'type' => 'Info Session',
                'location' => 'Online',
                'target' => 40,
                'budget' => 750000,
                'status' => 'Planned',
            ],
        ];

    $focusNotes = $mode === 'weekly'
        ? [
            'Minggu ini fokus utama ada pada retargeting leads yang sudah pernah engaged namun belum closing.',
            'Ads yang berjalan masih didominasi Meta Ads, sementara TikTok masih dalam tahap evaluasi.',
            'Event terdekat perlu dijaga agar execution rapi karena akan mempengaruhi snapshot hasil mingguan.',
        ]
        : [
            'Periode ini dashboard lebih cocok dibaca sebagai management summary karena data masih berbasis input manual.',
            'Campaign utama masih berfokus pada intake batch, awareness kampus, dan referral activation.',
            'Ads sudah berjalan di beberapa channel, tetapi evaluasi scale tetap harus hati-hati sebelum terkoneksi ke API.',
        ];

    $recommendedActions = $mode === 'weekly'
        ? [
            'Pastikan semua leads baru difollow up maksimal 1x24 jam.',
            'Gunakan event minggu ini sebagai momentum untuk mendorong registrasi dan attendance.',
            'Review ads yang masih pause atau review sebelum masuk minggu berikutnya.',
        ]
        : [
            'Jaga disiplin update manual agar snapshot hasil tetap relevan untuk management.',
            'Pantau penyerapan budget per campaign sebelum memutuskan scale tambahan.',
            'Pisahkan pembacaan antara campaign plan, ads execution, dan event execution agar insight tidak tercampur.',
        ];

    $statusBadgeMap = [
        'On Progress' => 'bg-primary-subtle text-primary-emphasis',
        'Planned' => 'bg-warning-subtle text-warning-emphasis',
        'Review' => 'bg-secondary-subtle text-secondary-emphasis',
        'Done' => 'bg-success-subtle text-success-emphasis',
        'Active' => 'bg-success-subtle text-success-emphasis',
        'Paused' => 'bg-secondary-subtle text-secondary-emphasis',
        'Confirmed' => 'bg-primary-subtle text-primary-emphasis',
        'Scheduled' => 'bg-primary-subtle text-primary-emphasis',
        'Open Registration' => 'bg-success-subtle text-success-emphasis',
    ];
@endphp

<div class="container-fluid px-4 py-4">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Management View</div>
                <h1 class="page-title mb-2">Marketing Summary Dashboard</h1>
                <p class="page-subtitle mb-0">
                    Ringkasan plan marketing yang sedang berjalan, campaign beserta budget, ads yang aktif,
                    event yang akan dijalankan, dan snapshot hasil untuk membantu management membaca prioritas periode ini.
                </p>
            </div>

            <div class="d-flex flex-column align-items-md-end gap-2">
                <div class="revenue-total-box">
                    <div class="revenue-total-label">Reporting Period</div>
                    <div class="revenue-total-value">{{ $periodLabel }}</div>
                </div>

                <div class="btn-group dashboard-mode-switch" role="group" aria-label="Dashboard mode switch">
                    <a href="{{ route('marketing.dashboard', ['mode' => 'weekly']) }}"
                       class="btn {{ $mode === 'weekly' ? 'btn-primary' : 'btn-light border' }}">
                        Weekly
                    </a>
                    <a href="{{ route('marketing.dashboard', ['mode' => 'monthly']) }}"
                       class="btn {{ $mode === 'monthly' ? 'btn-primary' : 'btn-light border' }}">
                        Monthly
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Executive Snapshot --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Executive Snapshot</div>
        <h4 class="dashboard-section-title mb-1">Ringkasan cepat untuk management</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ikhtisar utama mengenai aktivitas pemasaran, penggunaan anggaran, agenda event, dan hasil utama dalam periode berjalan.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Campaigns</div>
                        <div class="stat-value">{{ number_format($summary['active_campaigns']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Campaign yang masih aktif atau sedang masuk fase eksekusi.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-badge-ad-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Ads Running</div>
                        <div class="stat-value">{{ number_format($summary['ads_running']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Iklan yang sedang aktif atau masih dimonitor pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Upcoming Events</div>
                        <div class="stat-value">{{ number_format($summary['upcoming_events']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Event internal maupun eksternal yang sudah masuk agenda.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Budget Used</div>
                        <div class="stat-value">Rp{{ number_format($summary['budget_used'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total budget Rp{{ number_format($summary['total_budget'], 0, ',', '.') }}
                    · terserap {{ number_format($budgetUtilization, 1) }}%
                </div>
            </div>
        </div>
    </div>

    {{-- Budget & Snapshot --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Budget & Snapshot</div>
        <h4 class="dashboard-section-title mb-1">Budget overview dan hasil ringkas</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan penggunaan anggaran dan capaian utama untuk memberikan gambaran umum atas pelaksanaan aktivitas pemasaran.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Budget Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Gambaran alokasi anggaran, realisasi penggunaan, dan saldo anggaran pada periode berjalan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-md-4 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-cash-stack me-1"></i> Total Budget
                                </div>
                                <div class="mini-stat-value">Rp{{ number_format($summary['total_budget'], 0, ',', '.') }}</div>
                                <div class="mini-stat-help">
                                    Total alokasi budget pada periode terpilih.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-credit-card-2-front-fill me-1"></i> Budget Used
                                </div>
                                <div class="mini-stat-value">Rp{{ number_format($summary['budget_used'], 0, ',', '.') }}</div>
                                <div class="mini-stat-help">
                                    Budget yang sudah terpakai sampai update terakhir.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-piggy-bank-fill me-1"></i> Remaining Budget
                                </div>
                                <div class="mini-stat-value">Rp{{ number_format($summary['budget_remaining'], 0, ',', '.') }}</div>
                                <div class="mini-stat-help">
                                    Sisa budget yang masih bisa digunakan atau disimpan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="insight-box mt-3">
                        <p class="mb-0">
                            Informasi pada bagian ini memberikan gambaran umum mengenai posisi anggaran dan capaian utama aktivitas pemasaran pada periode terpilih.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Result Snapshot</h5>
                        <p class="content-card-subtitle mb-0">
                            Hasil yang sudah masuk ke sistem sampai update terakhir.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">Leads Snapshot</div>
                                <div class="mini-stat-value">{{ number_format($summary['leads_snapshot']) }}</div>
                                <div class="mini-stat-help">Lead yang sudah tercatat.</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">Registrants</div>
                                <div class="mini-stat-value">{{ number_format($summary['registrants']) }}</div>
                                <div class="mini-stat-help">Prospek yang sudah registrasi.</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">Attendees</div>
                                <div class="mini-stat-value">{{ number_format($summary['attendees']) }}</div>
                                <div class="mini-stat-help">Peserta yang benar-benar hadir.</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">Conversions</div>
                                <div class="mini-stat-value">{{ number_format($summary['conversions']) }}</div>
                                <div class="mini-stat-help">Hasil akhir yang sudah closing.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Campaign Plan & Ads --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Execution Visibility</div>
        <h4 class="dashboard-section-title mb-1">Campaign plan dan ads yang berjalan</h4>
        <p class="dashboard-section-subtitle mb-0">
            Gambaran pelaksanaan campaign dan aktivitas iklan yang sedang berjalan pada periode terpilih.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Campaign Plan</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar campaign utama, objective, budget, dan status eksekusinya.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Period</th>
                                    <th>Budget</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($campaignPlans as $plan)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $plan['name'] }}</div>
                                            <div class="text-muted small">{{ $plan['objective'] }}</div>
                                            <div class="text-muted small mt-1">{{ $plan['note'] }}</div>
                                        </td>
                                        <td>{{ $plan['period'] }}</td>
                                        <td>
                                            <div>Rp{{ number_format($plan['budget'], 0, ',', '.') }}</div>
                                            <div class="text-muted small">Used Rp{{ number_format($plan['used'], 0, ',', '.') }}</div>
                                        </td>
                                        <td>{{ $plan['owner'] }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeMap[$plan['status']] ?? 'bg-light text-dark' }}">
                                                {{ $plan['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Ads Running</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar ads yang sedang aktif, pause, atau masih review.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ads</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($adsRunning as $ad)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $ad['name'] }}</div>
                                            <div class="text-muted small">
                                                {{ $ad['platform'] }} · {{ $ad['objective'] }}
                                            </div>
                                            <div class="text-muted small">
                                                Budget Rp{{ number_format($ad['budget'], 0, ',', '.') }}
                                                · Used Rp{{ number_format($ad['used'], 0, ',', '.') }}
                                            </div>
                                            <div class="text-muted small">{{ $ad['period'] }}</div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeMap[$ad['status']] ?? 'bg-light text-dark' }}">
                                                {{ $ad['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Events & Reading Note --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Event Visibility</div>
        <h4 class="dashboard-section-title mb-1">Agenda event dan catatan pembacaan management</h4>
        <p class="dashboard-section-subtitle mb-0">
            Event tetap penting ditampilkan karena bisa berupa event yang diselenggarakan sendiri
            maupun event eksternal yang dihadiri tim marketing.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Events</h5>
                        <p class="content-card-subtitle mb-0">
                            Event yang akan datang dan perlu diperhatikan eksekusinya.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($upcomingEvents as $event)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $event['name'] }}</div>
                                            <div class="text-muted small">
                                                {{ $event['type'] }} · {{ $event['location'] }}
                                            </div>
                                            <div class="text-muted small">
                                                Target {{ $event['target'] }} peserta
                                            </div>
                                        </td>
                                        <td>{{ $event['date'] }}</td>
                                        <td>Rp{{ number_format($event['budget'], 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeMap[$event['status']] ?? 'bg-light text-dark' }}">
                                                {{ $event['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Management Reading Note</h5>
                        <p class="content-card-subtitle mb-0">
                            Poin penting yang perlu cepat dipahami sebelum mengambil keputusan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="insight-section mb-4">
                        <div class="insight-heading">Key Reading</div>
                        <ul class="insight-list mb-0">
                            @foreach ($focusNotes as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="insight-section">
                        <div class="insight-heading">Recommended Action</div>
                        <ul class="insight-list mb-0">
                            @foreach ($recommendedActions as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
   
</style>
@endpush