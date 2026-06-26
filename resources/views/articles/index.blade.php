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
    $canArchiveArticle = $isAdminArticleUser || $isMarketingArticleUser;
    $canDeleteArticle = $isAdminArticleUser;

    $articleItems = method_exists($articles, 'items')
        ? collect($articles->items())
        : collect($articles ?? []);

    $totalArticles = method_exists($articles, 'total')
        ? (int) $articles->total()
        : $articleItems->count();

    $readyStatuses = [
        'ready_to_copy',
        'ready_for_review',
        'approved',
        'scheduled',
        'published',
    ];

    $generatedStatuses = [
        'ai_generated',
        'edited',
        'ready_to_copy',
        'ready_for_review',
        'approved',
        'scheduled',
        'published',
    ];

    $pageReadyToCopyCount = $articleItems
        ->whereIn('status', $readyStatuses)
        ->count();

    $pageGeneratingCount = $articleItems
        ->where('status', 'generating')
        ->count();

    $pageFailedCount = $articleItems
        ->where('status', 'generation_failed')
        ->count();

    $pageGeneratedCount = $articleItems
        ->whereIn('status', $generatedStatuses)
        ->count();

    $statusFilterOptions = [
        'draft' => 'Brief Only',
        'generating' => 'Generating',
        'ai_generated' => 'Generated',
        'edited' => 'Edited',
        'ready_to_copy' => 'Ready to Copy',
        'generation_failed' => 'AI Failed',
        'archived' => 'Archived',
    ];

    $statusMeta = [
        'draft' => [
            'label' => 'Brief Only',
            'class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'icon' => 'bi bi-pencil-square',
        ],
        'generating' => [
            'label' => 'Generating',
            'class' => 'bg-warning-subtle text-warning border border-warning-subtle',
            'icon' => 'bi bi-hourglass-split',
        ],
        'ai_generated' => [
            'label' => 'Generated',
            'class' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'icon' => 'bi bi-stars',
        ],
        'edited' => [
            'label' => 'Edited',
            'class' => 'bg-info-subtle text-info border border-info-subtle',
            'icon' => 'bi bi-brush',
        ],
        'ready_to_copy' => [
            'label' => 'Ready to Copy',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-clipboard-check',
        ],
        'generation_failed' => [
            'label' => 'AI Failed',
            'class' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'icon' => 'bi bi-exclamation-triangle',
        ],
        'archived' => [
            'label' => 'Archived',
            'class' => 'bg-dark-subtle text-dark border border-dark-subtle',
            'icon' => 'bi bi-archive',
        ],

        // Legacy statuses dari workflow lama, ditampilkan sebagai Ready to Copy.
        'ready_for_review' => [
            'label' => 'Ready to Copy',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-clipboard-check',
        ],
        'approved' => [
            'label' => 'Ready to Copy',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-clipboard-check',
        ],
        'scheduled' => [
            'label' => 'Ready to Copy',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-clipboard-check',
        ],
        'published' => [
            'label' => 'Ready to Copy',
            'class' => 'bg-success-subtle text-success border border-success-subtle',
            'icon' => 'bi bi-clipboard-check',
        ],
    ];
@endphp

<div class="container-fluid px-4 py-4 article-index-page">

    <div id="articleToast" class="alert rounded-4 shadow-sm position-fixed d-none article-toast" role="alert"></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Tools</div>
                <h1 class="page-title mb-2">Article Generator</h1>
                <p class="page-subtitle mb-0">
                    Buat bahan artikel, SEO, creative direction, dan caption untuk dicopy manual ke website FlexLabs.
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
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Content Generation</div>
        <h4 class="dashboard-section-title mb-1">Article Result Summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau hasil artikel yang sudah siap dicopy, sedang digenerate, atau perlu dicoba ulang.
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
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Ready to Copy</div>
                        <div class="stat-value">{{ number_format($pageReadyToCopyCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel di halaman ini yang sudah siap dipindahkan ke website.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-stars"></i>
                    </div>
                    <div>
                        <div class="stat-title">Generated</div>
                        <div class="stat-value">{{ number_format($pageGeneratedCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel yang sudah punya hasil AI pada halaman ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Need Retry</div>
                        <div class="stat-value">{{ number_format($pageFailedCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Artikel yang gagal digenerate dan perlu dicoba ulang.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Marketing Workspace</div>
        <h4 class="dashboard-section-title mb-1">How This Tool Works</h4>
        <p class="dashboard-section-subtitle mb-0">
            FlexOps membantu menyiapkan bahan konten. Publikasi final tetap dilakukan manual di website FlexLabs.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Generate dari Brief</div>
                            <div class="small text-muted">
                                Isi judul, keyword, target pembaca, tone, dan arahan utama. Sistem akan membuat artikel lengkap secara otomatis.
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
                            <div class="fw-bold text-dark mb-1">SEO & Creative Ready</div>
                            <div class="small text-muted">
                                Setiap hasil artikel dilengkapi meta description, OG copy, tags, alt text, image prompt, dan caption sosial.
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
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Copy to Website</div>
                            <div class="small text-muted">
                                Buka result artikel, copy HTML artikel atau SEO bundle, lalu masukkan manual ke flexlabs.co.id.
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
                    Temukan hasil artikel berdasarkan judul, status, kategori, atau tipe konten.
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
                            @foreach($statusFilterOptions as $value => $label)
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
                <h5 class="content-card-title mb-1">Article Results</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola hasil artikel yang sudah digenerate dan siap dipindahkan ke website FlexLabs.
                </p>
            </div>

            @if($canCreateArticle)
                <a href="{{ route('articles.create') }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-plus-lg me-2"></i>Create & Generate
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
                                <th class="text-nowrap">Article Result</th>
                                <th class="text-nowrap">Source</th>
                                <th class="text-nowrap">SEO Snapshot</th>
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

                                    $hasArticleBody = filled($article->body_html);
                                    $hasSeo = filled($article->seo_title) || filled($article->meta_description);
                                    $hasCreative = ! empty($article->creative_brief) || filled($article->hero_image_alt);
                                @endphp

                                <tr id="article-row-{{ $article->id }}">
                                    <td class="text-muted">
                                        {{ $rowNumber }}
                                    </td>

                                    <td style="min-width: 340px;">
                                        <div class="fw-bold text-dark">
                                            <a href="{{ route('articles.show', $article) }}" class="text-decoration-none text-dark">
                                                {{ $article->title ?: 'Untitled Article' }}
                                            </a>
                                        </div>

                                        <div class="text-muted small mt-1">
                                            {{ $categoryLabel }} · {{ $typeLabel }}
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap mt-2">
                                            <span class="article-mini-badge {{ $hasArticleBody ? 'is-ready' : '' }}">
                                                <i class="bi {{ $hasArticleBody ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                Article
                                            </span>

                                            <span class="article-mini-badge {{ $hasSeo ? 'is-ready' : '' }}">
                                                <i class="bi {{ $hasSeo ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                SEO
                                            </span>

                                            <span class="article-mini-badge {{ $hasCreative ? 'is-ready' : '' }}">
                                                <i class="bi {{ $hasCreative ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                Creative
                                            </span>
                                        </div>

                                        @if($article->excerpt)
                                            <div class="text-muted small mt-2 text-truncate" style="max-width: 560px;">
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
                                                Manual brief
                                            </div>
                                        @endif
                                    </td>

                                    <td style="min-width: 250px;">
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
                                            <div class="text-muted small text-truncate" style="max-width: 300px;">
                                                {{ $article->meta_description }}
                                            </div>
                                        @else
                                            <div class="text-muted small">
                                                Meta description belum tersedia.
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
                                                        <i class="bi bi-eye me-2"></i>View Result
                                                    </a>
                                                </li>

                                                @if($canUpdateArticle && $article->canBeEdited())
                                                    <li>
                                                        <a href="{{ route('articles.edit', $article) }}" class="dropdown-item">
                                                            <i class="bi bi-stars me-2"></i>Edit & Regenerate
                                                        </a>
                                                    </li>
                                                @endif

                                                @if(
                                                    ($canArchiveArticle && $article->status !== 'archived')
                                                    || $canDeleteArticle
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
                                                            data-message="Artikel akan dipindahkan ke arsip dan tidak ditampilkan sebagai hasil aktif."
                                                            data-confirm-label="Archive"
                                                            data-confirm-class="btn-warning"
                                                            data-row-id="article-row-{{ $article->id }}"
                                                        >
                                                            <i class="bi bi-archive me-2"></i>Archive
                                                        </button>
                                                    </li>
                                                @endif

                                                @if($canDeleteArticle)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-danger js-confirm-article-action"
                                                            data-action="delete"
                                                            data-method="DELETE"
                                                            data-url="{{ route('articles.destroy', $article) }}"
                                                            data-title="Delete artikel?"
                                                            data-message="Artikel akan dihapus dari daftar Article Generator."
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

                    <h5 class="empty-state-title">Belum ada hasil artikel</h5>
                    <p class="empty-state-text mb-0">
                        Mulai dengan membuat artikel baru atau gunakan data workshop sebagai bahan awal.
                    </p>

                    @if($canCreateArticle)
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ route('articles.create') }}" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-lg me-2"></i>Create & Generate Article
                            </a>

                           
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
                        Gunakan data workshop sebagai bahan awal artikel. Setelah form dibuka, submit akan langsung generate seluruh hasil AI.
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

@push('styles')
<style>
    .article-toast {
        top: 96px;
        right: 24px;
        z-index: 99999;
        min-width: 280px;
        max-width: 420px;
    }

    .article-mini-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .55rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, .08);
        color: #64748b;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
    }

    .article-mini-badge.is-ready {
        background: rgba(59, 142, 77, .1);
        border-color: rgba(59, 142, 77, .18);
        color: #3B8E4D;
    }

    @media (max-width: 767.98px) {
        .article-toast {
            top: 84px;
            left: 16px;
            right: 16px;
            min-width: 0;
            max-width: none;
        }
    }
</style>
@endpush

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

            toastEl.className = `alert ${className} rounded-4 shadow-sm position-fixed article-toast`;
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