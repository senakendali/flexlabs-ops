@extends('layouts.webinar')

@section('title', 'Workshop | FlexLabs')
@section('meta_description', 'Workshop berbayar FlexLabs untuk skill digital yang praktis, relevan, dan langsung bisa diterapkan.')
@section('brand_url', url('/workshop'))

@section('content')
<section class="workshop-hero-section relative isolate overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-32 pb-16 sm:pt-36 lg:pt-40 lg:pb-20">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>

    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_12px_35px_rgba(91,62,142,0.10)] backdrop-blur">
                        <i class="bi bi-stars text-sm"></i>
                        Builders Clinique
                    </span>

                    <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                        Upgrade skill digitalmu
                        <span class="text-flex-primary">lewat workshop praktis</span>
                        bareng FlexLabs
                    </h1>

                    <p class="mt-6 max-w-2xl text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Ikuti workshop FlexLabs untuk belajar skill yang langsung bisa dipakai:
                        praktis, fokus, dan relevan dengan kebutuhan kerja maupun project nyata.
                        Cocok buat pemula, freelancer, content creator, sampai profesional yang ingin upgrade cepat.
                    </p>

                    {{-- Hero image mobile --}}
                    <div class="mt-8 lg:hidden">
                        <div
                            class="hero-tilt-card relative transition duration-500 hover:rotate-[-1deg]"
                            data-tilt-target
                        >
                            <div class="absolute -right-4 -top-4 -z-10 h-24 w-24 rounded-full bg-flex-primary/20 blur-2xl"></div>
                            <div class="absolute -bottom-4 -left-4 -z-10 h-28 w-28 rounded-full bg-purple-300/30 blur-2xl"></div>

                            <div class="hero-image-frame relative overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white p-3 shadow-[0_24px_70px_rgba(91,62,142,0.16)]">
                                <span class="hero-camera-flash pointer-events-none absolute inset-0 z-20 rounded-[2rem] bg-white opacity-0"></span>

                                <img
                                    src="{{ asset('images/hero-workshop.png') }}"
                                    alt="Workshop FlexLabs"
                                    class="h-auto w-full rounded-[1.5rem] object-cover"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                        <a
                            href="#workshop-list"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] sm:w-auto"
                        >
                            Lihat Workshop
                            <i class="bi bi-arrow-down-short text-xl"></i>
                        </a>

                        <a
                            href="#about-workshop"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary shadow-[0_12px_30px_rgba(91,62,142,0.10)] transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/25 hover:bg-flex-soft sm:w-auto"
                        >
                            Kenapa Ikut Workshop?
                        </a>
                    </div>
                </div>
            </div>

            {{-- Hero image desktop --}}
            <div class="hidden lg:col-span-5 lg:flex lg:justify-end">
                <div
                    class="hero-tilt-card relative transition duration-500 hover:rotate-[-2deg]"
                    data-tilt-target
                >
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-flex-primary/10 blur-2xl"></div>
                    <div class="absolute -right-5 -top-5 -z-10 h-28 w-28 rounded-full bg-flex-primary/20 blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-32 w-32 rounded-full bg-purple-300/30 blur-2xl"></div>

                    <div class="hero-image-frame relative overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white p-3 shadow-[0_28px_80px_rgba(91,62,142,0.18)]">
                        <span class="hero-camera-flash pointer-events-none absolute inset-0 z-20 rounded-[2.25rem] bg-white opacity-0"></span>

                        <img
                            src="{{ asset('images/hero-workshop.png') }}"
                            alt="Workshop FlexLabs"
                            class="h-auto w-full max-w-[460px] rounded-[1.75rem] object-cover"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="relative z-10 bg-flex-primary">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid overflow-hidden border-white/10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-mortarboard"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Beginner-Friendly
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Materi dibuat mudah diikuti, bahkan untuk peserta yang baru mulai.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:pl-7 lg:border-b-0 lg:border-r lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-tools"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Hands-On Learning
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Peserta langsung praktik dan membuat output nyata.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10 lg:pl-7">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-compass"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Industry-Focused
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Topik diarahkan ke kebutuhan kerja dan project digital saat ini.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 py-8 sm:pl-7 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-kanban"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Engaging Projects
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Workshop diarahkan supaya peserta punya hasil yang bisa dibanggakan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-16 lg:py-20" id="about-workshop">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-5">
                <span class="inline-flex rounded-full bg-flex-soft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Why Workshop
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl lg:text-5xl">
                    Belajar cepat, fokus, dan langsung bisa dipakai
                </h2>
            </div>

            <div class="lg:col-span-7">
                <div class="rounded-[2rem] border border-slate-200 bg-slate-50/70 p-6 sm:p-8">
                    <p class="text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Workshop FlexLabs dirancang untuk peserta yang ingin belajar lebih singkat tetapi tetap
                        padat manfaat. Setiap workshop fokus pada satu topik yang jelas, dengan pendekatan
                        praktik dan hasil yang bisa langsung dirasakan.
                    </p>

                    <p class="mt-5 text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Jadi bukan cuma dengar teori, tapi benar-benar mencoba alur kerja, tools, dan output
                        yang biasa dipakai di dunia kerja digital saat ini.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-16 lg:py-20" id="workshop-list">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        @php
            $workshopCollection = collect($workshops ?? []);

            $workshopTypeOptions = collect([
                [
                    'key' => 'skill-sprint',
                    'label' => 'Skill Sprint',
                    'icon' => 'bi-lightning-charge',
                ],
                [
                    'key' => 'deep-dive',
                    'label' => 'Deep Dive',
                    'icon' => 'bi-layers',
                ],
                [
                    'key' => 'mastery-weekend',
                    'label' => 'Mastery Weekend',
                    'icon' => 'bi-trophy',
                ],
            ]);

            $normalizeWorkshopText = function ($value) {
                return strtolower(trim(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', (string) $value))));
            };

            $getWorkshopTypeKeys = function ($workshop) use ($normalizeWorkshopText) {
                return collect($workshop['schedules'] ?? [])
                    ->flatMap(function ($schedule) use ($normalizeWorkshopText) {
                        $scheduleTitle = $normalizeWorkshopText($schedule['title'] ?? $schedule['display_title'] ?? '');
                        $types = [];

                        if (str_contains($scheduleTitle, 'skill sprint') || str_contains($scheduleTitle, 'skillsprint')) {
                            $types[] = 'skill-sprint';
                        }

                        if (str_contains($scheduleTitle, 'deep dive') || str_contains($scheduleTitle, 'deepdive')) {
                            $types[] = 'deep-dive';
                        }

                        if (str_contains($scheduleTitle, 'mastery weekend') || str_contains($scheduleTitle, 'masteryweekend')) {
                            $types[] = 'mastery-weekend';
                        }

                        return $types;
                    })
                    ->filter()
                    ->unique()
                    ->values();
            };

            $getWorkshopModeKeys = function ($workshop) {
                return collect($workshop['schedules'] ?? [])
                    ->flatMap(function ($schedule) {
                        $locationType = strtolower((string) (
                            $schedule['location_type']
                            ?? $schedule['location_type_label']
                            ?? ''
                        ));

                        $modes = [];

                        if (str_contains($locationType, 'online')) {
                            $modes[] = 'online';
                        }

                        if (str_contains($locationType, 'offline')) {
                            $modes[] = 'offline';
                        }

                        return $modes;
                    })
                    ->filter()
                    ->unique()
                    ->values();
            };

            $onlineWorkshopCount = $workshopCollection
                ->filter(fn ($workshop) => $getWorkshopModeKeys($workshop)->contains('online'))
                ->count();

            $offlineWorkshopCount = $workshopCollection
                ->filter(fn ($workshop) => $getWorkshopModeKeys($workshop)->contains('offline'))
                ->count();
        @endphp

       <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_10px_28px_rgba(91,62,142,0.10)]">
                Workshop List
            </span>

            <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl lg:text-5xl">
                Pilih workshop yang mau kamu ikutin
            </h2>

            <p class="mx-auto mt-4 max-w-2xl text-base font-medium leading-7 text-slate-600">
                Pilih format workshop yang paling sesuai dengan kebutuhan belajarmu, mulai dari sesi cepat, pembahasan mendalam, sampai praktik intensif akhir pekan.
            </p>
        </div>

        <div class="mt-8 grid w-full gap-5 lg:grid-cols-3">
            <div class="h-full rounded-[1.75rem] border border-flex-primary/10 bg-white p-6 shadow-[0_16px_45px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-flex-primary/20 hover:shadow-[0_22px_60px_rgba(91,62,142,0.12)]">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-flex-primary">
                        <i class="bi bi-lightning-charge-fill text-xl"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-xl font-black text-slate-950">
                            Skill Sprint
                        </h3>
                        <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                            Sesi singkat dan fokus untuk mengenal skill baru dengan cepat. Cocok untuk kamu yang ingin mulai dari dasar dan langsung paham arah praktiknya.
                        </p>
                    </div>
                </div>
            </div>

            <div class="h-full rounded-[1.75rem] border border-flex-primary/10 bg-white p-6 shadow-[0_16px_45px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-flex-primary/20 hover:shadow-[0_22px_60px_rgba(91,62,142,0.12)]">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-flex-primary">
                        <i class="bi bi-layers-fill text-xl"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-xl font-black text-slate-950">
                            Deep Dive
                        </h3>
                        <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                            Sesi yang membahas topik lebih dalam, lengkap dengan alur berpikir, studi kasus, dan praktik yang lebih terarah.
                        </p>
                    </div>
                </div>
            </div>

            <div class="h-full rounded-[1.75rem] border border-flex-primary/10 bg-white p-6 shadow-[0_16px_45px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:border-flex-primary/20 hover:shadow-[0_22px_60px_rgba(91,62,142,0.12)]">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-flex-primary">
                        <i class="bi bi-trophy-fill text-xl"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-xl font-black text-slate-950">
                            Mastery Weekend
                        </h3>
                        <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                            Sesi intensif akhir pekan untuk membangun output yang lebih matang. Cocok untuk belajar lebih serius dan menyelesaikan project mini.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-5 lg:mt-16 lg:flex-row lg:items-end lg:justify-between">
            <div class="workshop-filter-panel">
                <div class="mb-2 px-1 text-xs font-black uppercase tracking-[0.14em] text-flex-primary">
                    FORMAT WORKSHOP
                </div>

                <div class="workshop-filter-wrap workshop-filter-wrap-left">
                    <button
                        type="button"
                        class="workshop-filter-btn is-active"
                        data-workshop-type-filter="all"
                        aria-pressed="true"
                    >
                        <span class="workshop-filter-icon">
                            <i class="bi bi-grid"></i>
                        </span>
                        <span>All</span>
                        <span class="workshop-filter-count">{{ $workshopCollection->count() }}</span>
                    </button>

                    @foreach ($workshopTypeOptions as $typeOption)
                        <button
                            type="button"
                            class="workshop-filter-btn"
                            data-workshop-type-filter="{{ $typeOption['key'] }}"
                            aria-pressed="false"
                        >
                            <span class="workshop-filter-icon">
                                <i class="bi {{ $typeOption['icon'] }}"></i>
                            </span>
                            <span>{{ $typeOption['label'] }}</span>
                            <span class="workshop-filter-count">
                                {{ $workshopCollection->filter(fn ($workshop) => $getWorkshopTypeKeys($workshop)->contains($typeOption['key']))->count() }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="workshop-filter-panel lg:text-right">
                <div class="mb-2 px-1 text-xs font-black uppercase tracking-[0.14em] text-flex-primary">
                    Mode Belajar
                </div>

                <div class="workshop-filter-wrap workshop-filter-wrap-right">
                    <button
                        type="button"
                        class="workshop-filter-btn is-active"
                        data-workshop-mode-filter="all"
                        aria-pressed="true"
                    >
                        <span class="workshop-filter-icon">
                            <i class="bi bi-grid"></i>
                        </span>
                        <span>All</span>
                        <span class="workshop-filter-count">{{ $workshopCollection->count() }}</span>
                    </button>

                    <button
                        type="button"
                        class="workshop-filter-btn"
                        data-workshop-mode-filter="online"
                        aria-pressed="false"
                    >
                        <span class="workshop-filter-icon">
                            <i class="bi bi-camera-video"></i>
                        </span>
                        <span>Online</span>
                        <span class="workshop-filter-count">{{ $onlineWorkshopCount }}</span>
                    </button>

                    <button
                        type="button"
                        class="workshop-filter-btn"
                        data-workshop-mode-filter="offline"
                        aria-pressed="false"
                    >
                        <span class="workshop-filter-icon">
                            <i class="bi bi-geo-alt"></i>
                        </span>
                        <span>Offline</span>
                        <span class="workshop-filter-count">{{ $offlineWorkshopCount }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3" id="workshop-grid">
            @forelse ($workshops as $workshop)
                @php
                    $priceText = 'Rp ' . number_format($workshop['price'] ?? 0, 0, ',', '.');

                    $oldPriceText = !empty($workshop['old_price'])
                        ? 'Rp ' . number_format($workshop['old_price'], 0, ',', '.')
                        : null;

                    $rating = (int) ($workshop['rating'] ?? 0);
                    $ratingCount = (int) ($workshop['rating_count'] ?? 0);
                    $image = $workshop['image'] ?? 'images/hero-workshop.png';

                    $workshopSchedules = collect($workshop['schedules'] ?? [])->take(2);
                    $allWorkshopSchedules = collect($workshop['schedules'] ?? []);
                    $availableScheduleCount = (int) ($workshop['available_schedule_count'] ?? $workshopSchedules->count());

                    $workshopTypeKeys = $getWorkshopTypeKeys($workshop);
                    $workshopTypeData = $workshopTypeKeys->implode(' ');

                    $workshopModeKeys = $getWorkshopModeKeys($workshop);
                    $workshopModeData = $workshopModeKeys->implode(' ');

                    $workshopTypeLabel = $workshop['badge']
                        ?? ($allWorkshopSchedules->first()['title'] ?? null);

                    $hasOnline = $workshopModeKeys->contains('online');
                    $hasOffline = $workshopModeKeys->contains('offline');
                @endphp

                <a
                    href="{{ route('workshop.show', $workshop['slug']) }}"
                    class="group block h-full no-underline"
                    data-workshop-card
                    data-workshop-types="{{ $workshopTypeData }}"
                    data-workshop-modes="{{ $workshopModeData }}"
                >
                    <article class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] transition duration-300 hover:-translate-y-1 hover:border-flex-primary/20 hover:shadow-[0_28px_70px_rgba(91,62,142,0.18)]">
                        <div class="relative overflow-hidden bg-flex-primary/5 p-3">
                            <img
                                src="{{ asset($image) }}"
                                alt="{{ $workshop['title'] }}"
                                class="h-56 w-full rounded-[1.45rem] object-cover transition duration-500 group-hover:scale-[1.04]"
                                onerror="this.src='{{ asset('images/hero-workshop.png') }}'"
                            >

                            @if (!empty($workshopTypeLabel))
                                <span class="absolute left-6 top-6 inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-flex-primary shadow-[0_12px_30px_rgba(15,23,42,0.14)]">
                                    {{ $workshopTypeLabel }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                @if (!empty($workshop['level']))
                                    <span>{{ $workshop['level'] }}</span>
                                @endif

                                @if (!empty($workshop['level']) && !empty($workshop['duration']))
                                    <span class="h-1.5 w-1.5 rounded-full bg-flex-primary/35"></span>
                                @endif

                                @if (!empty($workshop['duration']))
                                    <span>{{ $workshop['duration'] }}</span>
                                @endif

                                @if ((!empty($workshop['level']) || !empty($workshop['duration'])))
                                    <span class="h-1.5 w-1.5 rounded-full bg-flex-primary/35"></span>
                                @endif

                                <span>
                                    {{ $availableScheduleCount > 0 ? $availableScheduleCount . ' jadwal tersedia' : 'Jadwal segera hadir' }}
                                </span>
                            </div>

                            <h3 class="mt-4 text-xl font-black leading-snug tracking-[-0.04em] text-slate-950 transition group-hover:text-flex-primary">
                                {{ $workshop['title'] }}
                            </h3>

                            <p class="mt-3 line-clamp-3 text-sm font-medium leading-7 text-slate-600">
                                {{ $workshop['short_description'] }}
                            </p>

                            @if ($hasOnline || $hasOffline)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($hasOnline)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-600 bg-blue-600 px-3 py-1 text-[11px] font-black uppercase tracking-[0.08em] text-white shadow-[0_10px_24px_rgba(37,99,235,0.24)]">
                                            <i class="bi bi-camera-video-fill text-xs text-white"></i>
                                            Online
                                        </span>
                                    @endif

                                    @if ($hasOffline)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500 bg-amber-400 px-3 py-1 text-[11px] font-black uppercase tracking-[0.08em] text-slate-950 shadow-[0_10px_24px_rgba(245,158,11,0.26)]">
                                            <i class="bi bi-geo-alt-fill text-xs text-slate-950"></i>
                                            Offline
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if ($workshopSchedules->count())
                                <div class="mt-5 rounded-2xl border border-flex-primary/10 bg-flex-soft p-4">
                                    <div class="mb-3 text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                        Jadwal Terdekat
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ($workshopSchedules as $schedule)
                                            @php
                                                $scheduleTitle = $schedule['display_title']
                                                    ?? $schedule['title']
                                                    ?? $workshop['title'];

                                                $scheduleDateLabel = $schedule['schedule_date_label']
                                                    ?? (! empty($schedule['schedule_date'])
                                                        ? \Illuminate\Support\Carbon::parse($schedule['schedule_date'])->format('d M Y')
                                                        : '-');

                                                $scheduleTimeLabel = $schedule['time_label']
                                                    ?? trim(implode(' - ', array_filter([
                                                        $schedule['start_time'] ?? null,
                                                        $schedule['end_time'] ?? null,
                                                    ])));

                                                $scheduleLocationLabel = $schedule['location_type_label'] ?? null;

                                                $locationType = strtolower((string) (
                                                    $schedule['location_type']
                                                    ?? $schedule['location_type_label']
                                                    ?? ''
                                                ));

                                                $isOnline = str_contains($locationType, 'online');
                                                $isOffline = str_contains($locationType, 'offline');
                                            @endphp

                                            <div class="flex items-start gap-3 rounded-2xl bg-white/80 p-3">
                                                <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-flex-primary/10 text-flex-primary">
                                                    <i class="bi bi-calendar-event"></i>
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-black text-slate-900">
                                                        {{ $scheduleTitle }}
                                                    </div>

                                                    <div class="mt-1 text-xs font-bold leading-5 text-slate-500">
                                                        {{ $scheduleDateLabel }}

                                                        @if($scheduleTimeLabel && $scheduleTimeLabel !== '-')
                                                            • {{ $scheduleTimeLabel }}
                                                        @endif
                                                    </div>

                                                    @if($scheduleLocationLabel)
                                                        <div class="mt-2">
                                                            @if($isOnline)
                                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-flex-primary/15 bg-flex-primary/10 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.08em] text-flex-primary">
                                                                    <i class="bi bi-camera-video-fill text-xs"></i>
                                                                    {{ $scheduleLocationLabel }}
                                                                </span>
                                                            @elseif($isOffline)
                                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-flex-primary/15 bg-flex-primary/10 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.08em] text-flex-primary">
                                                                    <i class="bi bi-geo-alt-fill text-xs"></i>
                                                                    {{ $scheduleLocationLabel }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-flex-primary/15 bg-flex-primary/10 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.08em] text-flex-primary">
                                                                    <i class="bi bi-info-circle-fill text-xs"></i>
                                                                    {{ $scheduleLocationLabel }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-5 rounded-2xl border border-flex-primary/10 bg-flex-soft p-4">
                                @if ($oldPriceText)
                                    <div class="text-sm font-bold text-slate-400 line-through">
                                        {{ $oldPriceText }}
                                    </div>
                                @endif

                                <div class="mt-1 text-2xl font-black tracking-[-0.04em] text-flex-primary">
                                    {{ $priceText }}
                                </div>
                            </div>

                            <div class="mt-5 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1 text-amber-400">
                                        @for ($i = 0; $i < $rating; $i++)
                                            <i class="bi bi-star-fill text-sm"></i>
                                        @endfor

                                        @for ($i = $rating; $i < 5; $i++)
                                            <i class="bi bi-star text-sm"></i>
                                        @endfor
                                    </div>

                                    <span class="text-sm font-bold text-slate-500">
                                        ({{ number_format($ratingCount, 0, ',', '.') }})
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto pt-6">
                                <div class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-5 py-3 text-sm font-black text-white shadow-[0_14px_30px_rgba(91,62,142,0.22)] transition duration-200 group-hover:bg-flex-primaryDark">
                                    <span>Lihat detail workshop</span>
                                    <i class="bi bi-arrow-right transition duration-200 group-hover:translate-x-1"></i>
                                </div>
                            </div>
                        </div>
                    </article>
                </a>
            @empty
                <div class="md:col-span-2 lg:col-span-3">
                    <div class="rounded-[2rem] border border-dashed border-flex-primary/20 bg-white p-8 text-center shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <div class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-flex-primary/10 text-3xl text-flex-primary">
                            <i class="bi bi-calendar-x"></i>
                        </div>

                        <h3 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                            Workshop belum tersedia
                        </h3>

                        <p class="mx-auto mt-3 max-w-xl text-base font-medium leading-7 text-slate-600">
                            Saat ini belum ada workshop yang dibuka. Silakan cek kembali halaman ini nanti.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

        <div id="workshop-filter-empty" class="mt-8 hidden">
            <div class="rounded-[2rem] border border-dashed border-flex-primary/20 bg-white p-8 text-center shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                <div class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-flex-primary/10 text-3xl text-flex-primary">
                    <i class="bi bi-funnel"></i>
                </div>

                <h3 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                    Belum ada workshop untuk filter ini
                </h3>

                <p class="mx-auto mt-3 max-w-xl text-base font-medium leading-7 text-slate-600">
                    Coba pilih filter lainnya untuk melihat workshop yang tersedia.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="relative overflow-hidden py-16 lg:py-20" id="webinar-cta">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="relative overflow-hidden rounded-[2.25rem] border border-[#6f4aa9] bg-[#5B3E8E] px-6 py-8 shadow-[0_28px_80px_rgba(91,62,142,0.28)] sm:px-8 sm:py-10 lg:px-10 lg:py-12">
            <div class="pointer-events-none absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(255,255,255,0.55)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.55)_1px,transparent_1px)] [background-size:40px_40px]"></div>
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.16),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(255,190,4,0.18),transparent_24%)]"></div>

            <div class="relative grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-white backdrop-blur">
                        <i class="bi bi-broadcast-pin text-[#FFBE04]"></i>
                        Webinar FlexLabs
                    </span>

                    <h2 class="mt-5 max-w-3xl text-3xl font-black leading-[1.05] tracking-[-0.05em] text-white sm:text-4xl lg:text-5xl">
                        Belum siap ikut workshop?
                        Mulai dari webinar gratis
                        yang tetap terarah.
                    </h2>

                    <p class="mt-5 max-w-2xl text-base font-medium leading-8 text-white/90 sm:text-lg">
                        Ikuti sesi webinar FlexLabs untuk mengenal topik digital secara ringan, terstruktur, dan relevan sebelum lanjut ke workshop yang lebih praktis dan intensif.
                    </p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
                            <div class="text-sm font-black text-white">Tanpa biaya</div>
                            <div class="mt-1 text-sm font-medium leading-6 text-white/80">Akses sesi pengantar tanpa biaya pendaftaran.</div>
                        </div>
                        <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
                            <div class="text-sm font-black text-white">Topik relevan</div>
                            <div class="mt-1 text-sm font-medium leading-6 text-white/80">Bahas skill digital yang dekat dengan kebutuhan kerja.</div>
                        </div>
                        <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
                            <div class="text-sm font-black text-white">Next step jelas</div>
                            <div class="mt-1 text-sm font-medium leading-6 text-white/80">Bantu pilih jalur belajar sebelum masuk workshop.</div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="https://webinar.flexlabs.co.id" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#FFBE04] px-7 py-4 text-sm font-black text-slate-950 shadow-[0_18px_40px_rgba(255,190,4,0.24)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#ffd15a]">
                            <span>Lihat Webinar Gratis</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>

                        <a href="#workshop-list" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/16 bg-white/10 px-7 py-4 text-sm font-black text-white backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:bg-white/15">
                            <span>Tetap lihat workshop</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="relative mx-auto max-w-md">
                        <div class="absolute -inset-4 rounded-[2.25rem] bg-white/10 blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-[2rem] border border-white/14 bg-white/8 p-3 shadow-[0_24px_65px_rgba(31,18,57,0.35)] backdrop-blur">
                            <img
                                src="{{ asset('images/trial-hero.png') }}"
                                alt="Webinar Gratis FlexLabs"
                                class="h-auto w-full rounded-[1.5rem] object-cover"
                                onerror="this.onerror=null;this.src='{{ asset('images/hero-workshop.png') }}';"
                            >

                            
                        </div>

                        <div class="absolute -right-4 -top-4 hidden rounded-2xl bg-white px-4 py-3 text-sm font-black text-[#5B3E8E] shadow-[0_16px_40px_rgba(15,23,42,0.16)] sm:block">
                            <i class="bi bi-stars text-[#FFBE04]"></i>
                            Free Access
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
    .workshop-hero-section {
        display: flex;
        align-items: center;
        min-height: 100vh;
        min-height: 100svh;
    }

    @supports (height: 100dvh) {
        .workshop-hero-section {
            min-height: 100dvh;
        }
    }

    .hero-tilt-card {
        transform: rotate(0deg) scale(1);
        transform-origin: center center;
        will-change: transform;
    }

    .hero-tilt-card.is-tilted {
        transform: rotate(-4deg) scale(1);
    }

    .hero-tilt-card.is-tilted:hover {
        transform: rotate(-2deg) scale(1.01);
    }

    .hero-tilt-card.is-flashing .hero-camera-flash {
        animation: heroCameraFlash 700ms ease-out;
    }

    .workshop-filter-panel {
        width: auto;
        max-width: 100%;
    }

    .workshop-filter-panel:first-child {
        flex: 1 1 auto;
    }

    .workshop-filter-panel:last-child {
        flex: 0 0 auto;
    }

    .workshop-filter-wrap {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        border-radius: 9999px;
        background: #ffffff;
        padding: 0.45rem;
        border: 1px solid rgba(91, 62, 142, 0.10);
        box-shadow: 0 16px 36px rgba(91, 62, 142, 0.08);
    }

    .workshop-filter-wrap-left {
        justify-content: flex-start;
    }

    .workshop-filter-wrap-right {
        justify-content: flex-end;
    }

    .workshop-filter-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.48rem;
        border: 0;
        border-radius: 9999px;
        background: transparent;
        padding: 0.68rem 0.78rem;
        font-size: 0.82rem;
        font-weight: 900;
        color: #5B3E8E;
        transition: all 200ms ease;
        white-space: nowrap;
    }

    .workshop-filter-btn:hover {
        background: rgba(91, 62, 142, 0.08);
    }

    .workshop-filter-icon {
        display: inline-flex;
        height: 1.75rem;
        width: 1.75rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background: rgba(91, 62, 142, 0.10);
        color: #5B3E8E;
        transition: all 200ms ease;
    }

    .workshop-filter-count {
        display: inline-flex;
        min-width: 1.45rem;
        height: 1.45rem;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background: rgba(91, 62, 142, 0.10);
        padding-inline: 0.38rem;
        font-size: 0.68rem;
        font-weight: 950;
        line-height: 1;
        color: #5B3E8E;
        transition: all 200ms ease;
    }

    .workshop-filter-btn.is-active {
        background: #5B3E8E;
        color: #ffffff;
        box-shadow: 0 14px 30px rgba(91, 62, 142, 0.24);
    }

    .workshop-filter-btn.is-active .workshop-filter-icon,
    .workshop-filter-btn.is-active .workshop-filter-count {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    @keyframes heroCameraFlash {
        0% {
            opacity: 0;
            transform: scale(0.96);
        }

        12% {
            opacity: 0.95;
            transform: scale(1);
        }

        28% {
            opacity: 0.35;
        }

        100% {
            opacity: 0;
            transform: scale(1.04);
        }
    }

    @media (max-width: 1023px) {
        .hero-tilt-card.is-tilted {
            transform: rotate(-2deg) scale(1);
        }

        .hero-tilt-card.is-tilted:hover {
            transform: rotate(-1deg) scale(1.01);
        }

        .workshop-filter-panel {
            width: 100%;
        }

        .workshop-filter-wrap,
        .workshop-filter-wrap-left,
        .workshop-filter-wrap-right {
            width: 100%;
            justify-content: flex-start;
            border-radius: 1.5rem;
        }
    }

    @media (max-width: 640px) {
        .workshop-filter-btn {
            width: 100%;
            justify-content: space-between;
            padding-inline: 1rem;
        }

        .workshop-filter-btn span:nth-child(2) {
            flex: 1;
            text-align: left;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .hero-tilt-card,
        .hero-tilt-card.is-tilted,
        .hero-tilt-card.is-tilted:hover {
            transform: none;
            transition: none;
        }

        .hero-tilt-card.is-flashing .hero-camera-flash {
            animation: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tiltTargets = document.querySelectorAll('[data-tilt-target]');

        window.setTimeout(() => {
            tiltTargets.forEach((target) => {
                target.classList.add('is-tilted', 'is-flashing');

                window.setTimeout(() => {
                    target.classList.remove('is-flashing');
                }, 750);
            });
        }, 2000);

        const typeButtons = document.querySelectorAll('[data-workshop-type-filter]');
        const modeButtons = document.querySelectorAll('[data-workshop-mode-filter]');
        const workshopCards = document.querySelectorAll('[data-workshop-card]');
        const filterEmptyState = document.getElementById('workshop-filter-empty');

        let activeTypeFilter = 'all';
        let activeModeFilter = 'all';

        const syncActiveButtons = (buttons, dataKey, activeValue) => {
            buttons.forEach((button) => {
                const isActive = button.dataset[dataKey] === activeValue;

                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const applyWorkshopFilters = () => {
            let visibleCount = 0;

            workshopCards.forEach((card) => {
                const cardTypes = (card.dataset.workshopTypes || '')
                    .split(/\s+/)
                    .filter(Boolean);

                const cardModes = (card.dataset.workshopModes || '')
                    .split(/\s+/)
                    .filter(Boolean);

                const matchType = activeTypeFilter === 'all' || cardTypes.includes(activeTypeFilter);
                const matchMode = activeModeFilter === 'all' || cardModes.includes(activeModeFilter);
                const shouldShow = matchType && matchMode;

                card.classList.toggle('hidden', !shouldShow);

                if (shouldShow) {
                    visibleCount++;
                }
            });

            if (filterEmptyState) {
                filterEmptyState.classList.toggle('hidden', visibleCount > 0);
            }

            syncActiveButtons(typeButtons, 'workshopTypeFilter', activeTypeFilter);
            syncActiveButtons(modeButtons, 'workshopModeFilter', activeModeFilter);
        };

        typeButtons.forEach((button) => {
            button.addEventListener('click', function () {
                activeTypeFilter = this.dataset.workshopTypeFilter || 'all';
                applyWorkshopFilters();
            });
        });

        modeButtons.forEach((button) => {
            button.addEventListener('click', function () {
                activeModeFilter = this.dataset.workshopModeFilter || 'all';
                applyWorkshopFilters();
            });
        });

        applyWorkshopFilters();
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = this.getAttribute('href');

            if (!target || !target.startsWith('#')) {
                return;
            }

            const section = document.querySelector(target);

            if (!section) {
                return;
            }

            e.preventDefault();

            const navOffset = 96;
            const top = section.getBoundingClientRect().top + window.pageYOffset - navOffset;

            window.scrollTo({
                top,
                behavior: 'smooth'
            });
        });
    });
</script>
@endpush