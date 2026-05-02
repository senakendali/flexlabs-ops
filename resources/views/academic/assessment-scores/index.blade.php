@extends('layouts.app-dashboard')

@section('title', 'Assessment Scores')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Assessment</div>
                <h1 class="page-title mb-2">Assessment Scores</h1>
                <p class="page-subtitle mb-0">
                    Input nilai student berdasarkan <strong>assessment template</strong>, rubrik, dan komponen penilaian per batch.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if($selectedBatch && $template)
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#scoreModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Input Score
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Students</div>
                        <div class="stat-value">{{ $stats['students'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total student yang terdaftar di batch terpilih.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-ui-checks-grid"></i>
                    </div>
                    <div>
                        <div class="stat-title">Components</div>
                        <div class="stat-value">{{ $stats['components'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Komponen penilaian dari assessment template.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Scores Filled</div>
                        <div class="stat-value">{{ $stats['scores_filled'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah nilai yang sudah diinput.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completion</div>
                        <div class="stat-value">{{ $stats['completion_rate'] ?? 0 }}%</div>
                    </div>
                </div>
                <div class="stat-description">Persentase kelengkapan nilai batch.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Assessment</h5>
                <p class="content-card-subtitle mb-0">
                    Pilih batch untuk menampilkan student, komponen nilai, dan progress assessment.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.assessment-scores.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-8 col-md-8">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select" required>
                            <option value="">Select Batch</option>
                            @foreach($batches ?? [] as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->program->name ?? 'Program' }} - {{ $batch->name ?? ('Batch #' . $batch->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-4">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('academic.assessment-scores.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Load Batch
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            @if($selectedBatch)
                <div class="assessment-context-box mt-4">
                    <div class="assessment-context-icon">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <div class="assessment-context-title">
                            {{ $selectedBatch->program->name ?? 'Program' }} - {{ $selectedBatch->name ?? 'Batch' }}
                        </div>
                        <div class="assessment-context-text">
                            Template:
                            <strong>{{ $template->name ?? 'Assessment template belum tersedia' }}</strong>
                            @if(!$template)
                                <span class="text-danger ms-2">Batch ini belum punya template assessment aktif.</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student Score List</h5>
                <p class="content-card-subtitle mb-0">
                    Input nilai per student dan preview hasil final score sebelum generate report card.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(!$selectedBatch)
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <h5 class="empty-state-title">Pilih batch terlebih dahulu</h5>
                    <p class="empty-state-text mb-0">
                        Gunakan filter di atas untuk memuat student dan komponen assessment.
                    </p>
                </div>
            @elseif(!$template)
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h5 class="empty-state-title">Assessment template belum tersedia</h5>
                    <p class="empty-state-text mb-0">
                        Assign assessment template ke batch ini terlebih dahulu sebelum input nilai.
                    </p>
                </div>
            @elseif(($students ?? collect())->count())
                <div class="assessment-score-list">
                    @foreach($students as $student)
                        @php
                            $studentName = $student->name
                                ?? $student->full_name
                                ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

                            if(!$studentName) {
                                $studentName = $student->email ?? 'Student #' . $student->id;
                            }

                            $studentScores = $scoreMap[$student->id] ?? [];

                            $filledCount = 0;
                            $totalWeighted = 0;

                            foreach($components as $component) {
                                $existingScore = $studentScores[$component->id] ?? null;

                                if($existingScore) {
                                    $filledCount++;
                                    $totalWeighted += (float) ($existingScore['weighted_score'] ?? 0);
                                }
                            }

                            $completionPercent = $components->count()
                                ? round(($filledCount / $components->count()) * 100)
                                : 0;

                            $grade = match(true) {
                                $totalWeighted >= 90 => 'A',
                                $totalWeighted >= 80 => 'B',
                                $totalWeighted >= 70 => 'C',
                                $totalWeighted >= 60 => 'D',
                                default => 'E',
                            };

                            $statusClass = $totalWeighted >= 70 && $completionPercent >= 100
                                ? 'status-published'
                                : ($filledCount > 0 ? 'status-draft' : 'status-closed');
                        @endphp

                        <div class="assessment-score-card">
                            <div class="assessment-score-main">
                                <div class="assessment-score-avatar">
                                    {{ strtoupper(substr($studentName, 0, 1)) }}
                                </div>

                                <div class="assessment-score-info">
                                    <div class="assessment-score-title-row">
                                        <div>
                                            <h5 class="assessment-score-title mb-1">
                                                {{ $studentName }}
                                            </h5>
                                            <div class="assessment-score-subtitle">
                                                {{ $student->email ?? 'No email' }}
                                            </div>
                                        </div>

                                        <div class="assessment-score-badges">
                                            <span class="assignment-status-badge {{ $statusClass }}">
                                                {{ $completionPercent }}% Filled
                                            </span>

                                            <span class="deadline-badge deadline-open">
                                                Grade {{ $grade }}
                                            </span>

                                            <span class="late-badge is-allowed">
                                                Score {{ number_format($totalWeighted, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="assessment-component-grid mt-3">
                                        @foreach($components as $component)
                                            @php
                                                $existingScore = $studentScores[$component->id] ?? null;
                                                $scoreValue = $existingScore['raw_score'] ?? null;
                                                $weightedScore = $existingScore['weighted_score'] ?? null;
                                                $chipClass = $existingScore ? 'is-filled' : 'is-empty';
                                            @endphp

                                            <button
                                                type="button"
                                                class="assessment-component-chip {{ $chipClass }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#scoreModal"
                                                data-mode="{{ $existingScore ? 'edit' : 'create' }}"
                                                data-student-id="{{ $student->id }}"
                                                data-component-id="{{ $component->id }}"
                                            >
                                                <span class="component-chip-name">{{ $component->name }}</span>
                                                <span class="component-chip-score">
                                                    @if($existingScore)
                                                        {{ number_format($scoreValue, 1) }}
                                                        <small>/ {{ number_format($weightedScore, 1) }} pts</small>
                                                    @else
                                                        Not set
                                                    @endif
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="assessment-score-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm preview-score-btn"
                                    data-student-id="{{ $student->id }}"
                                    data-student-name="{{ $studentName }}"
                                >
                                    <i class="bi bi-eye me-1"></i>Preview
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm generate-report-btn"
                                    data-student-id="{{ $student->id }}"
                                    data-student-name="{{ $studentName }}"
                                    data-student-email="{{ $student->email ?? 'No email' }}"
                                    data-final-score="{{ number_format($totalWeighted, 2) }}"
                                    data-grade="{{ $grade }}"
                                    data-completion="{{ $completionPercent }}"
                                >
                                    <i class="bi bi-file-earmark-text me-1"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada student di batch ini</h5>
                    <p class="empty-state-text mb-0">
                        Student belum terhubung ke batch, jadi nilai belum bisa diinput.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="scoreModal" tabindex="-1" aria-labelledby="scoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="scoreForm" data-store-url="{{ route('academic.assessment-scores.store') }}">
                @csrf

                <input type="hidden" name="student_id" value="">
                <input type="hidden" name="batch_id" value="{{ $selectedBatch->id ?? '' }}">
                <input type="hidden" name="assessment_component_id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="scoreModalLabel">Input Assessment Score</h5>
                        <p class="text-muted mb-0" id="scoreModalSubtitle">
                            Input nilai berdasarkan komponen assessment yang dipilih.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="score-modal-summary mb-4">
                        <div class="score-summary-item">
                            <div class="score-summary-label">Student</div>
                            <div class="score-summary-value" id="modalStudentName">-</div>
                        </div>
                        <div class="score-summary-item">
                            <div class="score-summary-label">Component</div>
                            <div class="score-summary-value" id="modalComponentName">-</div>
                        </div>
                        <div class="score-summary-item">
                            <div class="score-summary-label">Weight</div>
                            <div class="score-summary-value" id="modalComponentWeight">-</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 raw-score-section">
                            <label class="form-label">Raw Score</label>
                            <input
                                type="number"
                                name="raw_score"
                                class="form-control"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="0 - 100"
                            >
                            <div class="form-text">
                                Nilai mentah 0 - 100. Jika memakai rubric, nilai ini dihitung otomatis dari criteria.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                                <option value="reviewed" selected>Reviewed</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Weighted Preview</label>
                            <input
                                type="text"
                                class="form-control"
                                id="weightedPreview"
                                value="-"
                                readonly
                            >
                            <div class="form-text">
                                Rumus: raw score × component weight.
                            </div>
                        </div>

                        <div class="col-12 d-none" id="rubricScoreWrapper">
                            <div class="rubric-score-box">
                                <div class="rubric-score-header">
                                    <div>
                                        <div class="rubric-score-title">Rubric Criteria</div>
                                        <div class="rubric-score-subtitle">
                                            Isi nilai per criteria. Raw score component akan dihitung otomatis.
                                        </div>
                                    </div>
                                </div>

                                <div id="rubricCriteriaList" class="rubric-criteria-list"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Feedback</label>
                            <textarea
                                name="feedback"
                                class="form-control"
                                rows="4"
                                placeholder="Catatan instructor untuk nilai ini..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Score
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="previewScoreModal" tabindex="-1" aria-labelledby="previewScoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="previewScoreModalLabel">Assessment Preview</h5>
                    <p class="text-muted mb-0">
                        Preview final score sebelum report card digenerate.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div id="previewScoreContent">
                    <div class="text-muted">Loading preview...</div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="generateReportConfirmModal" tabindex="-1" aria-labelledby="generateReportConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="generateReportConfirmModalLabel">Generate Report Card</h5>
                    <p class="text-muted mb-0">
                        Buat report card berdasarkan assessment score terbaru.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="alert alert-danger d-none generate-report-alert" role="alert"></div>

                <div class="alert alert-warning mb-3">
                    Proses ini akan membuat atau memperbarui report card student menggunakan nilai final, grade, feedback, dan komponen assessment yang sudah terisi.
                </div>

                <div class="border rounded-3 p-3">
                    <div class="text-muted small mb-1">Student</div>
                    <div class="fw-bold text-dark" id="generateReportStudentName">-</div>
                    <div class="text-muted small" id="generateReportStudentEmail">-</div>

                    <div class="text-muted small mt-3 mb-1">Batch</div>
                    <div class="fw-semibold text-dark">
                        {{ $selectedBatch?->program?->name ?? 'Program' }} - {{ $selectedBatch?->name ?? '-' }}
                    </div>

                    <div class="text-muted small mt-3 mb-1">Assessment Template</div>
                    <div class="fw-semibold text-dark">
                        {{ $template?->name ?? '-' }}
                    </div>

                    <div class="text-muted small mt-3 mb-1">Current Assessment</div>
                    <div class="fw-semibold text-dark">
                        Score <span id="generateReportFinalScore">-</span>
                        · Grade <span id="generateReportGrade">-</span>
                        · <span id="generateReportCompletion">-</span>% filled
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-primary btn-modern" id="confirmGenerateReportBtn">
                    <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                </button>
            </div>
        </div>
    </div>
</div>


<form id="generateReportForm" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectedBatchId = @json($selectedBatch->id ?? null);
    const components = @json($componentsPayload ?? []);
    const students = @json($studentPayload ?? []);
    const scoreMap = @json($scoreMap ?? []);

    const csrfToken =
        document.querySelector('#scoreForm input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');
        if (!toastEl) return;

        const toastBody = toastEl.querySelector('.toast-body');
        toastBody.innerHTML = message;

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 2500
        });

        toast.show();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function findStudent(studentId) {
        return students.find(item => Number(item.id) === Number(studentId));
    }

    function findComponent(componentId) {
        return components.find(item => Number(item.id) === Number(componentId));
    }

    function getExistingScore(studentId, componentId) {
        return scoreMap?.[studentId]?.[componentId] || null;
    }

    function showErrors(form, errors) {
        const alertBox = form.querySelector('.form-alert');
        if (!alertBox) return;

        let messages = [];

        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(function (key) {
                const fieldErrors = errors[key];
                if (Array.isArray(fieldErrors)) {
                    fieldErrors.forEach(function (message) {
                        messages.push(`<div>${escapeHtml(message)}</div>`);
                    });
                } else if (fieldErrors) {
                    messages.push(`<div>${escapeHtml(fieldErrors)}</div>`);
                }
            });
        }

        if (!messages.length) {
            messages = ['<div>Terjadi kesalahan. Silakan coba lagi.</div>'];
        }

        alertBox.innerHTML = messages.join('');
        alertBox.classList.remove('d-none');
    }

    function resetScoreForm(form) {
        form.reset();

        form.querySelector('input[name="student_id"]').value = '';
        form.querySelector('input[name="assessment_component_id"]').value = '';
        form.querySelector('input[name="batch_id"]').value = selectedBatchId || '';

        const alertBox = form.querySelector('.form-alert');
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
        }

        document.getElementById('modalStudentName').textContent = '-';
        document.getElementById('modalComponentName').textContent = '-';
        document.getElementById('modalComponentWeight').textContent = '-';
        document.getElementById('weightedPreview').value = '-';

        document.getElementById('rubricScoreWrapper').classList.add('d-none');
        document.getElementById('rubricCriteriaList').innerHTML = '';

        const submitBtn = form.querySelector('.submit-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Score';
            submitBtn.dataset.defaultText = 'Save Score';
        }
    }

    function calculateWeighted(rawScore, weight) {
        const raw = Number(rawScore || 0);
        const componentWeight = Number(weight || 0);

        return ((raw * componentWeight) / 100).toFixed(2);
    }

    function updateWeightedPreview(form, component) {
        const rawInput = form.querySelector('input[name="raw_score"]');
        const preview = document.getElementById('weightedPreview');

        preview.value = calculateWeighted(rawInput.value, component?.weight || 0);
    }

    function calculateRubricRawScore(form) {
        const inputs = form.querySelectorAll('[data-rubric-score-input]');
        let total = 0;

        inputs.forEach(function (input) {
            const score = Number(input.value || 0);
            const weight = Number(input.dataset.weight || 0);

            total += (score * weight) / 100;
        });

        return Number(total.toFixed(2));
    }

    function renderRubricCriteria(form, component, existingScore) {
        const wrapper = document.getElementById('rubricScoreWrapper');
        const list = document.getElementById('rubricCriteriaList');
        const rawInput = form.querySelector('input[name="raw_score"]');

        list.innerHTML = '';

        if (!component?.rubric?.criteria?.length) {
            wrapper.classList.add('d-none');
            rawInput.readOnly = false;
            return;
        }

        wrapper.classList.remove('d-none');
        rawInput.readOnly = true;

        const existingRubricMap = {};

        if (existingScore?.rubric_scores?.length) {
            existingScore.rubric_scores.forEach(function (item) {
                existingRubricMap[item.criteria_id] = item;
            });
        }

        component.rubric.criteria.forEach(function (criterion, index) {
            const existing = existingRubricMap[criterion.id] || null;

            const row = document.createElement('div');
            row.className = 'rubric-criteria-item';

            row.innerHTML = `
                <div class="rubric-criteria-info">
                    <div class="rubric-criteria-title">${escapeHtml(criterion.name)}</div>
                    <div class="rubric-criteria-description">${escapeHtml(criterion.description || 'No description')}</div>
                    <div class="rubric-criteria-weight">Weight ${Number(criterion.weight).toFixed(0)}%</div>
                </div>

                <div class="rubric-criteria-inputs">
                    <input type="hidden" name="rubric_scores[${index}][criteria_id]" value="${criterion.id}">

                    <input
                        type="number"
                        class="form-control rubric-score-input"
                        name="rubric_scores[${index}][raw_score]"
                        min="0"
                        max="100"
                        step="0.01"
                        value="${existing ? existing.raw_score : ''}"
                        placeholder="0 - 100"
                        data-rubric-score-input
                        data-weight="${criterion.weight}"
                    >

                    <input
                        type="text"
                        class="form-control"
                        name="rubric_scores[${index}][note]"
                        value="${existing ? escapeHtml(existing.note || '') : ''}"
                        placeholder="Note"
                    >
                </div>
            `;

            list.appendChild(row);
        });

        list.querySelectorAll('[data-rubric-score-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                rawInput.value = calculateRubricRawScore(form);
                updateWeightedPreview(form, component);
            });
        });

        rawInput.value = calculateRubricRawScore(form);
    }

    function setupScoreModal() {
        const modalEl = document.getElementById('scoreModal');
        const form = document.getElementById('scoreForm');

        if (!modalEl || !form) return;

        const rawInput = form.querySelector('input[name="raw_score"]');

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetScoreForm(form);

            const button = event.relatedTarget;
            const studentId = button?.dataset?.studentId || '';
            const componentId = button?.dataset?.componentId || '';

            const student = findStudent(studentId);
            const component = findComponent(componentId);
            const existingScore = getExistingScore(studentId, componentId);

            form.querySelector('input[name="student_id"]').value = studentId;
            form.querySelector('input[name="assessment_component_id"]').value = componentId;

            document.getElementById('modalStudentName').textContent = student?.name || '-';
            document.getElementById('modalComponentName').textContent = component?.name || '-';
            document.getElementById('modalComponentWeight').textContent = component ? `${component.weight}%` : '-';

            document.getElementById('scoreModalLabel').textContent = existingScore
                ? 'Edit Assessment Score'
                : 'Input Assessment Score';

            document.getElementById('scoreModalSubtitle').textContent = component
                ? `Component: ${component.name}`
                : 'Input nilai berdasarkan komponen assessment yang dipilih.';

            if (existingScore) {
                rawInput.value = existingScore.raw_score ?? '';
                form.querySelector('textarea[name="feedback"]').value = existingScore.feedback ?? '';
                form.querySelector('select[name="status"]').value = existingScore.status ?? 'reviewed';
            }

            renderRubricCriteria(form, component, existingScore);
            updateWeightedPreview(form, component);

            rawInput.addEventListener('input', function () {
                updateWeightedPreview(form, component);
            });
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.submit-btn');
            const storeUrl = form.dataset.storeUrl;
            const formData = new FormData(form);

            const alertBox = form.querySelector('.form-alert');
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Saving...';

            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422) {
                        showErrors(form, data.errors || {});
                        showToast('Mohon cek kembali input nilai.', 'error');
                    } else {
                        showErrors(form, { general: [data.message || 'Terjadi kesalahan pada server.'] });
                        showToast(data.message || 'Gagal menyimpan nilai.', 'error');
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save Score';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Assessment score berhasil disimpan.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showErrors(form, { general: ['Gagal menghubungi server.'] });
                showToast('Gagal menghubungi server.', 'error');

                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Score';
            }
        });
    }

    function setupPreviewButtons() {
        const modalEl = document.getElementById('previewScoreModal');
        const content = document.getElementById('previewScoreContent');

        if (!modalEl || !content) return;

        function normalizePreviewPayload(payload) {
            return payload?.data && typeof payload.data === 'object'
                ? payload.data
                : (payload || {});
        }

        function formatScore(value, digits = 2) {
            if (value === null || value === undefined || value === '') {
                return '-';
            }

            const number = Number(value);
            return Number.isFinite(number) ? number.toFixed(digits) : '-';
        }

        function calculateGrade(score) {
            const value = Number(score || 0);

            if (value >= 90) return 'A';
            if (value >= 80) return 'B';
            if (value >= 70) return 'C';
            if (value >= 60) return 'D';

            return 'E';
        }

        function resolvePreviewStatus(data) {
            if (typeof data.is_passed === 'boolean') {
                return data.is_passed ? 'Passed' : 'Not Passed';
            }

            return data.status || '-';
        }

        function renderScoreRows(rows) {
            return rows.map(function (row) {
                const componentName = row.component || row.name || row.component_name || '-';
                const componentType = row.type || row.component_type || '-';
                const hasScore = row.has_score ?? (
                    row.raw_score !== null
                    && row.raw_score !== undefined
                    && row.raw_score !== ''
                );

                const rowStatus = row.status || (hasScore ? 'reviewed' : 'missing');
                const badge = hasScore
                    ? `<span class="assignment-status-badge status-published">${escapeHtml(rowStatus)}</span>`
                    : '<span class="assignment-status-badge status-closed">Missing</span>';

                return `
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(componentName)}</div>
                            ${row.feedback ? `<div class="text-muted small mt-1">${escapeHtml(row.feedback)}</div>` : ''}
                        </td>
                        <td>${escapeHtml(componentType)}</td>
                        <td class="text-end">${formatScore(row.raw_score)}</td>
                        <td class="text-end">${formatScore(row.weight)}%</td>
                        <td class="text-end fw-semibold text-dark">${formatScore(row.weighted_score)}</td>
                        <td>${badge}</td>
                    </tr>
                `;
            }).join('');
        }

        function renderMissingRows(rows) {
            if (!rows.length) return '';

            return rows.map(function (row) {
                const componentName = typeof row === 'string'
                    ? row
                    : (row.component || row.name || row.component_name || '-');

                const componentType = typeof row === 'string'
                    ? '-'
                    : (row.type || row.component_type || '-');

                const weight = typeof row === 'string'
                    ? '-'
                    : formatScore(row.weight);

                return `
                    <tr>
                        <td><div class="fw-semibold text-dark">${escapeHtml(componentName)}</div></td>
                        <td>${escapeHtml(componentType)}</td>
                        <td class="text-end">-</td>
                        <td class="text-end">${weight === '-' ? '-' : `${weight}%`}</td>
                        <td class="text-end fw-semibold text-dark">-</td>
                        <td><span class="assignment-status-badge status-closed">Missing</span></td>
                    </tr>
                `;
            }).join('');
        }

        document.querySelectorAll('.preview-score-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                const studentId = button.dataset.studentId;
                const fallbackStudentName = button.dataset.studentName || 'student';

                content.innerHTML = `<div class="text-muted">Loading preview for ${escapeHtml(fallbackStudentName)}...</div>`;

                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                const url = new URL(`{{ route('academic.assessment-scores.preview') }}`, window.location.origin);
                url.searchParams.set('student_id', studentId);
                url.searchParams.set('batch_id', selectedBatchId || '');

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        content.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                ${escapeHtml(payload.message || 'Gagal memuat preview score.')}
                            </div>
                        `;
                        return;
                    }

                    const data = normalizePreviewPayload(payload);
                    const student = data.student || {};
                    const batch = data.batch || {};
                    const template = data.template || {};
                    const scoreRows = Array.isArray(data.components)
                        ? data.components
                        : (Array.isArray(data.scores) ? data.scores : []);
                    const missingRows = Array.isArray(data.missing_components)
                        ? data.missing_components
                        : [];

                    const studentName = student.name || fallbackStudentName;
                    const studentEmail = student.email || '-';
                    const batchName = batch.name || '-';
                    const programName = batch.program || batch.program_name || '-';
                    const templateName = template.name || '-';
                    const passingScore = template.passing_score ?? '-';
                    const finalScore = Number(data.final_score || 0);
                    const grade = data.grade || calculateGrade(finalScore);
                    const status = resolvePreviewStatus(data);
                    const rows = renderScoreRows(scoreRows) + renderMissingRows(missingRows);

                    content.innerHTML = `
                        <div class="score-preview-header mb-4">
                            <div>
                                <div class="score-preview-label">Student</div>
                                <div class="score-preview-name">${escapeHtml(studentName)}</div>
                                <div class="text-muted small mt-1">${escapeHtml(studentEmail)}</div>
                            </div>
                            <div class="score-preview-metrics">
                                <div class="score-preview-metric">
                                    <span>Final Score</span>
                                    <strong>${formatScore(finalScore)}</strong>
                                </div>
                                <div class="score-preview-metric">
                                    <span>Grade</span>
                                    <strong>${escapeHtml(grade)}</strong>
                                </div>
                                <div class="score-preview-metric">
                                    <span>Status</span>
                                    <strong>${escapeHtml(status)}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-muted small">Batch</div>
                                    <div class="fw-semibold text-dark">${escapeHtml(batchName)}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Program</div>
                                    <div class="fw-semibold text-dark">${escapeHtml(programName)}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Assessment Template</div>
                                    <div class="fw-semibold text-dark">${escapeHtml(templateName)}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Passing Score</div>
                                    <div class="fw-semibold text-dark">${escapeHtml(passingScore)}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Completed Components</div>
                                    <div class="fw-semibold text-dark">${scoreRows.length}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">Missing Components</div>
                                    <div class="fw-semibold text-dark">${missingRows.length}</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Type</th>
                                        <th class="text-end">Raw</th>
                                        <th class="text-end">Weight</th>
                                        <th class="text-end">Final</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows || '<tr><td colspan="6" class="text-center text-muted py-4">No component data.</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    `;
                } catch (error) {
                    content.innerHTML = `<div class="alert alert-danger mb-0">Gagal memuat preview score.</div>`;
                }
            });
        });
    }

    function setupGenerateReportButtons() {
        const modalEl = document.getElementById('generateReportConfirmModal');
        const confirmBtn = document.getElementById('confirmGenerateReportBtn');
        const studentNameEl = document.getElementById('generateReportStudentName');
        const studentEmailEl = document.getElementById('generateReportStudentEmail');
        const finalScoreEl = document.getElementById('generateReportFinalScore');
        const gradeEl = document.getElementById('generateReportGrade');
        const completionEl = document.getElementById('generateReportCompletion');
        const alertBox = modalEl?.querySelector('.generate-report-alert');

        if (!modalEl || !confirmBtn || !studentNameEl) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let activeButton = null;
        let activeStudentId = null;
        let activeStudentName = null;
        let activeButtonDefaultHtml = '';

        function resetGenerateDialog() {
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>Generate Report';
        }

        function showGenerateDialogError(message) {
            if (!alertBox) {
                showToast(message, 'error');
                return;
            }

            alertBox.innerHTML = escapeHtml(message);
            alertBox.classList.remove('d-none');
        }

        document.querySelectorAll('.generate-report-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                activeButton = button;
                activeStudentId = button.dataset.studentId;
                activeStudentName = button.dataset.studentName || 'student';
                activeButtonDefaultHtml = button.innerHTML;

                resetGenerateDialog();

                studentNameEl.textContent = activeStudentName;

                if (studentEmailEl) {
                    studentEmailEl.textContent = button.dataset.studentEmail || 'No email';
                }

                if (finalScoreEl) {
                    finalScoreEl.textContent = button.dataset.finalScore || '0.00';
                }

                if (gradeEl) {
                    gradeEl.textContent = button.dataset.grade || '-';
                }

                if (completionEl) {
                    completionEl.textContent = button.dataset.completion || '0';
                }

                modal.show();
            });
        });

        confirmBtn.addEventListener('click', async function () {
            if (!activeButton || !activeStudentId) {
                showGenerateDialogError('Data student tidak ditemukan. Silakan refresh halaman dan coba lagi.');
                return;
            }

            if (!selectedBatchId) {
                showGenerateDialogError('Batch belum dipilih. Silakan pilih batch terlebih dahulu.');
                return;
            }

            resetGenerateDialog();

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Generating...';

            activeButton.disabled = true;
            activeButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Generating...';

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('student_id', activeStudentId);
            formData.append('batch_id', selectedBatchId);

            try {
                const response = await fetch(`{{ route('academic.report-cards.generate') }}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message || 'Gagal generate report card.';

                    showGenerateDialogError(message);
                    showToast(message, 'error');

                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>Generate Report';

                    activeButton.disabled = false;
                    activeButton.innerHTML = activeButtonDefaultHtml;
                    return;
                }

                modal.hide();
                showToast(data.message || 'Report card berhasil digenerate.', 'success');

                setTimeout(() => {
                    if (data?.data?.id) {
                        window.location.href = `/academic/report-cards/${data.data.id}`;
                        return;
                    }

                    window.location.reload();
                }, 900);
            } catch (error) {
                showGenerateDialogError('Gagal menghubungi server.');
                showToast('Gagal menghubungi server.', 'error');

                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i>Generate Report';

                activeButton.disabled = false;
                activeButton.innerHTML = activeButtonDefaultHtml;
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            resetGenerateDialog();

            if (activeButton && !activeButton.disabled) {
                activeButton.innerHTML = activeButtonDefaultHtml;
            }
        });
    }

    setupScoreModal();
    setupPreviewButtons();
    setupGenerateReportButtons();
});
</script>
@endpush