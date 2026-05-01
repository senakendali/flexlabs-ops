@extends('layouts.app-dashboard')

@section('title', ($mode ?? 'create') === 'edit' ? 'Edit Assessment Template' : 'Create Assessment Template')

@section('content')
@php
    $template = $template ?? new \App\Models\AssessmentTemplate();

    $isEdit = ($mode ?? null) === 'edit' || ($template->exists ?? false);

    $formAction = $isEdit
        ? route('academic.assessment-templates.update', $template)
        : route('academic.assessment-templates.store');
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit Assessment Template' : 'Create Assessment Template' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Atur standar kelulusan program, mulai dari passing score, minimum attendance,
                    minimum progress, sampai kebutuhan final project.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-templates.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if($isEdit)
                    <a href="{{ route('academic.assessment-templates.show', $template) }}" class="btn btn-primary btn-modern">
                        <i class="bi bi-eye me-2"></i>View Detail
                    </a>
                @endif
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

    <form method="POST" action="{{ $formAction }}">
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Template Information</h5>
                            <p class="content-card-subtitle mb-0">
                                Informasi utama assessment template yang akan digunakan untuk program.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="program_id" class="form-label">
                                    Program <span class="text-danger">*</span>
                                </label>
                                <select
                                    name="program_id"
                                    id="program_id"
                                    class="form-select @error('program_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Select Program</option>
                                    @foreach($programs ?? [] as $program)
                                        <option
                                            value="{{ $program->id }}"
                                            {{ old('program_id', $template->program_id) == $program->id ? 'selected' : '' }}
                                        >
                                            {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('program_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-5">
                                <label for="code" class="form-label">Code</label>
                                <input
                                    type="text"
                                    name="code"
                                    id="code"
                                    class="form-control @error('code') is-invalid @enderror"
                                    value="{{ old('code', $template->code) }}"
                                    placeholder="Contoh: INTRO-SE-ASSESSMENT"
                                >

                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="form-text">
                                    Kosongkan kalau code dibuat otomatis dari controller.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="name" class="form-label">
                                    Template Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $template->name) }}"
                                    placeholder="Contoh: Intro Software Engineering Assessment"
                                    required
                                >

                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea
                                    name="description"
                                    id="description"
                                    rows="5"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Jelaskan fungsi template assessment ini..."
                                >{{ old('description', $template->description) }}</textarea>

                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Passing Rules</h5>
                            <p class="content-card-subtitle mb-0">
                                Standar minimum kelulusan student.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="mb-3">
                            <label for="passing_score" class="form-label">
                                Passing Score <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="passing_score"
                                    id="passing_score"
                                    class="form-control @error('passing_score') is-invalid @enderror"
                                    value="{{ old('passing_score', $template->passing_score ?? 70) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text">/100</span>

                                @error('passing_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="min_attendance_percent" class="form-label">
                                Minimum Attendance <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="min_attendance_percent"
                                    id="min_attendance_percent"
                                    class="form-control @error('min_attendance_percent') is-invalid @enderror"
                                    value="{{ old('min_attendance_percent', $template->min_attendance_percent ?? 80) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text">%</span>

                                @error('min_attendance_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="min_progress_percent" class="form-label">
                                Minimum Progress <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="min_progress_percent"
                                    id="min_progress_percent"
                                    class="form-control @error('min_progress_percent') is-invalid @enderror"
                                    value="{{ old('min_progress_percent', $template->min_progress_percent ?? 80) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text">%</span>

                                @error('min_progress_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Template Settings</h5>
                            <p class="content-card-subtitle mb-0">
                                Status dan kebutuhan final project.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="mb-3">
                            <label for="requires_final_project" class="form-label">
                                Final Project Requirement
                            </label>
                            <select
                                name="requires_final_project"
                                id="requires_final_project"
                                class="form-select @error('requires_final_project') is-invalid @enderror"
                            >
                                <option
                                    value="1"
                                    {{ old('requires_final_project', (int) ($template->requires_final_project ?? 1)) == 1 ? 'selected' : '' }}
                                >
                                    Required
                                </option>
                                <option
                                    value="0"
                                    {{ old('requires_final_project', (int) ($template->requires_final_project ?? 1)) == 0 ? 'selected' : '' }}
                                >
                                    Not Required
                                </option>
                            </select>

                            @error('requires_final_project')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="is_active" class="form-label">Status</label>
                            <select
                                name="is_active"
                                id="is_active"
                                class="form-select @error('is_active') is-invalid @enderror"
                            >
                                <option
                                    value="1"
                                    {{ old('is_active', (int) ($template->is_active ?? 1)) == 1 ? 'selected' : '' }}
                                >
                                    Active
                                </option>
                                <option
                                    value="0"
                                    {{ old('is_active', (int) ($template->is_active ?? 1)) == 0 ? 'selected' : '' }}
                                >
                                    Inactive
                                </option>
                            </select>

                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                    <a href="{{ route('academic.assessment-templates.index') }}" class="btn btn-outline-secondary btn-modern">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-save me-2"></i>
                        {{ $isEdit ? 'Update Template' : 'Save Template' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection