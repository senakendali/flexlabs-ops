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

    <div class="row g-3 mb-4" id="curriculumStatsRow">
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

        <div class="content-card-body" id="curriculumStructureBody">
            @php
                $curriculumPrograms = $curriculumPrograms ?? collect();
            @endphp

            @if($curriculumPrograms->count())
                <div class="program-stack">
                    @foreach($curriculumPrograms as $program)
                        <div class="program-block" data-program-block-id="{{ $program->id }}">
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
                                        <div class="stage-card" data-stage-card-id="{{ $stage->id }}">
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
                                                        <div class="accordion-item curriculum-module-item" data-module-item-id="{{ $module->id }}">
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
                                                                                <div class="topic-card" data-topic-card-id="{{ $topic->id }}">
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
                                                                                                @php
                                                                                                    $subTopicVideoProvider = $subTopic->video_provider
                                                                                                        ?: (!empty($subTopic->video_url) ? 'youtube' : (!empty($subTopic->video_path) ? 'self_hosted' : null));

                                                                                                    $hasSubTopicVideo = !empty($subTopic->video_url) || !empty($subTopic->video_path);
                                                                                                @endphp

                                                                                                <div class="subtopic-item" data-subtopic-item-id="{{ $subTopic->id }}">
                                                                                                    <div class="subtopic-left">
                                                                                                        <div class="subtopic-thumb">
                                                                                                            @if(($subTopic->lesson_type ?? 'video') === 'live_session')
                                                                                                                <div class="subtopic-thumb-placeholder live">
                                                                                                                    <i class="bi bi-broadcast"></i>
                                                                                                                </div>
                                                                                                            @elseif($hasSubTopicVideo)
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
                                                                                                                    @if($hasSubTopicVideo)
                                                                                                                        @php
                                                                                                                            $videoProviderLabel = match ($subTopicVideoProvider) {
                                                                                                                                'self_hosted' => 'Server Video',
                                                                                                                                'bunny' => 'Bunny Stream',
                                                                                                                                'youtube' => 'YouTube / External Video',
                                                                                                                                default => 'External Video',
                                                                                                                            };

                                                                                                                            $videoProviderIcon = match ($subTopicVideoProvider) {
                                                                                                                                'self_hosted' => 'bi-hdd-network',
                                                                                                                                'bunny' => 'bi-cloud-play',
                                                                                                                                'youtube' => 'bi-youtube',
                                                                                                                                default => 'bi-play-circle',
                                                                                                                            };
                                                                                                                        @endphp

                                                                                                                        <span>
                                                                                                                            <i class="bi {{ $videoProviderIcon }} me-1"></i>
                                                                                                                            {{ $videoProviderLabel }}
                                                                                                                        </span>

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

                                                                                                                @if(!empty($subTopic->content))
                                                                                                                    <span class="text-success fw-semibold">
                                                                                                                        <i class="bi bi-file-earmark-text me-1"></i>Lesson material ready
                                                                                                                    </span>
                                                                                                                @else
                                                                                                                    <span class="text-muted">
                                                                                                                        <i class="bi bi-file-earmark-text me-1"></i>No lesson material
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
                                                                                                            data-video-provider="{{ $subTopicVideoProvider ?? 'youtube' }}"
                                                                                                            data-video-url="{{ $subTopic->video_url ?? '' }}"
                                                                                                            data-video-disk="{{ $subTopic->video_disk ?? '' }}"
                                                                                                            data-video-path="{{ $subTopic->video_path ?? '' }}"
                                                                                                            data-video-mime="{{ $subTopic->video_mime ?? '' }}"
                                                                                                            data-video-size="{{ $subTopic->video_size ?? '' }}"
                                                                                                            data-video-duration-minutes="{{ $subTopic->video_duration_minutes ?? '' }}"
                                                                                                            data-video-duration-seconds="{{ $subTopic->video_duration_seconds ?? '' }}"
                                                                                                            data-thumbnail-url="{{ $subTopic->thumbnail_url ?? '' }}"
                                                                                                            data-has-video-file="{{ !empty($subTopic->video_path) ? 1 : 0 }}"
                                                                                                            data-content-base64="{{ base64_encode($subTopic->content ?? '') }}"
                                                                                                            data-content-format="{{ $subTopic->content_format ?? 'markdown' }}"
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
            <form
                id="subTopicForm"
                data-create-url="{{ route('curriculum.sub-topics.store') }}"
                enctype="multipart/form-data"
            >
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
                                            Pilih sumber video dari file yang sudah ada di server, atau upload file video baru jika diperlukan.
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Video Source</label>
                                        <select name="video_source" class="form-select">
                                            <option value="server">Get from Server</option>
                                            <option value="upload">Upload Video</option>
                                            <option value="bunny">Bunny Stream</option>
                                            <option value="youtube">YouTube / External URL</option>
                                        </select>

                                        <input type="hidden" name="video_provider" value="self_hosted">
                                        <input type="hidden" name="clear_video_file" value="0">

                                        <div class="form-text">
                                            Pilih Server/Upload untuk video lokal, Bunny Stream untuk embed Bunny, atau YouTube/External untuk link video lama.
                                        </div>
                                    </div>

                                    <div class="col-md-8 subtopic-video-server-wrap">
                                        <label class="form-label">Server Video File</label>
                                        <div class="d-flex gap-2">
                                            <select name="server_video_path" class="form-select">
                                                <option value="">Loading server videos...</option>
                                            </select>

                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-modern refresh-server-videos-btn"
                                            >
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>

                                        <div class="form-text">
                                            File dibaca dari folder <code>storage/app/private/learning-videos/sub-topics</code>.
                                        </div>

                                        <div class="selected-server-video-info d-none mt-3"></div>
                                    </div>

                                    <div class="col-12 subtopic-video-upload-wrap d-none">
                                        <label class="form-label">Upload Video File</label>
                                        <input
                                            type="file"
                                            name="video_file"
                                            class="form-control"
                                            accept="video/mp4,video/webm,video/quicktime,video/x-m4v"
                                        >

                                        <div class="form-text">
                                            Gunakan opsi ini hanya untuk file kecil/urgent. Untuk video besar, upload manual ke server lalu pilih dari daftar Get from Server.
                                        </div>

                                        <div class="selected-video-file-info d-none mt-3"></div>
                                    </div>

                                    <div class="col-12 subtopic-video-url-wrap d-none">
                                        <label class="form-label">Video URL</label>
                                        <input
                                            type="text"
                                            name="video_url"
                                            class="form-control"
                                            placeholder="Paste Bunny Video ID atau https://iframe.mediadelivery.net/embed/library-id/video-id"
                                        >

                                        <div class="form-text video-url-help">
                                            Paste Bunny Video ID saja, atau Bunny embed URL. Sistem otomatis membentuk URL embed dan membersihkan token/expires.
                                        </div>

                                        <div class="selected-video-url-info d-none mt-3"></div>
                                    </div>

                                    <div class="col-12 existing-video-file-info d-none">
                                        <div class="video-file-info-box">
                                            <div class="video-file-info-icon">
                                                <i class="bi bi-file-earmark-play"></i>
                                            </div>

                                            <div class="video-file-info-content">
                                                <div class="video-file-info-title">Video server sudah tersedia</div>
                                                <div class="video-file-info-meta existing-video-file-meta">-</div>
                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm clear-video-file-btn"
                                            >
                                                Remove
                                            </button>
                                        </div>
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

                                    <div class="col-md-9">
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

                        <div class="col-12">
                            <div class="lesson-config-card lesson-material-editor-card">
                                <div class="lesson-config-header">
                                    <div>
                                        <div class="lesson-config-title">
                                            <i class="bi bi-file-earmark-richtext me-2"></i>Lesson Material
                                        </div>
                                        <div class="lesson-config-subtitle">
                                            Materi bacaan untuk LMS. Gunakan Markdown untuk penjelasan, heading, list, dan code block yang bisa dicopy oleh student.
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="content_format" value="markdown">

                                <textarea
                                    name="content"
                                    id="subTopicContentEditor"
                                    rows="14"
                                    class="form-control markdown-editor"
                                    placeholder="Write lesson material using Markdown. You can mix explanation, headings, lists, and code blocks."
                                ></textarea>

                                <div class="markdown-helper-box mt-3">
                                    <div class="fw-bold mb-2">Format cepat:</div>
                                    <div><code># Title</code> untuk heading besar</div>
                                    <div><code>## Section</code> untuk sub heading</div>
                                    <div><code>`inline code`</code> untuk kode pendek</div>
                                    <div><code>```html ... ```</code>, <code>```css ... ```</code>, <code>```js ... ```</code>, atau <code>```php ... ```</code> untuk code block.</div>
                                </div>
                            </div>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const serverVideosUrl = "{{ Route::has('curriculum.server-videos') ? route('curriculum.server-videos') : '' }}";
    const bunnyDefaultLibraryId = "{{ config('services.bunny.stream_library_id', env('BUNNY_STREAM_LIBRARY_ID', '672159')) }}";
    let subTopicContentEditor = null;
    let serverVideoFiles = [];
    let isLoadingServerVideos = false;

    function decodeBase64Unicode(value) {
        if (!value) return '';

        try {
            const binary = atob(value);
            const bytes = Uint8Array.from(binary, function (char) {
                return char.charCodeAt(0);
            });

            return new TextDecoder('utf-8').decode(bytes);
        } catch (error) {
            return '';
        }
    }

    function initSubTopicContentEditor() {
        const textarea = document.getElementById('subTopicContentEditor');

        if (!textarea || typeof EasyMDE === 'undefined') {
            return;
        }

        if (subTopicContentEditor) {
            return;
        }

        subTopicContentEditor = new EasyMDE({
            element: textarea,
            spellChecker: false,
            status: false,
            minHeight: '340px',
            placeholder: 'Write lesson material using Markdown...',
            toolbar: [
                'bold',
                'italic',
                'heading',
                '|',
                'quote',
                'unordered-list',
                'ordered-list',
                '|',
                'link',
                'code',
                '|',
                'preview',
                'side-by-side',
                'fullscreen',
                '|',
                'guide'
            ]
        });
    }

    function setSubTopicEditorValue(value) {
        initSubTopicContentEditor();

        if (subTopicContentEditor) {
            subTopicContentEditor.value(value || '');
            return;
        }

        const textarea = document.getElementById('subTopicContentEditor');
        if (textarea) textarea.value = value || '';
    }

    function syncMarkdownEditors() {
        if (subTopicContentEditor) {
            subTopicContentEditor.codemirror.save();
        }
    }

    function getExpandedCollapseIds() {
        return Array.from(document.querySelectorAll('#curriculumStructureBody .accordion-collapse.show'))
            .map(function (collapseEl) {
                return collapseEl.id;
            })
            .filter(Boolean);
    }

    function setCollapseButtonState(collapseId, isOpen) {
        const button = document.querySelector(`[data-bs-target="#${collapseId}"]`);

        if (!button) return;

        if (isOpen) {
            button.classList.remove('collapsed');
            button.setAttribute('aria-expanded', 'true');
        } else {
            button.classList.add('collapsed');
            button.setAttribute('aria-expanded', 'false');
        }
    }

    function closeCollapseEl(collapseEl) {
        if (!collapseEl) return;

        collapseEl.classList.remove('show');
        collapseEl.classList.remove('collapsing');
        collapseEl.style.height = '';

        if (collapseEl.id) {
            setCollapseButtonState(collapseEl.id, false);
        }
    }

    function openCollapseById(collapseId) {
        if (!collapseId) return;

        const collapseEl = document.getElementById(collapseId);

        if (!collapseEl) return;

        const parentSelector = collapseEl.getAttribute('data-bs-parent');

        if (parentSelector) {
            document.querySelectorAll(`${parentSelector} .accordion-collapse.show`).forEach(function (siblingEl) {
                if (siblingEl !== collapseEl) {
                    closeCollapseEl(siblingEl);
                }
            });
        }

        collapseEl.classList.add('show');
        collapseEl.classList.remove('collapsing');
        collapseEl.style.height = '';

        setCollapseButtonState(collapseId, true);
    }

    function restoreExpandedCollapses(collapseIds) {
        document.querySelectorAll('#curriculumStructureBody .accordion-collapse.show').forEach(function (collapseEl) {
            closeCollapseEl(collapseEl);
        });

        if (!Array.isArray(collapseIds)) return;

        collapseIds.forEach(function (collapseId) {
            openCollapseById(collapseId);
        });
    }

    function focusSelectorFromPayload(focus) {
        if (!focus || typeof focus !== 'object') return '';

        if (focus.sub_topic_id) {
            return `[data-subtopic-item-id="${focus.sub_topic_id}"]`;
        }

        if (focus.topic_id) {
            return `[data-topic-card-id="${focus.topic_id}"]`;
        }

        if (focus.module_id) {
            return `[data-module-item-id="${focus.module_id}"]`;
        }

        if (focus.stage_id) {
            return `[data-stage-card-id="${focus.stage_id}"]`;
        }

        if (focus.program_id) {
            return `[data-program-block-id="${focus.program_id}"]`;
        }

        return '';
    }

    function applyFocusPayload(focus) {
        if (!focus || typeof focus !== 'object') return;

        if (focus.collapse_id) {
            openCollapseById(focus.collapse_id);
        } else if (focus.module_id) {
            openCollapseById(`moduleCollapse${focus.module_id}`);
        }

        const selector = focusSelectorFromPayload(focus);
        const targetEl = selector ? document.querySelector(selector) : null;

        if (!targetEl) return;

        targetEl.classList.add('curriculum-focus-highlight');

        window.setTimeout(function () {
            targetEl.classList.remove('curriculum-focus-highlight');
        }, 1700);
    }

    function replaceOptionsFromNextDocument(nextDocument, selector) {
        const currentSelect = document.querySelector(selector);
        const nextSelect = nextDocument.querySelector(selector);

        if (!currentSelect || !nextSelect) return;

        const currentValue = currentSelect.value;

        currentSelect.innerHTML = nextSelect.innerHTML;

        if (currentValue) {
            currentSelect.value = currentValue;
        }
    }

    function refreshDynamicModalOptions(nextDocument) {
        replaceOptionsFromNextDocument(nextDocument, '#stageForm select[name="program_id"]');
        replaceOptionsFromNextDocument(nextDocument, '#moduleForm select[name="program_stage_id"]');
        replaceOptionsFromNextDocument(nextDocument, '#topicForm select[name="module_id"]');
        replaceOptionsFromNextDocument(nextDocument, '#subTopicForm select[name="topic_id"]');
    }

    async function refreshCurriculumView(options = {}) {
        const statsRow = document.getElementById('curriculumStatsRow');
        const structureBody = document.getElementById('curriculumStructureBody');

        if (!statsRow || !structureBody) {
            return;
        }

        const scrollY = Number.isFinite(options.scrollY) ? options.scrollY : window.scrollY;
        const scrollX = Number.isFinite(options.scrollX) ? options.scrollX : window.scrollX;
        const expandedCollapseIds = Array.isArray(options.expandedCollapseIds)
            ? options.expandedCollapseIds
            : getExpandedCollapseIds();

        structureBody.classList.add('curriculum-refreshing');

        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to refresh curriculum view.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const nextDocument = parser.parseFromString(html, 'text/html');

            const nextStatsRow = nextDocument.getElementById('curriculumStatsRow');
            const nextStructureBody = nextDocument.getElementById('curriculumStructureBody');

            if (nextStatsRow) {
                statsRow.innerHTML = nextStatsRow.innerHTML;
            }

            if (nextStructureBody) {
                structureBody.innerHTML = nextStructureBody.innerHTML;
            }

            refreshDynamicModalOptions(nextDocument);
            restoreExpandedCollapses(expandedCollapseIds);
            applyFocusPayload(options.focus || null);

            requestAnimationFrame(function () {
                window.scrollTo({
                    left: scrollX,
                    top: scrollY,
                    behavior: 'auto',
                });

                requestAnimationFrame(function () {
                    window.scrollTo({
                        left: scrollX,
                        top: scrollY,
                        behavior: 'auto',
                    });
                });
            });
        } catch (error) {
            showToast('Data tersimpan, tapi gagal refresh tampilan. Silakan refresh manual jika perlu.', 'error');
        } finally {
            structureBody.classList.remove('curriculum-refreshing');
        }
    }

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

    function isBunnyVideoId(value) {
        return /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i.test(String(value || '').trim());
    }

    function buildBunnyEmbedUrlFromVideoId(videoId, libraryId = bunnyDefaultLibraryId) {
        const safeVideoId = String(videoId || '').trim();
        const safeLibraryId = String(libraryId || bunnyDefaultLibraryId || '').trim();

        if (!safeVideoId || !safeLibraryId) {
            return '';
        }

        return `https://iframe.mediadelivery.net/embed/${safeLibraryId}/${safeVideoId}`;
    }

    function normalizeBunnyVideoInputValue(value, options = {}) {
        const rawValue = String(value || '').trim();

        if (!rawValue) {
            return '';
        }

        if (isBunnyVideoId(rawValue)) {
            return buildBunnyEmbedUrlFromVideoId(rawValue);
        }

        try {
            const parsedUrl = new URL(rawValue);
            const host = parsedUrl.hostname.toLowerCase();

            if (!host.includes('mediadelivery.net')) {
                return rawValue;
            }

            const segments = parsedUrl.pathname.split('/').filter(Boolean);
            const mode = String(segments[0] || '').toLowerCase();
            const libraryId = segments[1] || bunnyDefaultLibraryId;
            const videoId = segments[2] || '';

            if ((mode === 'embed' || mode === 'play') && libraryId && videoId) {
                return buildBunnyEmbedUrlFromVideoId(videoId, libraryId);
            }

            return rawValue;
        } catch (error) {
            if (options.onlyWhenComplete) {
                return rawValue;
            }

            return isBunnyVideoId(rawValue)
                ? buildBunnyEmbedUrlFromVideoId(rawValue)
                : rawValue;
        }
    }

    function normalizeBunnyVideoUrlInput(form, options = {}) {
        if (!form) return '';

        const source = form.querySelector('select[name="video_source"]')?.value || 'server';
        const videoUrlInput = form.querySelector('input[name="video_url"]');

        if (source !== 'bunny' || !videoUrlInput) {
            return videoUrlInput?.value || '';
        }

        const currentValue = String(videoUrlInput.value || '').trim();
        const normalizedValue = normalizeBunnyVideoInputValue(currentValue, options);

        if (normalizedValue && normalizedValue !== currentValue) {
            videoUrlInput.value = normalizedValue;
        }

        return videoUrlInput.value || '';
    }

    function formatBytes(bytes) {
        const size = Number(bytes || 0);

        if (!size) return '-';

        const units = ['B', 'KB', 'MB', 'GB'];
        let value = size;
        let unitIndex = 0;

        while (value >= 1024 && unitIndex < units.length - 1) {
            value = value / 1024;
            unitIndex++;
        }

        return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
    }

    function getServerVideoByPath(path) {
        if (!path) return null;

        return serverVideoFiles.find(function (file) {
            return file.path === path;
        }) || null;
    }

    function resetSubTopicVideoUploadUI(form) {
        if (!form) return;

        const selectedUploadInfo = form.querySelector('.selected-video-file-info');
        const selectedServerInfo = form.querySelector('.selected-server-video-info');
        const selectedUrlInfo = form.querySelector('.selected-video-url-info');
        const existingInfo = form.querySelector('.existing-video-file-info');
        const existingMeta = form.querySelector('.existing-video-file-meta');
        const clearInput = form.querySelector('input[name="clear_video_file"]');
        const fileInput = form.querySelector('input[name="video_file"]');
        const serverSelect = form.querySelector('select[name="server_video_path"]');

        if (selectedUploadInfo) {
            selectedUploadInfo.classList.add('d-none');
            selectedUploadInfo.innerHTML = '';
        }

        if (selectedServerInfo) {
            selectedServerInfo.classList.add('d-none');
            selectedServerInfo.innerHTML = '';
        }

        if (selectedUrlInfo) {
            selectedUrlInfo.classList.add('d-none');
            selectedUrlInfo.innerHTML = '';
        }

        if (existingInfo) {
            existingInfo.classList.add('d-none');
        }

        if (existingMeta) {
            existingMeta.textContent = '-';
        }

        if (clearInput) {
            clearInput.value = '0';
        }

        if (fileInput) {
            fileInput.value = '';
        }

        if (serverSelect) {
            serverSelect.value = '';
        }
    }

    function renderServerVideoOptions(form, selectedPath = '') {
        if (!form) return;

        const serverSelect = form.querySelector('select[name="server_video_path"]');

        if (!serverSelect) return;

        const safeSelectedPath = selectedPath || serverSelect.value || '';
        const options = ['<option value="">Pilih video dari server</option>'];

        serverVideoFiles.forEach(function (file) {
            const selected = file.path === safeSelectedPath ? 'selected' : '';
            const label = `${file.name} • ${file.size_label || formatBytes(file.size)} • ${file.last_modified || '-'}`;

            options.push(`<option value="${escapeHtml(file.path)}" ${selected}>${escapeHtml(label)}</option>`);
        });

        if (safeSelectedPath && !serverVideoFiles.some(file => file.path === safeSelectedPath)) {
            options.push(`<option value="${escapeHtml(safeSelectedPath)}" selected>${escapeHtml(safeSelectedPath)} • file lama</option>`);
        }

        serverSelect.innerHTML = options.join('');
        serverSelect.value = safeSelectedPath;

        updateSelectedServerVideoInfo(form);
    }

    async function loadServerVideos(form, selectedPath = '', forceReload = false) {
        if (!form) return;

        const serverSelect = form.querySelector('select[name="server_video_path"]');

        if (!serverSelect || !serverVideosUrl) {
            if (serverSelect) {
                serverSelect.innerHTML = '<option value="">Route server video belum tersedia</option>';
            }
            return;
        }

        if (serverVideoFiles.length && !forceReload) {
            renderServerVideoOptions(form, selectedPath);
            return;
        }

        if (isLoadingServerVideos) return;

        isLoadingServerVideos = true;
        serverSelect.innerHTML = '<option value="">Loading server videos...</option>';

        try {
            const response = await fetch(serverVideosUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'Gagal memuat video server.');
            }

            serverVideoFiles = Array.isArray(data.data) ? data.data : [];
            renderServerVideoOptions(form, selectedPath);
        } catch (error) {
            serverSelect.innerHTML = '<option value="">Gagal memuat video server</option>';
            showToast('Gagal memuat daftar video server.', 'error');
        } finally {
            isLoadingServerVideos = false;
        }
    }

    function updateSelectedServerVideoInfo(form) {
        if (!form) return;

        const serverSelect = form.querySelector('select[name="server_video_path"]');
        const selectedServerInfo = form.querySelector('.selected-server-video-info');

        if (!serverSelect || !selectedServerInfo) return;

        const selectedPath = serverSelect.value || '';

        if (!selectedPath) {
            selectedServerInfo.classList.add('d-none');
            selectedServerInfo.innerHTML = '';
            return;
        }

        const file = getServerVideoByPath(selectedPath);
        const label = file
            ? `<strong>${escapeHtml(file.name)}</strong><br>${escapeHtml(file.path)} • ${escapeHtml(file.size_label || formatBytes(file.size))} • ${escapeHtml(file.last_modified || '-')}`
            : `<strong>File lama</strong><br>${escapeHtml(selectedPath)}`;

        selectedServerInfo.innerHTML = `<i class="bi bi-hdd-network me-1"></i>${label}`;
        selectedServerInfo.classList.remove('d-none');
    }

    function setExistingVideoFileInfo(form, button) {
        if (!form || !button) return;

        const hasVideoFile = button.dataset.hasVideoFile === '1';
        const existingInfo = form.querySelector('.existing-video-file-info');
        const existingMeta = form.querySelector('.existing-video-file-meta');

        if (!existingInfo || !existingMeta) return;

        if (!hasVideoFile) {
            existingInfo.classList.add('d-none');
            existingMeta.textContent = '-';
            return;
        }

        const videoPath = button.dataset.videoPath || '-';
        const videoMime = button.dataset.videoMime || 'video';
        const videoSize = formatBytes(button.dataset.videoSize || 0);

        existingMeta.textContent = `${videoPath} • ${videoMime} • ${videoSize}`;
        existingInfo.classList.remove('d-none');
    }

    function syncSubTopicVideoSourceFields(form) {
        if (!form) return;

        const lessonType = form.querySelector('select[name="lesson_type"]')?.value || 'video';
        const source = form.querySelector('select[name="video_source"]')?.value || 'server';
        const providerInput = form.querySelector('input[name="video_provider"]');

        const serverWrap = form.querySelector('.subtopic-video-server-wrap');
        const uploadWrap = form.querySelector('.subtopic-video-upload-wrap');
        const urlWrap = form.querySelector('.subtopic-video-url-wrap');

        const fileInput = form.querySelector('input[name="video_file"]');
        const serverSelect = form.querySelector('select[name="server_video_path"]');
        const videoUrlInput = form.querySelector('input[name="video_url"]');
        const videoUrlHelp = form.querySelector('.video-url-help');
        const clearVideoFileInput = form.querySelector('input[name="clear_video_file"]');
        const existingVideoFileInfo = form.querySelector('.existing-video-file-info');

        if (providerInput) {
            if (source === 'bunny') {
                providerInput.value = 'bunny';
            } else if (source === 'youtube') {
                providerInput.value = 'youtube';
            } else {
                providerInput.value = 'self_hosted';
            }
        }

        if (lessonType === 'live_session') {
            serverWrap?.classList.add('d-none');
            uploadWrap?.classList.add('d-none');
            urlWrap?.classList.add('d-none');

            if (fileInput) fileInput.value = '';
            if (serverSelect) serverSelect.value = '';
            if (videoUrlInput) videoUrlInput.value = '';
            if (clearVideoFileInput) clearVideoFileInput.value = '1';
            existingVideoFileInfo?.classList.add('d-none');

            updateSelectedVideoFileInfo(form);
            updateSelectedServerVideoInfo(form);
            updateSelectedVideoUrlInfo(form);

            return;
        }

        if (source === 'bunny' || source === 'youtube') {
            serverWrap?.classList.add('d-none');
            uploadWrap?.classList.add('d-none');
            urlWrap?.classList.remove('d-none');

            if (fileInput) {
                fileInput.value = '';
                updateSelectedVideoFileInfo(form);
            }

            if (serverSelect) {
                serverSelect.value = '';
                updateSelectedServerVideoInfo(form);
            }

            if (clearVideoFileInput) {
                clearVideoFileInput.value = '1';
            }

            existingVideoFileInfo?.classList.add('d-none');

            if (videoUrlInput) {
                videoUrlInput.placeholder = source === 'bunny'
                    ? 'Paste Bunny Video ID atau https://iframe.mediadelivery.net/embed/library-id/video-id'
                    : 'https://youtube.com/watch?v=... atau embed URL lain';
            }

            if (videoUrlHelp) {
                videoUrlHelp.textContent = source === 'bunny'
                    ? 'Paste Bunny Video ID saja, atau Bunny embed URL. Sistem otomatis membentuk URL embed dan membersihkan token/expires.'
                    : 'Masukkan URL YouTube atau external video.';
            }

            normalizeBunnyVideoUrlInput(form, { onlyWhenComplete: true });
            updateSelectedVideoUrlInfo(form);

            return;
        }

        if (source === 'upload') {
            serverWrap?.classList.add('d-none');
            uploadWrap?.classList.remove('d-none');
            urlWrap?.classList.add('d-none');

            if (serverSelect) {
                serverSelect.value = '';
                updateSelectedServerVideoInfo(form);
            }

            if (videoUrlInput) {
                videoUrlInput.value = '';
                updateSelectedVideoUrlInfo(form);
            }

            if (clearVideoFileInput) {
                clearVideoFileInput.value = '0';
            }

            existingVideoFileInfo?.classList.add('d-none');

            return;
        }

        serverWrap?.classList.remove('d-none');
        uploadWrap?.classList.add('d-none');
        urlWrap?.classList.add('d-none');

        if (fileInput) {
            fileInput.value = '';
            updateSelectedVideoFileInfo(form);
        }

        if (videoUrlInput) {
            videoUrlInput.value = '';
            updateSelectedVideoUrlInfo(form);
        }

        if (clearVideoFileInput) {
            clearVideoFileInput.value = '0';
        }

        loadServerVideos(form, serverSelect?.value || '');
    }

    function updateSelectedVideoFileInfo(form) {
        if (!form) return;

        const fileInput = form.querySelector('input[name="video_file"]');
        const selectedInfo = form.querySelector('.selected-video-file-info');

        if (!fileInput || !selectedInfo) return;

        const file = fileInput.files?.[0];

        if (!file) {
            selectedInfo.classList.add('d-none');
            selectedInfo.innerHTML = '';
            return;
        }

        selectedInfo.innerHTML = `
            <i class="bi bi-check-circle me-1"></i>
            File dipilih: ${escapeHtml(file.name)} • ${formatBytes(file.size)}
        `;
        selectedInfo.classList.remove('d-none');
    }

    function updateSelectedVideoUrlInfo(form) {
        if (!form) return;

        const videoUrlInput = form.querySelector('input[name="video_url"]');
        const selectedInfo = form.querySelector('.selected-video-url-info');
        const source = form.querySelector('select[name="video_source"]')?.value || 'server';

        if (!videoUrlInput || !selectedInfo) return;

        if (source === 'bunny') {
            normalizeBunnyVideoUrlInput(form, { onlyWhenComplete: true });
        }

        const videoUrl = (videoUrlInput.value || '').trim();

        if (!videoUrl || (source !== 'bunny' && source !== 'youtube')) {
            selectedInfo.classList.add('d-none');
            selectedInfo.innerHTML = '';
            return;
        }

        const label = source === 'bunny' ? 'Bunny Stream Embed URL' : 'External Video URL';

        selectedInfo.innerHTML = `
            <i class="bi bi-link-45deg me-1"></i>
            <strong>${escapeHtml(label)}</strong><br>
            ${escapeHtml(videoUrl)}
        `;
        selectedInfo.classList.remove('d-none');
    }

    function resetFormState(form) {
        form.reset();

        if (form.id === 'subTopicForm') {
            setSubTopicEditorValue('');
            resetSubTopicVideoUploadUI(form);
        }

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

    function buildFallbackFocusFromForm(form) {
        const id = form.querySelector('input[name="id"]')?.value || '';

        if (form.id === 'stageForm') {
            return {
                type: 'stage',
                stage_id: id || null,
                program_id: form.querySelector('select[name="program_id"]')?.value || null,
            };
        }

        if (form.id === 'moduleForm') {
            if (id) {
                return {
                    type: 'module',
                    module_id: id,
                    collapse_id: `moduleCollapse${id}`,
                };
            }

            return {
                type: 'stage',
                stage_id: form.querySelector('select[name="program_stage_id"]')?.value || null,
            };
        }

        if (form.id === 'topicForm') {
            const moduleId = form.querySelector('select[name="module_id"]')?.value || null;

            if (id) {
                return {
                    type: 'topic',
                    topic_id: id,
                    module_id: moduleId,
                    collapse_id: moduleId ? `moduleCollapse${moduleId}` : null,
                };
            }

            return {
                type: 'module',
                module_id: moduleId,
                collapse_id: moduleId ? `moduleCollapse${moduleId}` : null,
            };
        }

        if (form.id === 'subTopicForm') {
            if (id) {
                return {
                    type: 'sub_topic',
                    sub_topic_id: id,
                    topic_id: form.querySelector('select[name="topic_id"]')?.value || null,
                };
            }

            return {
                type: 'topic',
                topic_id: form.querySelector('select[name="topic_id"]')?.value || null,
            };
        }

        return null;
    }

    async function hideModalById(modalId) {
        const modalEl = document.getElementById(modalId);

        if (!modalEl) return;

        const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);

        if (!modalInstance) return;

        if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
        }

        await new Promise(function (resolve) {
            let resolved = false;

            function done() {
                if (resolved) return;
                resolved = true;
                resolve();
            }

            modalEl.addEventListener('hidden.bs.modal', done, { once: true });
            modalInstance.hide();

            window.setTimeout(done, 360);
        });
    }

    async function bindAsyncForm(form, modalId, updateUrlBuilder) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            syncMarkdownEditors();

            if (form.id === 'subTopicForm') {
                normalizeBunnyVideoUrlInput(form);
                updateSelectedVideoUrlInfo(form);
            }

            const submitBtn = form.querySelector('.submit-btn');
            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const createUrl = form.dataset.createUrl;
            const actionUrl = method === 'PUT' ? updateUrlBuilder(id) : createUrl;
            const formData = new FormData(form);

            const refreshContext = {
                scrollY: window.scrollY,
                scrollX: window.scrollX,
                expandedCollapseIds: getExpandedCollapseIds(),
            };

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

                const hasFileUpload = Array.from(form.querySelectorAll('input[type="file"]'))
                    .some(function (input) {
                        return input.files && input.files.length > 0;
                    });

                submitBtn.innerHTML = hasFileUpload ? 'Uploading...' : 'Saving...';
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

                const focusPayload = data.focus || data.data?.focus || buildFallbackFocusFromForm(form);

                await hideModalById(modalId);

                showToast(data.message || 'Data berhasil disimpan.', 'success');
                resetFormState(form);

                await refreshCurriculumView({
                    ...refreshContext,
                    focus: focusPayload,
                });
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

            const refreshContext = {
                scrollY: window.scrollY,
                scrollX: window.scrollX,
                expandedCollapseIds: getExpandedCollapseIds(),
            };

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

                await hideModalById('deleteConfirmModal');

                showToast(data.message || 'Data berhasil dihapus.', 'success');

                await refreshCurriculumView({
                    ...refreshContext,
                    focus: data.focus || null,
                });
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

        syncSubTopicVideoSourceFields(form);
    }

    function setupSubTopicModal() {
        const modalEl = document.getElementById('subTopicModal');
        const form = document.getElementById('subTopicForm');

        if (!modalEl || !form) return;

        const lessonTypeSelect = form.querySelector('select[name="lesson_type"]');
        const videoSourceSelect = form.querySelector('select[name="video_source"]');
        const serverVideoSelect = form.querySelector('select[name="server_video_path"]');
        const refreshServerVideosBtn = form.querySelector('.refresh-server-videos-btn');
        const videoFileInput = form.querySelector('input[name="video_file"]');
        const clearVideoFileBtn = form.querySelector('.clear-video-file-btn');
        const clearVideoFileInput = form.querySelector('input[name="clear_video_file"]');
        const videoUrlInput = form.querySelector('input[name="video_url"]');
        const existingVideoFileInfo = form.querySelector('.existing-video-file-info');

        if (lessonTypeSelect) {
            lessonTypeSelect.addEventListener('change', function () {
                syncSubTopicLessonFields(form);
            });
        }

        if (videoSourceSelect) {
            videoSourceSelect.addEventListener('change', function () {
                syncSubTopicVideoSourceFields(form);
            });
        }

        if (videoUrlInput) {
            videoUrlInput.addEventListener('input', function () {
                normalizeBunnyVideoUrlInput(form, { onlyWhenComplete: true });
                updateSelectedVideoUrlInfo(form);
            });

            videoUrlInput.addEventListener('blur', function () {
                normalizeBunnyVideoUrlInput(form);
                updateSelectedVideoUrlInfo(form);
            });

            videoUrlInput.addEventListener('paste', function () {
                window.setTimeout(function () {
                    normalizeBunnyVideoUrlInput(form);
                    updateSelectedVideoUrlInfo(form);
                }, 0);
            });
        }

        if (serverVideoSelect) {
            serverVideoSelect.addEventListener('change', function () {
                updateSelectedServerVideoInfo(form);

                if (clearVideoFileInput) {
                    clearVideoFileInput.value = '0';
                }
            });
        }

        if (refreshServerVideosBtn) {
            refreshServerVideosBtn.addEventListener('click', function () {
                loadServerVideos(form, serverVideoSelect?.value || '', true);
            });
        }

        if (videoFileInput) {
            videoFileInput.addEventListener('change', function () {
                updateSelectedVideoFileInfo(form);

                if (clearVideoFileInput) {
                    clearVideoFileInput.value = '0';
                }
            });
        }

        if (clearVideoFileBtn) {
            clearVideoFileBtn.addEventListener('click', function () {
                if (clearVideoFileInput) {
                    clearVideoFileInput.value = '1';
                }

                if (existingVideoFileInfo) {
                    existingVideoFileInfo.classList.add('d-none');
                }

                if (serverVideoSelect) {
                    serverVideoSelect.value = '';
                    updateSelectedServerVideoInfo(form);
                }

                if (videoFileInput) {
                    videoFileInput.value = '';
                    updateSelectedVideoFileInfo(form);
                }

                if (videoUrlInput) {
                    videoUrlInput.value = '';
                    updateSelectedVideoUrlInfo(form);
                }

                showToast('Video akan dihapus setelah perubahan disimpan.', 'success');
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

                const currentProvider = button.dataset.videoProvider || '';
                const hasServerVideo = button.dataset.hasVideoFile === '1' || Boolean(button.dataset.videoPath || '');
                const hasExternalVideo = Boolean(button.dataset.videoUrl || '');

                let currentVideoSource = 'server';

                if (currentProvider === 'bunny') {
                    currentVideoSource = 'bunny';
                } else if (currentProvider === 'youtube') {
                    currentVideoSource = 'youtube';
                } else if (hasServerVideo) {
                    currentVideoSource = 'server';
                } else if (hasExternalVideo) {
                    currentVideoSource = 'youtube';
                }

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('select[name="topic_id"]').value = button.dataset.topicId || '';
                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';

                form.querySelector('select[name="lesson_type"]').value = button.dataset.lessonType || 'video';
                form.querySelector('select[name="video_source"]').value = currentVideoSource;
                form.querySelector('input[name="video_provider"]').value = currentVideoSource === 'bunny'
                    ? 'bunny'
                    : (currentVideoSource === 'youtube' ? 'youtube' : 'self_hosted');
                form.querySelector('input[name="video_url"]').value = button.dataset.videoUrl || '';
                form.querySelector('input[name="video_duration_minutes"]').value = button.dataset.videoDurationMinutes || '';
                form.querySelector('input[name="thumbnail_url"]').value = button.dataset.thumbnailUrl || '';
                form.querySelector('input[name="content_format"]').value = button.dataset.contentFormat || 'markdown';

                resetSubTopicVideoUploadUI(form);
                setExistingVideoFileInfo(form, button);
                setSubTopicEditorValue(decodeBase64Unicode(button.dataset.contentBase64 || ''));

                if (currentVideoSource === 'server') {
                    loadServerVideos(form, button.dataset.videoPath || '');
                }
            } else {
                title.textContent = 'Add Sub Topic';
                subtitle.textContent = 'Tambahkan sub topic sebagai unit learning item dan checklist instructor.';
                submitBtn.innerHTML = 'Save Sub Topic';
                submitBtn.dataset.defaultText = 'Save Sub Topic';

                form.querySelector('select[name="lesson_type"]').value = 'video';
                form.querySelector('select[name="video_source"]').value = 'server';
                form.querySelector('input[name="video_provider"]').value = 'self_hosted';
                form.querySelector('input[name="video_url"]').value = '';
                form.querySelector('input[name="content_format"]').value = 'markdown';

                resetSubTopicVideoUploadUI(form);
                setSubTopicEditorValue('');
                loadServerVideos(form);

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

    initSubTopicContentEditor();

    setupStageModal();
    setupModuleModal();
    setupTopicModal();
    setupSubTopicModal();
    setupDeleteConfirmModal();
});
</script>
@endpush
