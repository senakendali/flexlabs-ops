@extends('layouts.app-dashboard')

@section('title', 'Manage Assessment Rubric')

@section('content')
@php
    $criteria = $rubric?->criteria ?? collect();
    $levels = $rubric?->levels ?? collect();

    $criteriaWeight = (float) $criteria->sum('weight');
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Manage Rubric</h1>
                <p class="page-subtitle mb-0">
                    Component: <strong>{{ $component->name }}</strong> —
                    Template: <strong>{{ $assessmentTemplate->name }}</strong>
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-templates.show', $assessmentTemplate) }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back to Template
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Mohon cek kembali input berikut:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-ui-checks-grid"></i>
                    </div>
                    <div>
                        <div class="stat-title">Rubric Status</div>
                        <div class="stat-value">{{ $rubric ? 'Ready' : 'Empty' }}</div>
                    </div>
                </div>
                <div class="stat-description">Status active rubric pada component ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Criteria</div>
                        <div class="stat-value">{{ $criteria->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah aspek penilaian rubric.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bar-chart-steps"></i>
                    </div>
                    <div>
                        <div class="stat-title">Levels</div>
                        <div class="stat-value">{{ $levels->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah level score rubric.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div>
                        <div class="stat-title">Criteria Weight</div>
                        <div class="stat-value">{{ number_format($criteriaWeight, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Total bobot criteria aktif.</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Rubric Information</h5>
                        <p class="content-card-subtitle mb-0">
                            Rubric digunakan untuk menilai component manual seperti assignment, project, atau attitude.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <form
                        method="POST"
                        action="{{ $rubric
                            ? route('academic.assessment-templates.components.rubric.update', [$assessmentTemplate, $component, $rubric])
                            : route('academic.assessment-templates.components.rubric.store', [$assessmentTemplate, $component])
                        }}"
                    >
                        @csrf

                        @if($rubric)
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Rubric Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $rubric->name ?? $component->name . ' Rubric') }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Jelaskan fungsi rubric ini..."
                            >{{ old('description', $rubric->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', (int) ($rubric->is_active ?? 1)) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="0" {{ old('is_active', (int) ($rubric->is_active ?? 1)) == 0 ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                            <div class="form-text">
                                Hanya satu rubric aktif yang akan dipakai untuk component ini.
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-save me-2"></i>
                                {{ $rubric ? 'Update Rubric' : 'Create Rubric' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Component Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan component yang sedang dinilai.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Component</span>
                        <span class="fw-semibold text-end">{{ $component->name }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Type</span>
                        <span class="fw-semibold text-end">{{ ucfirst($component->type) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Weight</span>
                        <span class="fw-semibold">{{ number_format((float) $component->weight, 2) }}%</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Max Score</span>
                        <span class="fw-semibold">{{ number_format((float) $component->max_score, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Calculation</span>
                        <span class="fw-semibold">
                            {{ $component->is_auto_calculated ? 'Auto' : 'Manual' }}
                        </span>
                    </div>

                    @if($component->is_auto_calculated)
                        <div class="alert alert-info mt-3 mb-0">
                            Component ini auto calculated. Rubric biasanya lebih relevan untuk component manual.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($rubric)
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Rubric Criteria</h5>
                            <p class="content-card-subtitle mb-0">
                                Aspek yang dinilai pada rubric ini. Total weight idealnya 100%.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary btn-modern"
                            data-bs-toggle="modal"
                            data-bs-target="#criteriaModal"
                            data-mode="create"
                        >
                            <i class="bi bi-plus-circle me-2"></i>Add Criteria
                        </button>
                    </div>

                    <div class="content-card-body">
                        @if($criteriaWeight < 100)
                            <div class="alert alert-warning">
                                Total criteria weight masih {{ number_format($criteriaWeight, 2) }}%.
                            </div>
                        @elseif($criteriaWeight > 100)
                            <div class="alert alert-danger">
                                Total criteria weight melebihi 100%.
                            </div>
                        @else
                            <div class="alert alert-success">
                                Total criteria weight sudah 100%.
                            </div>
                        @endif

                        @if($criteria->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Criteria</th>
                                            <th class="text-nowrap">Weight</th>
                                            <th class="text-nowrap">Max Score</th>
                                            <th class="text-nowrap">Required</th>
                                            <th class="text-end text-nowrap">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($criteria as $criterion)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $criterion->name }}</div>
                                                    <div class="text-muted small">{{ $criterion->code ?: '-' }}</div>

                                                    @if($criterion->description)
                                                        <div class="text-muted small mt-1">
                                                            {{ \Illuminate\Support\Str::limit(strip_tags($criterion->description), 90) }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="text-nowrap">
                                                    {{ number_format((float) $criterion->weight, 2) }}%
                                                </td>

                                                <td class="text-nowrap">
                                                    {{ number_format((float) $criterion->max_score, 2) }}
                                                </td>

                                                <td class="text-nowrap">
                                                    @if($criterion->is_required)
                                                        <span class="badge rounded-pill bg-success-subtle text-success">Yes</span>
                                                    @else
                                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary">No</span>
                                                    @endif
                                                </td>

                                                <td class="text-end text-nowrap">
                                                    <div class="dropdown">
                                                        <button
                                                            class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            data-bs-boundary="viewport"
                                                            aria-expanded="false"
                                                        >
                                                            Actions
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="dropdown-item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#criteriaModal"
                                                                    data-mode="edit"
                                                                    data-action="{{ route('academic.assessment-templates.components.rubric.criteria.update', [$assessmentTemplate, $component, $rubric, $criterion]) }}"
                                                                    data-name="{{ $criterion->name }}"
                                                                    data-code="{{ $criterion->code }}"
                                                                    data-description="{{ e($criterion->description) }}"
                                                                    data-weight="{{ $criterion->weight }}"
                                                                    data-max-score="{{ $criterion->max_score }}"
                                                                    data-sort-order="{{ $criterion->sort_order }}"
                                                                    data-is-required="{{ (int) $criterion->is_required }}"
                                                                >
                                                                    <i class="bi bi-pencil-square me-2"></i>Edit Criteria
                                                                </button>
                                                            </li>

                                                            <li><hr class="dropdown-divider"></li>

                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal"
                                                                    data-title="Delete Criteria"
                                                                    data-name="{{ $criterion->name }}"
                                                                    data-action="{{ route('academic.assessment-templates.components.rubric.criteria.destroy', [$assessmentTemplate, $component, $rubric, $criterion]) }}"
                                                                >
                                                                    <i class="bi bi-trash me-2"></i>Delete Criteria
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box">
                                <div class="empty-state-icon">
                                    <i class="bi bi-list-check"></i>
                                </div>
                                <h5 class="empty-state-title">Criteria belum tersedia</h5>
                                <p class="empty-state-text mb-3">
                                    Tambahkan aspek penilaian seperti feature completeness, UI implementation, atau code structure.
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-primary btn-modern"
                                    data-bs-toggle="modal"
                                    data-bs-target="#criteriaModal"
                                    data-mode="create"
                                >
                                    <i class="bi bi-plus-circle me-2"></i>Add First Criteria
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Rubric Levels</h5>
                            <p class="content-card-subtitle mb-0">
                                Level score untuk mengubah score menjadi kategori penilaian.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary btn-modern"
                            data-bs-toggle="modal"
                            data-bs-target="#levelModal"
                            data-mode="create"
                        >
                            <i class="bi bi-plus-circle me-2"></i>Add Level
                        </button>
                    </div>

                    <div class="content-card-body">
                        @if($levels->count())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Level</th>
                                            <th class="text-nowrap">Range</th>
                                            <th class="text-end text-nowrap">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($levels as $level)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $level->name }}</div>
                                                    @if($level->description)
                                                        <div class="text-muted small mt-1">
                                                            {{ \Illuminate\Support\Str::limit(strip_tags($level->description), 80) }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="text-nowrap">
                                                    {{ number_format((float) $level->min_score, 2) }}
                                                    -
                                                    {{ number_format((float) $level->max_score, 2) }}
                                                </td>

                                                <td class="text-end text-nowrap">
                                                    <div class="dropdown">
                                                        <button
                                                            class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            data-bs-boundary="viewport"
                                                            aria-expanded="false"
                                                        >
                                                            Actions
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="dropdown-item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#levelModal"
                                                                    data-mode="edit"
                                                                    data-action="{{ route('academic.assessment-templates.components.rubric.levels.update', [$assessmentTemplate, $component, $rubric, $level]) }}"
                                                                    data-name="{{ $level->name }}"
                                                                    data-description="{{ e($level->description) }}"
                                                                    data-min-score="{{ $level->min_score }}"
                                                                    data-max-score="{{ $level->max_score }}"
                                                                    data-sort-order="{{ $level->sort_order }}"
                                                                >
                                                                    <i class="bi bi-pencil-square me-2"></i>Edit Level
                                                                </button>
                                                            </li>

                                                            <li><hr class="dropdown-divider"></li>

                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal"
                                                                    data-title="Delete Level"
                                                                    data-name="{{ $level->name }}"
                                                                    data-action="{{ route('academic.assessment-templates.components.rubric.levels.destroy', [$assessmentTemplate, $component, $rubric, $level]) }}"
                                                                >
                                                                    <i class="bi bi-trash me-2"></i>Delete Level
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box">
                                <div class="empty-state-icon">
                                    <i class="bi bi-bar-chart-steps"></i>
                                </div>
                                <h5 class="empty-state-title">Level belum tersedia</h5>
                                <p class="empty-state-text mb-3">
                                    Tambahkan level seperti Excellent, Good, Fair, dan Needs Improvement.
                                </p>
                                <button
                                    type="button"
                                    class="btn btn-primary btn-modern"
                                    data-bs-toggle="modal"
                                    data-bs-target="#levelModal"
                                    data-mode="create"
                                >
                                    <i class="bi bi-plus-circle me-2"></i>Add First Level
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="content-card">
            <div class="content-card-body">
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-ui-checks-grid"></i>
                    </div>
                    <h5 class="empty-state-title">Rubric belum dibuat</h5>
                    <p class="empty-state-text mb-0">
                        Buat rubric terlebih dahulu sebelum menambahkan criteria dan levels.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

@if($rubric)
    <div class="modal fade" id="criteriaModal" tabindex="-1" aria-labelledby="criteriaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form
                    id="criteriaForm"
                    method="POST"
                    action="{{ route('academic.assessment-templates.components.rubric.criteria.store', [$assessmentTemplate, $component, $rubric]) }}"
                    data-create-action="{{ route('academic.assessment-templates.components.rubric.criteria.store', [$assessmentTemplate, $component, $rubric]) }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="POST">

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="criteriaModalLabel">Add Criteria</h5>
                            <p class="text-muted mb-0">Tambahkan aspek penilaian rubric.</p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label">Criteria Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" placeholder="Auto">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Weight <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="weight" class="form-control" min="0" max="100" step="0.01" value="0" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Max Score <span class="text-danger">*</span></label>
                                <input type="number" name="max_score" class="form-control" min="0" max="100" step="0.01" value="100" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" min="0" step="1" placeholder="Auto">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Required</label>
                                <select name="is_required" class="form-select">
                                    <option value="1">Required</option>
                                    <option value="0">Optional</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern" id="criteriaSubmitBtn">Save Criteria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="levelModal" tabindex="-1" aria-labelledby="levelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form
                    id="levelForm"
                    method="POST"
                    action="{{ route('academic.assessment-templates.components.rubric.levels.store', [$assessmentTemplate, $component, $rubric]) }}"
                    data-create-action="{{ route('academic.assessment-templates.components.rubric.levels.store', [$assessmentTemplate, $component, $rubric]) }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="POST">

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="levelModalLabel">Add Level</h5>
                            <p class="text-muted mb-0">Tambahkan level score rubric.</p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Level Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Excellent" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" min="0" step="1" placeholder="Auto">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Min Score <span class="text-danger">*</span></label>
                                <input type="number" name="min_score" class="form-control" min="0" max="100" step="0.01" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Max Score <span class="text-danger">*</span></label>
                                <input type="number" name="max_score" class="form-control" min="0" max="100" step="0.01" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern" id="levelSubmitBtn">Save Level</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="deleteModalLabel">Delete Item</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus data.</p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-warning">
                        Data yang sudah dihapus tidak akan tampil lagi.
                    </div>

                    <div class="border rounded-3 p-3">
                        <div class="text-muted small mb-1">Data yang akan dihapus</div>
                        <div class="fw-bold text-dark" id="deleteName">-</div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-modern">
                        <i class="bi bi-trash me-2"></i>Delete
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
    const criteriaModal = document.getElementById('criteriaModal');
    const criteriaForm = document.getElementById('criteriaForm');

    if (criteriaModal && criteriaForm) {
        criteriaModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            criteriaForm.reset();

            const methodInput = criteriaForm.querySelector('input[name="_method"]');
            const title = document.getElementById('criteriaModalLabel');
            const submitBtn = document.getElementById('criteriaSubmitBtn');

            if (mode === 'edit') {
                criteriaForm.action = button.dataset.action;
                methodInput.value = 'PUT';

                title.textContent = 'Edit Criteria';
                submitBtn.textContent = 'Update Criteria';

                criteriaForm.querySelector('input[name="name"]').value = button.dataset.name || '';
                criteriaForm.querySelector('input[name="code"]').value = button.dataset.code || '';
                criteriaForm.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                criteriaForm.querySelector('input[name="weight"]').value = button.dataset.weight || 0;
                criteriaForm.querySelector('input[name="max_score"]').value = button.dataset.maxScore || 100;
                criteriaForm.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || '';
                criteriaForm.querySelector('select[name="is_required"]').value = button.dataset.isRequired || '1';
            } else {
                criteriaForm.action = criteriaForm.dataset.createAction;
                methodInput.value = 'POST';

                title.textContent = 'Add Criteria';
                submitBtn.textContent = 'Save Criteria';

                criteriaForm.querySelector('input[name="weight"]').value = 0;
                criteriaForm.querySelector('input[name="max_score"]').value = 100;
                criteriaForm.querySelector('select[name="is_required"]').value = '1';
            }
        });
    }

    const levelModal = document.getElementById('levelModal');
    const levelForm = document.getElementById('levelForm');

    if (levelModal && levelForm) {
        levelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            levelForm.reset();

            const methodInput = levelForm.querySelector('input[name="_method"]');
            const title = document.getElementById('levelModalLabel');
            const submitBtn = document.getElementById('levelSubmitBtn');

            if (mode === 'edit') {
                levelForm.action = button.dataset.action;
                methodInput.value = 'PUT';

                title.textContent = 'Edit Level';
                submitBtn.textContent = 'Update Level';

                levelForm.querySelector('input[name="name"]').value = button.dataset.name || '';
                levelForm.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                levelForm.querySelector('input[name="min_score"]').value = button.dataset.minScore || '';
                levelForm.querySelector('input[name="max_score"]').value = button.dataset.maxScore || '';
                levelForm.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || '';
            } else {
                levelForm.action = levelForm.dataset.createAction;
                methodInput.value = 'POST';

                title.textContent = 'Add Level';
                submitBtn.textContent = 'Save Level';
            }
        });
    }

    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');

    if (deleteModal && deleteForm) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            deleteForm.action = button.dataset.action || '';

            document.getElementById('deleteModalLabel').textContent = button.dataset.title || 'Delete Item';
            document.getElementById('deleteName').textContent = button.dataset.name || '-';
        });
    }
});
</script>
@endpush