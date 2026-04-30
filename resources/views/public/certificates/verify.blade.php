@php
    $student = $certificate->student;

    $studentName = $student->name
        ?? $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

    if (! $studentName) {
        $studentName = $student->email ?? 'Student #' . $certificate->student_id;
    }

    $isValid = $certificate->status === 'issued';

    $statusLabel = match ($certificate->status) {
        'issued' => 'Valid Certificate',
        'revoked' => 'Revoked Certificate',
        'expired' => 'Expired Certificate',
        default => ucwords(str_replace('_', ' ', $certificate->status)),
    };

    $typeLabel = ucwords(str_replace('_', ' ', $certificate->type));

    $badgeClass = match ($certificate->status) {
        'issued' => 'text-bg-success',
        'revoked' => 'text-bg-danger',
        'expired' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };

    $noticeClass = $isValid ? 'alert-success' : 'alert-danger';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate Verification - {{ $certificate->certificate_no }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Noto Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    {{-- Bootstrap --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --flex-primary: #5B3E8E;
            --flex-primary-dark: #432b72;
            --flex-secondary: #FFBE04;
            --flex-soft: #F8F5FF;
            --flex-text: #262033;
            --flex-muted: #777283;
            --flex-border: #eee7ff;
        }

        body {
            font-family: "Noto Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--flex-text);
            background:
                radial-gradient(circle at top left, rgba(91, 62, 142, .18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(255, 190, 4, .20), transparent 34%),
                linear-gradient(135deg, #fbf9ff 0%, #f7f4fc 100%);
            min-height: 100vh;
        }

        .verify-wrapper {
            min-height: 100vh;
            padding: 32px 0;
        }

        .verify-container {
            max-width: 1060px;
        }

        .brand-logo {
            height: 44px;
            width: auto;
            object-fit: contain;
        }

        .brand-title {
            color: var(--flex-primary);
            font-weight: 900;
            letter-spacing: -.02em;
        }

        .verify-card {
            border: 1px solid rgba(91, 62, 142, .12);
            border-radius: 32px;
            overflow: hidden;
            box-shadow:
                0 28px 90px rgba(37, 26, 59, .14),
                0 0 0 1px rgba(255, 255, 255, .72) inset;
            background: #ffffff;
        }

        .verify-hero {
            position: relative;
            color: #ffffff;
            background:
                linear-gradient(135deg, #5B3E8E 0%, #6f50a6 58%, #FFBE04 150%);
            overflow: hidden;
        }

        .verify-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 12% 20%, rgba(255, 255, 255, .20), transparent 24%),
                radial-gradient(circle at 92% 80%, rgba(255, 190, 4, .34), transparent 30%);
            pointer-events: none;
        }

        .verify-hero::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            right: -170px;
            top: -150px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 50%;
            box-shadow:
                0 0 0 42px rgba(255,255,255,.035),
                0 0 0 92px rgba(255,255,255,.025);
            pointer-events: none;
        }

        .verify-hero-content {
            position: relative;
            z-index: 1;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .24);
            backdrop-filter: blur(12px);
            font-size: .86rem;
            font-weight: 900;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: {{ $isValid ? '#72f09c' : '#ffb4ad' }};
            box-shadow: 0 0 0 4px rgba(255,255,255,.16);
        }

        .hero-title {
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1.02;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, .82);
            line-height: 1.7;
            max-width: 720px;
        }

        .certificate-no-box {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 22px;
            backdrop-filter: blur(12px);
            min-width: 250px;
        }

        .section-card {
            border: 1px solid #eeeef4;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(37, 26, 59, .04);
        }

        .soft-card {
            background: var(--flex-soft);
            border: 1px solid var(--flex-border);
            border-radius: 24px;
        }

        .score-value {
            font-size: 2.75rem;
            line-height: 1;
            font-weight: 900;
            color: var(--flex-primary);
            letter-spacing: -.04em;
        }

        .grade-box {
            width: 68px;
            height: 68px;
            border-radius: 22px;
            background: var(--flex-primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            font-weight: 900;
            box-shadow: 0 14px 28px rgba(91, 62, 142, .25);
        }

        .info-row {
            border-bottom: 1px solid #f0edf5;
            padding: 13px 0;
        }

        .info-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            color: var(--flex-muted);
            font-size: .9rem;
        }

        .info-value {
            font-weight: 800;
            color: var(--flex-text);
        }

        .token-box {
            background: #fffaf0;
            border: 1px solid #f7e6c9;
            border-radius: 18px;
        }

        .token-text {
            word-break: break-all;
            font-size: .78rem;
            color: #9a6a00;
        }

        .mini-badge {
            background: #ffffff;
            border: 1px solid #eeeef4;
            border-radius: 18px;
        }

        .footer-text {
            color: var(--flex-muted);
            font-size: .86rem;
            line-height: 1.6;
        }

        @media (max-width: 767.98px) {
            .verify-wrapper {
                padding: 16px 0;
            }

            .verify-card {
                border-radius: 24px;
            }

            .certificate-no-box {
                min-width: 100%;
            }

            .hero-title {
                font-size: 2rem;
            }

            .score-value {
                font-size: 2.2rem;
            }

            .grade-box {
                width: 58px;
                height: 58px;
                border-radius: 18px;
                font-size: 1.55rem;
            }
        }
    </style>
</head>
<body>
    <main class="verify-wrapper d-flex align-items-center">
        <div class="container verify-container">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3 px-1">
                <div class="d-flex align-items-center gap-3">
                    <img
                        src="{{ asset('images/logo-black.png') }}"
                        alt="FlexLabs Logo"
                        class="brand-logo"
                    >

                    <div>
                        <div class="brand-title fs-5">FlexLabs</div>
                        <div class="small text-muted">Certificate Verification</div>
                    </div>
                </div>

                <div class="small text-muted">
                    Verified at {{ now()->format('d M Y, H:i') }}
                </div>
            </div>

            <section class="verify-card">
                <div class="verify-hero p-4 p-md-5">
                    <div class="verify-hero-content">
                        <div class="d-flex justify-content-between align-items-start gap-4 flex-wrap">
                            <div>
                                <div class="status-pill mb-4">
                                    <span class="status-dot"></span>
                                    {{ $statusLabel }}
                                </div>

                                <div class="text-uppercase fw-bold small opacity-75 mb-2">
                                    {{ $certificate->title }}
                                </div>

                                <h1 class="hero-title display-5 mb-3">
                                    {{ $studentName }}
                                </h1>

                                <p class="hero-subtitle mb-0">
                                    This certificate record confirms that the student is associated with
                                    <strong class="text-white">{{ $certificate->program->name ?? 'the program' }}</strong>
                                    under certificate type
                                    <strong class="text-white">{{ $typeLabel }}</strong>.
                                </p>
                            </div>

                            <div class="certificate-no-box p-3 text-md-end">
                                <div class="small opacity-75 mb-1">Certificate No</div>
                                <div class="fw-bold">{{ $certificate->certificate_no }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="section-card p-4 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1">Certificate Details</h2>
                                        <div class="text-muted small">
                                            Public verification record from FlexLabs.
                                        </div>
                                    </div>

                                    <span class="badge rounded-pill {{ $badgeClass }} px-3 py-2">
                                        {{ $typeLabel }}
                                    </span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Student Name</span>
                                    <span class="info-value text-end">{{ $studentName }}</span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Program</span>
                                    <span class="info-value text-end">{{ $certificate->program->name ?? '-' }}</span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Batch</span>
                                    <span class="info-value text-end">
                                        {{ $certificate->batch->name ?? ('Batch #' . $certificate->batch_id) }}
                                    </span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Certificate Type</span>
                                    <span class="info-value text-end">{{ $typeLabel }}</span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Issued Date</span>
                                    <span class="info-value text-end">
                                        {{ optional($certificate->issued_date)->format('d M Y') ?? '-' }}
                                    </span>
                                </div>

                                <div class="info-row d-flex justify-content-between gap-3 flex-wrap">
                                    <span class="info-label">Completed Date</span>
                                    <span class="info-value text-end">
                                        {{ optional($certificate->completed_date)->format('d M Y') ?? '-' }}
                                    </span>
                                </div>

                                <div class="alert {{ $noticeClass }} mt-4 mb-0 rounded-4">
                                    @if($certificate->status === 'revoked')
                                        <strong>This certificate has been revoked.</strong>
                                        <br>
                                        Reason: {{ $certificate->revocation_reason ?: 'No reason provided.' }}
                                    @elseif($certificate->status === 'expired')
                                        <strong>This certificate has expired.</strong>
                                        <br>
                                        Please contact FlexLabs for additional verification.
                                    @elseif($certificate->status === 'issued')
                                        <strong>This certificate is valid.</strong>
                                        <br>
                                        The certificate is currently issued and active in FlexLabs records.
                                    @else
                                        <strong>This certificate is not currently active.</strong>
                                        <br>
                                        Current status: {{ $statusLabel }}.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="soft-card p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="score-value">
                                            {{ $certificate->final_score !== null ? number_format((float) $certificate->final_score, 2) : '-' }}
                                        </div>
                                        <div class="small text-muted mt-1">Final Score</div>
                                    </div>

                                    <div class="grade-box">
                                        {{ $certificate->grade ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="section-card p-4">
                                <h2 class="h5 fw-bold mb-3">Verification Data</h2>

                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <div class="mini-badge p-3 h-100">
                                            <div class="small text-muted mb-1">Status</div>
                                            <div class="fw-bold">{{ $statusLabel }}</div>
                                        </div>
                                    </div>

                                   
                                </div>

                                <div class="token-box p-3">
                                    <div class="small text-muted mb-2">Public Token</div>
                                    <code class="token-text d-block">{{ $certificate->public_token }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center footer-text mt-4">
                        This page verifies certificate records issued through FlexLabs system.
                        For additional validation, please contact FlexLabs academic team.
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>