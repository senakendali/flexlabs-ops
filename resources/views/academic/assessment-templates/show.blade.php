@extends('layouts.app-dashboard')

@section('title', 'Assessment Template Detail')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">{{ $template->name }}</h1>
                <p class="page-subtitle mb-0">
                    Detail standar assessment untuk program
                    <strong>{{ $template->program->name ?? '-' }}</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-templates.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                <a href="{{ route('academic.assessment-templates.edit', $template) }}" class="btn btn-light btn-modern">
                    <i class="bi bi-pencil-square me-2"></i>Edit
                </a>

                <form
                    method="POST"
                    action="{{ route('academic.assessment-templates.destroy', $template) }}"
                    onsubmit="return confirm('Delete assessment template ini?')"
                    class="d-inline"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger btn-modern">
                        <i class="bi bi-trash me-2"></i>Delete
                    </button>
                </form>
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

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="stat-title">Passing Score</div>
                        <div class="stat-value">{{ number_format((float) $template->passing_score, 2) }}</div>
                    </div>
                </div>
                <div class="stat-description">Minimum nilai akhir untuk lulus.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Attendance</div>
                        <div class="stat-value">{{ number_format((float) $template->min_attendance_percent, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Minimum kehadiran student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <div class="stat-title">Progress</div>
                        <div class="stat-value">{{ number_format((float) $template->min_progress_percent, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Minimum progress pembelajaran.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <div>
                        <div class="stat-title">Final Project</div>
                        <div class="stat-value">
                            {{ $template->requires_final_project ? 'Yes' : 'No' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Kebutuhan final project.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Template Detail</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi utama assessment template.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th width="220" class="text-muted">Template Name</th>
                                    <td class="fw-semibold text-dark">{{ $template->name }}</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Code</th>
                                    <td>
                                        @if($template->code)
                                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                                {{ $template->code }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Program</th>
                                    <td>{{ $template->program->name ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Description</th>
                                    <td>
                                        @if($template->description)
                                            {!! nl2br(e($template->description)) !!}
                                        @else
                                            <span class="text-muted">No description.</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Passing Score</th>
                                    <td>{{ number_format((float) $template->passing_score, 2) }} / 100</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Minimum Attendance</th>
                                    <td>{{ number_format((float) $template->min_attendance_percent, 2) }}%</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Minimum Progress</th>
                                    <td>{{ number_format((float) $template->min_progress_percent, 2) }}%</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Final Project</th>
                                    <td>
                                        @if($template->requires_final_project)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                                Required
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                                Not Required
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Status</th>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge rounded-pill bg-success-subtle text-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Quick Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan konfigurasi template.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Program</span>
                        <span class="fw-semibold text-end">{{ $template->program->name ?? '-' }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Passing</span>
                        <span class="fw-semibold">{{ number_format((float) $template->passing_score, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Attendance</span>
                        <span class="fw-semibold">{{ number_format((float) $template->min_attendance_percent, 2) }}%</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Progress</span>
                        <span class="fw-semibold">{{ number_format((float) $template->min_progress_percent, 2) }}%</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Status</span>
                        <span class="fw-semibold">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Record Info</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi pembuatan dan perubahan data.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Created At</span>
                        <span class="fw-semibold text-end">
                            {{ optional($template->created_at)->format('d M Y H:i') ?? '-' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Updated At</span>
                        <span class="fw-semibold text-end">
                            {{ optional($template->updated_at)->format('d M Y H:i') ?? '-' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Created By</span>
                        <span class="fw-semibold text-end">
                            {{ $template->created_by ?: '-' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Updated By</span>
                        <span class="fw-semibold text-end">
                            {{ $template->updated_by ?: '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection