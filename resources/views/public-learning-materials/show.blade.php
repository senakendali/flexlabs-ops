@extends('layouts.public')

@section('title', $material->title . ' | FlexLabs')
@section('meta_description', $material->subtitle ?: 'Materi public FlexLabs untuk trial class dan workshop.')
@section('brand_url', url('/workshop'))

@section('content')
@php
    $formattedDate = $material->event_date
        ? $material->event_date->format('d M Y')
        : '-';

    $formattedSchedule = ($material->starts_at && $material->ends_at)
        ? $material->starts_at->format('H:i') . ' - ' . $material->ends_at->format('H:i')
        : '-';

    $formattedAccessEnd = $material->access_ends_at
        ? $material->access_ends_at->format('d M Y H:i')
        : '-';

    $coverImage = $material->cover_image_path
        ? asset('storage/' . $material->cover_image_path)
        : asset('images/hero.png');

    $blockLabelMap = [
        'heading' => 'Opening',
        'text' => 'Materi',
        'code' => 'Kode Praktik',
        'image' => 'Gambar',
        'note' => 'Catatan',
        'task' => 'Tugas Praktik',
    ];

    $blockIconMap = [
        'heading' => 'bi bi-flag',
        'text' => 'bi bi-file-earmark-text',
        'code' => 'bi bi-code-slash',
        'image' => 'bi bi-image',
        'note' => 'bi bi-lightbulb',
        'task' => 'bi bi-clipboard-check',
    ];
@endphp

<section class="hero-section">
    <div class="container">
        <div class="row g-4 hero-row align-items-center">
            <div class="col-lg-7">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-file-earmark-code"></i>
                        {{ ucfirst($material->type) }} Material
                    </span>

                    <h1 class="display-4 fw-bold lh-1 mt-4 mb-3">
                        {{ $material->title }}
                    </h1>

                    @if($material->subtitle)
                        <p class="fs-3 fw-bold text-primary mb-3">
                            {{ $material->subtitle }}
                        </p>
                    @endif

                    @if($material->description)
                        <div class="hero-desc mb-0">
                            {!! nl2br(e($material->description)) !!}
                        </div>
                    @endif

                    <div class="hero-mobile-image-wrap d-lg-none mt-4">
                        <img
                            src="{{ $coverImage }}"
                            alt="{{ $material->title }}"
                            class="hero-image hero-image-mobile"
                        >
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <span class="hero-badge">
                            <i class="bi bi-calendar-event"></i>
                            {{ $formattedDate }}
                        </span>

                        <span class="hero-badge">
                            <i class="bi bi-clock"></i>
                            {{ $formattedSchedule }}
                        </span>

                        @if($material->instructor_name)
                            <span class="hero-badge">
                                <i class="bi bi-person-video3"></i>
                                {{ $material->instructor_name }}
                            </span>
                        @endif
                    </div>

                    <div class="hero-cta-wrap d-flex flex-wrap gap-3 mt-4">
                        <a href="#material-content" class="btn btn-brand btn-lg">
                            Mulai Baca Materi
                        </a>

                        <a href="#session-info" class="btn btn-outline-light btn-lg">
                            Info Sesi
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 hero-visual-col d-none d-lg-flex">
                <div class="hero-visual">
                    <img
                        src="{{ $coverImage }}"
                        alt="{{ $material->title }}"
                        class="hero-image hero-image-desktop"
                    >
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-highlight-section" id="session-info">
    <div class="container-fluid px-0">
        <div class="stats-highlight-full">
            <div class="row g-0">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>

                            <div>
                                <div class="stats-highlight-title">
                                    {{ $formattedDate }}
                                </div>

                                <div class="text-white-50 small">
                                    Event Date
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-clock"></i>
                            </div>

                            <div>
                                <div class="stats-highlight-title">
                                    {{ $formattedSchedule }}
                                </div>

                                <div class="text-white-50 small">
                                    Schedule
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-hourglass-bottom"></i>
                            </div>

                            <div>
                                <div class="stats-highlight-title">
                                    {{ $formattedAccessEnd }}
                                </div>

                                <div class="text-white-50 small">
                                    Access Until
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item stats-highlight-item-last">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-person-video3"></i>
                            </div>

                            <div>
                                <div class="stats-highlight-title">
                                    {{ $material->instructor_name ?: 'FlexLabs Instructor' }}
                                </div>

                                <div class="text-white-50 small">
                                    Instructor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>
</section>

<section class="about-section">
    <div class="about-full">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <span class="section-label">Learning Tip</span>

                    <h2 class="section-title-large mt-3">
                        Baca, copy kode, lalu langsung praktik
                    </h2>
                </div>

                <div class="col-lg-7">
                    <p class="about-main-text">
                        Materi ini dibuat supaya teman-teman bisa mengikuti sesi trial atau workshop dengan lebih mudah.
                        Ikuti urutan materi dari atas ke bawah, copy contoh kode yang tersedia, lalu praktikkan langsung.
                    </p>

                    <p class="about-main-text mb-0">
                        Kalau ada instruksi task, kerjakan pelan-pelan. Tujuannya bukan cuma selesai,
                        tapi paham alur berpikir dan struktur kodenya.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content-section" id="material-content">
    <div class="container">
        <div class="workshop-list-heading text-center">
            <span class="section-label">Material Content</span>

            <h2 class="section-title mt-3">
                Materi yang perlu teman-teman ikuti
            </h2>

            <p class="section-subtitle">
                Ikuti block materi secara berurutan. Gunakan tombol copy pada code snippet kalau tersedia.
            </p>
        </div>

        @if($material->activeBlocks->count())
            <div class="row g-4">
                @foreach($material->activeBlocks as $index => $block)
                    @php
                        $blockNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                        $blockTitle = $block->title ?: ucfirst($block->type);
                        $blockLabel = $blockLabelMap[$block->type] ?? ucfirst($block->type);
                        $blockIcon = $blockIconMap[$block->type] ?? 'bi bi-circle';
                        $codeContent = trim($block->code_content ?? '');
                    @endphp

                    <div class="col-12">
                        <article class="workshop-card">
                            <div class="workshop-card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                    <div class="workshop-meta mb-0">
                                        <span>
                                            <i class="{{ $blockIcon }} me-1"></i>
                                            {{ $blockLabel }}
                                        </span>

                                        <span class="workshop-meta-dot"></span>

                                        <span>
                                            Step {{ $blockNumber }}
                                        </span>
                                    </div>
                                </div>

                                @if($block->type === 'heading')
                                    <h2 class="workshop-card-title mb-3">
                                        {{ $blockTitle }}
                                    </h2>

                                    @if($block->content)
                                        <div class="about-main-text mb-0">
                                            {!! nl2br(e($block->content)) !!}
                                        </div>
                                    @endif

                                @elseif($block->type === 'text')
                                    <h3 class="workshop-card-title mb-3">
                                        {{ $blockTitle }}
                                    </h3>

                                    <div class="about-main-text mb-0">
                                        {!! nl2br(e($block->content)) !!}
                                    </div>

                                @elseif($block->type === 'code')
                                    <h3 class="workshop-card-title mb-3">
                                        {{ $blockTitle }}
                                    </h3>

                                    <div class="public-material-code-shell">
                                        <div class="public-material-code-toolbar">
                                            <span class="public-material-code-language">
                                                <i class="bi bi-terminal me-1"></i>
                                                {{ $block->code_language ? strtoupper($block->code_language) : 'CODE' }}
                                            </span>

                                            <button
                                                type="button"
                                                class="btn btn-brand btn-sm"
                                                data-code-base64="{{ base64_encode($codeContent) }}"
                                            >
                                                <i class="bi bi-copy me-1"></i>
                                                Copy Code
                                            </button>
                                        </div>

                                        <pre class="public-material-code-block"><code>{{ $codeContent }}</code></pre>
                                    </div>

                                @elseif($block->type === 'image')
                                    <h3 class="workshop-card-title mb-3">
                                        {{ $blockTitle }}
                                    </h3>

                                    @if($block->image_path)
                                        <img
                                            src="{{ asset('storage/' . $block->image_path) }}"
                                            alt="{{ $block->image_caption ?: $blockTitle }}"
                                            class="img-fluid rounded-4 border"
                                        >
                                    @endif

                                    @if($block->image_caption)
                                        <div class="about-main-text mt-3 mb-0">
                                            {{ $block->image_caption }}
                                        </div>
                                    @endif

                                @elseif($block->type === 'note')
                                    <div class="public-material-callout public-material-callout-note">
                                        <div class="public-material-callout-icon">
                                            <i class="bi bi-lightbulb"></i>
                                        </div>

                                        <div class="public-material-callout-content">
                                            <div class="public-material-callout-label">
                                                Learning Note
                                            </div>

                                            <h3 class="public-material-callout-title">
                                                {{ $blockTitle }}
                                            </h3>

                                            <div class="public-material-callout-text">
                                                {!! nl2br(e($block->content)) !!}
                                            </div>
                                        </div>
                                    </div>

                                @elseif($block->type === 'task')
                                    <div class="public-material-callout public-material-callout-task">
                                        <div class="public-material-callout-icon">
                                            <i class="bi bi-clipboard-check"></i>
                                        </div>

                                        <div class="public-material-callout-content">
                                            <div class="public-material-callout-label">
                                                Practice Task
                                            </div>

                                            <h3 class="public-material-callout-title">
                                                {{ $blockTitle }}
                                            </h3>

                                            <div class="public-material-callout-text">
                                                {!! nl2br(e($block->content)) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @else
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <article class="workshop-card">
                        <div class="workshop-card-body text-center">
                            <div class="section-label mb-3">
                                Empty Material
                            </div>

                            <h3 class="workshop-card-title">
                                Materi belum tersedia
                            </h3>

                            <div class="about-main-text mb-0">
                                Admin belum menambahkan content block untuk materi ini.
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        @endif

        @if($material->images->count())
            <div class="workshop-list-heading text-center mt-5">
                <span class="section-label">Gallery</span>

                <h2 class="section-title mt-3">
                    Supporting Images
                </h2>

                <p class="section-subtitle">
                    Gambar pendukung untuk membantu teman-teman memahami materi.
                </p>
            </div>

            <div class="row g-4">
                @foreach($material->images as $image)
                    <div class="col-lg-4 col-md-6">
                        <article class="workshop-card">
                            <div class="workshop-card-media">
                                <img
                                    src="{{ asset('storage/' . $image->image_path) }}"
                                    alt="{{ $image->caption ?: $material->title }}"
                                    class="workshop-card-image"
                                >
                            </div>

                            @if($image->caption)
                                <div class="workshop-card-body">
                                    <div class="about-main-text mb-0">
                                        {{ $image->caption }}
                                    </div>
                                </div>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function decodeBase64Unicode(value) {
        if (!value) {
            return '';
        }

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

    document.querySelectorAll('[data-code-base64]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const code = decodeBase64Unicode(button.getAttribute('data-code-base64') || '');

            if (!code) {
                return;
            }

            const originalHtml = button.innerHTML;

            try {
                await navigator.clipboard.writeText(code);

                button.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                }, 1400);
            } catch (error) {
                button.innerHTML = '<i class="bi bi-x-circle me-1"></i>Failed';

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                }, 1400);
            }
        });
    });
});
</script>
@endpush