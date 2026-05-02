@extends('layouts.app-dashboard')

@section('title', 'Certificate Detail')

@section('content')
@php
    $student = $certificate->student;

    $studentName = $student->name
        ?? $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

    if (! $studentName) {
        $studentName = $student->email ?? 'Student #' . $certificate->student_id;
    }

    $statusClass = match ($certificate->status) {
        'issued' => 'status-published',
        'revoked' => 'status-closed',
        'expired' => 'status-closed',
        default => 'status-draft',
    };

    $typeClass = match ($certificate->type) {
        'excellence' => 'cert-type-excellence',
        'achievement' => 'cert-type-achievement',
        'participation' => 'cert-type-participation',
        default => 'cert-type-completion',
    };

    $statusLabel = ucwords(str_replace('_', ' ', $certificate->status));
    $typeLabel = ucwords(str_replace('_', ' ', $certificate->type));

    $qrUrl = $certificate->qr_code_path
        ? asset('storage/' . $certificate->qr_code_path) . '?v=' . optional($certificate->updated_at)->timestamp
        : null;

    $pdfFileUrl = $certificate->pdf_path
        ? asset('storage/' . $certificate->pdf_path) . '?v=' . optional($certificate->updated_at)->timestamp
        : null;

    $hasGeneratePdfRoute = \Illuminate\Support\Facades\Route::has('academic.certificates.generate-pdf');
    $hasStreamPdfRoute = \Illuminate\Support\Facades\Route::has('academic.certificates.stream-pdf');

    $generatePdfUrl = $hasGeneratePdfRoute
        ? route('academic.certificates.generate-pdf', $certificate)
        : route('academic.certificates.regenerate-qr', $certificate);

    $pdfPreviewUrl = $hasStreamPdfRoute
        ? route('academic.certificates.stream-pdf', $certificate)
        : $pdfFileUrl;
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Certification</div>
                <h1 class="page-title mb-2">Certificate Detail</h1>
                <p class="page-subtitle mb-0">
                    {{ $certificate->certificate_no }} — {{ $studentName }}
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap justify-content-end">
                <a href="{{ route('academic.certificates.index') }}" class="btn btn-light btn-modern shadow-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if($certificate->reportCard)
                    <a href="{{ route('academic.report-cards.show', $certificate->reportCard) }}" class="btn btn-light btn-modern shadow-sm">
                        <i class="bi bi-file-earmark-text me-2"></i>Report Card
                    </a>
                @endif

                <a href="{{ route('academic.certificates.download-pdf', $certificate) }}" class="btn btn-light btn-modern shadow-sm">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                </a>

                <form
                    method="POST"
                    action="{{ route('academic.certificates.regenerate-qr', $certificate) }}"
                    class="d-inline"
                >
                    @csrf

                    <button type="submit" class="btn btn-light btn-modern shadow-sm">
                        <i class="bi bi-arrow-repeat me-2"></i>Regenerate QR & PDF
                    </button>
                </form>

                @if($certificate->status === 'issued')
                    <button
                        type="button"
                        class="btn btn-light btn-modern shadow-sm text-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#revokeCertificateModal"
                    >
                        <i class="bi bi-x-circle me-2"></i>Revoke
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="certificate-hero-card mb-4">
        <div class="certificate-hero-pattern"></div>

        <div class="certificate-hero-content">
            <div class="certificate-hero-left">
                <div class="certificate-hero-icon">
                    <i class="bi bi-award-fill"></i>
                </div>

                <div>
                    <div class="certificate-hero-eyebrow">{{ $certificate->title }}</div>
                    <h2 class="certificate-hero-title">{{ $studentName }}</h2>
                    <p class="certificate-hero-text mb-0">
                        Has successfully completed
                        <strong>{{ $certificate->program->name ?? 'the program' }}</strong>
                        with final score
                        <strong>{{ $certificate->final_score !== null ? number_format((float) $certificate->final_score, 2) : '-' }}</strong>.
                    </p>
                </div>
            </div>

            <div class="certificate-hero-right">
                <span class="cert-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                <span class="assignment-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star"></i>
                    </div>
                    <div>
                        <div class="stat-title">Final Score</div>
                        <div class="stat-value">
                            {{ $certificate->final_score !== null ? number_format((float) $certificate->final_score, 2) : '-' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Nilai akhir dari report card.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div>
                        <div class="stat-title">Grade</div>
                        <div class="stat-value">{{ $certificate->grade ?? '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Grade student saat certificate diterbitkan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Issued Date</div>
                        <div class="stat-value stat-value-sm">
                            {{ optional($certificate->issued_date)->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Tanggal certificate diterbitkan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completed Date</div>
                        <div class="stat-value stat-value-sm">
                            {{ optional($certificate->completed_date)->format('d M Y') ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Tanggal program dianggap selesai.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Certificate Information</h5>
                        <p class="content-card-subtitle mb-0">Detail nomor, tipe, status, dan issuer.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-list">
                        <div class="info-list-item">
                            <span>Certificate No</span>
                            <strong>{{ $certificate->certificate_no }}</strong>
                        </div>

                        <div class="info-list-item">
                            <span>Title</span>
                            <strong>{{ $certificate->title }}</strong>
                        </div>

                        <div class="info-list-item">
                            <span>Type</span>
                            <span class="cert-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                        </div>

                        <div class="info-list-item">
                            <span>Status</span>
                            <span class="assignment-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <div class="info-list-item">
                            <span>PDF File</span>
                            @if($certificate->pdf_path)
                                <span class="assignment-status-badge status-published">Generated</span>
                            @else
                                <span class="assignment-status-badge status-draft">Not Generated</span>
                            @endif
                        </div>

                        <div class="info-list-item">
                            <span>Issued By</span>
                            <strong>{{ $certificate->issuer->name ?? '-' }}</strong>
                        </div>

                        <div class="info-list-item">
                            <span>Issued At</span>
                            <strong>{{ optional($certificate->issued_at)->format('d M Y H:i') ?? '-' }}</strong>
                        </div>
                    </div>

                    @if($certificate->status === 'revoked')
                        <div class="alert alert-danger mt-3 mb-0">
                            <strong>Revoked:</strong>
                            {{ $certificate->revocation_reason ?: 'No revocation reason provided.' }}
                            <br>
                            <small>{{ optional($certificate->revoked_at)->format('d M Y H:i') }}</small>
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Student & Program</h5>
                        <p class="content-card-subtitle mb-0">Informasi student dan program terkait.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="student-report-profile mb-4">
                        <div class="student-report-avatar">
                            {{ strtoupper(substr($studentName, 0, 1)) }}
                        </div>

                        <div>
                            <h5 class="mb-1">{{ $studentName }}</h5>
                            <div class="text-muted">{{ $student->email ?? 'No email' }}</div>
                        </div>
                    </div>

                    <div class="info-list">
                        <div class="info-list-item">
                            <span>Program</span>
                            <strong>{{ $certificate->program->name ?? '-' }}</strong>
                        </div>

                        <div class="info-list-item">
                            <span>Batch</span>
                            <strong>{{ $certificate->batch->name ?? ('Batch #' . $certificate->batch_id) }}</strong>
                        </div>

                        <div class="info-list-item">
                            <span>Report Card</span>
                            @if($certificate->reportCard)
                                <a href="{{ route('academic.report-cards.show', $certificate->reportCard) }}" class="fw-bold">
                                    {{ $certificate->reportCard->report_no }}
                                </a>
                            @else
                                <strong>-</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Verification</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi untuk QR code dan public verification page.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="verification-box">
                        <div class="verification-qr-placeholder verification-qr-top">
                            @if($qrUrl)
                                <img
                                    src="{{ $qrUrl }}"
                                    alt="Certificate QR Code"
                                    class="certificate-qr-image"
                                >
                                <span class="certificate-qr-caption">Scan to verify</span>
                            @else
                                <i class="bi bi-qr-code"></i>
                                <span>QR not generated</span>
                            @endif
                        </div>

                        <div class="verification-content">
                            <div class="verification-label">Verification URL</div>

                            @if($certificate->verification_url)
                                <div class="verification-url-box">
                                    <code id="verificationUrlText">{{ $certificate->verification_url }}</code>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        id="copyVerificationUrlBtn"
                                    >
                                        <i class="bi bi-copy me-1"></i>Copy
                                    </button>
                                </div>

                                <div class="mt-3 d-flex gap-2 flex-wrap">
                                    <a
                                        href="{{ $certificate->verification_url }}"
                                        target="_blank"
                                        class="btn btn-primary btn-modern"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-2"></i>Open Verification Page
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('academic.certificates.regenerate-qr', $certificate) }}"
                                        class="d-inline"
                                    >
                                        @csrf

                                        <button type="submit" class="btn btn-outline-primary btn-modern">
                                            <i class="bi bi-arrow-repeat me-2"></i>Regenerate QR & PDF
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    Verification URL belum tersedia. Generate QR terlebih dahulu agar public verification bisa digunakan.
                                </div>
                            @endif

                            <div class="verification-token mt-3">
                                <div class="verification-label">Public Token</div>
                                <code>{{ $certificate->public_token ?: '-' }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Certificate PDF</h5>
                        <p class="content-card-subtitle mb-0">
                            Output resmi certificate dalam format PDF.
                        </p>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <form method="POST" action="{{ $generatePdfUrl }}" class="d-inline">
                            @csrf

                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-file-earmark-pdf me-1"></i>
                                {{ $certificate->pdf_path ? 'Regenerate PDF' : 'Generate PDF' }}
                            </button>
                        </form>

                        <a
                            href="{{ route('academic.certificates.download-pdf', $certificate) }}"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="bi bi-download me-1"></i>
                            Download PDF
                        </a>
                    </div>
                </div>

                <div class="content-card-body">
                    @if($pdfPreviewUrl)
                        <div class="ratio ratio-16x9 rounded-3 overflow-hidden border bg-light">
                            <iframe
                                src="{{ $pdfPreviewUrl }}"
                                title="Certificate PDF Preview"
                                class="border-0"
                            ></iframe>
                        </div>
                    @else
                        <div class="empty-state py-5 text-center">
                            <div class="empty-state-icon mb-3">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>
                            <h5 class="mb-2">PDF belum digenerate</h5>
                            <p class="text-muted mb-3">
                                Generate certificate PDF supaya file resmi bisa dipreview dan didownload.
                            </p>

                            <form method="POST" action="{{ $generatePdfUrl }}" class="d-inline">
                                @csrf

                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="d-flex gap-2 align-items-start">
                            <i class="bi bi-info-circle text-primary mt-1"></i>
                            <div>
                                <strong>Catatan:</strong>
                                tombol Download PDF akan otomatis membuat ulang file PDF kalau file belum tersedia atau perlu diperbarui dari data certificate terbaru.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Certificate Actions</h5>
                        <p class="content-card-subtitle mb-0">
                            Aksi lanjutan untuk certificate ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="certificate-action-grid">
                        <div class="certificate-action-card">
                            <div class="certificate-action-icon">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>

                            <div>
                                <div class="certificate-action-title">Certificate PDF</div>
                                <div class="certificate-action-text">
                                    Generate atau download certificate resmi dalam format PDF.
                                </div>

                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <form method="POST" action="{{ $generatePdfUrl }}" class="d-inline">
                                        @csrf

                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>
                                            {{ $certificate->pdf_path ? 'Regenerate PDF' : 'Generate PDF' }}
                                        </button>
                                    </form>

                                    <a
                                        href="{{ route('academic.certificates.download-pdf', $certificate) }}"
                                        class="btn btn-primary btn-sm"
                                    >
                                        <i class="bi bi-download me-1"></i>
                                        Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="certificate-action-card">
                            <div class="certificate-action-icon">
                                <i class="bi bi-qr-code"></i>
                            </div>

                            <div>
                                <div class="certificate-action-title">QR Code & Verification</div>
                                <div class="certificate-action-text">
                                    QR code diarahkan ke public verification URL certificate. Regenerate QR juga akan memperbarui PDF.
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('academic.certificates.regenerate-qr', $certificate) }}"
                                    class="mt-2"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i>
                                        {{ $certificate->qr_code_path ? 'Regenerate QR & PDF' : 'Generate QR & PDF' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if($certificate->verification_url)
                            <div class="certificate-action-card">
                                <div class="certificate-action-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>

                                <div>
                                    <div class="certificate-action-title">Public Verification</div>
                                    <div class="certificate-action-text">
                                        Buka halaman validasi publik untuk memastikan certificate bisa diverifikasi dari QR.
                                    </div>

                                    <a
                                        href="{{ $certificate->verification_url }}"
                                        target="_blank"
                                        class="btn btn-outline-primary btn-sm mt-2"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                        Open Verification
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($certificate->status === 'issued')
                            <div class="certificate-action-card is-danger">
                                <div class="certificate-action-icon">
                                    <i class="bi bi-x-circle"></i>
                                </div>

                                <div>
                                    <div class="certificate-action-title">Revoke Certificate</div>
                                    <div class="certificate-action-text">
                                        Tandai certificate ini sebagai tidak valid.
                                    </div>

                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm mt-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#revokeCertificateModal"
                                    >
                                        Revoke Certificate
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($certificate->status === 'issued')
    <div class="modal fade" id="revokeCertificateModal" tabindex="-1" aria-labelledby="revokeCertificateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" action="{{ route('academic.certificates.revoke', $certificate) }}">
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
                            <strong>{{ $certificate->certificate_no }}</strong>
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
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyVerificationUrlBtn');
    const textEl = document.getElementById('verificationUrlText');

    function showCopyFallback(message) {
        if (window.bootstrap) {
            const toastContainer = document.querySelector('.toast-container');

            if (toastContainer) {
                const toastEl = document.createElement('div');
                toastEl.className = 'toast align-items-center text-bg-danger border-0';
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                toastContainer.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', function () {
                    toastEl.remove();
                });
                return;
            }
        }

        console.warn(message);
    }

    if (copyBtn && textEl) {
        copyBtn.addEventListener('click', async function () {
            const originalHtml = copyBtn.innerHTML;

            try {
                await navigator.clipboard.writeText(textEl.textContent.trim());

                copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';
                copyBtn.disabled = true;

                setTimeout(function () {
                    copyBtn.innerHTML = originalHtml;
                    copyBtn.disabled = false;
                }, 1600);
            } catch (error) {
                showCopyFallback('Gagal copy verification URL.');
            }
        });
    }
});
</script>
@endpush
