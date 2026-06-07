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

    /*
    |--------------------------------------------------------------------------
    | Workshop Image
    |--------------------------------------------------------------------------
    | Priority:
    | 1. image_url dari controller, kalau ada.
    | 2. image dari database.
    | 3. fallback image.
    |
    | Jadi kalau DB menyimpan:
    | - images/workshop.png
    | - storage/workshops/image.jpg
    | - workshops/image.jpg
    | semuanya tetap aman ditampilkan.
    |--------------------------------------------------------------------------
    */
    $workshopImage = $workshop['image_url'] ?? null;

    if (empty($workshopImage)) {
        $rawImage = $workshop['image'] ?? null;

        if (! empty($rawImage)) {
            $workshopImage = str_starts_with($rawImage, 'images/')
                || str_starts_with($rawImage, 'storage/')
                || str_starts_with($rawImage, 'http://')
                || str_starts_with($rawImage, 'https://')
                    ? asset($rawImage)
                    : asset('storage/' . $rawImage);
        }
    }

    $workshopImage = $workshopImage ?: $fallbackImage;

    $schedules = collect($workshop['schedules'] ?? []);
    $availableScheduleCount = (int) ($workshop['available_schedule_count'] ?? $schedules->count());
    $nearestSchedule = $workshop['nearest_schedule'] ?? $schedules->first();

    /*
    |--------------------------------------------------------------------------
    | Public Workshop Registration Endpoint
    |--------------------------------------------------------------------------
    | Route utama nanti:
    | - local: /workshop/{slug}/register
    | - production subdomain: /{slug}/register
    |
    | Pakai relative route supaya aman di local dan subdomain.
    |--------------------------------------------------------------------------
    */
    $registrationEndpoint = \Illuminate\Support\Facades\Route::has('workshop.registration.store')
        ? route('workshop.registration.store', ['slug' => $workshop['slug']], false)
        : '/workshop/' . ($workshop['slug'] ?? $workshop['id']) . '/register';

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

<div
    id="toastContainer"
    class="fixed right-4 top-[96px] z-[999999] grid w-[calc(100%-2rem)] max-w-sm gap-3 sm:right-6 sm:w-full"
    style="z-index: 999999;"
></div>

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

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-calendar-event text-flex-primary"></i>
                        {{ $availableScheduleCount > 0 ? $availableScheduleCount . ' jadwal tersedia' : 'Jadwal segera hadir' }}
                    </span>
                </div>
            </div>

            <div class="mt-10">
                <div class="relative">
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-flex-primary/10 blur-2xl"></div>
                    <div class="absolute -right-5 -top-5 -z-10 h-28 w-28 rounded-full bg-flex-primary/20 blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-32 w-32 rounded-full bg-purple-300/30 blur-2xl"></div>

                    <div class="overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white p-3 shadow-[0_28px_80px_rgba(91,62,142,0.18)]">
                        <div class="relative overflow-hidden rounded-[1.75rem] bg-flex-soft">
                            @if ($hasIntroVideo)
                                @if ($introVideoType === 'youtube')
                                    <div class="aspect-video w-full">
                                        <iframe
                                            src="{{ $introVideoUrl }}"
                                            title="{{ $workshop['title'] }} Intro Video"
                                            class="h-full w-full"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                @else
                                    <video
                                        controls
                                        playsinline
                                        preload="metadata"
                                        class="h-auto w-full"
                                        poster="{{ $workshopImage }}"
                                    >
                                        <source src="{{ $introVideoUrl }}" type="video/mp4">
                                        Browser lu belum support video.
                                    </video>
                                @endif
                            @else
                                <img
                                    src="{{ $workshopImage }}"
                                    alt="{{ $workshop['title'] }}"
                                    class="h-auto w-full object-contain"
                                    onerror="this.src='{{ $fallbackImage }}'"
                                >

                               
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-6 w-full max-w-none text-base font-medium leading-8 text-slate-600 sm:text-lg">
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

<section class="workshop-detail-section bg-[#F2F4FA] py-16 lg:py-20" data-workshop-detail-section>
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

            <div class="lg:col-span-4 lg:self-start" data-workshop-sidebar-wrapper>
                <aside class="workshop-detail-sidebar-sticky" data-workshop-sidebar>
                    <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
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

                            @if($nearestSchedule && ! empty($nearestSchedule['formatted_price']))
                                <div class="mt-2 text-xs font-bold leading-5 text-slate-500">
                                    Harga jadwal dapat berbeda. Pilih jadwal di bawah untuk melihat detail harga.
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 rounded-2xl border border-flex-primary/10 bg-white p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                        Jadwal Tersedia
                                    </div>

                                    <div class="mt-1 text-sm font-bold text-slate-500">
                                        {{ $availableScheduleCount > 0 ? $availableScheduleCount . ' pilihan jadwal' : 'Belum ada jadwal aktif' }}
                                    </div>
                                </div>

                                <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-flex-primary">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse($schedules as $schedule)
                                    @php
                                        $scheduleTitle = $schedule['display_title']
                                            ?? $schedule['title']
                                            ?? $workshop['title'];

                                        $scheduleDateLabel = $schedule['schedule_date_label']
                                            ?? (! empty($schedule['schedule_date'])
                                                ? \Illuminate\Support\Carbon::parse($schedule['schedule_date'])->format('d M Y')
                                                : '-');

                                        $scheduleDayLabel = $schedule['schedule_day_label'] ?? null;

                                        $scheduleTimeLabel = $schedule['time_label']
                                            ?? trim(implode(' - ', array_filter([
                                                $schedule['start_time'] ?? null,
                                                $schedule['end_time'] ?? null,
                                            ])));

                                        $scheduleLocationLabel = $schedule['location_type_label'] ?? null;

                                        $scheduleRemainingQuota = $schedule['remaining_quota'] ?? null;
                                        $scheduleIsFull = (bool) ($schedule['is_full'] ?? false);

                                        $schedulePriceText = $schedule['formatted_price']
                                            ?? 'Rp ' . number_format((float) ($schedule['price'] ?? $workshop['price'] ?? 0), 0, ',', '.');

                                        $scheduleOldPriceText = ! empty($schedule['formatted_old_price'])
                                            ? $schedule['formatted_old_price']
                                            : (! empty($schedule['old_price'])
                                                ? 'Rp ' . number_format((float) $schedule['old_price'], 0, ',', '.')
                                                : null);
                                    @endphp

                                    <div class="rounded-2xl border {{ $scheduleIsFull ? 'border-red-100 bg-red-50/50' : 'border-slate-200 bg-slate-50/70' }} p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-flex-primary/10 text-flex-primary">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-black leading-6 text-slate-900">
                                                    {{ $scheduleTitle }}
                                                </div>

                                                <div class="mt-1 text-xs font-bold leading-5 text-slate-500">
                                                    @if($scheduleDayLabel)
                                                        {{ $scheduleDayLabel }},
                                                    @endif

                                                    {{ $scheduleDateLabel }}

                                                    @if($scheduleTimeLabel && $scheduleTimeLabel !== '-')
                                                        • {{ $scheduleTimeLabel }}
                                                    @endif
                                                </div>

                                                @if($scheduleLocationLabel || ! empty($schedule['location']))
                                                    <div class="mt-1 text-xs font-bold leading-5 text-slate-500">
                                                        {{ $scheduleLocationLabel ?: 'Lokasi' }}
                                                        @if(! empty($schedule['location']))
                                                            • {{ $schedule['location'] }}
                                                        @endif
                                                    </div>
                                                @endif

                                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        @if($scheduleOldPriceText)
                                                            <div class="text-xs font-bold text-slate-400 line-through">
                                                                {{ $scheduleOldPriceText }}
                                                            </div>
                                                        @endif

                                                        <div class="text-base font-black tracking-[-0.03em] text-flex-primary">
                                                            {{ $schedulePriceText }}
                                                        </div>
                                                    </div>

                                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $scheduleIsFull ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                        @if($scheduleIsFull)
                                                            Full
                                                        @elseif($scheduleRemainingQuota !== null)
                                                            Sisa {{ $scheduleRemainingQuota }} seat
                                                        @else
                                                            Open
                                                        @endif
                                                    </span>
                                                </div>

                                                <button
                                                    type="button"
                                                    data-register-schedule
                                                    data-schedule-id="{{ $schedule['id'] }}"
                                                    class="mt-4 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-full {{ $scheduleIsFull ? 'cursor-not-allowed bg-slate-200 text-slate-400' : 'bg-flex-primary text-white shadow-[0_12px_24px_rgba(91,62,142,0.18)] hover:bg-flex-primaryDark' }} px-4 py-2 text-xs font-black transition duration-200"
                                                    {{ $scheduleIsFull ? 'disabled' : '' }}
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                    {{ $scheduleIsFull ? 'Jadwal penuh' : 'Pilih jadwal ini' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-flex-primary/20 bg-flex-soft p-5 text-sm font-bold leading-7 text-slate-600">
                                        Jadwal workshop belum tersedia. Hubungi tim FlexLabs untuk info jadwal berikutnya.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <a
                            href="{{ $waUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary transition duration-200 hover:-translate-y-0.5 hover:bg-flex-soft"
                        >
                            <i class="bi bi-whatsapp"></i>
                            Tanya via WhatsApp
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
                                <span class="text-sm font-bold text-slate-500">Jadwal</span>
                                <strong class="text-right text-sm font-black text-slate-900">
                                    {{ $availableScheduleCount > 0 ? $availableScheduleCount . ' tersedia' : 'Segera hadir' }}
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
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>

<section
    id="workshop-registration-section"
    class="bg-white py-16 lg:py-20"
    data-workshop-registration-section
>
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-5">
                <span class="inline-flex rounded-full bg-flex-soft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Workshop Registration
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl lg:text-5xl">
                    Daftar workshop ini
                </h2>

                <p class="mt-4 max-w-xl text-base font-medium leading-8 text-slate-600">
                    Isi data diri kamu dan pastikan jadwal yang dipilih sudah sesuai. Setelah submit,
                    tim FlexLabs akan menghubungi kamu untuk konfirmasi pembayaran dan detail sesi.
                </p>

                <div id="selectedSchedulePreview" class="mt-6 rounded-[1.75rem] border border-flex-primary/10 bg-flex-soft p-5">
                    <div class="flex items-start gap-4">
                        <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-xl text-flex-primary">
                            <i class="bi bi-calendar-event"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                Jadwal dipilih
                            </div>

                            <div id="selectedScheduleTitle" class="mt-2 text-lg font-black leading-6 text-slate-950">
                                Pilih jadwal terlebih dahulu
                            </div>

                            <div id="selectedScheduleMeta" class="mt-2 text-sm font-bold leading-6 text-slate-500">
                                Klik tombol daftar pada jadwal yang kamu inginkan.
                            </div>

                            <div id="selectedSchedulePrice" class="mt-3 text-xl font-black tracking-[-0.04em] text-flex-primary"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(91,62,142,0.14),transparent_34%),linear-gradient(135deg,#ffffff_0%,#faf8ff_100%)] p-6 sm:p-8">
                        <h3 class="text-2xl font-black tracking-[-0.04em] text-slate-950">
                            Form Pendaftaran
                        </h3>

                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                            Data ini akan dipakai untuk membuat registrasi, order, dan payment schedule workshop.
                        </p>
                    </div>

                    <div class="p-6 sm:p-8">
                        <form id="workshopRegistrationForm">
                            @csrf

                            <div id="registrationFormAlert" class="mb-5 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold leading-6 text-red-700"></div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="workshop_schedule_id" class="mb-2 block text-sm font-black text-slate-800">
                                        Jadwal Workshop <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        id="workshop_schedule_id"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    >
                                        <option value="">Pilih jadwal</option>

                                        @foreach ($schedules as $schedule)
                                            @php
                                                $optionTitle = $schedule['display_title']
                                                    ?? $schedule['title']
                                                    ?? $workshop['title'];

                                                $optionDate = $schedule['schedule_date_label']
                                                    ?? (! empty($schedule['schedule_date'])
                                                        ? \Illuminate\Support\Carbon::parse($schedule['schedule_date'])->format('d M Y')
                                                        : '-');

                                                $optionTime = $schedule['time_label']
                                                    ?? trim(implode(' - ', array_filter([
                                                        $schedule['start_time'] ?? null,
                                                        $schedule['end_time'] ?? null,
                                                    ])));

                                                $optionIsFull = (bool) ($schedule['is_full'] ?? false);
                                            @endphp

                                            <option
                                                value="{{ $schedule['id'] }}"
                                                {{ $optionIsFull ? 'disabled' : '' }}
                                            >
                                                {{ $optionTitle }}
                                                - {{ $optionDate }}
                                                @if($optionTime && $optionTime !== '-')
                                                    ({{ $optionTime }})
                                                @endif
                                                {{ $optionIsFull ? '- FULL' : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_workshop_schedule_id"></div>
                                </div>

                                <div>
                                    <label for="full_name" class="mb-2 block text-sm font-black text-slate-800">
                                        Nama Lengkap <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="full_name"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Nama lengkap"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_full_name"></div>
                                </div>

                                <div>
                                    <label for="phone" class="mb-2 block text-sm font-black text-slate-800">
                                        Nomor HP / WhatsApp <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="phone"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Nomor WhatsApp aktif"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_phone"></div>
                                </div>

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-black text-slate-800">
                                        Email <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Email aktif"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_email"></div>
                                </div>

                                <div>
                                    <label for="city" class="mb-2 block text-sm font-black text-slate-800">
                                        Domisili
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Contoh: Jakarta"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_city"></div>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="goal" class="mb-2 block text-sm font-black text-slate-800">
                                        Tujuan Mengikuti Workshop
                                    </label>

                                    <textarea
                                        id="goal"
                                        rows="5"
                                        class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Ceritakan tujuan kamu mengikuti workshop ini"
                                    ></textarea>

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_goal"></div>
                                </div>

                                <div class="md:col-span-2">
                                    <button
                                        type="submit"
                                        id="submitRegistrationBtn"
                                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                                        {{ $schedules->isEmpty() ? 'disabled' : '' }}
                                    >
                                        <span class="default-text">
                                            Kirim Pendaftaran
                                        </span>

                                        <span class="loading-text hidden items-center gap-2">
                                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                            Mengirim...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="registrationSuccessState" class="hidden text-center">
                            <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-[1.7rem] bg-emerald-100 text-4xl text-emerald-600">
                                <i class="bi bi-check-lg"></i>
                            </div>

                            <h4 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                                Pendaftaran Berhasil
                            </h4>

                            <p class="mx-auto mt-3 max-w-xl text-base font-medium leading-7 text-slate-600">
                                Terima kasih, data kamu sudah kami terima. Tim FlexLabs akan menghubungi kamu untuk konfirmasi pembayaran dan detail workshop.
                            </p>

                            <a
                                href="{{ $waUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="mt-5 inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary transition duration-200 hover:-translate-y-0.5 hover:bg-flex-soft"
                            >
                                <i class="bi bi-whatsapp"></i>
                                Hubungi WhatsApp FlexLabs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<style>
    /*
    |--------------------------------------------------------------------------
    | Sticky Sidebar - Workshop Detail
    |--------------------------------------------------------------------------
    | Jangan cuma mengandalkan class Tailwind sticky karena beberapa parent layout
    | dashboard/public layout bisa punya overflow/height behavior yang bikin sticky
    | tidak jalan konsisten. Ini dibuat explicit untuk desktop.
    |--------------------------------------------------------------------------
    */
    .workshop-detail-section {
        position: relative;
    }

    [data-workshop-sidebar-wrapper] {
        position: relative;
    }

    @media (min-width: 1024px) {
        .workshop-detail-sidebar-sticky {
            position: static;
            width: 100%;
            z-index: 20;
            will-change: top, left, width;
        }
    }

    @media (max-width: 1023.98px) {
        .workshop-detail-sidebar-sticky {
            position: static !important;
            inset: auto !important;
            width: auto !important;
            transform: none !important;
        }

        [data-workshop-sidebar-wrapper] {
            min-height: 0 !important;
        }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const section = document.querySelector('[data-workshop-detail-section]');
        const wrapper = document.querySelector('[data-workshop-sidebar-wrapper]');
        const sidebar = document.querySelector('[data-workshop-sidebar]');

        if (!section || !wrapper || !sidebar) {
            return;
        }

        const desktopQuery = window.matchMedia('(min-width: 1024px)');
        const topOffset = 112; // 7rem, aman untuk fixed navbar
        const bottomGap = 32; // jarak aman sebelum footer / akhir section

        function resetSidebar() {
            sidebar.style.position = '';
            sidebar.style.top = '';
            sidebar.style.left = '';
            sidebar.style.width = '';
            sidebar.style.zIndex = '';
            wrapper.style.minHeight = '';
        }

        function getPageTop(element) {
            return element.getBoundingClientRect().top + window.scrollY;
        }

        function updateFloatingSidebar() {
            if (!desktopQuery.matches) {
                resetSidebar();
                return;
            }

            const scrollY = window.scrollY || window.pageYOffset;
            const sectionTop = getPageTop(section);
            const sectionBottom = sectionTop + section.offsetHeight;
            const wrapperTop = getPageTop(wrapper);
            const wrapperRect = wrapper.getBoundingClientRect();

            const sidebarHeight = sidebar.offsetHeight;
            const wrapperWidth = wrapper.offsetWidth;
            const wrapperLeft = wrapperRect.left + window.scrollX;

            wrapper.style.minHeight = `${sidebarHeight}px`;

            const startFixedAt = wrapperTop - topOffset;
            const stopFixedAt = sectionBottom - sidebarHeight - topOffset - bottomGap;

            if (scrollY < startFixedAt) {
                sidebar.style.position = 'static';
                sidebar.style.top = '';
                sidebar.style.left = '';
                sidebar.style.width = '';
                sidebar.style.zIndex = '';
                return;
            }

            if (scrollY >= stopFixedAt) {
                const absoluteTop = Math.max(0, sectionBottom - wrapperTop - sidebarHeight - bottomGap);

                sidebar.style.position = 'absolute';
                sidebar.style.top = `${absoluteTop}px`;
                sidebar.style.left = '0';
                sidebar.style.width = '100%';
                sidebar.style.zIndex = '20';
                return;
            }

            sidebar.style.position = 'fixed';
            sidebar.style.top = `${topOffset}px`;
            sidebar.style.left = `${wrapperLeft}px`;
            sidebar.style.width = `${wrapperWidth}px`;
            sidebar.style.zIndex = '20';
        }

        let ticking = false;

        function requestSidebarUpdate() {
            if (ticking) {
                return;
            }

            ticking = true;

            window.requestAnimationFrame(() => {
                updateFloatingSidebar();
                ticking = false;
            });
        }

        window.addEventListener('scroll', requestSidebarUpdate, { passive: true });
        window.addEventListener('resize', requestSidebarUpdate);
        window.addEventListener('load', requestSidebarUpdate);

        desktopQuery.addEventListener?.('change', requestSidebarUpdate);

        requestSidebarUpdate();
    });
</script>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const registrationEndpoint = @js($registrationEndpoint);
        const workshopId = @js($workshop['id']);
        const schedules = @json($schedules->values());

        const registrationSection = document.querySelector('[data-workshop-registration-section]');
        const registrationForm = document.getElementById('workshopRegistrationForm');
        const registrationAlert = document.getElementById('registrationFormAlert');
        const registrationSuccessState = document.getElementById('registrationSuccessState');
        const submitRegistrationBtn = document.getElementById('submitRegistrationBtn');

        const selectedScheduleTitle = document.getElementById('selectedScheduleTitle');
        const selectedScheduleMeta = document.getElementById('selectedScheduleMeta');
        const selectedSchedulePrice = document.getElementById('selectedSchedulePrice');

        const registrationFields = {
            workshop_schedule_id: document.getElementById('workshop_schedule_id'),
            full_name: document.getElementById('full_name'),
            email: document.getElementById('email'),
            phone: document.getElementById('phone'),
            city: document.getElementById('city'),
            goal: document.getElementById('goal'),
        };

        const invalidClasses = [
            'border-red-400',
            'ring-4',
            'ring-red-100',
        ];

        function findSchedule(scheduleId) {
            return schedules.find((schedule) => String(schedule.id) === String(scheduleId));
        }

        function getUrlParam(name) {
            return new URLSearchParams(window.location.search).get(name);
        }

        function scrollToRegistrationSection() {
            if (!registrationSection) {
                return;
            }

            const navOffset = 96;
            const top = registrationSection.getBoundingClientRect().top + window.pageYOffset - navOffset;

            window.scrollTo({
                top,
                behavior: 'smooth',
            });
        }

        function setSelectedSchedule(scheduleId, shouldScroll = false) {
            const schedule = findSchedule(scheduleId);

            if (!schedule || schedule.is_full) {
                return;
            }

            registrationFields.workshop_schedule_id.value = schedule.id;

            const title = schedule.display_title || schedule.title || @js($workshop['title']);
            const date = schedule.schedule_date_label || schedule.schedule_date || '-';
            const time = schedule.time_label && schedule.time_label !== '-' ? ` • ${schedule.time_label}` : '';
            const locationType = schedule.location_type_label ? ` • ${schedule.location_type_label}` : '';
            const location = schedule.location ? ` • ${schedule.location}` : '';
            const price = schedule.formatted_price || '';

            if (selectedScheduleTitle) {
                selectedScheduleTitle.textContent = title;
            }

            if (selectedScheduleMeta) {
                selectedScheduleMeta.textContent = `${date}${time}${locationType}${location}`;
            }

            if (selectedSchedulePrice) {
                selectedSchedulePrice.textContent = price;
            }

            if (shouldScroll) {
                scrollToRegistrationSection();
            }
        }

        function openRegistrationWithSchedule(scheduleId) {
            if (scheduleId) {
                setSelectedSchedule(scheduleId, true);
                return;
            }

            const firstAvailableSchedule = schedules.find((schedule) => !schedule.is_full);

            if (firstAvailableSchedule) {
                setSelectedSchedule(firstAvailableSchedule.id, true);
            } else {
                scrollToRegistrationSection();
            }
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');

            if (!container) {
                return;
            }

            const id = 'toast-' + Date.now();

            const theme = {
                success: {
                    wrapper: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    icon: 'bg-emerald-100 text-emerald-600',
                    iconClass: 'bi-check-circle',
                },
                danger: {
                    wrapper: 'border-red-200 bg-red-50 text-red-800',
                    icon: 'bg-red-100 text-red-600',
                    iconClass: 'bi-x-circle',
                },
                warning: {
                    wrapper: 'border-amber-200 bg-amber-50 text-amber-800',
                    icon: 'bg-amber-100 text-amber-600',
                    iconClass: 'bi-exclamation-triangle',
                },
            }[type] || {
                wrapper: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                icon: 'bg-emerald-100 text-emerald-600',
                iconClass: 'bi-check-circle',
            };

            const toast = document.createElement('div');
            toast.id = id;
            toast.style.zIndex = '999999';

            toast.className = `relative flex items-start gap-3 rounded-2xl border p-4 shadow-[0_18px_45px_rgba(15,23,42,0.18)] transition duration-300 ${theme.wrapper}`;

            toast.innerHTML = `
                <div class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${theme.icon}">
                    <i class="bi ${theme.iconClass}"></i>
                </div>

                <div class="min-w-0 flex-1 pt-0.5 text-sm font-bold leading-6">
                    ${message}
                </div>

                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/70 text-slate-500 transition hover:bg-white hover:text-slate-800"
                    aria-label="Close"
                    data-toast-close="${id}"
                >
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            `;

            container.appendChild(toast);

            const removeToast = () => {
                toast.classList.add('opacity-0', 'translate-y-1');

                setTimeout(() => {
                    toast.remove();
                }, 250);
            };

            toast.querySelector(`[data-toast-close="${id}"]`)?.addEventListener('click', removeToast);

            setTimeout(removeToast, 3500);
        }

        function clearRegistrationErrors() {
            Object.values(registrationFields).forEach(field => {
                if (!field || !field.classList) {
                    return;
                }

                field.classList.remove(...invalidClasses);
            });

            document.querySelectorAll('[id^="error_"]').forEach(errorEl => {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            });
        }

        function setRegistrationErrors(errors = {}) {
            clearRegistrationErrors();

            Object.keys(errors).forEach(key => {
                const field = registrationFields[key];
                const errorEl = document.getElementById(`error_${key}`);

                if (field && field.classList) {
                    field.classList.add(...invalidClasses);
                }

                if (errorEl) {
                    errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                    errorEl.classList.remove('hidden');
                }
            });
        }

        function setRegistrationLoading(isLoading) {
            if (!submitRegistrationBtn) {
                return;
            }

            const defaultText = submitRegistrationBtn.querySelector('.default-text');
            const loadingText = submitRegistrationBtn.querySelector('.loading-text');

            submitRegistrationBtn.disabled = isLoading;

            if (defaultText) {
                defaultText.classList.toggle('hidden', isLoading);
            }

            if (loadingText) {
                loadingText.classList.toggle('hidden', !isLoading);
                loadingText.classList.toggle('inline-flex', isLoading);
            }
        }

        document.querySelectorAll('[data-register-schedule]').forEach((button) => {
            button.addEventListener('click', function () {
                openRegistrationWithSchedule(this.dataset.scheduleId);
            });
        });

        registrationFields.workshop_schedule_id?.addEventListener('change', function () {
            setSelectedSchedule(this.value, false);
        });

        const defaultAvailableSchedule = schedules.find((schedule) => !schedule.is_full);

        if (defaultAvailableSchedule && registrationFields.workshop_schedule_id && !registrationFields.workshop_schedule_id.value) {
            setSelectedSchedule(defaultAvailableSchedule.id, false);
        }

        registrationForm?.addEventListener('submit', async function (event) {
            event.preventDefault();

            clearRegistrationErrors();

            registrationAlert.classList.add('hidden');
            registrationAlert.innerHTML = '';

            const payload = {
                workshop_id: workshopId,
                workshop_schedule_id: registrationFields.workshop_schedule_id.value,
                full_name: registrationFields.full_name.value.trim(),
                email: registrationFields.email.value.trim(),
                phone: registrationFields.phone.value.trim(),
                city: registrationFields.city.value.trim(),
                goal: registrationFields.goal.value.trim(),
                input_source: 'self_registration',
                utm_source: getUrlParam('utm_source'),
                utm_medium: getUrlParam('utm_medium'),
                utm_campaign: getUrlParam('utm_campaign'),
                utm_content: getUrlParam('utm_content'),
                utm_term: getUrlParam('utm_term'),
                referrer_url: document.referrer || null,
                landing_page_url: window.location.href,
            };

            setRegistrationLoading(true);

            try {
                const response = await fetch(registrationEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (response.status === 422) {
                    setRegistrationErrors(result.errors || {});
                    throw new Error(result.message || 'Validation failed.');
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Pendaftaran workshop gagal dikirim.');
                }

                registrationForm.classList.add('hidden');
                registrationSuccessState.classList.remove('hidden');

                showToast(result.message || 'Pendaftaran workshop berhasil dikirim.', 'success');
            } catch (error) {
                if (error.message !== 'Validation failed.') {
                    registrationAlert.classList.remove('hidden');
                    registrationAlert.innerHTML = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
                    showToast(error.message || 'Terjadi kesalahan.', 'danger');
                }
            } finally {
                setRegistrationLoading(false);
            }
        });
    });
</script>
@endpush
