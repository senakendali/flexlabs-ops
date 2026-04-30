@extends('layouts.app-dashboard')

@section('title', 'Certificates')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Certification</div>
                <h1 class="page-title mb-2">Certificates</h1>
                <p class="page-subtitle mb-0">
                    Manage sertifikat student yang sudah diterbitkan dari report card yang eligible.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.report-cards.index') }}" class="btn btn-outline-primary btn-modern">
                    <i class="bi bi-file-earmark-text me-2"></i>Report Cards
                </a>

                <a href="{{ route('academic.assessment-scores.index') }}" class="btn btn-outline-secondary btn-modern">
                    <i class="bi bi-pencil-square me-2"></i>Assessment Scores
                </a>
            </div>
        </div>
    </div>

    @php
        $items = collect($certificates->items());

        $totalCertificates = $certificates->total();
        $issuedCount = $items->where('status', 'issued')->count();
        $revokedCount = $items->where('status', 'revoked')->count();
        $completionCount = $items->where('type', 'completion')->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="stat-title">Certificates</div>
                        <div class="stat-value">{{ $totalCertificates }}</div>
                    </div>
                </div>
                <div class="stat-description">Total certificate sesuai filter.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Issued</div>
                        <div class="stat-value">{{ $issuedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Certificate yang valid dan aktif.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Revoked</div>
                        <div class="stat-value">{{ $revokedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Certificate yang sudah dibatalkan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completion</div>
                        <div class="stat-value">{{ $completionCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Certificate of Completion.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Certificates</h5>
                <p class="content-card-subtitle mb-0">
                    Filter berdasarkan status dan tipe certificate.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.certificates.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="completion" {{ request('type') === 'completion' ? 'selected' : '' }}>Completion</option>
                            <option value="achievement" {{ request('type') === 'achievement' ? 'selected' : '' }}>Achievement</option>
                            <option value="excellence" {{ request('type') === 'excellence' ? 'selected' : '' }}>Excellence</option>
                            <option value="participation" {{ request('type') === 'participation' ? 'selected' : '' }}>Participation</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-12">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('academic.certificates.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Certificate List</h5>
                <p class="content-card-subtitle mb-0">
                    Sertifikat yang sudah diterbitkan dari report card.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($certificates->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle admin-table">
                        <thead>
                            <tr>
                                <th>Certificate No</th>
                                <th>Student</th>
                                <th>Program / Batch</th>
                                <th>Type</th>
                                <th class="text-end">Score</th>
                                <th>Status</th>
                                <th>Issued Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($certificates as $certificate)
                                @php
                                    $student = $certificate->student;

                                    $studentName = $student->name
                                        ?? $student->full_name
                                        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

                                    if(!$studentName) {
                                        $studentName = $student->email ?? 'Student #' . $certificate->student_id;
                                    }

                                    $statusClass = match($certificate->status) {
                                        'issued' => 'status-published',
                                        'revoked' => 'status-closed',
                                        'expired' => 'status-closed',
                                        default => 'status-draft',
                                    };

                                    $typeClass = match($certificate->type) {
                                        'excellence' => 'cert-type-excellence',
                                        'achievement' => 'cert-type-achievement',
                                        'participation' => 'cert-type-participation',
                                        default => 'cert-type-completion',
                                    };

                                    $statusLabel = ucwords(str_replace('_', ' ', $certificate->status));
                                    $typeLabel = ucwords(str_replace('_', ' ', $certificate->type));
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $certificate->certificate_no }}</div>
                                        <div class="text-muted small">
                                            {{ $certificate->title ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="table-avatar">
                                                {{ strtoupper(substr($studentName, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $studentName }}</div>
                                                <div class="text-muted small">{{ $student->email ?? 'No email' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $certificate->program->name ?? '-' }}</div>
                                        <div class="text-muted small">
                                            {{ $certificate->batch->name ?? ('Batch #' . $certificate->batch_id) }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="cert-type-badge {{ $typeClass }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="fw-bold">
                                            {{ $certificate->final_score !== null ? number_format((float) $certificate->final_score, 2) : '-' }}
                                        </div>
                                        <div class="text-muted small">Grade {{ $certificate->grade ?? '-' }}</div>
                                    </td>

                                    <td>
                                        <span class="assignment-status-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ optional($certificate->issued_date)->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ optional($certificate->issued_at)->format('H:i') ?? '' }}
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                            <a href="{{ route('academic.certificates.show', $certificate) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>

                                            @if($certificate->status === 'issued')
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#revokeCertificateModal"
                                                    data-certificate-id="{{ $certificate->id }}"
                                                    data-certificate-no="{{ $certificate->certificate_no }}"
                                                    data-action="{{ route('academic.certificates.revoke', $certificate) }}"
                                                >
                                                    <i class="bi bi-x-circle me-1"></i>Revoke
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $certificates->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-award"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada certificate</h5>
                    <p class="empty-state-text mb-0">
                        Certificate akan muncul setelah report card yang eligible di-issue.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('academic.report-cards.index') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-file-earmark-text me-2"></i>Go to Report Cards
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="revokeCertificateModal" tabindex="-1" aria-labelledby="revokeCertificateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form method="POST" id="revokeCertificateForm">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="revokeCertificateModalLabel">Revoke Certificate</h5>
                        <p class="text-muted mb-0">
                            Certificate akan ditandai tidak valid.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-warning">
                        Kamu akan revoke certificate:
                        <strong id="revokeCertificateNo">-</strong>
                    </div>

                    <label class="form-label">Revocation Reason</label>
                    <textarea
                        name="reason"
                        class="form-control"
                        rows="4"
                        required
                        placeholder="Contoh: Data student perlu koreksi / certificate issued by mistake..."
                    ></textarea>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-modern">
                        <i class="bi bi-x-circle me-2"></i>Revoke Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const revokeModal = document.getElementById('revokeCertificateModal');
    const revokeForm = document.getElementById('revokeCertificateForm');
    const revokeCertificateNo = document.getElementById('revokeCertificateNo');

    if (revokeModal && revokeForm) {
        revokeModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            revokeForm.action = button.dataset.action || '#';
            revokeCertificateNo.textContent = button.dataset.certificateNo || '-';
            revokeForm.querySelector('textarea[name="reason"]').value = '';
        });
    }
});
</script>
@endpush