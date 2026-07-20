<div class="container-fluid px-4 py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-start gap-4 flex-wrap">
                <div class="d-flex align-items-start gap-3">
                    <div class="department-dashboard-icon">
                        <i class="{{ $departmentIcon }}"></i>
                    </div>

                    <div>
                        <div class="text-uppercase fw-bold text-primary small mb-2" style="letter-spacing:.12em;">
                            {{ $eyebrow }}
                        </div>

                        <h1 class="h2 fw-bold text-dark mb-2">
                            {{ $pageTitle }}
                        </h1>

                        <p class="text-muted mb-0" style="max-width:760px;">
                            {{ $description }}
                        </p>
                    </div>
                </div>

                <div class="text-lg-end">
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">
                        <i class="bi bi-cone-striped me-1"></i>
                        {{ $statusLabel }}
                    </span>

                    <div class="small text-muted mt-2">
                        Updated {{ $generatedAt }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($stats as $stat)
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="text-muted small fw-semibold mb-2">
                                    {{ $stat['label'] }}
                                </div>

                                <div class="display-6 fw-bold text-dark mb-2">
                                    {{ $stat['value'] }}
                                </div>
                            </div>

                            <div class="dashboard-stat-icon">
                                <i class="{{ $stat['icon'] }}"></i>
                            </div>
                        </div>

                        <p class="small text-muted mb-0">
                            {{ $stat['description'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-1">Dashboard Build Plan</h2>
                            <p class="small text-muted mb-0">
                                Fokus data yang akan disambungkan ke dashboard ini.
                            </p>
                        </div>

                        <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis px-3 py-2">
                            Dummy Data
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($focusItems as $item)
                            <div class="col-lg-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="dashboard-focus-icon mb-3">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>

                                    <h3 class="h6 fw-bold text-dark">
                                        {{ $item['title'] }}
                                    </h3>

                                    <p class="small text-muted mb-0">
                                        {{ $item['description'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                    <h2 class="h5 fw-bold text-dark mb-1">Implementation Status</h2>
                    <p class="small text-muted mb-0">
                        Status awal scaffolding dashboard.
                    </p>
                </div>

                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        @foreach($activityItems as $activity)
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success"
                                          style="width:32px;height:32px;">
                                        <i class="bi bi-check2"></i>
                                    </span>
                                </div>

                                <div class="small text-muted pt-1">
                                    {{ $activity }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .department-dashboard-icon,
            .dashboard-stat-icon,
            .dashboard-focus-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #5B3E8E;
                background: rgba(91, 62, 142, .10);
                border: 1px solid rgba(91, 62, 142, .12);
            }

            .department-dashboard-icon {
                width: 58px;
                height: 58px;
                border-radius: 18px;
                font-size: 1.4rem;
            }

            .dashboard-stat-icon {
                width: 46px;
                height: 46px;
                border-radius: 15px;
                font-size: 1.1rem;
            }

            .dashboard-focus-icon {
                width: 40px;
                height: 40px;
                border-radius: 13px;
            }
        </style>
    @endpush
@endonce
