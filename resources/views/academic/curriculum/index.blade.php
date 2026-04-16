@extends('layouts.app-dashboard')

@section('title', 'Manage Curriculum')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Manage Curriculum</h1>
                <p class="page-subtitle mb-0">
                    Kelola struktur pembelajaran berdasarkan <strong>Program</strong>, <strong>Stage</strong>,
                    <strong>Module</strong>, <strong>Topic</strong>, dan <strong>Sub Topic</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-light btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#stageModal"
                    data-mode="create"
                >
                    <i class="bi bi-diagram-3 me-2"></i>Add Stage
                </button>

                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#moduleModal"
                    data-mode="create"
                >
                    <i class="bi bi-folder-plus me-2"></i>Add Module
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                    <div>
                        <div class="stat-title">Programs</div>
                        <div class="stat-value">{{ $stats['programs'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total program yang aktif di sistem.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div>
                        <div class="stat-title">Stages</div>
                        <div class="stat-value">{{ $stats['stages'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Pembagian Intro, Core, dan Advance di seluruh program.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div>
                        <div class="stat-title">Modules</div>
                        <div class="stat-value">{{ $stats['modules'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah module aktif di semua stage.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Sub Topics</div>
                        <div class="stat-value">{{ $stats['sub_topics'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Unit bahasan terkecil yang nanti di-checklist instructor.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Curriculum</h5>
                <p class="content-card-subtitle mb-0">Pilih program atau cari struktur kurikulum tertentu.</p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('curriculum.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">Program</label>
                        <select name="program_id" class="form-select">
                            <option value="">All Programs</option>
                            @foreach($programs ?? [] as $program)
                                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari stage, module, topic, atau sub topic..."
                        >
                    </div>

                    <div class="col-lg-4">
                        <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                            <a href="{{ route('curriculum.index') }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Curriculum Structure</h5>
                <p class="content-card-subtitle mb-0">
                    Struktur disusun per program, lalu dibagi ke stage, module, topic, dan sub topic.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @php
                $curriculumPrograms = $curriculumPrograms ?? collect();
            @endphp

            @if($curriculumPrograms->count())
                <div class="program-stack">
                    @foreach($curriculumPrograms as $program)
                        <div class="program-block">
                            <div class="program-block-header">
                                <div class="program-block-info">
                                    <div class="program-badge">
                                        <i class="bi bi-mortarboard-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="program-name mb-1">{{ $program->name }}</h5>
                                        <div class="program-meta">
                                            <span>{{ $program->stages->count() }} Stages</span>
                                            <span>•</span>
                                            <span>{{ $program->modules_count ?? 0 }} Modules</span>
                                            <span>•</span>
                                            <span>{{ $program->topics_count ?? 0 }} Topics</span>
                                            <span>•</span>
                                            <span>{{ $program->sub_topics_count ?? 0 }} Sub Topics</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="program-block-actions">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#stageModal"
                                        data-mode="create"
                                        data-program-id="{{ $program->id }}"
                                        data-program-name="{{ $program->name }}"
                                    >
                                        <i class="bi bi-diagram-3 me-1"></i>Add Stage
                                    </button>
                                </div>
                            </div>

                            @if($program->stages->count())
                                <div class="stage-stack">
                                    @foreach($program->stages as $stage)
                                        <div class="stage-card">
                                            <div class="stage-card-header">
                                                <div>
                                                    <div class="stage-title">
                                                        <span class="level-badge level-stage">Stage</span>
                                                        {{ $stage->name }}
                                                    </div>
                                                    <div class="stage-meta">
                                                        {{ $stage->modules_count ?? $stage->modules->count() }} Modules
                                                    </div>
                                                </div>

                                                <div class="stage-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#stageModal"
                                                        data-mode="edit"
                                                        data-id="{{ $stage->id }}"
                                                        data-program-id="{{ $stage->program_id }}"
                                                        data-name="{{ $stage->name }}"
                                                        data-sort-order="{{ $stage->sort_order }}"
                                                        data-description="{{ $stage->description }}"
                                                        data-is-active="{{ (int) $stage->is_active }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#moduleModal"
                                                        data-mode="create"
                                                        data-stage-id="{{ $stage->id }}"
                                                        data-stage-name="{{ $stage->name }}"
                                                    >
                                                        <i class="bi bi-folder-plus me-1"></i>Add Module
                                                    </button>
                                                </div>
                                            </div>

                                            @if($stage->modules->count())
                                                <div class="accordion curriculum-accordion" id="stageAccordion{{ $stage->id }}">
                                                    @foreach($stage->modules as $module)
                                                        <div class="accordion-item curriculum-module-item">
                                                            <div class="accordion-header custom-module-header" id="moduleHeading{{ $module->id }}">
                                                                <div class="module-row">
                                                                    <button
                                                                        class="accordion-button module-toggle {{ $loop->first ? '' : 'collapsed' }}"
                                                                        type="button"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#moduleCollapse{{ $module->id }}"
                                                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                                        aria-controls="moduleCollapse{{ $module->id }}"
                                                                    >
                                                                        <div class="module-main">
                                                                            <div class="module-title">
                                                                                <span class="level-badge">Module</span>
                                                                                {{ $module->name }}
                                                                            </div>
                                                                            <div class="module-meta">
                                                                                {{ $module->topics_count ?? $module->topics->count() }} Topics
                                                                            </div>
                                                                        </div>
                                                                    </button>

                                                                    <div class="module-actions">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-outline-secondary btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#moduleModal"
                                                                            data-mode="edit"
                                                                            data-id="{{ $module->id }}"
                                                                            data-program-stage-id="{{ $module->program_stage_id }}"
                                                                            data-name="{{ $module->name }}"
                                                                            data-sort-order="{{ $module->sort_order }}"
                                                                            data-description="{{ $module->description }}"
                                                                            data-is-active="{{ (int) $module->is_active }}"
                                                                        >
                                                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                                                        </button>

                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-outline-primary btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#topicModal"
                                                                            data-mode="create"
                                                                            data-module-id="{{ $module->id }}"
                                                                            data-module-name="{{ $module->name }}"
                                                                        >
                                                                            <i class="bi bi-plus-circle me-1"></i>Add Topic
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div
                                                                id="moduleCollapse{{ $module->id }}"
                                                                class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                                                aria-labelledby="moduleHeading{{ $module->id }}"
                                                                data-bs-parent="#stageAccordion{{ $stage->id }}"
                                                            >
                                                                <div class="accordion-body">
                                                                    @if($module->topics->count())
                                                                        <div class="topic-list">
                                                                            @foreach($module->topics as $topic)
                                                                                <div class="topic-card">
                                                                                    <div class="topic-card-header">
                                                                                        <div>
                                                                                            <div class="topic-title">
                                                                                                <span class="level-badge level-topic">Topic</span>
                                                                                                {{ $topic->name }}
                                                                                            </div>
                                                                                            <div class="topic-meta">
                                                                                                {{ $topic->sub_topics_count ?? $topic->subTopics->count() }} Sub Topics
                                                                                            </div>
                                                                                        </div>

                                                                                        <div class="topic-actions">
                                                                                            <button
                                                                                                type="button"
                                                                                                class="btn btn-outline-secondary btn-sm"
                                                                                                data-bs-toggle="modal"
                                                                                                data-bs-target="#topicModal"
                                                                                                data-mode="edit"
                                                                                                data-id="{{ $topic->id }}"
                                                                                                data-module-id="{{ $topic->module_id }}"
                                                                                                data-name="{{ $topic->name }}"
                                                                                                data-sort-order="{{ $topic->sort_order }}"
                                                                                                data-description="{{ $topic->description }}"
                                                                                                data-is-active="{{ (int) $topic->is_active }}"
                                                                                            >
                                                                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                                                                            </button>

                                                                                            <button
                                                                                                type="button"
                                                                                                class="btn btn-outline-primary btn-sm"
                                                                                                data-bs-toggle="modal"
                                                                                                data-bs-target="#subTopicModal"
                                                                                                data-mode="create"
                                                                                                data-topic-id="{{ $topic->id }}"
                                                                                                data-topic-name="{{ $topic->name }}"
                                                                                            >
                                                                                                <i class="bi bi-plus-lg me-1"></i>Add Sub Topic
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>

                                                                                    @if($topic->subTopics->count())
                                                                                        <div class="subtopic-list">
                                                                                            @foreach($topic->subTopics as $subTopic)
                                                                                                <div class="subtopic-item">
                                                                                                    <div class="subtopic-left">
                                                                                                        <span class="subtopic-dot"></span>
                                                                                                        <div>
                                                                                                            <div class="subtopic-title">
                                                                                                                <span class="level-badge level-subtopic">Sub Topic</span>
                                                                                                                {{ $subTopic->name }}
                                                                                                            </div>
                                                                                                            @if(!empty($subTopic->description))
                                                                                                                <div class="subtopic-description">
                                                                                                                    {{ $subTopic->description }}
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>

                                                                                                    <div class="subtopic-actions">
                                                                                                        <button
                                                                                                            type="button"
                                                                                                            class="btn btn-outline-secondary btn-sm"
                                                                                                            data-bs-toggle="modal"
                                                                                                            data-bs-target="#subTopicModal"
                                                                                                            data-mode="edit"
                                                                                                            data-id="{{ $subTopic->id }}"
                                                                                                            data-topic-id="{{ $subTopic->topic_id }}"
                                                                                                            data-name="{{ $subTopic->name }}"
                                                                                                            data-sort-order="{{ $subTopic->sort_order }}"
                                                                                                            data-description="{{ $subTopic->description }}"
                                                                                                            data-is-active="{{ (int) $subTopic->is_active }}"
                                                                                                        >
                                                                                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                                                                                        </button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="empty-nested-state">
                                                                                            Belum ada sub topic pada topic ini.
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <div class="empty-nested-state">
                                                                            Belum ada topic pada module ini.
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="empty-program-state">
                                                    Belum ada module pada stage ini.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-program-state">
                                    Belum ada stage untuk program ini.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <h5 class="empty-state-title">Curriculum belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada struktur curriculum yang bisa ditampilkan. Mulai dari menambahkan stage,
                        lalu module, topic, dan sub topic.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#stageModal"
                        data-mode="create"
                    >
                        <i class="bi bi-diagram-3 me-2"></i>Add First Stage
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="stageModal" tabindex="-1" aria-labelledby="stageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="stageForm" data-create-url="{{ route('curriculum.stages.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="stageModalLabel">Add Stage</h5>
                        <p class="text-muted mb-0" id="stageModalSubtitle">Tambahkan stage seperti Intro, Core, atau Advance.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Program</label>
                            <select name="program_id" class="form-select" required>
                                <option value="">Select Program</option>
                                @foreach($programs ?? [] as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Stage Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Intro" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Order</label>
                            <input type="number" name="sort_order" class="form-control" min="1" value="1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Deskripsi singkat stage..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">Save Stage</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="moduleForm" data-create-url="{{ route('curriculum.modules.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="moduleModalLabel">Add Module</h5>
                        <p class="text-muted mb-0" id="moduleModalSubtitle">Tambahkan module baru ke stage yang dipilih.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Stage</label>
                            <select name="program_stage_id" class="form-select" required>
                                <option value="">Select Stage</option>
                                @foreach($allStages ?? [] as $stage)
                                    <option value="{{ $stage->id }}">
                                        {{ $stage->program->name ?? 'Program' }} - {{ $stage->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Module Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Module 1 - Fundamentals" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Order</label>
                            <input type="number" name="sort_order" class="form-control" min="1" value="1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Deskripsi singkat module..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">Save Module</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="topicModal" tabindex="-1" aria-labelledby="topicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="topicForm" data-create-url="{{ route('curriculum.topics.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="topicModalLabel">Add Topic</h5>
                        <p class="text-muted mb-0" id="topicModalSubtitle">Tambahkan topic baru ke module yang dipilih.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Module</label>
                            <select name="module_id" class="form-select" required>
                                <option value="">Select Module</option>
                                @foreach($allModules ?? [] as $module)
                                    <option value="{{ $module->id }}">
                                        {{ $module->stage->program->name ?? 'Program' }} - {{ $module->stage->name ?? 'Stage' }} - {{ $module->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Order</label>
                            <input type="number" name="sort_order" class="form-control" min="1" value="1">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Topic Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Basic HTML Structure" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Deskripsi singkat topic..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">Save Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="subTopicModal" tabindex="-1" aria-labelledby="subTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="subTopicForm" data-create-url="{{ route('curriculum.sub-topics.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="subTopicModalLabel">Add Sub Topic</h5>
                        <p class="text-muted mb-0" id="subTopicModalSubtitle">Tambahkan sub topic sebagai unit checklist instructor.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Topic</label>
                            <select name="topic_id" class="form-select" required>
                                <option value="">Select Topic</option>
                                @foreach($allTopics ?? [] as $topic)
                                    <option value="{{ $topic->id }}">
                                        {{ $topic->module->name ?? 'Module' }} - {{ $topic->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Order</label>
                            <input type="number" name="sort_order" class="form-control" min="1" value="1">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Sub Topic Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Menjelaskan struktur dasar HTML" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Catatan tambahan untuk sub topic..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">Save Sub Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetFormState(form) {
        form.reset();

        const idInput = form.querySelector('input[name="id"]');
        const methodInput = form.querySelector('input[name="_method"]');
        const alertBox = form.querySelector('.form-alert');
        const submitBtn = form.querySelector('.submit-btn');

        if (idInput) idInput.value = '';
        if (methodInput) methodInput.value = 'POST';

        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
        }

        if (submitBtn) {
            if (!submitBtn.dataset.defaultText) {
                submitBtn.dataset.defaultText = submitBtn.innerHTML;
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.defaultText;
        }
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

    async function bindAsyncForm(form, updateUrlBuilder) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.submit-btn');
            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const createUrl = form.dataset.createUrl;
            const actionUrl = method === 'PUT' ? updateUrlBuilder(id) : createUrl;
            const formData = new FormData(form);

            const alertBox = form.querySelector('.form-alert');
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            if (submitBtn) {
                if (!submitBtn.dataset.defaultText) {
                    submitBtn.dataset.defaultText = submitBtn.innerHTML;
                }
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';
            }

            if (method === 'PUT') {
                formData.set('_method', 'PUT');
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422) {
                        showErrors(form, data.errors || {});
                    } else {
                        showErrors(form, { general: [data.message || 'Terjadi kesalahan pada server.'] });
                    }

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.dataset.defaultText;
                    }

                    return;
                }

                window.location.reload();
            } catch (error) {
                showErrors(form, { general: ['Gagal menghubungi server.'] });

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.defaultText;
                }
            }
        });
    }

    function setupStageModal() {
        const modalEl = document.getElementById('stageModal');
        const form = document.getElementById('stageForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#stageModalLabel');
            const subtitle = modalEl.querySelector('#stageModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Stage';
                subtitle.textContent = 'Perbarui data stage.';
                submitBtn.innerHTML = 'Update Stage';
                submitBtn.dataset.defaultText = 'Update Stage';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="program_id"]').value = button.dataset.programId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Stage';
                subtitle.textContent = 'Tambahkan stage seperti Intro, Core, atau Advance.';
                submitBtn.innerHTML = 'Save Stage';
                submitBtn.dataset.defaultText = 'Save Stage';

                if (button?.dataset?.programId) {
                    form.querySelector('select[name="program_id"]').value = button.dataset.programId;
                }
            }
        });

        bindAsyncForm(form, function (id) {
            return `{{ route('curriculum.stages.update', ['stage' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    function setupModuleModal() {
        const modalEl = document.getElementById('moduleModal');
        const form = document.getElementById('moduleForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#moduleModalLabel');
            const subtitle = modalEl.querySelector('#moduleModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Module';
                subtitle.textContent = 'Perbarui data module.';
                submitBtn.innerHTML = 'Update Module';
                submitBtn.dataset.defaultText = 'Update Module';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="program_stage_id"]').value = button.dataset.programStageId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Module';
                subtitle.textContent = 'Tambahkan module baru ke stage yang dipilih.';
                submitBtn.innerHTML = 'Save Module';
                submitBtn.dataset.defaultText = 'Save Module';

                if (button?.dataset?.stageId) {
                    form.querySelector('select[name="program_stage_id"]').value = button.dataset.stageId;
                }
            }
        });

        bindAsyncForm(form, function (id) {
            return `{{ route('curriculum.modules.update', ['module' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    function setupTopicModal() {
        const modalEl = document.getElementById('topicModal');
        const form = document.getElementById('topicForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#topicModalLabel');
            const subtitle = modalEl.querySelector('#topicModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Topic';
                subtitle.textContent = 'Perbarui data topic.';
                submitBtn.innerHTML = 'Update Topic';
                submitBtn.dataset.defaultText = 'Update Topic';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="module_id"]').value = button.dataset.moduleId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Topic';
                subtitle.textContent = 'Tambahkan topic baru ke module yang dipilih.';
                submitBtn.innerHTML = 'Save Topic';
                submitBtn.dataset.defaultText = 'Save Topic';

                if (button?.dataset?.moduleId) {
                    form.querySelector('select[name="module_id"]').value = button.dataset.moduleId;
                }
            }
        });

        bindAsyncForm(form, function (id) {
            return `{{ route('curriculum.topics.update', ['topic' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    function setupSubTopicModal() {
        const modalEl = document.getElementById('subTopicModal');
        const form = document.getElementById('subTopicForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#subTopicModalLabel');
            const subtitle = modalEl.querySelector('#subTopicModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Sub Topic';
                subtitle.textContent = 'Perbarui data sub topic.';
                submitBtn.innerHTML = 'Update Sub Topic';
                submitBtn.dataset.defaultText = 'Update Sub Topic';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="topic_id"]').value = button.dataset.topicId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Sub Topic';
                subtitle.textContent = 'Tambahkan sub topic sebagai unit checklist instructor.';
                submitBtn.innerHTML = 'Save Sub Topic';
                submitBtn.dataset.defaultText = 'Save Sub Topic';

                if (button?.dataset?.topicId) {
                    form.querySelector('select[name="topic_id"]').value = button.dataset.topicId;
                }
            }
        });

        bindAsyncForm(form, function (id) {
            return `{{ route('curriculum.sub-topics.update', ['subTopic' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    setupStageModal();
    setupModuleModal();
    setupTopicModal();
    setupSubTopicModal();
});
</script>
@endpush