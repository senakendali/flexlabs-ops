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
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Topics</div>
                        <div class="stat-value">{{ $stats['topics'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah topik pembelajaran di seluruh module.</div>
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

                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteConfirmModal"
                                                                            data-delete-type="Module"
                                                                            data-delete-name="{{ $module->name }}"
                                                                            data-delete-url="{{ Route::has('curriculum.modules.destroy') ? route('curriculum.modules.destroy', $module->id) : '' }}"
                                                                            data-delete-warning="Semua topic dan sub topic di dalam module ini juga akan ikut terhapus."
                                                                        >
                                                                            <i class="bi bi-trash me-1"></i>Delete
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
                                                                                                data-slide-url="{{ $topic->slide_url ?? '' }}"
                                                                                                data-starter-code-url="{{ $topic->starter_code_url ?? '' }}"
                                                                                                data-supporting-file-url="{{ $topic->supporting_file_url ?? '' }}"
                                                                                                data-external-reference-url="{{ $topic->external_reference_url ?? '' }}"
                                                                                                data-practice-brief="{{ $topic->practice_brief ?? '' }}"
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

                                                                                            <button
                                                                                                type="button"
                                                                                                class="btn btn-outline-danger btn-sm"
                                                                                                data-bs-toggle="modal"
                                                                                                data-bs-target="#deleteConfirmModal"
                                                                                                data-delete-type="Topic"
                                                                                                data-delete-name="{{ $topic->name }}"
                                                                                                data-delete-url="{{ Route::has('curriculum.topics.destroy') ? route('curriculum.topics.destroy', $topic->id) : '' }}"
                                                                                                data-delete-warning="Semua sub topic di dalam topic ini juga akan ikut terhapus."
                                                                                            >
                                                                                                <i class="bi bi-trash me-1"></i>Delete
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>

                                                                                    @php
                                                                                        $topicMaterialCount = collect([
                                                                                            $topic->slide_url ?? null,
                                                                                            $topic->starter_code_url ?? null,
                                                                                            $topic->supporting_file_url ?? null,
                                                                                            $topic->external_reference_url ?? null,
                                                                                            $topic->practice_brief ?? null,
                                                                                        ])->filter()->count();
                                                                                    @endphp

                                                                                    <div class="topic-material-strip">
                                                                                        <div class="topic-material-info">
                                                                                            <span class="material-pill {{ !empty($topic->slide_url) ? 'is-ready' : '' }}">
                                                                                                <i class="bi bi-file-earmark-slides me-1"></i>Slide
                                                                                            </span>

                                                                                            <span class="material-pill {{ !empty($topic->starter_code_url) ? 'is-ready' : '' }}">
                                                                                                <i class="bi bi-code-slash me-1"></i>Starter Code
                                                                                            </span>

                                                                                            <span class="material-pill {{ !empty($topic->supporting_file_url) ? 'is-ready' : '' }}">
                                                                                                <i class="bi bi-paperclip me-1"></i>Supporting File
                                                                                            </span>

                                                                                            <span class="material-pill {{ !empty($topic->external_reference_url) ? 'is-ready' : '' }}">
                                                                                                <i class="bi bi-link-45deg me-1"></i>Reference
                                                                                            </span>

                                                                                            <span class="material-pill {{ !empty($topic->practice_brief) ? 'is-ready' : '' }}">
                                                                                                <i class="bi bi-clipboard-check me-1"></i>Practice Brief
                                                                                            </span>
                                                                                        </div>

                                                                                        <div class="topic-material-count">
                                                                                            {{ $topicMaterialCount }} / 5 materials ready
                                                                                        </div>
                                                                                    </div>

                                                                                    @if($topic->subTopics->count())
                                                                                        <div class="subtopic-list">
                                                                                            @foreach($topic->subTopics as $subTopic)
                                                                                                <div class="subtopic-item">
                                                                                                    <div class="subtopic-left">
                                                                                                        <div class="subtopic-thumb">
                                                                                                            @if(($subTopic->lesson_type ?? 'video') === 'live_session')
                                                                                                                <div class="subtopic-thumb-placeholder live">
                                                                                                                    <i class="bi bi-broadcast"></i>
                                                                                                                </div>
                                                                                                            @elseif(!empty($subTopic->video_url))
                                                                                                                @if(!empty($subTopic->thumbnail_url))
                                                                                                                    <img
                                                                                                                        src="{{ $subTopic->thumbnail_url }}"
                                                                                                                        alt="{{ $subTopic->name }}"
                                                                                                                        class="subtopic-thumb-img"
                                                                                                                    >
                                                                                                                @else
                                                                                                                    <div class="subtopic-thumb-placeholder video">
                                                                                                                        <i class="bi bi-play-circle"></i>
                                                                                                                    </div>
                                                                                                                @endif
                                                                                                            @else
                                                                                                                <div class="subtopic-thumb-placeholder empty">
                                                                                                                    <i class="bi bi-camera-video-off"></i>
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>

                                                                                                        <div class="subtopic-content">
                                                                                                            <div class="subtopic-title">
                                                                                                                <span class="level-badge level-subtopic">Sub Topic</span>

                                                                                                                @if(($subTopic->lesson_type ?? 'video') === 'live_session')
                                                                                                                    <span class="lesson-type-badge lesson-type-live">
                                                                                                                        <i class="bi bi-broadcast me-1"></i>Live Session
                                                                                                                    </span>
                                                                                                                @else
                                                                                                                    <span class="lesson-type-badge lesson-type-video">
                                                                                                                        <i class="bi bi-play-circle me-1"></i>Video
                                                                                                                    </span>
                                                                                                                @endif

                                                                                                                {{ $subTopic->name }}
                                                                                                            </div>

                                                                                                            <div class="subtopic-learning-meta">
                                                                                                                @if(($subTopic->lesson_type ?? 'video') === 'video')
                                                                                                                    @if(!empty($subTopic->video_url))
                                                                                                                        @if(!empty($subTopic->video_duration_minutes))
                                                                                                                            <span>
                                                                                                                                <i class="bi bi-clock me-1"></i>{{ $subTopic->video_duration_minutes }} min
                                                                                                                            </span>
                                                                                                                        @endif
                                                                                                                    @else
                                                                                                                        <span class="text-warning">
                                                                                                                            <i class="bi bi-exclamation-triangle me-1"></i>Belum ada video
                                                                                                                        </span>
                                                                                                                    @endif
                                                                                                                @else
                                                                                                                    <span>
                                                                                                                        <i class="bi bi-calendar-event me-1"></i>Live schedule mengikuti batch/session
                                                                                                                    </span>
                                                                                                                @endif
                                                                                                            </div>
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
                                                                                                            data-lesson-type="{{ $subTopic->lesson_type ?? 'video' }}"
                                                                                                            data-video-url="{{ $subTopic->video_url ?? '' }}"
                                                                                                            data-video-duration-minutes="{{ $subTopic->video_duration_minutes ?? '' }}"
                                                                                                            data-thumbnail-url="{{ $subTopic->thumbnail_url ?? '' }}"
                                                                                                        >
                                                                                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                                                                                        </button>

                                                                                                        <button
                                                                                                            type="button"
                                                                                                            class="btn btn-outline-danger btn-sm"
                                                                                                            data-bs-toggle="modal"
                                                                                                            data-bs-target="#deleteConfirmModal"
                                                                                                            data-delete-type="Sub Topic"
                                                                                                            data-delete-name="{{ $subTopic->name }}"
                                                                                                            data-delete-url="{{ Route::has('curriculum.sub-topics.destroy') ? route('curriculum.sub-topics.destroy', $subTopic->id) : '' }}"
                                                                                                            data-delete-warning="Sub topic ini akan dihapus dari struktur curriculum."
                                                                                                        >
                                                                                                            <i class="bi bi-trash me-1"></i>Delete
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
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="topicForm" data-create-url="{{ route('curriculum.topics.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="topicModalLabel">Add Topic</h5>
                        <p class="text-muted mb-0" id="topicModalSubtitle">
                            Tambahkan topic baru ke module yang dipilih.
                        </p>
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
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Project Flow Review" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Deskripsi singkat topic..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="lesson-config-card">
                                <div class="lesson-config-header">
                                    <div>
                                        <div class="lesson-config-title">
                                            <i class="bi bi-folder2-open me-2"></i>Topic Materials
                                        </div>
                                        <div class="lesson-config-subtitle">
                                            Materi utama untuk satu topic. Cocok untuk slide, starter code, file pendukung, dan practice brief.
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Slide URL</label>
                                        <input
                                            type="url"
                                            name="slide_url"
                                            class="form-control"
                                            placeholder="https://drive.google.com/..."
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Starter Code URL</label>
                                        <input
                                            type="url"
                                            name="starter_code_url"
                                            class="form-control"
                                            placeholder="https://github.com/... atau link file"
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Supporting File URL</label>
                                        <input
                                            type="url"
                                            name="supporting_file_url"
                                            class="form-control"
                                            placeholder="https://drive.google.com/..."
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">External Reference URL</label>
                                        <input
                                            type="url"
                                            name="external_reference_url"
                                            class="form-control"
                                            placeholder="https://developer.mozilla.org/..."
                                        >
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Practice Brief</label>
                                        <textarea
                                            name="practice_brief"
                                            rows="4"
                                            class="form-control"
                                            placeholder="Instruksi latihan/practice untuk topic ini..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
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
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="subTopicForm" data-create-url="{{ route('curriculum.sub-topics.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="subTopicModalLabel">Add Sub Topic</h5>
                        <p class="text-muted mb-0" id="subTopicModalSubtitle">
                            Tambahkan sub topic sebagai unit learning item dan checklist instructor.
                        </p>
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
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Product system flow" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lesson Type</label>
                            <select name="lesson_type" class="form-select">
                                <option value="video">Video Lesson</option>
                                <option value="live_session">Live Session</option>
                            </select>
                            <div class="form-text">
                                Tipe ini dipakai untuk membedakan item belajar video atau live session.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 subtopic-video-fields">
                            <div class="lesson-config-card">
                                <div class="lesson-config-header">
                                    <div>
                                        <div class="lesson-config-title">
                                            <i class="bi bi-play-circle me-2"></i>Video Lesson
                                        </div>
                                        <div class="lesson-config-subtitle">
                                            Isi informasi video jika sub topic ini adalah video lesson.
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Video URL</label>
                                        <input
                                            type="url"
                                            name="video_url"
                                            class="form-control"
                                            placeholder="https://www.youtube.com/watch?v=..."
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Duration Minutes</label>
                                        <input
                                            type="number"
                                            name="video_duration_minutes"
                                            class="form-control"
                                            min="1"
                                            placeholder="60"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Thumbnail URL</label>
                                        <input
                                            type="url"
                                            name="thumbnail_url"
                                            class="form-control"
                                            placeholder="https://..."
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 subtopic-live-fields d-none">
                            <div class="lesson-config-card lesson-config-live">
                                <div class="lesson-config-header">
                                    <div>
                                        <div class="lesson-config-title">
                                            <i class="bi bi-broadcast me-2"></i>Live Session
                                        </div>
                                        <div class="lesson-config-subtitle">
                                            Sub topic ini akan ditandai sebagai live session. Jadwal real-nya bisa diatur dari schedule/batch.
                                        </div>
                                    </div>
                                </div>

                                <div class="live-session-note">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Untuk UI curriculum, kita hanya menandai tipe lesson-nya dulu. Detail jadwal tidak diisi di sini.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Catatan tambahan untuk sub topic..."></textarea>
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

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Item</h5>
                        <p class="text-muted mb-0" id="deleteConfirmSubtitle">
                            Konfirmasi sebelum menghapus data.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Item yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3" id="deleteConfirmWarning">
                    Data yang sudah dihapus tidak bisa dikembalikan.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .program-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .program-block {
        border: 1px solid #ece7f7;
        border-radius: 20px;
        background: #ffffff;
        overflow: hidden;
    }

    .program-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem 1.25rem 1rem;
        border-bottom: 1px solid #f1ecf8;
        background: linear-gradient(180deg, #fcfbff 0%, #ffffff 100%);
    }

    .program-block-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .program-badge {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #efe8ff;
        color: #5B3E8E;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .program-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }

    .program-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
        font-size: .875rem;
        color: #6b7280;
    }

    .stage-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
    }

    .stage-card {
        border: 1px solid #ece7f7;
        border-radius: 18px;
        background: #fbfaff;
        overflow: hidden;
    }

    .stage-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1rem 0;
    }

    .stage-title,
    .module-title,
    .topic-title,
    .subtopic-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        font-weight: 700;
        color: #1f2937;
    }

    .stage-meta,
    .module-meta,
    .topic-meta {
        margin-top: .35rem;
        font-size: .875rem;
        color: #6b7280;
    }

    .level-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .25rem .55rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
        background: #ebe5ff;
        color: #5B3E8E;
        border: 1px solid #dfd2ff;
        white-space: nowrap;
    }

    .level-stage {
        background: #fff3d9;
        color: #b7791f;
        border-color: #f6ddab;
    }

    .level-topic {
        background: #e8f4ff;
        color: #1d4ed8;
        border-color: #cfe3ff;
    }

    .level-subtopic {
        background: #ecfdf3;
        color: #15803d;
        border-color: #ccf2da;
    }

    .curriculum-accordion {
        padding: 1rem;
    }

    .curriculum-module-item {
        border: 1px solid #ece7f7 !important;
        border-radius: 16px !important;
        overflow: hidden;
        margin-bottom: 1rem;
        background: #ffffff;
    }

    .curriculum-module-item:last-child {
        margin-bottom: 0;
    }

    .custom-module-header {
        padding: 0;
        border: 0;
        background: #fff;
    }

    .module-row {
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
    }

    .module-toggle {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        padding: 1rem 1.1rem;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .module-toggle:not(.collapsed) {
        box-shadow: none !important;
        color: inherit;
    }

    .module-toggle::after {
        margin-left: auto;
        flex-shrink: 0;
    }

    .module-main {
        display: flex;
        flex-direction: column;
        min-width: 0;
        text-align: left;
    }

    .module-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: 1rem 1rem 1rem 0;
        flex-shrink: 0;
    }

    .stage-actions,
    .topic-actions,
    .subtopic-actions {
        display: flex;
        gap: .5rem;
        align-items: center;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .module-actions .btn,
    .stage-actions .btn,
    .topic-actions .btn,
    .subtopic-actions .btn,
    .program-block-actions .btn {
        white-space: nowrap;
    }

    .topic-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .topic-card {
        border: 1px solid #ece7f7;
        border-radius: 16px;
        background: #fcfcff;
        padding: 1rem;
    }

    .topic-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .topic-material-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid #ece7f7;
        border-radius: 14px;
        background: #ffffff;
    }

    .topic-material-info {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .material-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .3rem .65rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .material-pill.is-ready {
        background: #ecfdf3;
        color: #15803d;
        border-color: #bbf7d0;
    }

    .topic-material-count {
        font-size: .8rem;
        font-weight: 700;
        color: #5B3E8E;
        white-space: nowrap;
    }

    .subtopic-list {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .subtopic-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: .875rem 1rem;
        border: 1px solid #ece7f7;
        border-radius: 14px;
        background: #fff;
    }

    .subtopic-left {
        display: flex;
        align-items: flex-start;
        gap: .9rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .subtopic-content {
        min-width: 0;
        flex: 1 1 auto;
        padding-top: .1rem;
    }

    .subtopic-thumb {
        width: 112px;
        height: 68px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #ece7f7;
        background: #f9f7ff;
        flex-shrink: 0;
    }

    .subtopic-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .subtopic-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.55rem;
    }

    .subtopic-thumb-placeholder.video {
        background: #ede7ff;
        color: #5B3E8E;
    }

    .subtopic-thumb-placeholder.live {
        background: #fff3d9;
        color: #b7791f;
    }

    .subtopic-thumb-placeholder.empty {
        background: #f3f4f6;
        color: #9ca3af;
    }

    .lesson-type-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .25rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .lesson-type-video {
        background: #ede7ff;
        color: #5B3E8E;
        border: 1px solid #d9cffa;
    }

    .lesson-type-live {
        background: #fff3d9;
        color: #b7791f;
        border: 1px solid #f6ddab;
    }

    .subtopic-learning-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .65rem;
        margin-top: .4rem;
        color: #6b7280;
        font-size: .84rem;
    }

    .lesson-config-card {
        border: 1px solid #ece7f7;
        border-radius: 16px;
        background: #fbfaff;
        padding: 1rem;
    }

    .lesson-config-live {
        background: #fffaf0;
        border-color: #f6ddab;
    }

    .lesson-config-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .lesson-config-title {
        font-weight: 800;
        color: #1f2937;
    }

    .lesson-config-subtitle {
        margin-top: .25rem;
        font-size: .875rem;
        color: #6b7280;
        line-height: 1.5;
    }

    .live-session-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        padding: .875rem 1rem;
        border-radius: 14px;
        background: #ffffff;
        color: #7c520c;
        font-size: .9rem;
        line-height: 1.5;
    }

    .delete-confirm-modal {
        border-radius: 22px;
    }

    .delete-confirm-heading {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .delete-confirm-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .delete-confirm-message {
        padding: 1rem;
        border: 1px solid #fee2e2;
        border-radius: 16px;
        background: #fff7f7;
    }

    .delete-confirm-label {
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #991b1b;
        margin-bottom: .35rem;
    }

    .delete-confirm-name {
        font-weight: 800;
        color: #1f2937;
        line-height: 1.4;
    }

    .delete-confirm-warning {
        padding: .85rem 1rem;
        border-radius: 14px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: .9rem;
        line-height: 1.5;
    }

    .empty-program-state,
    .empty-nested-state {
        margin: 1rem;
        padding: 1rem 1.25rem;
        border: 1px dashed #d9cffa;
        border-radius: 14px;
        background: #f9f7ff;
        color: #6b7280;
        font-size: .92rem;
    }

    .empty-state-box {
        text-align: center;
        padding: 3rem 1.5rem;
        border: 1px dashed #d9cffa;
        border-radius: 18px;
        background: #faf8ff;
    }

    .empty-state-icon {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: #efe8ff;
        color: #5B3E8E;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }

    .empty-state-title {
        font-weight: 700;
        color: #1f2937;
    }

    .empty-state-text {
        max-width: 620px;
        margin: 0 auto;
        color: #6b7280;
    }

    .form-alert {
        border-radius: 14px;
        font-size: .92rem;
    }

    .custom-modal {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
    }

    .toast.bg-success {
        background-color: #5B3E8E !important;
    }

    .toast.bg-danger {
        background-color: #dc3545 !important;
    }

    @media (max-width: 991.98px) {
        .program-block-header,
        .stage-card-header,
        .topic-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-content {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .page-header-actions {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .program-block-actions,
        .topic-actions,
        .stage-actions,
        .subtopic-actions {
            width: 100%;
        }

        .module-row {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
        }

        .module-toggle {
            width: 100%;
            padding-bottom: .75rem;
        }

        .module-actions {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
            padding: 0 1rem 1rem 1rem;
        }

        .topic-actions,
        .stage-actions,
        .subtopic-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 575.98px) {
        .page-header-actions,
        .program-block-actions,
        .topic-actions,
        .stage-actions,
        .subtopic-actions,
        .module-actions {
            width: 100%;
        }

        .page-header-actions .btn,
        .program-block-actions .btn,
        .topic-actions .btn,
        .stage-actions .btn,
        .subtopic-actions .btn,
        .module-actions .btn {
            width: 100%;
        }

        .topic-material-strip {
            align-items: flex-start;
        }

        .topic-material-count {
            width: 100%;
        }

        .subtopic-item {
            flex-direction: column;
        }

        .subtopic-left {
            width: 100%;
        }

        .subtopic-thumb {
            width: 96px;
            height: 60px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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

    async function bindAsyncForm(form, modalId, updateUrlBuilder) {
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
                        showToast('Mohon cek kembali input yang wajib diisi.', 'error');
                    } else {
                        showErrors(form, { general: [data.message || 'Terjadi kesalahan pada server.'] });
                        showToast(data.message || 'Gagal menyimpan data.', 'error');
                    }

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.dataset.defaultText;
                    }

                    return;
                }

                const modalEl = document.getElementById(modalId);
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                showToast(data.message || 'Data berhasil disimpan.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showErrors(form, { general: ['Gagal menghubungi server.'] });
                showToast('Gagal menghubungi server.', 'error');

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.defaultText;
                }
            }
        });
    }

    function setupDeleteConfirmModal() {
        const modalEl = document.getElementById('deleteConfirmModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        if (!modalEl || !confirmBtn) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const deleteType = button?.dataset?.deleteType || 'Item';
            const deleteName = button?.dataset?.deleteName || '-';
            const deleteUrl = button?.dataset?.deleteUrl || '';
            const deleteWarning = button?.dataset?.deleteWarning || 'Data yang sudah dihapus tidak bisa dikembalikan.';

            modalEl.dataset.deleteUrl = deleteUrl;

            modalEl.querySelector('#deleteConfirmModalLabel').textContent = `Delete ${deleteType}`;
            modalEl.querySelector('#deleteConfirmSubtitle').textContent = `Konfirmasi sebelum menghapus ${deleteType.toLowerCase()}.`;
            modalEl.querySelector('#deleteConfirmName').textContent = deleteName;
            modalEl.querySelector('#deleteConfirmWarning').textContent = deleteWarning;

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
        });

        confirmBtn.addEventListener('click', async function () {
            const deleteUrl = modalEl.dataset.deleteUrl;

            if (!deleteUrl) {
                showToast('Route delete belum tersedia.', 'error');
                return;
            }

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Deleting...';

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showToast(data.message || 'Gagal menghapus data.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Data berhasil dihapus.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showToast('Gagal menghubungi server.', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
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

        bindAsyncForm(form, 'stageModal', function (id) {
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

        bindAsyncForm(form, 'moduleModal', function (id) {
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
                subtitle.textContent = 'Perbarui data topic dan material pembelajaran.';
                submitBtn.innerHTML = 'Update Topic';
                submitBtn.dataset.defaultText = 'Update Topic';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="module_id"]').value = button.dataset.moduleId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';

                form.querySelector('input[name="slide_url"]').value = button.dataset.slideUrl || '';
                form.querySelector('input[name="starter_code_url"]').value = button.dataset.starterCodeUrl || '';
                form.querySelector('input[name="supporting_file_url"]').value = button.dataset.supportingFileUrl || '';
                form.querySelector('input[name="external_reference_url"]').value = button.dataset.externalReferenceUrl || '';
                form.querySelector('textarea[name="practice_brief"]').value = button.dataset.practiceBrief || '';
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

        bindAsyncForm(form, 'topicModal', function (id) {
            return `{{ route('curriculum.topics.update', ['topic' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    function syncSubTopicLessonFields(form) {
        const lessonType = form.querySelector('select[name="lesson_type"]')?.value || 'video';

        const videoFields = form.querySelector('.subtopic-video-fields');
        const liveFields = form.querySelector('.subtopic-live-fields');

        if (lessonType === 'live_session') {
            videoFields?.classList.add('d-none');
            liveFields?.classList.remove('d-none');
        } else {
            videoFields?.classList.remove('d-none');
            liveFields?.classList.add('d-none');
        }
    }

    function setupSubTopicModal() {
        const modalEl = document.getElementById('subTopicModal');
        const form = document.getElementById('subTopicForm');

        if (!modalEl || !form) return;

        const lessonTypeSelect = form.querySelector('select[name="lesson_type"]');

        if (lessonTypeSelect) {
            lessonTypeSelect.addEventListener('change', function () {
                syncSubTopicLessonFields(form);
            });
        }

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

                form.querySelector('select[name="lesson_type"]').value = button.dataset.lessonType || 'video';
                form.querySelector('input[name="video_url"]').value = button.dataset.videoUrl || '';
                form.querySelector('input[name="video_duration_minutes"]').value = button.dataset.videoDurationMinutes || '';
                form.querySelector('input[name="thumbnail_url"]').value = button.dataset.thumbnailUrl || '';
            } else {
                title.textContent = 'Add Sub Topic';
                subtitle.textContent = 'Tambahkan sub topic sebagai unit learning item dan checklist instructor.';
                submitBtn.innerHTML = 'Save Sub Topic';
                submitBtn.dataset.defaultText = 'Save Sub Topic';

                form.querySelector('select[name="lesson_type"]').value = 'video';

                if (button?.dataset?.topicId) {
                    form.querySelector('select[name="topic_id"]').value = button.dataset.topicId;
                }
            }

            syncSubTopicLessonFields(form);
        });

        bindAsyncForm(form, 'subTopicModal', function (id) {
            return `{{ route('curriculum.sub-topics.update', ['subTopic' => '__ID__']) }}`.replace('__ID__', id);
        });
    }

    setupStageModal();
    setupModuleModal();
    setupTopicModal();
    setupSubTopicModal();
    setupDeleteConfirmModal();
});
</script>
@endpush