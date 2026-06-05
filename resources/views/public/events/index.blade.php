@extends('layouts.webinar')

@section('title', 'Event FlexLabs | FlexLabs')
@section('meta_description', 'Event FlexLabs untuk informasi kegiatan, kolaborasi, job fair, edu fair, dan peluang belajar digital bersama FlexLabs.')
@section('brand_url', route('events.index'))

@section('content')
<section class="relative isolate overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-32 pb-16 sm:pt-36 lg:pt-40 lg:pb-20">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>

    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_12px_35px_rgba(91,62,142,0.10)] backdrop-blur">
                        <i class="bi bi-calendar-event text-sm"></i>
                        FlexLabs Event Hub
                    </span>

                    <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                        Temukan event dan peluang
                        <span class="text-flex-primary">bareng FlexLabs</span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Halaman ini berisi event yang diadakan, dihadiri, atau dikolaborasikan bersama FlexLabs.
                        Pilih event yang sesuai, lalu isi form minat supaya tim FlexLabs bisa follow up kamu.
                    </p>

                    <div class="mt-8 flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                        <a
                            href="#event-list"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] sm:w-auto"
                        >
                            Lihat Event
                            <i class="bi bi-arrow-down-short text-xl"></i>
                        </a>

                        <a
                            href="https://wa.me/62811134759?text=Halo%20FlexLabs%2C%20saya%20ingin%20bertanya%20tentang%20event%20FlexLabs."
                            target="_blank"
                            rel="noopener"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary shadow-[0_12px_30px_rgba(91,62,142,0.10)] transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/25 hover:bg-flex-soft sm:w-auto"
                        >
                            <i class="bi bi-whatsapp"></i>
                            Tanya Admin
                        </a>
                    </div>
                </div>
            </div>

            <div class="hidden lg:col-span-5 lg:flex lg:justify-end">
                <div class="relative transition duration-500 hover:rotate-[-2deg]">
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-flex-primary/10 blur-2xl"></div>
                    <div class="absolute -right-5 -top-5 -z-10 h-28 w-28 rounded-full bg-flex-primary/20 blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-32 w-32 rounded-full bg-purple-300/30 blur-2xl"></div>

                    <div class="relative overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white p-3 shadow-[0_28px_80px_rgba(91,62,142,0.18)]">
                        <div class="flex h-[420px] w-full max-w-[460px] items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-flex-primary to-[#8A62D2] p-8 text-center">
                            <div>
                                <div class="inline-flex h-20 w-20 items-center justify-center rounded-[2rem] bg-white/15 text-4xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                                    <i class="bi bi-calendar2-event"></i>
                                </div>

                                <div class="mt-6 text-sm font-black uppercase tracking-[0.2em] text-[#FFBE04]">
                                    FlexLabs Event
                                </div>

                                <div class="mt-3 text-4xl font-black leading-tight text-white">
                                    Capture leads dari setiap event
                                </div>

                                <p class="mt-4 text-sm font-semibold leading-7 text-white/75">
                                    Job fair, edu fair, seminar, kolaborasi, dan kegiatan FlexLabs lainnya.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($featuredEvents->isNotEmpty())
            @php
                $highlight = $featuredEvents->first();
            @endphp

            <div class="mt-12 overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(91,62,142,0.12)]">
                <div class="grid gap-0 lg:grid-cols-12">
                    <div class="bg-flex-primary p-7 text-white sm:p-8 lg:col-span-5">
                        <span class="inline-flex rounded-full bg-white/15 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-white">
                            Featured Event
                        </span>

                        <h2 class="mt-5 text-3xl font-black leading-tight tracking-[-0.05em]">
                            {{ $highlight->title }}
                        </h2>

                        <p class="mt-4 text-sm font-semibold leading-7 text-white/75">
                            {{ $highlight->short_description ?: 'Event pilihan FlexLabs yang sedang aktif.' }}
                        </p>
                    </div>

                    <div class="p-7 sm:p-8 lg:col-span-7">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl bg-flex-soft p-5">
                                <div class="text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                    Lokasi
                                </div>
                                <div class="mt-2 text-base font-black text-slate-900">
                                    {{ $highlight->location ?: 'Akan diinformasikan' }}
                                </div>
                            </div>

                            <div class="rounded-3xl bg-flex-soft p-5">
                                <div class="text-xs font-black uppercase tracking-[0.12em] text-flex-primary">
                                    Tanggal
                                </div>
                                <div class="mt-2 text-base font-black text-slate-900">
                                    @if($highlight->start_date)
                                        {{ $highlight->start_date->translatedFormat('d M Y') }}
                                        @if($highlight->end_date && !$highlight->end_date->isSameDay($highlight->start_date))
                                            - {{ $highlight->end_date->translatedFormat('d M Y') }}
                                        @endif
                                    @else
                                        Akan diinformasikan
                                    @endif
                                </div>
                            </div>
                        </div>

                        <a
                            href="{{ route('events.show', $highlight->slug) }}"
                            class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.22)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark sm:w-auto"
                        >
                            Isi Form Minat
                            <i class="bi bi-arrow-right-short text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

<section class="relative z-10 bg-flex-primary">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid overflow-hidden border-white/10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <div>
                    <div class="text-lg font-black leading-tight text-white">Lead Capture</div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">Data peserta masuk rapi per event.</p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:pl-7 lg:border-b-0 lg:border-r lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-qr-code"></i>
                </div>
                <div>
                    <div class="text-lg font-black leading-tight text-white">QR Friendly</div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">Cocok ditaruh di booth, poster, dan flyer.</p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10 lg:pl-7">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-megaphone"></i>
                </div>
                <div>
                    <div class="text-lg font-black leading-tight text-white">Campaign Ready</div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">Support UTM untuk tracking campaign.</p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 py-8 sm:pl-7 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-whatsapp"></i>
                </div>
                <div>
                    <div class="text-lg font-black leading-tight text-white">Easy Follow Up</div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">Tim bisa follow up leads setelah event.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-16 lg:py-20" id="event-list">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <span class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                    Active Events
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                    Pilih event yang kamu ikuti
                </h2>
            </div>

            <p class="max-w-xl text-base font-medium leading-7 text-slate-600">
                Isi data minat di halaman event supaya tim FlexLabs bisa menghubungi kamu sesuai kebutuhan program.
            </p>
        </div>

        @if($events->isEmpty())
            <div class="rounded-[2rem] border border-dashed border-flex-primary/20 bg-white p-8 text-center shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                <div class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-flex-soft text-3xl text-flex-primary">
                    <i class="bi bi-calendar-x"></i>
                </div>

                <h3 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                    Belum ada event aktif
                </h3>

                <p class="mt-3 text-base font-medium leading-7 text-slate-600">
                    Event akan muncul di sini setelah data event diaktifkan.
                </p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($events as $event)
                    @php
                        $eventImage = filled($event->image)
                            ? asset($event->image)
                            : null;
                    @endphp

                    <article class="group overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.06)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_rgba(91,62,142,0.14)]">
                        <div class="relative aspect-[16/10] overflow-hidden bg-flex-primary">
                            @if($eventImage)
                                <img
                                    src="{{ $eventImage }}"
                                    alt="{{ $event->title }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-flex-primary to-[#8A62D2] p-8 text-center">
                                    <div>
                                        <div class="text-xs font-black uppercase tracking-[0.2em] text-[#FFBE04]">
                                            FlexLabs Event
                                        </div>
                                        <div class="mt-3 text-2xl font-black leading-tight text-white">
                                            {{ $event->title }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($event->is_featured)
                                <span class="absolute left-4 top-4 rounded-full bg-[#FFBE04] px-3 py-1 text-xs font-black text-slate-950">
                                    Featured
                                </span>
                            @endif
                        </div>

                        <div class="p-6">
                            <div class="mb-4 flex flex-wrap gap-2">
                                @if($event->event_type)
                                    <span class="inline-flex rounded-full bg-flex-soft px-3 py-1 text-xs font-black text-flex-primary">
                                        {{ ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                    </span>
                                @endif

                                @if($event->event_mode)
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                        {{ ucfirst($event->event_mode) }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="line-clamp-2 text-xl font-black leading-tight tracking-[-0.04em] text-slate-950">
                                {{ $event->title }}
                            </h3>

                            <p class="mt-3 line-clamp-3 text-sm font-medium leading-7 text-slate-600">
                                {{ $event->short_description ?: 'Lihat detail event dan isi form minat.' }}
                            </p>

                            <div class="mt-5 grid gap-2 text-sm font-bold text-slate-600">
                                @if($event->location)
                                    <div class="flex items-start gap-2">
                                        <i class="bi bi-geo-alt text-flex-primary"></i>
                                        <span>{{ $event->location }}</span>
                                    </div>
                                @endif

                                @if($event->start_date)
                                    <div class="flex items-start gap-2">
                                        <i class="bi bi-calendar-event text-flex-primary"></i>
                                        <span>
                                            {{ $event->start_date->translatedFormat('d M Y') }}
                                            @if($event->end_date && !$event->end_date->isSameDay($event->start_date))
                                                - {{ $event->end_date->translatedFormat('d M Y') }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <a
                                href="{{ route('events.show', $event->slug) }}"
                                class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.22)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark"
                            >
                                Isi Form Minat
                                <i class="bi bi-arrow-right-short text-xl"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection