@extends('layouts.app-dashboard')

@section('title', 'Marketing Dashboard')

@section('content')
@php
    $mode = request('mode', 'monthly');

    $periodLabel = $mode === 'weekly' ? 'Week 3 - April 2026' : 'April 2026';

    $summary = $mode === 'weekly'
        ? [
            'leads' => 38,
            'qualified_leads' => 21,
            'conversions' => 4,
            'revenue' => 6000000,
            'budget' => 1000000,
            'actual_spend' => 850000,
            'cpl' => 22368,
            'cac' => 212500,
            'conversion_rate' => 10.53,
            'qualification_rate' => 55.26,
        ]
        : [
            'leads' => 150,
            'qualified_leads' => 80,
            'conversions' => 15,
            'revenue' => 22500000,
            'budget' => 4000000,
            'actual_spend' => 3500000,
            'cpl' => 23333,
            'cac' => 233333,
            'conversion_rate' => 10.00,
            'qualification_rate' => 53.33,
        ];

    $channelPerformance = $mode === 'weekly'
        ? collect([
            (object) [
                'channel' => 'Meta Ads',
                'leads' => 18,
                'qualified_leads' => 11,
                'conversions' => 2,
                'revenue' => 3000000,
                'spend' => 450000,
                'note' => 'Masih menjadi sumber lead paling stabil minggu ini.',
            ],
            (object) [
                'channel' => 'TikTok Ads',
                'leads' => 8,
                'qualified_leads' => 3,
                'conversions' => 0,
                'revenue' => 0,
                'spend' => 200000,
                'note' => 'Awareness cukup baik, namun conversion belum terbentuk.',
            ],
            (object) [
                'channel' => 'Webinar',
                'leads' => 7,
                'qualified_leads' => 5,
                'conversions' => 2,
                'revenue' => 3000000,
                'spend' => 200000,
                'note' => 'Konversi paling baik pada minggu berjalan.',
            ],
            (object) [
                'channel' => 'Referral',
                'leads' => 5,
                'qualified_leads' => 2,
                'conversions' => 0,
                'revenue' => 0,
                'spend' => 0,
                'note' => 'Masih kecil secara volume, tetapi biaya sangat rendah.',
            ],
        ])
        : collect([
            (object) [
                'channel' => 'Meta Ads',
                'leads' => 70,
                'qualified_leads' => 40,
                'conversions' => 7,
                'revenue' => 10500000,
                'spend' => 2000000,
                'note' => 'Lead generator utama dengan biaya paling efisien.',
            ],
            (object) [
                'channel' => 'TikTok Ads',
                'leads' => 30,
                'qualified_leads' => 14,
                'conversions' => 2,
                'revenue' => 3000000,
                'spend' => 1000000,
                'note' => 'Traffic cukup baik, tetapi conversion masih rendah.',
            ],
            (object) [
                'channel' => 'Webinar',
                'leads' => 25,
                'qualified_leads' => 18,
                'conversions' => 4,
                'revenue' => 6000000,
                'spend' => 500000,
                'note' => 'Konversi tinggi, cocok sebagai channel pemanas dan closing.',
            ],
            (object) [
                'channel' => 'Referral',
                'leads' => 25,
                'qualified_leads' => 8,
                'conversions' => 2,
                'revenue' => 3000000,
                'spend' => 0,
                'note' => 'Volume kecil, tetapi biaya akuisisi sangat rendah.',
            ],
        ]);

    $trendData = $mode === 'weekly'
        ? [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'leads' => [4, 5, 6, 4, 7, 5, 7],
            'conversions' => [0, 1, 0, 1, 1, 0, 1],
        ]
        : [
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            'leads' => [28, 36, 42, 44],
            'conversions' => [2, 4, 4, 5],
        ];

    $currentPlans = $mode === 'weekly'
        ? [
            [
                'title' => 'Retargeting campaign untuk leads yang belum closing',
                'owner' => 'Marketing Team',
                'deadline' => '24 Apr 2026',
                'status' => 'On Progress',
                'description' => 'Fokus pada leads yang sudah engaged namun belum melakukan pendaftaran.',
            ],
            [
                'title' => 'Optimasi landing page untuk free trial class',
                'owner' => 'Marketing + Design',
                'deadline' => '25 Apr 2026',
                'status' => 'Planned',
                'description' => 'Perbaikan headline, CTA, dan struktur form untuk meningkatkan conversion.',
            ],
            [
                'title' => 'Sinkronisasi follow up leads dengan tim sales',
                'owner' => 'Sales Team',
                'deadline' => '26 Apr 2026',
                'status' => 'On Progress',
                'description' => 'Pastikan semua leads minggu ini di-follow up maksimal 1x24 jam.',
            ],
        ]
        : [
            [
                'title' => 'Scale Meta Ads untuk campaign dengan CPL terbaik',
                'owner' => 'Marketing Team',
                'deadline' => '30 Apr 2026',
                'status' => 'On Progress',
                'description' => 'Budget dinaikkan bertahap selama CPL tetap berada di bawah target.',
            ],
            [
                'title' => 'Menjalankan webinar mingguan untuk meningkatkan conversion',
                'owner' => 'Marketing Team',
                'deadline' => 'Ongoing',
                'status' => 'Planned',
                'description' => 'Webinar difokuskan sebagai channel edukasi sekaligus booster conversion.',
            ],
            [
                'title' => 'Evaluasi performa TikTok Ads dan materi creative',
                'owner' => 'Content Team',
                'deadline' => '28 Apr 2026',
                'status' => 'Review',
                'description' => 'Mengecek efektivitas targeting, creative, dan CTA untuk channel TikTok.',
            ],
        ];

    $upcomingEvents = $mode === 'weekly'
        ? [
            [
                'name' => 'Free Trial Class - AI Software Engineering',
                'date' => '24 Apr 2026',
                'type' => 'Trial Class',
                'target' => 30,
                'status' => 'Open Registration',
            ],
            [
                'name' => 'Webinar - Build Simple Web App with AI',
                'date' => '26 Apr 2026',
                'type' => 'Webinar',
                'target' => 50,
                'status' => 'Scheduled',
            ],
        ]
        : [
            [
                'name' => 'Webinar - Career in Tech for Beginners',
                'date' => '22 Apr 2026',
                'type' => 'Webinar',
                'target' => 50,
                'status' => 'Scheduled',
            ],
            [
                'name' => 'Free Trial Class - AI Software Engineering',
                'date' => '25 Apr 2026',
                'type' => 'Trial Class',
                'target' => 30,
                'status' => 'Open Registration',
            ],
            [
                'name' => 'Mini Info Session - FlexLabs Program Overview',
                'date' => '29 Apr 2026',
                'type' => 'Info Session',
                'target' => 40,
                'status' => 'Planned',
            ],
        ];

    $keyInsights = $mode === 'weekly'
        ? [
            'Minggu ini webinar menghasilkan conversion rate tertinggi dibanding channel lain.',
            'Meta Ads masih menjadi kontributor terbesar terhadap volume leads mingguan.',
            'Leads dari TikTok Ads masih membutuhkan evaluasi lanjutan sebelum budget ditingkatkan.',
        ]
        : [
            'Meta Ads masih menjadi sumber leads terbesar dan paling stabil untuk mendorong volume funnel.',
            'Webinar menghasilkan conversion rate paling kuat, sehingga layak dijadikan channel penguat closing.',
            'TikTok Ads masih berguna untuk awareness, tetapi perlu evaluasi creative atau audience karena conversion masih rendah.',
        ];

    $nextActions = $mode === 'weekly'
        ? [
            'Pastikan semua leads minggu ini difollow up maksimal 1x24 jam.',
            'Gunakan webinar minggu ini sebagai momentum closing untuk leads yang sudah engaged.',
            'Review early result TikTok Ads sebelum masuk budget minggu berikutnya.',
        ]
        : [
            'Scale budget Meta Ads secara bertahap selama CPL masih berada di bawah target.',
            'Tingkatkan frekuensi webinar atau event edukatif untuk mendorong conversion.',
            'Review targeting dan materi creative TikTok Ads sebelum budget ditingkatkan.',
        ];
@endphp

<div class="container-fluid px-4 py-4">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Management View</div>
                <h1 class="page-title mb-2">Marketing Performance Summary</h1>
                <p class="page-subtitle mb-0">
                    Ringkasan hasil marketing, rencana kerja yang sedang berjalan, serta event yang akan datang
                    untuk membantu management membaca kondisi dan menentukan prioritas tindak lanjut.
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
        <h4 class="dashboard-section-title mb-1">Ringkasan hasil utama</h4>
        <p class="dashboard-section-subtitle mb-0">
            Bagian ini menunjukkan hasil utama yang paling cepat dibaca management: volume leads, conversion,
            revenue, dan biaya yang dikeluarkan pada periode terpilih.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Leads</div>
                        <div class="stat-value">{{ number_format($summary['leads']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    {{ number_format($summary['qualified_leads']) }} qualified leads
                    · qualification rate {{ number_format($summary['qualification_rate'], 2) }}%
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Conversion</div>
                        <div class="stat-value">{{ number_format($summary['conversions']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Conversion rate {{ number_format($summary['conversion_rate'], 2) }}%
                    dari total leads yang masuk
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">Rp{{ number_format($summary['revenue'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Nilai pendapatan yang dihasilkan dari conversion pada periode ini
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
                        <div class="stat-title">Actual Spend</div>
                        <div class="stat-value">Rp{{ number_format($summary['actual_spend'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Budget Rp{{ number_format($summary['budget'], 0, ',', '.') }}
                    · terserap {{ $summary['budget'] > 0 ? number_format(($summary['actual_spend'] / $summary['budget']) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>
    </div>

    {{-- Cost & Efficiency --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Efficiency Review</div>
        <h4 class="dashboard-section-title mb-1">Biaya dan efisiensi akuisisi</h4>
        <p class="dashboard-section-subtitle mb-0">
            Fokus bagian ini adalah menjawab apakah biaya marketing yang keluar sudah proporsional
            terhadap hasil yang didapat.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Cost & Efficiency Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Indikator efisiensi utama untuk membaca performa biaya marketing terhadap hasil.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-cash-stack me-1"></i> Cost per Lead
                                </div>
                                <div class="mini-stat-value">Rp{{ number_format($summary['cpl'], 0, ',', '.') }}</div>
                                <div class="mini-stat-help">
                                    Biaya rata-rata untuk menghasilkan 1 lead.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-bullseye me-1"></i> Cost per Acquisition
                                </div>
                                <div class="mini-stat-value">Rp{{ number_format($summary['cac'], 0, ',', '.') }}</div>
                                <div class="mini-stat-help">
                                    Biaya rata-rata untuk menghasilkan 1 customer.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-graph-up-arrow me-1"></i> Conversion Rate
                                </div>
                                <div class="mini-stat-value">{{ number_format($summary['conversion_rate'], 2) }}%</div>
                                <div class="mini-stat-help">
                                    Persentase total leads yang berhasil menjadi conversion.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mini-stat-card">
                                <div class="mini-stat-label">
                                    <i class="bi bi-person-check-fill me-1"></i> Qualified Lead Rate
                                </div>
                                <div class="mini-stat-value">{{ number_format($summary['qualification_rate'], 2) }}%</div>
                                <div class="mini-stat-help">
                                    Persentase lead yang dinilai layak untuk difollow up lebih lanjut.
                                </div>
                            </div>
                        </div>
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
                            Cara cepat membaca efisiensi marketing pada periode ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="insight-box h-100">
                        <p class="mb-3">
                            Secara umum, management perlu memastikan bahwa biaya yang dikeluarkan
                            untuk marketing masih sepadan dengan jumlah leads, conversion, dan revenue yang dihasilkan.
                        </p>

                        <ul class="insight-list mb-0">
                            <li><strong>CPL</strong> membantu membaca efisiensi akuisisi awal.</li>
                            <li><strong>CAC</strong> membantu melihat biaya riil untuk menghasilkan customer.</li>
                            <li><strong>Conversion rate</strong> membantu menilai kualitas funnel dan follow up.</li>
                            <li><strong>Qualified lead rate</strong> membantu mengecek kualitas source dan targeting.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Plan & Event Visibility --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Execution Visibility</div>
        <h4 class="dashboard-section-title mb-1">Plan dan event yang sedang berjalan</h4>
        <p class="dashboard-section-subtitle mb-0">
            Selain hasil, management juga perlu mengetahui aktivitas yang sedang dikerjakan serta event
            yang akan dijalankan untuk mendukung pipeline marketing.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Current Plan & Action</h5>
                        <p class="content-card-subtitle mb-0">
                            Rencana kerja utama yang sedang berjalan atau akan dijalankan pada periode terpilih.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Owner</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($currentPlans as $plan)
                                    @php
                                        $badgeClass = match ($plan['status']) {
                                            'On Progress' => 'bg-primary-subtle text-primary-emphasis',
                                            'Planned' => 'bg-warning-subtle text-warning-emphasis',
                                            'Review' => 'bg-secondary-subtle text-secondary-emphasis',
                                            'Done' => 'bg-success-subtle text-success-emphasis',
                                            default => 'bg-light text-dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $plan['title'] }}</div>
                                            <div class="text-muted small">{{ $plan['description'] }}</div>
                                        </td>
                                        <td>{{ $plan['owner'] }}</td>
                                        <td>{{ $plan['deadline'] }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $badgeClass }}">
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
                        <h5 class="content-card-title mb-1">Upcoming Events</h5>
                        <p class="content-card-subtitle mb-0">
                            Event yang sedang dijadwalkan untuk mendukung awareness, engagement, dan conversion.
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
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($upcomingEvents as $event)
                                    @php
                                        $eventBadge = match ($event['status']) {
                                            'Scheduled' => 'bg-primary-subtle text-primary-emphasis',
                                            'Open Registration' => 'bg-success-subtle text-success-emphasis',
                                            'Planned' => 'bg-warning-subtle text-warning-emphasis',
                                            default => 'bg-light text-dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $event['name'] }}</div>
                                            <div class="text-muted small">
                                                {{ $event['type'] }} · Target {{ $event['target'] }} peserta
                                            </div>
                                        </td>
                                        <td>{{ $event['date'] }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $eventBadge }}">
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
    </div>

    {{-- Channel Performance --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Channel Contribution</div>
        <h4 class="dashboard-section-title mb-1">Channel performance summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Bagian ini membantu management membaca channel mana yang paling berkontribusi terhadap leads,
            conversion, dan revenue.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Per Channel Performance</h5>
                <p class="content-card-subtitle mb-0">
                    Bandingkan performa tiap channel untuk melihat prioritas scale, evaluasi, atau efisiensi biaya.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th class="text-end">Leads</th>
                            <th class="text-end">Qualified</th>
                            <th class="text-end">Conversions</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Spend</th>
                            <th>Reading Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($channelPerformance as $channel)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $channel->channel }}</div>
                                </td>
                                <td class="text-end">{{ number_format($channel->leads) }}</td>
                                <td class="text-end">{{ number_format($channel->qualified_leads) }}</td>
                                <td class="text-end">{{ number_format($channel->conversions) }}</td>
                                <td class="text-end">Rp{{ number_format($channel->revenue, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    {{ $channel->spend > 0 ? 'Rp' . number_format($channel->spend, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-muted">{{ $channel->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Trend + Recommendation --}}
    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Decision Support</div>
        <h4 class="dashboard-section-title mb-1">Trend and recommendation</h4>
        <p class="dashboard-section-subtitle mb-0">
            Trend membantu membaca momentum, sedangkan insight membantu management menentukan langkah berikutnya.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">
                            {{ $mode === 'weekly' ? 'Daily Trend Overview' : 'Weekly Trend Overview' }}
                        </h5>
                        <p class="content-card-subtitle mb-0">
                            Pergerakan leads dan conversions untuk membaca momentum aktivitas marketing selama periode berjalan.
                        </p>
                    </div>

                    <div class="revenue-total-box small-box">
                        <div class="revenue-total-label">{{ $mode === 'weekly' ? 'Current Week' : 'Current Month' }}</div>
                        <div class="revenue-total-value">{{ $periodLabel }}</div>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="chart-wrap" style="height: 340px;">
                        <canvas id="managementMarketingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Key Insight & Recommendation</h5>
                        <p class="content-card-subtitle mb-0">
                            Poin penting yang sebaiknya menjadi perhatian management pada periode ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="insight-section mb-4">
                        <div class="insight-heading">Key Insight</div>
                        <ul class="insight-list mb-0">
                            @foreach ($keyInsights as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="insight-section">
                        <div class="insight-heading">Recommended Action</div>
                        <ul class="insight-list mb-0">
                            @foreach ($nextActions as $item)
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
    .mini-stat-card {
        background: #f8f5ff;
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 16px;
        padding: 14px 16px;
        height: 100%;
    }

    .mini-stat-label {
        font-size: 0.78rem;
        color: #6b7280;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }

    .mini-stat-value {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.35;
        word-break: break-word;
    }

    .mini-stat-help {
        margin-top: 8px;
        font-size: 0.78rem;
        color: #6b7280;
        line-height: 1.5;
    }

    .table-modern thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #6b7280;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
        padding-top: 14px;
        padding-bottom: 14px;
        white-space: nowrap;
    }

    .table-modern tbody td {
        padding-top: 14px;
        padding-bottom: 14px;
        border-color: #eef2f7;
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: rgba(91, 62, 142, 0.025);
    }

    .insight-box,
    .insight-section {
        background: #f8f5ff;
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 16px;
        padding: 16px;
    }

    .insight-heading {
        font-size: 0.86rem;
        font-weight: 700;
        color: #5B3E8E;
        margin-bottom: 10px;
    }

    .insight-list {
        padding-left: 18px;
        color: #4b5563;
    }

    .insight-list li + li {
        margin-top: 8px;
    }

    .small-box {
        min-width: 180px;
    }

    .dashboard-mode-switch .btn {
        min-width: 92px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .mini-stat-value {
            font-size: 0.95rem;
        }

        .dashboard-mode-switch {
            width: 100%;
        }

        .dashboard-mode-switch .btn {
            width: 50%;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('managementMarketingChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($trendData['labels']),
            datasets: [
                {
                    label: 'Leads',
                    data: @json($trendData['leads']),
                    borderColor: '#5B3E8E',
                    backgroundColor: 'rgba(91, 62, 142, 0.08)',
                    tension: 0.35,
                    fill: false,
                    borderWidth: 2
                },
                {
                    label: 'Conversions',
                    data: @json($trendData['conversions']),
                    borderColor: '#3B8E4D',
                    backgroundColor: 'rgba(59, 142, 77, 0.08)',
                    tension: 0.35,
                    fill: false,
                    borderWidth: 2
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                        color: '#6b7280'
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#6b7280' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(107, 114, 128, 0.08)'
                    }
                }
            }
        }
    });
});
</script>
@endpush