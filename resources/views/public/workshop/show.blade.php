@extends('layouts.webinar')

@section('title', $workshop['title'] . ' | Workshop FlexLabs')
@section('meta_description', $workshop['short_description'] ?? 'Workshop FlexLabs untuk skill digital yang praktis dan relevan.')
@section('brand_url', url('/workshop'))

@section('content')
@php
    $priceText = 'Rp ' . number_format($workshop['price'] ?? 0, 0, ',', '.');

    $oldPriceText = !empty($workshop['old_price'])
        ? 'Rp ' . number_format($workshop['old_price'], 0, ',', '.')
        : null;

    $waText = rawurlencode('Halo FlexLabs, saya ingin daftar workshop: ' . ($workshop['title'] ?? 'Workshop FlexLabs'));
    $waUrl = 'https://wa.me/62811134759?text=' . $waText;

    $introVideoUrl = trim($workshop['intro_video_url'] ?? '');
    $introVideoType = $workshop['intro_video_type'] ?? 'youtube';
    $hasIntroVideo = filled($introVideoUrl);

    $fallbackImage = asset('images/universal.png');

    $workshopImage = !empty($workshop['image'])
        ? asset($workshop['image'])
        : $fallbackImage;

    $rating = (int) ($workshop['rating'] ?? 0);
    $ratingCount = (int) ($workshop['rating_count'] ?? 0);

    /**
     * Menampilkan text dari textarea dengan aman.
     * - Tetap escape HTML agar aman dari XSS.
     * - New line dari textarea tetap tampil sebagai baris/paragraf.
     */
    $formatTextarea = function ($value, string $fallback = '-') {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return e($fallback);
        }

        return nl2br(e($text));
    };
@endphp

<section class="relative isolate overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-32 pb-14 sm:pt-36 lg:pt-40 lg:pb-16">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>

    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
       

        <div class="mt-8">
            <div class="max-w-5xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_12px_35px_rgba(91,62,142,0.10)] backdrop-blur">
                    <i class="bi bi-stars text-sm"></i>
                    {{ $workshop['badge'] ?? 'Workshop FlexLabs' }}
                </span>

                <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                    {{ $workshop['title'] }}
                </h1>

                

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-black text-amber-700">
                        <i class="bi bi-star-fill"></i>
                        {{ $workshop['rating'] ?? 0 }}/5
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        {{ number_format($ratingCount, 0, ',', '.') }} reviews
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-clock text-flex-primary"></i>
                        {{ $workshop['duration'] ?? '-' }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-bar-chart text-flex-primary"></i>
                        {{ $workshop['level'] ?? '-' }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-grid text-flex-primary"></i>
                        {{ $workshop['category'] ?? '-' }}
                    </span>
                </div>
            </div>

            <div class="mt-10">
                <div class="relative">
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-flex-primary/10 blur-2xl"></div>
                    <div class="absolute -right-5 -top-5 -z-10 h-28 w-28 rounded-full bg-flex-primary/20 blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-32 w-32 rounded-full bg-purple-300/30 blur-2xl"></div>

                    <div class="overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white p-3 shadow-[0_28px_80px_rgba(91,62,142,0.18)]">
                        <div class="relative aspect-[16/7] overflow-hidden rounded-[1.75rem] bg-flex-soft max-lg:aspect-video">
                            @if ($hasIntroVideo)
                                @if ($introVideoType === 'youtube')
                                    <iframe
                                        src="{{ $introVideoUrl }}"
                                        title="{{ $workshop['title'] }} Intro Video"
                                        class="h-full w-full"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>
                                @else
                                    <video
                                        controls
                                        playsinline
                                        preload="metadata"
                                        class="h-full w-full object-cover"
                                        poster="{{ $fallbackImage }}"
                                    >
                                        <source src="{{ $introVideoUrl }}" type="video/mp4">
                                        Browser lu belum support video.
                                    </video>
                                @endif
                            @else
                                <img
                                    src="{{ $fallbackImage }}"
                                    alt="Workshop FlexLabs"
                                    class="h-full w-full object-cover"
                                >

                               
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-6 max-w-4xl text-base font-medium leading-8 text-slate-600 sm:text-lg">
                {!! $formatTextarea($workshop['overview'] ?? $workshop['short_description'] ?? '-') !!}
            </p>
        </div>
    </div>
</section>

<section class="relative z-10 bg-flex-primary">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid overflow-hidden border-white/10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-clock"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        {{ $workshop['duration'] ?? '-' }}
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Durasi workshop dibuat padat dan fokus.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:pl-7 lg:border-b-0 lg:border-r lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-bar-chart"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        {{ $workshop['level'] ?? '-' }}
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Level materi disesuaikan dengan kebutuhan peserta.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-9 lg:pl-7">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-grid"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        {{ $workshop['category'] ?? '-' }}
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Topik diarahkan ke skill digital yang relevan.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 py-8 sm:pl-7 lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-star-fill"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        {{ $workshop['rating'] ?? 0 }}/5
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Berdasarkan {{ number_format($ratingCount, 0, ',', '.') }} review peserta.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-16 lg:py-20">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-8">
                <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(91,62,142,0.14),transparent_34%),linear-gradient(135deg,#ffffff_0%,#faf8ff_100%)] p-6 sm:p-8">
                        <span class="inline-flex rounded-full bg-flex-primary/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                            What You Will Learn
                        </span>

                        <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                            Yang akan dipelajari
                        </h2>

                        <p class="mt-3 max-w-2xl text-base font-medium leading-7 text-slate-600">
                            Workshop ini dibuat praktis, fokus, dan langsung bisa dipakai.
                        </p>
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="grid gap-4">
                            @forelse (($workshop['benefits'] ?? []) as $benefit)
                                <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-lg text-flex-primary">
                                        <i class="bi bi-check-lg"></i>
                                    </span>

                                    <span class="pt-1 text-base font-bold leading-7 text-slate-700">
                                        {!! $formatTextarea($benefit) !!}
                                    </span>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-flex-primary/20 bg-flex-soft p-5 text-base font-bold leading-7 text-slate-600">
                                    Benefit workshop belum ditambahkan.
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-8 rounded-[1.75rem] border border-flex-primary/10 bg-flex-soft p-6">
                            <h3 class="text-xl font-black tracking-[-0.04em] text-slate-950">
                                Workshop ini cocok untuk
                            </h3>

                            <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                                {!! $formatTextarea($workshop['audience'] ?? '-') !!}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-200 bg-white p-6 sm:p-8">
                        <span class="inline-flex rounded-full bg-flex-soft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                            About This Workshop
                        </span>

                        <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                            Ringkasan workshop
                        </h2>
                    </div>

                    <div class="p-6 sm:p-8">
                        <p class="text-base font-medium leading-8 text-slate-600 sm:text-lg">
                            {!! $formatTextarea($workshop['short_description'] ?? '-') !!}
                        </p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4">
                <aside class="sticky top-28 overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
                    <div class="bg-flex-primary/5 p-3">
                        <img
                            src="{{ $workshopImage }}"
                            alt="{{ $workshop['title'] }}"
                            class="h-64 w-full rounded-[1.5rem] object-cover"
                            onerror="this.src='{{ $fallbackImage }}'"
                        >
                    </div>

                    <div class="p-6">
                        <div class="rounded-2xl border border-flex-primary/10 bg-flex-soft p-5">
                            @if ($oldPriceText)
                                <div class="text-sm font-bold text-slate-400 line-through">
                                    {{ $oldPriceText }}
                                </div>
                            @endif

                            <div class="mt-1 text-3xl font-black tracking-[-0.05em] text-flex-primary">
                                {{ $priceText }}
                            </div>
                        </div>

                        <a
                            href="{{ $waUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-4 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)]"
                        >
                            <i class="bi bi-whatsapp"></i>
                            Daftar Workshop
                        </a>

                        <a
                            href="{{ route('workshop.index') }}"
                            class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary transition duration-200 hover:-translate-y-0.5 hover:bg-flex-soft"
                        >
                            <i class="bi bi-arrow-left"></i>
                            Lihat Workshop Lain
                        </a>

                        <div class="mt-6 divide-y divide-slate-200 rounded-2xl border border-slate-200">
                            <div class="flex items-center justify-between gap-4 p-4">
                                <span class="text-sm font-bold text-slate-500">Kategori</span>
                                <strong class="text-right text-sm font-black text-slate-900">
                                    {{ $workshop['category'] ?? '-' }}
                                </strong>
                            </div>

                            <div class="flex items-center justify-between gap-4 p-4">
                                <span class="text-sm font-bold text-slate-500">Level</span>
                                <strong class="text-right text-sm font-black text-slate-900">
                                    {{ $workshop['level'] ?? '-' }}
                                </strong>
                            </div>

                            <div class="flex items-center justify-between gap-4 p-4">
                                <span class="text-sm font-bold text-slate-500">Durasi</span>
                                <strong class="text-right text-sm font-black text-slate-900">
                                    {{ $workshop['duration'] ?? '-' }}
                                </strong>
                            </div>

                            <div class="flex items-center justify-between gap-4 p-4">
                                <span class="text-sm font-bold text-slate-500">Rating</span>
                                <strong class="text-right text-sm font-black text-slate-900">
                                    {{ $workshop['rating'] ?? 0 }}/5
                                </strong>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection