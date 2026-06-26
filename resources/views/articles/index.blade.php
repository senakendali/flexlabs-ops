@extends('layouts.app-dashboard')

@section('title', 'Article Generator')

@section('content')
@php
    $currentUser = auth()->user();

    $userRole = strtolower((string) ($currentUser->role ?? ''));
    $userType = strtolower((string) ($currentUser->user_type ?? ''));

    $accessKeys = array_filter([$userRole, $userType]);

    $isAdminArticleUser = count(array_intersect($accessKeys, ['admin', 'super_admin'])) > 0;
    $isMarketingArticleUser = count(array_intersect($accessKeys, ['marketing'])) > 0;

    $canCreateArticle = $isAdminArticleUser || $isMarketingArticleUser;
    $canUpdateArticle = $isAdminArticleUser || $isMarketingArticleUser;
    $canSubmitReviewArticle = $isAdminArticleUser || $isMarketingArticleUser;
    $canArchiveArticle = $isAdminArticleUser || $isMarketingArticleUser;

    $canApproveArticle = $isAdminArticleUser;
    $canPublishArticle = $isAdminArticleUser;
    $canScheduleArticle = $isAdminArticleUser;
    $canDeleteArticle = $isAdminArticleUser;

    $articleItems = method_exists($articles, 'items')
        ? collect($articles->items())
        : collect($articles ?? []);

    $totalArticles = method_exists($articles, 'total')
        ? (int) $articles->total()
        : $articleItems->count();

    $pageDraftCount = $articleItems
        ->whereIn('status', ['draft', 'ai_generated', 'edited'])
        ->count();

    $pageReviewCount = $articleItems
        ->whereIn('status', ['ready_for_review', 'approved'])
        ->count();

    $pagePublishedCount = $articleItems
        ->where('status', 'published')
        ->count();

    $statusMeta = [
        'draft' => [
            'label' => 'Draft',
            'class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'icon' => 'bi bi-pencil-square',
        ],
        'ai_generated' => [
            'label' => 'AI Draft',
            'class' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'icon' => 'bi bi-stars',
        ],
        'edited' => [
            'label' => 'Edited',
            'class' => 'bg-info-subtle text-info border border-info-subtle',
            'icon' => 'bi bi-brush',
        ],
        'ready_for_review' => [
            'label' => 'Ready for Review',
            'class' => 'bg-warning-subtle text-warning border border-warning-subtle',
            'icon' => 'bi bi-eye',
        ],
        'approved' => [
            'label' => 'Approved',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-check2-circle',
        ],
        'scheduled' => [
            'label' => 'Scheduled',
            'class' => 'bg-info-subtle text-info border border-info-subtle',
            'icon' => 'bi bi-calendar-event',
        ],
        'published' => [
            'label' => 'Published',
            'class' => 'bg-success text-white',
            'icon' => 'bi bi-globe2',
        ],
        'archived' => [
            'label' => 'Archived',
            'class' => 'bg-dark-subtle text-dark border border-dark-subtle',
            'icon' => 'bi bi-archive',
        ],
    ];
@endphp

<div class="container-fluid px-4 py-4">

    <div
        id="articleToast"
        class="alert rounded-4 shadow-sm position-fixed d-none"
        style="top: 92px; right: 22px; z-index: 1080; min-width: 280px; max-width: 420px;"
        role="alert"
    ></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Tools</div>
                <h1 class="page-title mb-2">Article Generator</h1>
                <p class="page-subtitle mb-0">
                    Buat dan kelola artikel siap publikasi untuk website FlexLabs, lengkap dengan arahan SEO,
                    ide visual, dan caption pendukung.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if($canCreateArticle)
                    <button
                        type="button"
                        class="btn btn-light btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#generateFromWorkshopModal"
                    >
                        <i class="bi bi-easel2-fill me-2"></i>Generate from Workshop
                    </button>

                    <a href="{{ route('articles.create') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-plus-lg me-2"></i>New Article
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Content Operations</div>
        <h4 class="dashboard-section-title mb-1">Article Summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau jumlah artikel, draft yang sedang dikerjakan, antrean review, dan artikel yang sudah dipublikasikan.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Articles</div>
                        <div class="stat-value">{{ number_format($totalArticles) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total artikel sesuai filter yang sedang digunakan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft Queue</div>
                        <div class="stat-value">{{ number_format($pageDraftCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel yang masih dalam proses penulisan di halaman ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Review Queue</div>
                        <div class="stat-value">{{ number_format($pageReviewCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel yang menunggu pengecekan sebelum publish.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published</div>
                        <div class="stat-value">{{ number_format($pagePublishedCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel yang sudah berstatus published di halaman ini.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Publishing Workflow</div>
        <h4 class="dashboard-section-title mb-1">Content Readiness</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pastikan setiap artikel punya struktur yang jelas, SEO yang siap, dan arahan visual sebelum dipublikasikan.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Smart Drafting</div>
                            <div class="small text-muted">
                                Buat draft artikel dari brief, keyword, atau data workshop agar proses penulisan lebih cepat dan konsisten.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-search-heart"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">SEO Preparation</div>
                            <div class="small text-muted">
                                Lengkapi focus keyword, SEO title, meta description, tags, dan image alt text untuk mendukung performa pencarian.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Publish Approval</div>
                            <div class="small text-muted">
                                @if($isAdminArticleUser)
                                    Akun ini dapat mempublikasikan, mengarsipkan, dan menghapus artikel sesuai kebutuhan.
                                @else
                                    Artikel dapat dibuat dan diajukan untuk review. Proses publish akan dilakukan oleh admin.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Articles</h5>
                <p class="content-card-subtitle mb-0">
                    Temukan artikel berdasarkan judul, status, kategori, atau tipe konten.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('articles.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? request('search') }}"
                            placeholder="Search title, slug, keyword..."
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? request('status')) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All Category</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['category'] ?? request('category')) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="article_type" class="form-label">Type</label>
                        <select name="article_type" id="article_type" class="form-select">
                            <option value="">All Type</option>
                            @foreach($articleTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['article_type'] ?? request('article_type')) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label d-none d-xl-block">&nbsp;</label>

                        <div class="d-grid d-sm-flex gap-2 justify-content-xl-end">
                            <a href="{{ route('articles.index') }}" class="btn btn-secondary btn-modern text-nowrap">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern text-nowrap">
                                <i class="bi bi-funnel me-2"></i>Apply
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
                <h5 class="content-card-title mb-1">Article List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola artikel, cek status penulisan, dan lanjutkan proses review atau publish.
                </p>
            </div>

            @if($canCreateArticle)
                <a href="{{ route('articles.create') }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-plus-lg me-2"></i>Create Draft
                </a>
            @endif
        </div>

        <div class="content-card-body">
            @if($articles->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">No</th>
                                <th class="text-nowrap">Article</th>
                                <th class="text-nowrap">Source</th>
                                <th class="text-nowrap">SEO Keyword</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Updated</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($articles as $article)
                                @php
                                    $rowNumber = method_exists($articles, 'currentPage')
                                        ? (($articles->currentPage() - 1) * $articles->perPage()) + $loop->iteration
                                        : $loop->iteration;

                                    $meta = $statusMeta[$article->status] ?? [
                                        'label' => $article->status_label ?? ucfirst(str_replace('_', ' ', (string) $article->status)),
                                        'class' => 'bg-light text-muted border',
                                        'icon' => 'bi bi-circle',
                                    ];

                                    $categoryLabel = $categories[$article->category] ?? ($article->category ? ucfirst(str_replace('_', ' ', $article->category)) : 'Uncategorized');
                                    $typeLabel = $articleTypes[$article->article_type] ?? ($article->article_type ? ucfirst(str_replace('_', ' ', $article->article_type)) : 'Article');
                                    $sourceLabel = $sourceTypes[$article->source_type] ?? ($article->source_type ? ucfirst(str_replace('_', ' ', $article->source_type)) : 'Manual');

                                    $canMoveToReview = in_array($article->status, ['draft', 'ai_generated', 'edited'], true);
                                    $canMoveToApprove = in_array($article->status, ['ready_for_review', 'edited', 'ai_generated'], true);
                                    $canMoveToPublish = in_array($article->status, ['ai_generated', 'edited', 'ready_for_review', 'approved'], true);
                                @endphp

                                <tr id="article-row-{{ $article->id }}">
                                    <td class="text-muted">
                                        {{ $rowNumber }}
                                    </td>

                                    <td style="min-width: 320px;">
                                        <div class="fw-bold text-dark">
                                            <a href="{{ route('articles.show', $article) }}" class="text-decoration-none text-dark">
                                                {{ $article->title }}
                                            </a>
                                        </div>

                                        <div class="text-muted small mt-1">
                                            {{ $categoryLabel }} · {{ $typeLabel }}
                                        </div>

                                        @if($article->excerpt)
                                            <div class="text-muted small mt-1 text-truncate" style="max-width: 520px;">
                                                {{ $article->excerpt }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ $sourceLabel }}
                                        </div>

                                        @if($article->source_id)
                                            <div class="text-muted small">
                                                Source ID: {{ $article->source_id }}
                                            </div>
                                        @else
                                            <div class="text-muted small">
                                                Manual draft
                                            </div>
                                        @endif
                                    </td>

                                    <td style="min-width: 220px;">
                                        @if($article->primary_keyword)
                                            <div class="fw-semibold text-dark">
                                                {{ $article->primary_keyword }}
                                            </div>
                                        @else
                                            <div class="text-muted">
                                                -
                                            </div>
                                        @endif

                                        @if($article->meta_description)
                                            <div class="text-muted small text-truncate" style="max-width: 260px;">
                                                {{ $article->meta_description }}
                                            </div>
                                        @else
                                            <div class="text-muted small">
                                                Meta description belum diisi.
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill px-3 py-2 {{ $meta['class'] }}">
                                            <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ optional($article->updated_at)->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ optional($article->updated_at)->format('H:i') ?? '-' }}
                                        </div>
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
                                                    <a href="{{ route('articles.show', $article) }}" class="dropdown-item">
                                                        <i class="bi bi-eye me-2"></i>Show Detail
                                                    </a>
                                                </li>

                                                @if($canUpdateArticle && $article->canBeEdited())
                                                    <li>
                                                        <a href="{{ route('articles.edit', $article) }}" class="dropdown-item">
                                                            <i class="bi bi-pencil-square me-2"></i>Edit Article
                                                        </a>
                                                    </li>
                                                @endif

                                                @if($canSubmitReviewArticle && $canMoveToReview)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item js-confirm-article-action"
                                                            data-action="status"
                                                            data-method="PATCH"
                                                            data-url="{{ route('articles.ready-for-review', $article) }}"
                                                            data-title="Submit artikel untuk review?"
                                                            data-message="Artikel ini akan masuk ke antrean review sebelum dipublikasikan."
                                                            data-confirm-label="Submit Review"
                                                            data-confirm-class="btn-primary"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-send-check me-2"></i>Submit Review
                                                        </button>
                                                    </li>
                                                @endif

                                                @if($canApproveArticle && $canMoveToApprove)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item js-confirm-article-action"
                                                            data-action="status"
                                                            data-method="PATCH"
                                                            data-url="{{ route('articles.approve', $article) }}"
                                                            data-title="Approve artikel?"
                                                            data-message="Artikel ini akan ditandai approved dan siap dipublikasikan."
                                                            data-confirm-label="Approve"
                                                            data-confirm-class="btn-success"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-check2-circle me-2"></i>Approve
                                                        </button>
                                                    </li>
                                                @endif

                                                @if($canPublishArticle && $canMoveToPublish)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item js-confirm-article-action"
                                                            data-action="publish"
                                                            data-method="PATCH"
                                                            data-url="{{ route('articles.publish', $article) }}"
                                                            data-title="Publish artikel?"
                                                            data-message="Artikel akan dipublikasikan. Pastikan isi, SEO, dan arahan visual sudah sesuai."
                                                            data-confirm-label="Publish"
                                                            data-confirm-class="btn-success"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-globe2 me-2"></i>Publish
                                                        </button>
                                                    </li>
                                                @endif

                                                @if(
                                                    ($canArchiveArticle && $article->status !== 'archived')
                                                    || ($canDeleteArticle && $article->status !== 'published')
                                                )
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif

                                                @if($canArchiveArticle && $article->status !== 'archived')
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item js-confirm-article-action"
                                                            data-action="archive"
                                                            data-method="PATCH"
                                                            data-url="{{ route('articles.archive', $article) }}"
                                                            data-title="Archive artikel?"
                                                            data-message="Artikel akan dipindahkan ke arsip dan tidak masuk antrean aktif."
                                                            data-confirm-label="Archive"
                                                            data-confirm-class="btn-warning"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-archive me-2"></i>Archive
                                                        </button>
                                                    </li>
                                                @endif

                                                @if($canDeleteArticle && $article->status !== 'published')
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-danger js-confirm-article-action"
                                                            data-action="delete"
                                                            data-method="DELETE"
                                                            data-url="{{ route('articles.destroy', $article) }}"
                                                            data-title="Delete artikel?"
                                                            data-message="Artikel akan dihapus dari daftar. Untuk artikel yang sudah published, gunakan archive."
                                                            data-confirm-label="Delete"
                                                            data-confirm-class="btn-danger"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($articles, 'hasPages') && $articles->hasPages())
                    <div class="mt-3">
                        {{ $articles->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-richtext"></i>
                    </div>

                    <h5 class="empty-state-title">Belum ada artikel</h5>
                    <p class="empty-state-text mb-0">
                        Mulai dengan membuat draft artikel baru atau gunakan data workshop sebagai bahan awal.
                    </p>

                    @if($canCreateArticle)
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ route('articles.create') }}" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-lg me-2"></i>Create First Article
                            </a>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#generateFromWorkshopModal"
                            >
                                <i class="bi bi-easel2-fill me-2"></i>Generate from Workshop
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="generateFromWorkshopModal" tabindex="-1" aria-labelledby="generateFromWorkshopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="generateFromWorkshopModalLabel">
                        Generate from Workshop
                    </h5>
                    <div class="small text-muted">
                        Gunakan data workshop sebagai bahan awal untuk membuat draft artikel.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <label for="workshopIdInput" class="form-label">Workshop ID</label>
                <input
                    type="number"
                    min="1"
                    id="workshopIdInput"
                    class="form-control"
                    placeholder="Contoh: 12"
                >

                <div class="alert alert-warning rounded-4 small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Masukkan ID workshop yang ingin dijadikan bahan artikel.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary btn-modern" id="goGenerateFromWorkshopBtn">
                    Continue
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="articleConfirmModal" tabindex="-1" aria-labelledby="articleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="articleConfirmModalLabel">Confirm action</h5>
                    <div class="small text-muted" id="articleConfirmModalSubtitle">
                        Please confirm this action.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-icon-wrap flex-shrink-0">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-1" id="articleConfirmMessage">
                            Are you sure?
                        </div>
                        <div class="small text-muted">
                            Pastikan aksi ini sudah sesuai sebelum dilanjutkan.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary btn-modern" id="articleConfirmBtn">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const csrfToken = '{{ csrf_token() }}';

        const toastEl = document.getElementById('articleToast');

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        };

        const showToast = (message, type = 'success') => {
            if (!toastEl) return;

            const className = type === 'success'
                ? 'alert-success'
                : type === 'warning'
                    ? 'alert-warning'
                    : 'alert-danger';

            toastEl.className = `alert ${className} rounded-4 shadow-sm position-fixed`;
            toastEl.style.top = '92px';
            toastEl.style.right = '22px';
            toastEl.style.zIndex = '1080';
            toastEl.style.minWidth = '280px';
            toastEl.style.maxWidth = '420px';

            toastEl.innerHTML = `
                <div class="d-flex align-items-start gap-2">
                    <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} mt-1"></i>
                    <div class="fw-semibold">${escapeHtml(message)}</div>
                </div>
            `;

            toastEl.classList.remove('d-none');

            window.setTimeout(() => {
                toastEl.classList.add('d-none');
            }, 3200);
        };

        const confirmModalEl = document.getElementById('articleConfirmModal');
        const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;

        const confirmTitleEl = document.getElementById('articleConfirmModalLabel');
        const confirmSubtitleEl = document.getElementById('articleConfirmModalSubtitle');
        const confirmMessageEl = document.getElementById('articleConfirmMessage');
        const confirmBtn = document.getElementById('articleConfirmBtn');

        let pendingAction = null;

        document.querySelectorAll('.js-confirm-article-action').forEach((button) => {
            button.addEventListener('click', () => {
                pendingAction = {
                    url: button.dataset.url,
                    method: button.dataset.method || 'POST',
                    rowId: button.dataset.rowId,
                    action: button.dataset.action,
                };

                if (confirmTitleEl) {
                    confirmTitleEl.textContent = button.dataset.title || 'Confirm action';
                }

                if (confirmSubtitleEl) {
                    confirmSubtitleEl.textContent = button.dataset.confirmLabel || 'Please confirm this action.';
                }

                if (confirmMessageEl) {
                    confirmMessageEl.textContent = button.dataset.message || 'Are you sure?';
                }

                if (confirmBtn) {
                    confirmBtn.textContent = button.dataset.confirmLabel || 'Confirm';
                    confirmBtn.className = `btn btn-modern ${button.dataset.confirmClass || 'btn-primary'}`;
                    confirmBtn.disabled = false;
                }

                confirmModal?.show();
            });
        });

        confirmBtn?.addEventListener('click', async () => {
            if (!pendingAction?.url) return;

            const originalText = confirmBtn.textContent;

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                Processing...
            `;

            try {
                const response = await fetch(pendingAction.url, {
                    method: pendingAction.method,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Action gagal diproses.');
                }

                confirmModal?.hide();

                if (pendingAction.action === 'delete') {
                    const row = document.getElementById(pendingAction.rowId);
                    row?.remove();
                    showToast(data.message || 'Artikel berhasil dihapus.', 'success');
                    return;
                }

                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan.', 'danger');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.textContent = originalText;
                pendingAction = null;
            }
        });

        const goGenerateFromWorkshopBtn = document.getElementById('goGenerateFromWorkshopBtn');
        const workshopIdInput = document.getElementById('workshopIdInput');

        goGenerateFromWorkshopBtn?.addEventListener('click', () => {
            const workshopId = String(workshopIdInput?.value || '').trim();

            if (!workshopId || Number(workshopId) < 1) {
                showToast('Masukkan Workshop ID yang valid.', 'warning');
                workshopIdInput?.focus();
                return;
            }

            window.location.href = `{{ url('/articles/create/from-workshop') }}/${workshopId}`;
        });
    })();
</script>
@endsection