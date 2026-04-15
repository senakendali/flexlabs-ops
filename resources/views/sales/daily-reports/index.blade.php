@extends('layouts.app-dashboard')

@section('title', 'Sales Daily Reports')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Sales Daily Reports</h4>
            <small class="text-muted">
                Monitor laporan harian sales untuk melihat volume leads, progress interaksi, dan peluang konsultasi secara lebih terstruktur.
            </small>
        </div>

        <a href="{{ route('sales-daily-reports.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Report
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('sales-daily-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="date_from" class="form-label mb-1">Date From</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label mb-1">Date To</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="per_page" class="form-label mb-1">Show Entries</label>
                    <select
                        name="per_page"
                        id="per_page"
                        class="form-select"
                    >
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Date</th>
                        <th>Total Leads</th>
                        <th>Interacted</th>
                        <th>Ignored</th>
                        <th>Hot Leads</th>
                        <th>Consultation</th>
                        <th>Created By</th>
                        <th style="width: 220px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>
                                {{ ($reports->currentPage() - 1) * $reports->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    {{ optional($report->report_date)->format('d M Y') }}
                                </div>
                                <div class="small text-muted">
                                    {{ optional($report->created_at)->format('H:i') ?: '-' }}
                                </div>
                            </td>
                            <td class="fw-semibold">{{ $report->total_leads }}</td>
                            <td>
                                <span class="badge bg-info-subtle text-info-emphasis">
                                    {{ $report->interacted }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    {{ $report->ignored }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning-subtle text-warning-emphasis">
                                    {{ $report->hot_leads }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">
                                    {{ $report->consultation }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $report->creator?->name ?? '-' }}</div>
                                <div class="small text-muted">Report owner</div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-center">
                                    <a
                                        href="{{ route('sales-daily-reports.show', $report) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="View Report"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('sales-daily-reports.edit', $report) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit Report"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('sales-daily-reports.destroy', $report) }}" method="POST" onsubmit="return confirm('Delete this report?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete Report"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No sales daily reports found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="card-footer bg-white">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection