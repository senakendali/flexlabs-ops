@php
    $normalizeDash = function ($value) {
        return str_replace(["\u{2014}", "\u{2013}"], '-', (string) $value);
    };

    $resolveMediaUrl = function ($path) {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (\Illuminate\Support\Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };

    $materialTitle = $normalizeDash($material->title);
    $materialSubtitle = $normalizeDash($material->subtitle);
    $materialType = $normalizeDash($material->type ?? 'Material');
@endphp

@extends('layouts.builder-hub')

@section('title', $materialTitle . ' | FlexLabs')
@section('meta_description', $materialSubtitle ?: 'Materi FlexLabs untuk trial class dan workshop.')
@section('brand_url', url('/workshop'))
@section('page_kicker', ucfirst($materialType) . ' Material')
@section('page_title', $materialTitle)
@section('whatsapp_text', 'Halo FlexLabs, saya ingin konsultasi tentang materi ' . $materialTitle)

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

    $instructorName = $normalizeDash($material->instructor_name ?: 'FlexLabs Instructor');

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

    $coverImagePath = collect([
        data_get($material, 'cover_image'),
        data_get($material, 'cover_image_path'),
        data_get($material, 'cover_path'),
        data_get($material, 'thumbnail_url'),
        data_get($material, 'image'),
        data_get($material, 'image_path'),
    ])->first(fn ($path) => filled($path));

    $coverImageUrl = $resolveMediaUrl($coverImagePath);

    $coverImageCaption = $normalizeDash(
        data_get($material, 'cover_caption')
        ?: data_get($material, 'image_caption')
        ?: $materialTitle
    );

    $renderRichText = function ($content, $class = '') use ($normalizeDash) {
        $content = trim($normalizeDash($content));

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

    $downloadFilename = function ($image, int $index) use ($materialTitle) {
        $extension = pathinfo((string) $image->image_path, PATHINFO_EXTENSION);

        if (! $extension) {
            $extension = 'jpg';
        }

        return \Illuminate\Support\Str::slug($materialTitle . '-gallery-' . $index) . '.' . $extension;
    };

    $listIconLarge = '
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z"
                stroke="currentColor"
                stroke-width="2.2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $bookIcon = '
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $bookIconLarge = '
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $bookIconSmall = '
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $codeIcon = '
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M17.25 6.75 21.75 12l-4.5 5.25M6.75 6.75 2.25 12l4.5 5.25M14.25 4.5l-4.5 15"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $imageIcon = '
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    ';

    $getBlockIcon = function ($type) use ($bookIcon, $codeIcon, $imageIcon) {
        return match ($type) {
            'code' => $codeIcon,
            'image' => $imageIcon,
            default => $bookIcon,
        };
    };
@endphp

@push('styles')
<style>
    .builder-rich {
        color: #4F4A5E;
    }

    .builder-rich > * + * {
        margin-top: 1rem;
    }

    .builder-rich p {
        margin: 0;
        line-height: 1.9;
    }

    .builder-rich p + p {
        margin-top: 1rem;
    }

    .builder-rich ul,
    .builder-rich ol {
        margin: 1rem 0 0;
        padding-left: 1.35rem;
        line-height: 1.9;
    }

    .builder-rich ul {
        list-style: disc;
    }

    .builder-rich ol {
        list-style: decimal;
    }

    .builder-rich li + li {
        margin-top: 0.45rem;
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
        margin: 1.15rem 0 0;
        border-left: 4px solid #5B3E8E;
        border-radius: 1rem;
        background: #F7F3FC;
        padding: 1rem 1.25rem;
        color: #4F4A5E;
    }
</style>
@endpush

@section('builder_hero')
    <section class="overflow-hidden rounded-[1.75rem] bg-flex-primary px-6 py-6 text-white shadow-card sm:px-8 lg:px-10">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.15rem] bg-white/12 text-white ring-1 ring-white/15">
                    {!! $bookIconLarge !!}
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-white/65">
                        Learning Builder Hub
                    </p>

                    <p class="mt-1 text-base font-bold leading-7 text-white/82">
                        Ikuti materi secara berurutan dan praktikkan langsung di project teman-teman.
                    </p>
                </div>
            </div>

            <a
                href="#material-content"
                class="inline-flex shrink-0 items-center justify-center gap-3 rounded-[1rem] bg-white px-6 py-4 text-sm font-black text-flex-primary shadow-sm transition hover:-translate-y-0.5 hover:bg-flex-primarySoft"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
                    {!! $bookIcon !!}
                </span>

                Mulai Materi
            </a>
        </div>
    </section>
@endsection

@section('sidebar')
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary">
            {!! $listIconLarge !!}
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
                    $blockTitle = $normalizeDash($block->title ?: ucfirst($block->type));
                    $blockLabel = $blockLabelMap[$block->type] ?? ucfirst($block->type);
                    $stepId = 'material-step-' . $block->id;
                @endphp

                <a
                    href="#{{ $stepId }}"
                    class="group flex items-center gap-4 rounded-[1.5rem] px-4 py-4 transition {{ $loop->first ? 'active bg-flex-primary text-white shadow-button' : 'bg-flex-soft text-flex-dark hover:bg-flex-primarySoft hover:text-flex-primary' }}"
                    data-step-link="{{ $stepId }}"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $loop->first ? 'bg-white/20 text-white' : 'bg-white text-flex-primary shadow-sm' }}">
                        {!! $getBlockIcon($block->type) !!}
                    </span>

                    <span class="min-w-0">
                        <span class="block truncate text-base font-black">
                            {{ \Illuminate\Support\Str::limit($blockTitle, 32) }}
                        </span>

                        <span class="mt-0.5 block truncate text-sm font-semibold {{ $loop->first ? 'text-white/75' : 'text-flex-muted' }}">
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
                    {{ $instructorName }}
                </p>
            </div>
        </div>

        <a
            href="#material-content"
            class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-5 py-3 text-sm font-black text-white shadow-button transition hover:-translate-y-0.5 hover:bg-flex-primaryDark"
        >
            Mulai Baca Materi
        </a>
    </div>
@endsection

@section('content')
    <section class="px-5 py-8 sm:px-7 lg:px-10">
        <div class="max-w-6xl">
            <div class="inline-flex w-fit items-center gap-2 rounded-full bg-flex-primarySoft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm">
                    {!! $bookIconSmall !!}
                </span>
                {{ ucfirst($materialType) }} Material
            </div>

            <h1 class="mt-6 max-w-5xl text-3xl font-black leading-[1.12] tracking-[-0.055em] text-flex-dark md:text-4xl xl:text-5xl">
                {{ $materialTitle }}
            </h1>

            <div class="mt-7 grid gap-7 lg:grid-cols-[minmax(0,1fr)_minmax(300px,420px)] lg:items-start">
                <div class="min-w-0">
                    @if($materialSubtitle)
                        <p class="mb-5 max-w-4xl text-lg font-black leading-8 text-flex-primary md:text-xl">
                            {{ $materialSubtitle }}
                        </p>
                    @endif

                    @if($material->description)
                        {!! $renderRichText($material->description, 'builder-rich max-w-4xl text-base font-semibold text-flex-muted md:text-lg') !!}
                    @endif

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
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-flex-soft px-6 py-3 text-sm font-black text-flex-dark shadow-sm ring-1 ring-flex-line transition hover:-translate-y-0.5 hover:bg-flex-primarySoft hover:text-flex-primary"
                            >
                                Download Gambar
                            </a>
                        @endif
                    </div>
                </div>

                @if($coverImageUrl)
                    <figure class="overflow-hidden border border-flex-line bg-flex-soft p-3 shadow-card">
                        <div class="aspect-[4/3] overflow-hidden bg-white">
                            <img
                                src="{{ $coverImageUrl }}"
                                alt="{{ $coverImageCaption ?: $materialTitle }}"
                                class="h-full w-full object-cover"
                            >
                        </div>

                        @if($coverImageCaption)
                            <figcaption class="px-2 pb-1 pt-4 text-sm font-bold leading-6 text-flex-muted">
                                {{ $coverImageCaption }}
                            </figcaption>
                        @endif
                    </figure>
                @endif
            </div>
        </div>
    </section>

    <section id="session-info" class="grid gap-4 border-t border-flex-line px-5 py-6 sm:px-7 md:grid-cols-2 lg:px-10 xl:grid-cols-4">
        <div class="rounded-[1.5rem] border border-[#E7DDF4] bg-[#FAF7FF] p-5">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-flex-primary shadow-sm ring-1 ring-[#E7DDF4]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6.75 3v2.25m10.5-2.25v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.75V8.25a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                        Event Date
                    </p>
                    <p class="mt-1 text-lg font-black leading-tight text-flex-dark">
                        {{ $formattedDate }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-[#D8E3FF] bg-[#F6F8FF] p-5">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-[#4263EB] shadow-sm ring-1 ring-[#D8E3FF]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                        Schedule
                    </p>
                    <p class="mt-1 text-lg font-black leading-tight text-flex-dark">
                        {{ $formattedSchedule }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-[#D7F3E4] bg-[#F4FFF8] p-5">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-[#0F9F6E] shadow-sm ring-1 ring-[#D7F3E4]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                        Access Until
                    </p>
                    <p class="mt-1 text-lg font-black leading-tight text-flex-dark">
                        {{ $formattedAccessEnd }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-[#FFE4C7] bg-[#FFF9F2] p-5">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-[#D97706] shadow-sm ring-1 ring-[#FFE4C7]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-flex-muted">
                        Instructor
                    </p>
                    <p class="mt-1 truncate text-lg font-black leading-tight text-flex-dark">
                        {{ $instructorName }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-flex-line bg-flex-primarySoft px-5 py-8 sm:px-7 lg:px-10">
        <div class="grid gap-5 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
            <div>
                <span class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-sm">
                    Learning Tip
                </span>

                <h2 class="mt-4 text-2xl font-black leading-tight tracking-[-0.045em] text-flex-dark md:text-3xl">
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

    <section id="material-content" class="scroll-mt-28 border-t border-flex-line">
        @if($blocks->count())
            @foreach($blocks as $index => $block)
                @php
                    $blockNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $blockTitle = $normalizeDash($block->title ?: ucfirst($block->type));
                    $blockLabel = $blockLabelMap[$block->type] ?? ucfirst($block->type);
                    $blockBadge = $blockBadgeMap[$block->type] ?? 'bg-slate-50 text-slate-700 ring-slate-100';
                    $codeContent = trim($normalizeDash($block->code_content ?? ''));
                    $imageCaption = $normalizeDash($block->image_caption ?: '');
                    $stepId = 'material-step-' . $block->id;
                @endphp

                <article
                    id="{{ $stepId }}"
                    data-step-section="{{ $stepId }}"
                    class="scroll-mt-32 px-5 py-8 sm:px-7 lg:px-10 lg:py-10 {{ ! $loop->first ? 'border-t border-flex-line' : '' }}"
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

                            <h3 class="text-xl font-black leading-tight tracking-[-0.04em] text-flex-dark md:text-2xl">
                                {{ $blockTitle }}
                            </h3>
                        </div>
                    </div>

                    @if($block->type === 'heading')
                        @if($block->content)
                            {!! $renderRichText($block->content, 'builder-rich text-base font-semibold text-flex-muted md:text-lg') !!}
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
                                    alt="{{ $imageCaption ?: $blockTitle }}"
                                    class="w-full rounded-[1.2rem] object-cover"
                                >
                            </div>
                        @endif

                        @if($imageCaption)
                            <p class="mt-4 text-base font-semibold leading-8 text-flex-muted">
                                {{ $imageCaption }}
                            </p>
                        @endif

                    @elseif($block->type === 'note')
                        <div class="flex gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary ring-1 ring-flex-line">
                                {!! $bookIconLarge !!}
                            </div>

                            <div class="min-w-0 flex-1">
                                {!! $renderRichText($block->content, 'builder-rich text-base font-semibold text-flex-muted') !!}
                            </div>
                        </div>

                    @elseif($block->type === 'task')
                        <div class="flex gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-flex-primarySoft text-flex-primary ring-1 ring-flex-line">
                                {!! $bookIconLarge !!}
                            </div>

                            <div class="min-w-0 flex-1">
                                {!! $renderRichText($block->content, 'builder-rich text-base font-semibold text-flex-muted') !!}
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        @else
            <div class="px-5 py-10 text-center sm:px-7 lg:px-10">
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
        <section id="supporting-images" class="scroll-mt-32 border-t border-flex-line px-5 py-10 sm:px-7 lg:px-10">
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
                        $imageTitle = $normalizeDash($image->caption ?: 'Supporting Image ' . $loop->iteration);
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
                iconBox.classList.toggle('bg-white/20', isActive);
                iconBox.classList.toggle('text-white', isActive);
                iconBox.classList.toggle('bg-white', !isActive);
                iconBox.classList.toggle('text-flex-primary', !isActive);
                iconBox.classList.toggle('shadow-sm', !isActive);
            }

            if (meta) {
                meta.classList.toggle('text-white/75', isActive);
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