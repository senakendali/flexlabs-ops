@extends('layouts.builder-hub')

@section('title', $material->title . ' | FlexLabs')
@section('meta_description', $material->subtitle ?: 'Materi FlexLabs untuk trial class dan workshop.')
@section('brand_url', url('/workshop'))
@section('page_kicker', ucfirst($material->type ?? 'Material') . ' Material')
@section('page_title', $material->title)
@section('whatsapp_text', 'Halo FlexLabs, saya ingin konsultasi tentang materi ' . $material->title)

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

    $blocks = $material->activeBlocks->values();

    $blockLabelMap = [
        'heading' => 'Opening',
        'text' => 'Materi',
        'code' => 'Kode Praktik',
        'image' => 'Gambar',
        'note' => 'Catatan',
        'task' => 'Tugas Praktik',
    ];

    $blockBadgeMap = [
        'heading' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'text' => 'bg-slate-50 text-slate-700 ring-slate-100',
        'code' => 'bg-zinc-950 text-white ring-zinc-800',
        'image' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'note' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'task' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    ];

    $galleryImages = $material->images
        ->filter(fn ($image) => (bool) ($image->is_active ?? true))
        ->sortBy(fn ($image) => $image->sort_order ?? 999999)
        ->values();

    $renderRichText = function ($content, $class = '') {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        $class = trim((string) $class);

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><code><pre><blockquote><h1><h2><h3><h4><h5><h6>';

        $hasHtml = $content !== strip_tags($content);

        if ($hasHtml) {
            return '<div class="' . e($class) . '">' . strip_tags($content, $allowedTags) . '</div>';
        }

        $normalizedContent = str_replace(["\r\n", "\r"], "\n", $content);
        $paragraphs = preg_split('/\n+/', $normalizedContent);

        return '<div class="' . e($class) . '">' . collect($paragraphs)
            ->map(fn ($paragraph) => trim($paragraph))
            ->filter()
            ->map(fn ($paragraph) => '<p>' . e($paragraph) . '</p>')
            ->implode('') . '</div>';
    };

    $downloadFilename = function ($image, int $index) use ($material) {
        $extension = pathinfo((string) $image->image_path, PATHINFO_EXTENSION);

        if (! $extension) {
            $extension = 'jpg';
        }

        return \Illuminate\Support\Str::slug($material->title . '-gallery-' . $index) . '.' . $extension;
    };
@endphp

@push('styles')
<style>
    .builder-rich > * + * {
        margin-top: 0.85rem;
    }

    .builder-rich p {
        margin: 0;
        line-height: 1.82;
    }

    .builder-rich ul,
    .builder-rich ol {
        margin: 0.85rem 0 0;
        padding-left: 1.35rem;
        line-height: 1.82;
    }

    .builder-rich ul {
        list-style: disc;
    }

    .builder-rich ol {
        list-style: decimal;
    }

    .builder-rich li + li {
        margin-top: 0.35rem;
    }

    .builder-rich strong,
    .builder-rich b {
        color: #1F1B2E;
        font-weight: 900;
    }

    .builder-rich code {
        border-radius: 0.5rem;
        background: #F0EAF8;
        padding: 0.15rem 0.42rem;
        color: #5B3E8E;
        font-size: 0.9em;
        font-weight: 800;
    }

    .builder-rich blockquote {
        margin: 1rem 0 0;
        border-left: 4px solid #5B3E8E;
        border-radius: 1rem;
        background: #F7F3FC;
        padding: 1rem 1.25rem;
        color: #4F4A5E;
    }
</style>
@endpush

@section('sidebar')
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-flex-primary">
                Material
            </p>

            <h2 class="truncate text-lg font-black tracking-[-0.04em] text-flex-dark">
                Daftar Materi
            </h2>

            <p class="mt-0.5 text-sm font-semibold text-flex-muted">
                {{ $blocks->count() }} langkah praktik
            </p>
        </div>
    </div>

    @if($blocks->count())
        <div class="space-y-3">
            @foreach($blocks as $index => $block)
                @php
                    $blockNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $blockTitle = $block->title ?: ucfirst($block->type);
                    $blockLabel = $blockLabelMap[$block->type] ?? ucfirst($block->type);
                    $stepId = 'material-step-' . $block->id;
                @endphp

                <a
                    href="#{{ $stepId }}"
                    class="group flex items-center gap-4 rounded-[1.5rem] px-4 py-4 transition {{ $loop->first ? 'active bg-flex-primary text-white shadow-button' : 'bg-flex-soft text-flex-dark hover:bg-flex-primarySoft hover:text-flex-primary' }}"
                    data-step-link="{{ $stepId }}"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $loop->first ? 'bg-white/16 text-white' : 'bg-white text-flex-primary shadow-sm' }}">
                        @if($block->type === 'code')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m17.25 6.75 4.5 5.25-4.5 5.25M6.75 6.75 2.25 12l4.5 5.25m7.5-12-4.5 13.5" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @elseif($block->type === 'image')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @elseif($block->type === 'task')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @elseif($block->type === 'note')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.4 14.4 0 0 1-3 0M15 6.75a3 3 0 1 0-6 0c0 1.21.714 2.257 1.744 2.735.214.1.256.366.256.602v.413h2v-.413c0-.236.042-.502.256-.602A3.001 3.001 0 0 0 15 6.75Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endif
                    </span>

                    <span class="min-w-0">
                        <span class="block truncate text-base font-black">
                            {{ \Illuminate\Support\Str::limit($blockTitle, 32) }}
                        </span>

                        <span class="mt-0.5 block truncate text-sm font-semibold {{ $loop->first ? 'text-white/72' : 'text-flex-muted' }}">
                            Step {{ $blockNumber }} · {{ $blockLabel }}
                        </span>
                    </span>

                    <span class="ml-auto hidden h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-flex-primary group-[.active]:flex">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m4.5 12.75 6 6 9-13.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="rounded-[1.5rem] bg-flex-soft p-5">
            <p class="text-sm font-bold leading-6 text-flex-muted">
                Materi belum tersedia.
            </p>
        </div>
    @endif

    <div class="mt-6 rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
            Session Info
        </p>

        <div class="mt-4 space-y-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-flex-muted">
                    Event Date
                </p>
                <p class="mt-1 text-base font-black text-flex-dark">
                    {{ $formattedDate }}
                </p>
            </div>

            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-flex-muted">
                    Schedule
                </p>
                <p class="mt-1 text-base font-black text-flex-dark">
                    {{ $formattedSchedule }}
                </p>
            </div>

            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-flex-muted">
                    Instructor
                </p>
                <p class="mt-1 text-base font-black text-flex-dark">
                    {{ $material->instructor_name ?: 'FlexLabs Instructor' }}
                </p>
            </div>
        </div>

        <a
            href="#material-content"
            class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-5 py-3 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 4.5v15m7.5-7.5h-15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Mulai Baca Materi
        </a>
    </div>
@endsection

@section('content')
    <section class="overflow-hidden rounded-[2rem] border border-flex-line bg-gradient-to-br from-flex-primarySoft via-white to-white">
        <div class="grid gap-8 p-5 sm:p-7 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] lg:p-8">
            <div class="flex min-w-0 flex-col justify-center">
                <div class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-sm ring-1 ring-flex-line">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    {{ ucfirst($material->type) }} Material
                </div>

                <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-flex-dark md:text-5xl xl:text-6xl">
                    {{ $material->title }}
                </h1>

                @if($material->subtitle)
                    <p class="mt-5 text-xl font-black leading-8 text-flex-primary md:text-2xl">
                        {{ $material->subtitle }}
                    </p>
                @endif

                @if($material->description)
                    {!! $renderRichText($material->description, 'builder-rich mt-5 max-w-3xl text-base font-semibold text-flex-muted md:text-lg') !!}
                @endif

                <div class="mt-7 flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6.75 3v2.25m10.5-2.25v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.75V8.25a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        {{ $formattedDate }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        {{ $formattedSchedule }}
                    </span>

                    @if($material->instructor_name)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            {{ $material->instructor_name }}
                        </span>
                    @endif

                    @if($galleryImages->count())
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            {{ $galleryImages->count() }} Supporting Images
                        </span>
                    @endif
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a
                        href="#material-content"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
                    >
                        Mulai Baca Materi
                    </a>

                    @if($galleryImages->count())
                        <a
                            href="#supporting-images"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line transition hover:-translate-y-0.5 hover:text-flex-primary"
                        >
                            Download Gambar
                        </a>
                    @endif
                </div>
            </div>

            <div class="relative">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-flex-primary/10 blur-3xl"></div>

                <div class="relative overflow-hidden rounded-[2rem] bg-white p-3 shadow-soft ring-1 ring-white">
                    <img
                        src="{{ $coverImage }}"
                        alt="{{ $material->title }}"
                        class="h-[260px] w-full rounded-[1.5rem] object-cover sm:h-[360px] lg:h-[430px]"
                    >
                </div>
            </div>
        </div>
    </section>

    <section id="session-info" class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5 text-flex-dark shadow-card">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm ring-1 ring-flex-line">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6.75 3v2.25m10.5-2.25v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.75V8.25a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="mt-4 text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                Event Date
            </p>
            <p class="mt-1 text-lg font-black text-flex-dark">
                {{ $formattedDate }}
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5 text-flex-dark shadow-card">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm ring-1 ring-flex-line">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="mt-4 text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                Schedule
            </p>
            <p class="mt-1 text-lg font-black text-flex-dark">
                {{ $formattedSchedule }}
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5 text-flex-dark shadow-card">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm ring-1 ring-flex-line">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="mt-4 text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                Access Until
            </p>
            <p class="mt-1 text-lg font-black text-flex-dark">
                {{ $formattedAccessEnd }}
            </p>
        </div>

        <div class="rounded-[1.75rem] border border-flex-line bg-flex-primarySoft p-5 text-flex-dark shadow-card">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm ring-1 ring-flex-line">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p class="mt-4 text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                Instructor
            </p>
            <p class="mt-1 text-lg font-black text-flex-dark">
                {{ $material->instructor_name ?: 'FlexLabs Instructor' }}
            </p>
        </div>
    </section>

    <section class="mt-8 rounded-[2rem] border border-flex-line bg-flex-primarySoft p-5 sm:p-7">
        <div class="grid gap-5 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
            <div>
                <span class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-sm">
                    Learning Tip
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-flex-dark md:text-4xl">
                    Baca, copy kode, lalu langsung praktik
                </h2>
            </div>

            <div class="builder-rich text-base font-semibold text-flex-muted">
                <p>
                    Materi ini dibuat supaya teman-teman bisa mengikuti sesi trial atau workshop dengan lebih mudah.
                    Ikuti urutan materi dari atas ke bawah, copy contoh kode yang tersedia, lalu praktikkan langsung.
                </p>

                <p>
                    Kalau ada instruksi task, kerjakan pelan-pelan. Tujuannya bukan cuma selesai,
                    tapi paham alur berpikir dan struktur kodenya.
                </p>
            </div>
        </div>
    </section>

    <section id="material-content" class="mt-10">
        <div class="text-center">
            <span class="inline-flex rounded-full bg-flex-primarySoft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                Material Content
            </span>

            <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-flex-dark md:text-5xl">
                Materi yang perlu teman-teman ikuti
            </h2>

            <p class="mx-auto mt-4 max-w-3xl text-base font-semibold leading-8 text-flex-muted md:text-lg">
                Ikuti block materi secara berurutan. Gunakan tombol copy pada code snippet kalau tersedia.
            </p>
        </div>

        @if($blocks->count())
            <div class="mt-8 rounded-[2rem] border border-flex-line bg-white shadow-card">
                @foreach($blocks as $index => $block)
                    @php
                        $blockNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                        $blockTitle = $block->title ?: ucfirst($block->type);
                        $blockLabel = $blockLabelMap[$block->type] ?? ucfirst($block->type);
                        $blockBadge = $blockBadgeMap[$block->type] ?? 'bg-slate-50 text-slate-700 ring-slate-100';
                        $codeContent = trim($block->code_content ?? '');
                        $stepId = 'material-step-' . $block->id;
                    @endphp

                    <article
                        id="{{ $stepId }}"
                        data-step-section="{{ $stepId }}"
                        class="scroll-mt-32 px-5 py-8 sm:px-7 lg:px-9 {{ ! $loop->first ? 'border-t border-flex-line' : '' }}"
                    >
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black uppercase tracking-[0.14em] ring-1 {{ $blockBadge }}">
                                        {{ $blockLabel }}
                                    </span>

                                    <span class="inline-flex rounded-full bg-flex-soft px-3 py-1.5 text-xs font-black uppercase tracking-[0.14em] text-flex-muted">
                                        Step {{ $blockNumber }}
                                    </span>
                                </div>

                                @if(! in_array($block->type, ['note', 'task']))
                                    <h3 class="text-2xl font-black leading-tight tracking-[-0.04em] text-flex-dark md:text-3xl">
                                        {{ $blockTitle }}
                                    </h3>
                                @endif
                            </div>
                        </div>

                        @if($block->type === 'heading')
                            @if($block->content)
                                {!! $renderRichText($block->content, 'builder-rich text-lg font-semibold text-flex-muted') !!}
                            @endif

                        @elseif($block->type === 'text')
                            {!! $renderRichText($block->content, 'builder-rich text-base font-semibold text-flex-muted md:text-lg') !!}

                        @elseif($block->type === 'code')
                            <div class="overflow-hidden rounded-[1.5rem] border border-zinc-800 bg-zinc-950 shadow-card">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-black uppercase tracking-[0.14em] text-white">
                                        {{ $block->code_language ? strtoupper($block->code_language) : 'CODE' }}
                                    </span>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-black text-zinc-950 transition hover:-translate-y-0.5 hover:bg-flex-primarySoft hover:text-flex-primary"
                                        data-code-base64="{{ base64_encode($codeContent) }}"
                                    >
                                        Copy Code
                                    </button>
                                </div>

                                <pre class="builder-scrollbar max-h-[620px] overflow-auto p-5 text-sm leading-7 text-zinc-100"><code>{{ $codeContent }}</code></pre>
                            </div>

                        @elseif($block->type === 'image')
                            @if($block->image_path)
                                <div class="overflow-hidden rounded-[1.5rem] border border-flex-line bg-flex-soft p-3">
                                    <img
                                        src="{{ asset('storage/' . $block->image_path) }}"
                                        alt="{{ $block->image_caption ?: $blockTitle }}"
                                        class="w-full rounded-[1.2rem] object-cover"
                                    >
                                </div>
                            @endif

                            @if($block->image_caption)
                                <p class="mt-4 text-base font-semibold leading-8 text-flex-muted">
                                    {{ $block->image_caption }}
                                </p>
                            @endif

                        @elseif($block->type === 'note')
                            <div class="rounded-[1.75rem] border border-amber-100 bg-amber-50 p-5 sm:p-6">
                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-amber-600 shadow-sm">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0M15 6.75a3 3 0 1 0-6 0c0 1.21.714 2.257 1.744 2.735.214.1.256.366.256.602v.413h2v-.413c0-.236.042-.502.256-.602A3.001 3.001 0 0 0 15 6.75Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700">
                                            Learning Note
                                        </p>

                                        <h3 class="mt-2 text-2xl font-black leading-tight tracking-[-0.04em] text-flex-dark">
                                            {{ $blockTitle }}
                                        </h3>

                                        {!! $renderRichText($block->content, 'builder-rich mt-3 text-base font-semibold text-flex-muted') !!}
                                    </div>
                                </div>
                            </div>

                        @elseif($block->type === 'task')
                            <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 sm:p-6">
                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">
                                            Practice Task
                                        </p>

                                        <h3 class="mt-2 text-2xl font-black leading-tight tracking-[-0.04em] text-flex-dark">
                                            {{ $blockTitle }}
                                        </h3>

                                        {!! $renderRichText($block->content, 'builder-rich mt-3 text-base font-semibold text-flex-muted') !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="mx-auto mt-8 max-w-3xl rounded-[2rem] border border-flex-line bg-white p-8 text-center shadow-card">
                <span class="inline-flex rounded-full bg-flex-primarySoft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Empty Material
                </span>

                <h3 class="mt-4 text-3xl font-black tracking-[-0.05em] text-flex-dark">
                    Materi belum tersedia
                </h3>

                <p class="mt-3 text-base font-semibold leading-8 text-flex-muted">
                    Admin belum menambahkan content block untuk materi ini.
                </p>
            </div>
        @endif
    </section>

    @if($galleryImages->count())
        <section id="supporting-images" class="mt-12 scroll-mt-32">
            <div class="text-center">
                <span class="inline-flex rounded-full bg-flex-primarySoft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Supporting Images
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-flex-dark md:text-5xl">
                    Gambar Pendukung Materi
                </h2>

                <p class="mx-auto mt-4 max-w-3xl text-base font-semibold leading-8 text-flex-muted md:text-lg">
                    Download gambar pendukung untuk membantu teman-teman mengikuti praktik dengan lebih mudah.
                </p>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($galleryImages as $image)
                    @php
                        $imageUrl = asset('storage/' . $image->image_path);
                        $imageTitle = $image->caption ?: 'Supporting Image ' . $loop->iteration;
                        $fileName = $downloadFilename($image, $loop->iteration);
                    @endphp

                    <article class="overflow-hidden rounded-[2rem] border border-flex-line bg-white shadow-card">
                        <div class="aspect-[4/3] overflow-hidden bg-flex-soft">
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $imageTitle }}"
                                class="h-full w-full object-cover transition duration-500 hover:scale-105"
                            >
                        </div>

                        <div class="p-5">
                            <span class="inline-flex rounded-full bg-flex-primarySoft px-3 py-1.5 text-xs font-black uppercase tracking-[0.14em] text-flex-primary">
                                Image {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>

                            <h3 class="mt-3 text-xl font-black leading-tight tracking-[-0.04em] text-flex-dark">
                                {{ $imageTitle }}
                            </h3>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <a
                                    href="{{ $imageUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center justify-center rounded-full bg-flex-primary px-4 py-2.5 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
                                >
                                    Open Image
                                </a>

                                <a
                                    href="{{ $imageUrl }}"
                                    download="{{ $fileName }}"
                                    class="inline-flex items-center justify-center rounded-full bg-flex-soft px-4 py-2.5 text-sm font-black text-flex-dark transition hover:-translate-y-0.5 hover:bg-flex-primarySoft hover:text-flex-primary"
                                >
                                    Download
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6 rounded-[2rem] border border-flex-line bg-flex-primarySoft p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                            Download Reminder
                        </p>

                        <p class="mt-2 text-base font-semibold leading-8 text-flex-muted">
                            Kalau tombol download membuka gambar di tab baru, klik kanan pada gambar lalu pilih
                            <strong class="font-black text-flex-dark">Save image as</strong>.
                        </p>
                    </div>

                    <a
                        href="#material-content"
                        class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line transition hover:-translate-y-0.5 hover:text-flex-primary"
                    >
                        Kembali ke Materi
                    </a>
                </div>
            </div>
        </section>
    @endif
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

                button.innerHTML = 'Copied';

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                }, 1400);
            } catch (error) {
                button.innerHTML = 'Failed';

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                }, 1400);
            }
        });
    });

    const stepLinks = document.querySelectorAll('[data-step-link]');
    const stepSections = document.querySelectorAll('[data-step-section]');

    function setActiveStep(stepId) {
        stepLinks.forEach(function (link) {
            const isActive = link.dataset.stepLink === stepId;

            link.classList.toggle('active', isActive);

            link.classList.toggle('bg-flex-primary', isActive);
            link.classList.toggle('text-white', isActive);
            link.classList.toggle('shadow-button', isActive);

            link.classList.toggle('bg-flex-soft', !isActive);
            link.classList.toggle('text-flex-dark', !isActive);
            link.classList.toggle('hover:bg-flex-primarySoft', !isActive);
            link.classList.toggle('hover:text-flex-primary', !isActive);

            const iconBox = link.querySelector('span:first-child');
            const meta = link.querySelector('span span + span');

            if (iconBox) {
                iconBox.classList.toggle('bg-white/16', isActive);
                iconBox.classList.toggle('text-white', isActive);
                iconBox.classList.toggle('bg-white', !isActive);
                iconBox.classList.toggle('text-flex-primary', !isActive);
                iconBox.classList.toggle('shadow-sm', !isActive);
            }

            if (meta) {
                meta.classList.toggle('text-white/72', isActive);
                meta.classList.toggle('text-flex-muted', !isActive);
            }
        });
    }

    if (stepLinks.length && stepSections.length) {
        const observer = new IntersectionObserver(function (entries) {
            const visibleEntries = entries
                .filter(function (entry) {
                    return entry.isIntersecting;
                })
                .sort(function (a, b) {
                    return b.intersectionRatio - a.intersectionRatio;
                });

            if (!visibleEntries.length) {
                return;
            }

            const stepId = visibleEntries[0].target.dataset.stepSection;

            if (stepId) {
                setActiveStep(stepId);
            }
        }, {
            root: null,
            threshold: [0.18, 0.35, 0.55],
            rootMargin: '-120px 0px -45% 0px',
        });

        stepSections.forEach(function (section) {
            observer.observe(section);
        });

        stepLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                const stepId = link.dataset.stepLink;

                if (stepId) {
                    setActiveStep(stepId);
                }
            });
        });
    }
});
</script>
@endpush