@extends('layouts.app-dashboard')

@section('content')
@php
    $creativeBrief = $article->creative_brief ?? [];
    $creativeBrief = is_array($creativeBrief) ? $creativeBrief : [];

    $socialCaptions = $article->social_captions ?? [];
    $socialCaptions = is_array($socialCaptions) ? $socialCaptions : [];

    $aiOutline = $article->ai_outline ?? [];
    $aiOutline = is_array($aiOutline) ? $aiOutline : [];

    $arrayToText = function ($value): string {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        return collect($value)
            ->filter(fn ($item) => filled($item))
            ->map(function ($item) use (&$arrayToText) {
                if (is_array($item)) {
                    return $arrayToText($item);
                }

                return trim((string) $item);
            })
            ->filter()
            ->implode(', ');
    };

    $listToLines = function ($value) use ($arrayToText): string {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        return collect($value)
            ->filter(fn ($item) => filled($item))
            ->map(function ($item) use ($arrayToText) {
                if (is_array($item)) {
                    return $arrayToText($item);
                }

                return '- ' . trim((string) $item);
            })
            ->filter()
            ->implode("\n");
    };

    $secondaryKeywordsText = $arrayToText($article->secondary_keywords ?? []);
    $metaKeywordsText = $arrayToText($article->meta_keywords ?? []);
    $tagsText = $arrayToText($article->tags ?? []);

    $heroImageConcept = $creativeBrief['hero_image_concept'] ?? '';
    $visualStyle = $creativeBrief['visual_style'] ?? '';
    $creativeNotes = $creativeBrief['creative_notes'] ?? ($creativeBrief['notes'] ?? '');
    $imagePrompt = $creativeBrief['image_prompt'] ?? '';
    $visualElements = $listToLines($creativeBrief['visual_elements'] ?? []);

    $instagramCaption = $socialCaptions['instagram'] ?? '';
    $linkedinCaption = $socialCaptions['linkedin'] ?? '';
    $whatsappCaption = $socialCaptions['whatsapp'] ?? '';

    $outlineSections = collect($aiOutline['outline'] ?? [])->filter();
    $titleOptions = collect($aiOutline['title_options'] ?? [])->filter();
    $seoNotes = collect($aiOutline['seo_notes'] ?? [])->filter();

    $articleBodyHtml = (string) ($article->body_html ?? '');
@endphp

<div class="container-fluid px-4 py-4 article-show-page">

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing / Article Generator</div>
                <h1 class="page-title mb-2">
                    {{ $article->title ?: 'Generated Article Result' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Hasil bahan artikel untuk dicopy ke website FlexLabs. Review konten, SEO, creative direction, dan caption sebelum dipindahkan.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('articles.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                <a href="{{ route('articles.edit', $article) }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-pencil-square me-1"></i> Edit Brief
                </a>

                
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
        </div>
    @endif

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Copy Actions</h5>
                <p class="content-card-subtitle mb-0">
                    Gunakan tombol copy untuk memindahkan hasil artikel ke website, caption, atau brief creative.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <button type="button" class="copy-action-card w-100 js-copy-field" data-copy-target="articleBodyCodeText">
                        <span class="copy-action-icon"><i class="bi bi-code-slash"></i></span>
                        <span>
                            <span class="copy-action-title">Copy Article Code</span>
                            <span class="copy-action-subtitle">Source HTML artikel</span>
                        </span>
                    </button>
                </div>

                <div class="col-xl-3 col-md-6">
                    <button type="button" class="copy-action-card w-100" id="copySeoBundleBtn">
                        <span class="copy-action-icon"><i class="bi bi-search-heart"></i></span>
                        <span>
                            <span class="copy-action-title">Copy SEO Bundle</span>
                            <span class="copy-action-subtitle">Title, meta, tags, OG</span>
                        </span>
                    </button>
                </div>

                <div class="col-xl-3 col-md-6">
                    <button type="button" class="copy-action-card w-100" id="copyCreativeBundleBtn">
                        <span class="copy-action-icon"><i class="bi bi-image"></i></span>
                        <span>
                            <span class="copy-action-title">Copy Creative Brief</span>
                            <span class="copy-action-subtitle">Visual, prompt, alt text</span>
                        </span>
                    </button>
                </div>

                <div class="col-xl-3 col-md-6">
                    <button type="button" class="copy-action-card w-100" id="copyCaptionBundleBtn">
                        <span class="copy-action-icon"><i class="bi bi-megaphone"></i></span>
                        <span>
                            <span class="copy-action-title">Copy Captions</span>
                            <span class="copy-action-subtitle">Instagram, LinkedIn, WhatsApp</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Article Result</h5>
                        <p class="content-card-subtitle mb-0">
                            Preview hasil HTML yang sudah digenerate. Tombol Copy Code akan menyalin source HTML untuk dimasukkan ke website FlexLabs.
                        </p>
                    </div>

                    
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <label class="form-label">Title</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="articleTitleText"
                                    class="form-control"
                                    value="{{ $article->title }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="articleTitleText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Slug</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="articleSlugText"
                                    class="form-control"
                                    value="{{ $article->slug }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="articleSlugText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Excerpt</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="articleExcerptText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="articleExcerptText"
                                rows="4"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->excerpt }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Generated HTML Preview</label>
                                <button type="button" class="btn btn-primary btn-sm js-copy-field" data-copy-target="articleBodyCodeText">
                                    <i class="bi bi-code-slash me-1"></i> Copy HTML Code
                                </button>
                            </div>

                            <div class="article-html-preview-wrap">
                                <div id="articleBodyPreview" class="article-html-preview">
                                    @if(filled($articleBodyHtml))
                                        {!! $articleBodyHtml !!}
                                    @else
                                        <div class="empty-result-box">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <div>
                                                <div class="fw-bold">Belum ada generated HTML</div>
                                                <div class="small text-muted">Generate artikel dulu untuk melihat preview.</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <textarea
                                id="articleBodyCodeText"
                                class="d-none"
                                readonly
                            >{{ $articleBodyHtml }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(! empty($aiOutline))
            <div class="col-12">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Outline Result</h5>
                            <p class="content-card-subtitle mb-0">
                                Struktur artikel dari AI sebagai referensi saat review konten.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="article-result-card h-100">
                                    <div class="article-result-title">Recommended Title</div>
                                    <div class="article-result-text">
                                        {{ $aiOutline['recommended_title'] ?? '-' }}
                                    </div>

                                    @if(! empty($aiOutline['recommended_slug']))
                                        <div class="article-result-title mt-3">Recommended Slug</div>
                                        <div class="article-result-text">
                                            {{ $aiOutline['recommended_slug'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="article-result-card h-100">
                                    <div class="article-result-title">Title Options</div>

                                    @if($titleOptions->count())
                                        <ul class="small text-muted mb-0 ps-3">
                                            @foreach($titleOptions as $titleOption)
                                                <li>{{ $titleOption }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="article-result-text">Belum ada title options.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="article-result-card h-100">
                                    <div class="article-result-title">CTA</div>
                                    <div class="article-result-text">
                                        {{ $aiOutline['cta'] ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            @if($outlineSections->count())
                                <div class="col-12">
                                    <div class="article-result-card">
                                        <div class="article-result-title mb-3">Outline Sections</div>

                                        <div class="row g-3">
                                            @foreach($outlineSections as $index => $section)
                                                <div class="col-lg-6">
                                                    <div class="article-outline-item h-100">
                                                        <div class="d-flex align-items-start gap-2 mb-2">
                                                            <span class="article-outline-number">{{ $index + 1 }}</span>
                                                            <div>
                                                                <div class="fw-bold mb-1">
                                                                    {{ $section['heading'] ?? '-' }}
                                                                </div>
                                                                <div class="text-muted small">
                                                                    {{ $section['summary'] ?? '-' }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if(! empty($section['key_points']) && is_array($section['key_points']))
                                                            <ul class="small text-muted mb-0 ps-3">
                                                                @foreach($section['key_points'] as $point)
                                                                    <li>{{ $point }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($seoNotes->count())
                                <div class="col-12">
                                    <div class="article-result-card">
                                        <div class="article-result-title mb-2">SEO Notes</div>
                                        <ul class="small text-muted mb-0 ps-3">
                                            @foreach($seoNotes as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Brief Result</h5>
                        <p class="content-card-subtitle mb-0">
                            Arahan brief utama yang dipakai untuk menghasilkan artikel.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Primary Keyword</label>
                            <input
                                type="text"
                                id="primaryKeywordText"
                                class="form-control"
                                value="{{ $article->primary_keyword }}"
                                disabled
                                readonly
                            >
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Secondary Keywords</label>
                            <input
                                type="text"
                                id="secondaryKeywordsText"
                                class="form-control"
                                value="{{ $secondaryKeywordsText }}"
                                disabled
                                readonly
                            >
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Target Audience</label>
                            <input
                                type="text"
                                id="targetAudienceText"
                                class="form-control"
                                value="{{ $article->target_audience }}"
                                disabled
                                readonly
                            >
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Tone</label>
                            <input
                                type="text"
                                id="toneText"
                                class="form-control"
                                value="{{ $article->tone_label ?? ucfirst(str_replace('_', ' ', (string) $article->tone)) }}"
                                disabled
                                readonly
                            >
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Main Angle</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="mainAngleText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="mainAngleText"
                                rows="4"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->main_angle }}</textarea>
                        </div>

                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Must Include</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="mustIncludeText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="mustIncludeText"
                                rows="6"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->must_include }}</textarea>
                        </div>

                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Avoid Points</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="avoidPointsText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="avoidPointsText"
                                rows="6"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->avoid_points }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">SEO Result</h5>
                        <p class="content-card-subtitle mb-0">
                            Copy SEO title, meta description, OG copy, dan tags ke website FlexLabs.
                        </p>
                    </div>

                    <button type="button" class="btn btn-light border btn-sm" id="copySeoBundleBtnHeader">
                        <i class="bi bi-clipboard me-1"></i> Copy SEO Bundle
                    </button>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">SEO Title</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="seoTitleText"
                                    class="form-control"
                                    value="{{ $article->seo_title }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="seoTitleText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Tags</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="tagsText"
                                    class="form-control"
                                    value="{{ $tagsText }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="tagsText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Meta Description</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="metaDescriptionText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="metaDescriptionText"
                                rows="4"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->meta_description }}</textarea>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Meta Keywords</label>
                            <input
                                type="text"
                                id="metaKeywordsText"
                                class="form-control"
                                value="{{ $metaKeywordsText }}"
                                disabled
                                readonly
                            >
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">OG Image URL</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="ogImageUrlText"
                                    class="form-control"
                                    value="{{ $article->og_image_url }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="ogImageUrlText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">OG Title</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="ogTitleText"
                                    class="form-control"
                                    value="{{ $article->og_title }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="ogTitleText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">OG Description</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="ogDescriptionText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="ogDescriptionText"
                                rows="4"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $article->og_description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Creative Result</h5>
                        <p class="content-card-subtitle mb-0">
                            Arahan visual untuk designer atau image generator.
                        </p>
                    </div>

                    <button type="button" class="btn btn-light border btn-sm" id="copyCreativeBundleBtnHeader">
                        <i class="bi bi-clipboard me-1"></i> Copy Creative Brief
                    </button>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Hero Image URL</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="heroImageUrlText"
                                    class="form-control"
                                    value="{{ $article->hero_image_url }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="heroImageUrlText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Hero Image Alt Text</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="heroImageAltText"
                                    class="form-control"
                                    value="{{ $article->hero_image_alt }}"
                                    disabled
                                    readonly
                                >
                                <button type="button" class="btn btn-light border js-copy-field" data-copy-target="heroImageAltText">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Hero Image Concept</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="heroImageConceptText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="heroImageConceptText"
                                rows="6"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $heroImageConcept }}</textarea>
                        </div>

                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Visual Style</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="visualStyleText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="visualStyleText"
                                rows="6"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $visualStyle }}</textarea>
                        </div>

                        @if(filled($visualElements))
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                    <label class="form-label mb-0">Visual Elements</label>
                                    <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="visualElementsText">
                                        <i class="bi bi-clipboard me-1"></i> Copy
                                    </button>
                                </div>

                                <textarea
                                    id="visualElementsText"
                                    rows="5"
                                    class="form-control article-result-textarea"
                                    disabled
                                    readonly
                                >{{ $visualElements }}</textarea>
                            </div>
                        @endif

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Image Prompt</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="imagePromptText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="imagePromptText"
                                rows="5"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $imagePrompt }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Creative Notes</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="creativeNotesText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="creativeNotesText"
                                rows="5"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $creativeNotes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Social Caption Result</h5>
                        <p class="content-card-subtitle mb-0">
                            Caption turunan untuk distribusi artikel ke channel marketing.
                        </p>
                    </div>

                    <button type="button" class="btn btn-light border btn-sm" id="copyCaptionBundleBtnHeader">
                        <i class="bi bi-clipboard me-1"></i> Copy Captions
                    </button>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">Instagram</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="instagramCaptionText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="instagramCaptionText"
                                rows="8"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $instagramCaption }}</textarea>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">LinkedIn</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="linkedinCaptionText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="linkedinCaptionText"
                                rows="8"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $linkedinCaption }}</textarea>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <label class="form-label mb-0">WhatsApp</label>
                                <button type="button" class="btn btn-light border btn-sm js-copy-field" data-copy-target="whatsappCaptionText">
                                    <i class="bi bi-clipboard me-1"></i> Copy
                                </button>
                            </div>

                            <textarea
                                id="whatsappCaptionText"
                                rows="8"
                                class="form-control article-result-textarea"
                                disabled
                                readonly
                            >{{ $whatsappCaption }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card">
                <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="footer-note">
                        <div class="footer-note-title">Result Ready</div>
                        <div class="footer-note-subtitle">
                            Copy bagian yang dibutuhkan, lalu masukkan manual ke website FlexLabs.
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('articles.index') }}" class="btn btn-light border">
                            Back
                        </a>

                        <a href="{{ route('articles.edit', $article) }}" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> Edit Brief
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .copy-action-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 22px;
        background: #fff;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        text-align: left;
        transition: .15s ease;
        min-height: 96px;
    }

    .copy-action-card:hover {
        transform: translateY(-1px);
        border-color: rgba(91, 62, 142, .25);
        box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
    }

    .copy-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, .1);
        color: #5B3E8E;
        font-size: 1.15rem;
        flex: 0 0 auto;
    }

    .copy-action-title {
        display: block;
        color: #0f172a;
        font-weight: 900;
        font-size: .92rem;
        margin-bottom: .2rem;
    }

    .copy-action-subtitle {
        display: block;
        color: #64748b;
        font-size: .8rem;
        line-height: 1.35;
    }

    .article-html-preview-wrap {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 22px;
        background: #fff;
        overflow: hidden;
    }

    .article-html-preview {
        min-height: 520px;
        padding: 1.25rem;
        color: #0f172a;
        line-height: 1.75;
        background: #fff;
    }

    .article-html-preview h1,
    .article-html-preview h2,
    .article-html-preview h3,
    .article-html-preview h4 {
        color: #0f172a;
        font-weight: 900;
        margin-top: 1.35rem;
        margin-bottom: .75rem;
    }

    .article-html-preview h1:first-child,
    .article-html-preview h2:first-child,
    .article-html-preview h3:first-child {
        margin-top: 0;
    }

    .article-html-preview p {
        color: #334155;
        margin-bottom: 1rem;
    }

    .article-html-preview ul,
    .article-html-preview ol {
        color: #334155;
        margin-bottom: 1rem;
        padding-left: 1.35rem;
    }

    .article-html-preview a {
        color: #5B3E8E;
        font-weight: 700;
    }

    .article-html-preview blockquote {
        border-left: 4px solid #5B3E8E;
        padding: .75rem 1rem;
        background: rgba(91, 62, 142, .06);
        border-radius: 0 14px 14px 0;
        color: #334155;
    }

    .article-result-textarea {
        background: #f8fafc;
        border-color: rgba(15, 23, 42, .08);
        line-height: 1.65;
        resize: vertical;
        color: #0f172a;
        opacity: 1;
        -webkit-text-fill-color: #0f172a;
        cursor: default;
    }

    .article-result-card {
        border-radius: 22px;
        background: rgba(255, 255, 255, .88);
        border: 1px solid rgba(15, 23, 42, .08);
        padding: 1rem;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
    }

    .article-result-title {
        color: #0f172a;
        font-weight: 900;
        font-size: .95rem;
        margin-bottom: .35rem;
    }

    .article-result-text {
        color: #64748b;
        font-size: .84rem;
        line-height: 1.55;
        margin-bottom: .85rem;
    }

    .article-outline-item {
        border-radius: 18px;
        border: 1px solid rgba(15, 23, 42, .08);
        background: #fff;
        padding: 1rem;
    }

    .article-outline-number {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        background: rgba(91, 62, 142, .1);
        color: #5B3E8E;
        flex: 0 0 auto;
    }

    .empty-result-box {
        min-height: 360px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .9rem;
        text-align: left;
        color: #64748b;
    }

    .empty-result-box i {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, .1);
        color: #5B3E8E;
        font-size: 1.2rem;
    }

    .form-control:disabled,
    .form-control[readonly] {
        background-color: #f8fafc;
        opacity: 1;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toastContainer = document.getElementById('toastContainer');

    function showToast(message, type = 'success') {
        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1600 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function getFieldValue(id) {
        const field = document.getElementById(id);

        if (!field) return '';

        return String(field.value || field.textContent || '').trim();
    }

    async function copyText(text, successMessage = 'Copied.') {
        const value = String(text || '').trim();

        if (!value) {
            showToast('Belum ada teks untuk dicopy.', 'warning');
            return;
        }

        try {
            await navigator.clipboard.writeText(value);
            showToast(successMessage, 'success');
        } catch (error) {
            showToast('Browser tidak mengizinkan copy otomatis. Copy manual dulu ya.', 'warning');
        }
    }

    function buildBundle(items) {
        return items
            .map(function (item) {
                const value = getFieldValue(item.id);

                if (!value) return '';

                return `${item.label}\n${value}`;
            })
            .filter(Boolean)
            .join('\n\n---\n\n');
    }

    document.querySelectorAll('.js-copy-field').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.dataset.copyTarget;
            const value = getFieldValue(targetId);

            copyText(value, 'Berhasil dicopy.');
        });
    });

    function copySeoBundle() {
        const bundle = buildBundle([
            { label: 'SEO Title', id: 'seoTitleText' },
            { label: 'Slug', id: 'articleSlugText' },
            { label: 'Meta Description', id: 'metaDescriptionText' },
            { label: 'Meta Keywords', id: 'metaKeywordsText' },
            { label: 'Tags', id: 'tagsText' },
            { label: 'OG Title', id: 'ogTitleText' },
            { label: 'OG Description', id: 'ogDescriptionText' },
            { label: 'OG Image URL', id: 'ogImageUrlText' },
        ]);

        copyText(bundle, 'SEO bundle berhasil dicopy.');
    }

    function copyCreativeBundle() {
        const bundle = buildBundle([
            { label: 'Hero Image URL', id: 'heroImageUrlText' },
            { label: 'Hero Image Alt Text', id: 'heroImageAltText' },
            { label: 'Hero Image Concept', id: 'heroImageConceptText' },
            { label: 'Visual Style', id: 'visualStyleText' },
            { label: 'Visual Elements', id: 'visualElementsText' },
            { label: 'Image Prompt', id: 'imagePromptText' },
            { label: 'Creative Notes', id: 'creativeNotesText' },
        ]);

        copyText(bundle, 'Creative brief berhasil dicopy.');
    }

    function copyCaptionBundle() {
        const bundle = buildBundle([
            { label: 'Instagram', id: 'instagramCaptionText' },
            { label: 'LinkedIn', id: 'linkedinCaptionText' },
            { label: 'WhatsApp', id: 'whatsappCaptionText' },
        ]);

        copyText(bundle, 'Caption bundle berhasil dicopy.');
    }

    document.getElementById('copySeoBundleBtn')?.addEventListener('click', copySeoBundle);
    document.getElementById('copySeoBundleBtnHeader')?.addEventListener('click', copySeoBundle);

    document.getElementById('copyCreativeBundleBtn')?.addEventListener('click', copyCreativeBundle);
    document.getElementById('copyCreativeBundleBtnHeader')?.addEventListener('click', copyCreativeBundle);

    document.getElementById('copyCaptionBundleBtn')?.addEventListener('click', copyCaptionBundle);
    document.getElementById('copyCaptionBundleBtnHeader')?.addEventListener('click', copyCaptionBundle);
});
</script>
@endpush
@endsection