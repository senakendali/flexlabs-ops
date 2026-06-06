@extends('layouts.webinar')

@section('title', $theme->name . ' | Webinar FlexLabs')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($theme->description ?? 'Webinar FlexLabs untuk mengenal skill digital dan arah belajar yang relevan.'), 155))
@section('brand_url', route('webinar.index', [], false))

@section('content')
@php
    $fallbackImage = asset('images/triall-hero.png');

    $themeImage = $theme->image_url
        ?? (! empty($theme->image)
            ? asset('storage/' . $theme->image)
            : $fallbackImage);

    $programName = $theme->program->name ?? 'FlexLabs Program';

    $nearestSchedule = $schedules->first();

    $scheduleCount = $schedules->count();

    $waText = rawurlencode('Halo FlexLabs, saya ingin bertanya tentang webinar: ' . $theme->name);
    $waUrl = 'https://wa.me/62811134759?text=' . $waText;

    /**
     * Endpoint dibuat relative supaya:
     * - Local: POST /webinar
     * - Production domain webinar: POST /
     *
     * Ini mencegah URL campur seperti:
     * http://webinar.flexlabs.co.id:8007/...
     */
    $registrationEndpoint = \Illuminate\Support\Facades\Route::has('webinar.store')
        ? route('webinar.store', [], false)
        : route('trial-class.store', [], false);

    $webinarIndexUrl = \Illuminate\Support\Facades\Route::has('webinar.index')
        ? route('webinar.index', [], false)
        : url('/webinar');

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
                    <i class="bi bi-broadcast text-sm"></i>
                    Webinar FlexLabs
                </span>

                <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                    {{ $theme->name }}
                </h1>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-grid text-flex-primary"></i>
                        {{ $programName }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                        <i class="bi bi-calendar-event text-flex-primary"></i>
                        {{ $scheduleCount > 0 ? $scheduleCount . ' jadwal tersedia' : 'Jadwal segera hadir' }}
                    </span>

                    @if ($nearestSchedule)
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
                            <i class="bi bi-clock text-flex-primary"></i>
                            {{ \Illuminate\Support\Carbon::parse($nearestSchedule->schedule_date)->format('d M Y') }}
                            •
                            {{ \Illuminate\Support\Carbon::parse($nearestSchedule->start_time)->format('H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-10">
                <div class="relative">
                    <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-flex-primary/10 blur-2xl"></div>
                    <div class="absolute -right-5 -top-5 -z-10 h-28 w-28 rounded-full bg-flex-primary/20 blur-2xl"></div>
                    <div class="absolute -bottom-6 -left-6 -z-10 h-32 w-32 rounded-full bg-purple-300/30 blur-2xl"></div>

                    <div class="overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white p-3 shadow-[0_28px_80px_rgba(91,62,142,0.18)]">
                        <div class="relative aspect-[16/7] overflow-hidden rounded-[1.75rem] bg-flex-soft max-lg:aspect-video">
                            <img
                                src="{{ $themeImage }}"
                                alt="{{ $theme->name }}"
                                class="h-full w-full object-cover"
                                onerror="this.src='{{ $fallbackImage }}'"
                            >
                        </div>
                    </div>
                </div>
            </div>

            @if (! empty($theme->description))
                <p class="mt-6 w-full max-w-none text-base font-medium leading-8 text-slate-600 sm:text-lg">
                    {!! $formatTextarea($theme->description) !!}
                </p>
            @endif
        </div>
    </div>
</section>

<section class="relative z-10 bg-flex-primary">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid overflow-hidden border-white/10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-mortarboard"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Beginner-Friendly
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Cocok untuk teman-teman yang baru mulai eksplor topik digital.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:pl-7 lg:border-b-0 lg:border-r lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-lightbulb"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Clear Direction
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Peserta dapat gambaran arah belajar dan skill yang perlu dibangun.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-9 lg:pl-7">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-compass"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Guided Session
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Sesi diarahkan step by step supaya peserta lebih mudah mengikuti.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[140px] items-center gap-5 py-8 sm:pl-7 lg:py-9">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-kanban"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Program Preview
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Kenali pengalaman belajar sebelum lanjut ke program utama.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-16 lg:py-20">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            {{-- Form kiri, dibuat lebih lebar --}}
            <div class="lg:col-span-8">
                <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(91,62,142,0.14),transparent_34%),linear-gradient(135deg,#ffffff_0%,#faf8ff_100%)] p-6 sm:p-8">
                        <span class="inline-flex rounded-full bg-flex-primary/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                            Webinar Registration
                        </span>

                        <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                            Daftar Webinar
                        </h2>

                        <p class="mt-3 max-w-2xl text-base font-medium leading-7 text-slate-600">
                            Isi data diri kamu dan pilih jadwal webinar yang paling cocok. Tim FlexLabs akan menghubungi untuk konfirmasi.
                        </p>
                    </div>

                    <div class="p-6 sm:p-8">
                        <form id="trialRegistrationForm">
                            @csrf

                            <input type="hidden" id="trial_theme_id" value="{{ $theme->id }}">

                            <div id="formAlert" class="mb-5 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold leading-6 text-red-700"></div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="trial_schedule_id" class="mb-2 block text-sm font-black text-slate-800">
                                        Jadwal Webinar <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        id="trial_schedule_id"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    >
                                        <option value="">Pilih jadwal</option>

                                        @foreach ($schedules as $schedule)
                                            <option value="{{ $schedule->id }}">
                                                {{ $schedule->name }}
                                                - {{ \Illuminate\Support\Carbon::parse($schedule->schedule_date)->format('d M Y') }}
                                                ({{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                                - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_trial_schedule_id"></div>
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
                                    <label for="domicile_city" class="mb-2 block text-sm font-black text-slate-800">
                                        Domisili <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="domicile_city"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Contoh: Jakarta"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_domicile_city"></div>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="current_activity" class="mb-2 block text-sm font-black text-slate-800">
                                        Aktivitas Saat Ini <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="current_activity"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Mahasiswa, karyawan, freelancer, business owner, dll"
                                    >

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_current_activity"></div>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="goal" class="mb-2 block text-sm font-black text-slate-800">
                                        Tujuan Mengikuti Webinar <span class="text-red-500">*</span>
                                    </label>

                                    <textarea
                                        id="goal"
                                        rows="5"
                                        class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                        placeholder="Ceritakan tujuan kamu mengikuti webinar ini"
                                    ></textarea>

                                    <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_goal"></div>
                                </div>

                                <div class="md:col-span-2">
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                                        id="submitBtn"
                                        {{ $schedules->isEmpty() ? 'disabled' : '' }}
                                    >
                                        <span class="default-text">
                                            Daftar Webinar
                                        </span>

                                        <span class="loading-text hidden items-center gap-2">
                                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                            Mengirim...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="successState" class="hidden text-center">
                            <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-[1.7rem] bg-emerald-100 text-4xl text-emerald-600">
                                <i class="bi bi-check-lg"></i>
                            </div>

                            <h4 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                                Pendaftaran Berhasil
                            </h4>

                            <p class="mx-auto mt-3 max-w-xl text-base font-medium leading-7 text-slate-600">
                                Terima kasih, data kamu sudah kami terima. Tim FlexLabs akan segera menghubungi kamu.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info kanan --}}
            <div class="lg:col-span-4 lg:self-start">
                <aside class="lg:sticky lg:top-28">
                    <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
                        <div class="bg-flex-primary/5 p-3">
                            <img
                                src="{{ $themeImage }}"
                                alt="{{ $theme->name }}"
                                class="h-64 w-full rounded-[1.5rem] object-cover"
                                onerror="this.src='{{ $fallbackImage }}'"
                            >
                        </div>

                        <div class="border-b border-slate-200 p-6">
                            <div class="rounded-2xl border border-flex-primary/10 bg-flex-soft p-5">
                                <div class="text-sm font-black uppercase tracking-[0.12em] text-flex-primary">
                                    {{ $theme->name }}
                                </div>

                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                                    Pilih jadwal melalui dropdown di form pendaftaran. Tim FlexLabs akan menghubungi kamu untuk konfirmasi.
                                </p>

                                <div class="mt-5 divide-y divide-flex-primary/10 rounded-2xl border border-flex-primary/10 bg-white/80">
                                    <div class="flex items-center justify-between gap-4 p-4">
                                        <span class="text-sm font-bold text-slate-500">Program</span>
                                        <strong class="text-right text-sm font-black text-slate-900">
                                            {{ $programName }}
                                        </strong>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 p-4">
                                        <span class="text-sm font-bold text-slate-500">Jadwal</span>
                                        <strong class="text-right text-sm font-black text-slate-900">
                                            {{ $scheduleCount > 0 ? $scheduleCount . ' tersedia' : 'Segera hadir' }}
                                        </strong>
                                    </div>

                                    @if ($nearestSchedule)
                                        <div class="flex items-center justify-between gap-4 p-4">
                                            <span class="text-sm font-bold text-slate-500">Terdekat</span>
                                            <strong class="text-right text-sm font-black text-slate-900">
                                                {{ \Illuminate\Support\Carbon::parse($nearestSchedule->schedule_date)->format('d M Y') }}
                                            </strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <a
                                href="{{ $waUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary transition duration-200 hover:-translate-y-0.5 hover:bg-flex-soft"
                            >
                                <i class="bi bi-whatsapp"></i>
                                Tanya via WhatsApp
                            </a>

                            <a
                                href="{{ $webinarIndexUrl }}"
                                class="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-black text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50"
                            >
                                <i class="bi bi-arrow-left"></i>
                                Lihat Webinar Lain
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const registrationEndpoint = @js($registrationEndpoint);

    const trialRegistrationForm = document.getElementById('trialRegistrationForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('formAlert');
    const successState = document.getElementById('successState');

    const fields = {
        full_name: document.getElementById('full_name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        domicile_city: document.getElementById('domicile_city'),
        current_activity: document.getElementById('current_activity'),
        trial_schedule_id: document.getElementById('trial_schedule_id'),
        trial_theme_id: document.getElementById('trial_theme_id'),
        goal: document.getElementById('goal'),
    };

    const invalidClasses = [
        'border-red-400',
        'ring-4',
        'ring-red-100',
    ];

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
            info: {
                wrapper: 'border-sky-200 bg-sky-50 text-sky-800',
                icon: 'bg-sky-100 text-sky-600',
                iconClass: 'bi-info-circle',
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

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
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

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key];
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

    function setSubmitLoading(isLoading) {
        if (!submitBtn) {
            return;
        }

        const defaultText = submitBtn.querySelector('.default-text');
        const loadingText = submitBtn.querySelector('.loading-text');

        submitBtn.disabled = isLoading;

        if (defaultText) {
            defaultText.classList.toggle('hidden', isLoading);
        }

        if (loadingText) {
            loadingText.classList.toggle('hidden', !isLoading);
            loadingText.classList.toggle('inline-flex', isLoading);
        }
    }

    trialRegistrationForm?.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();

        formAlert.classList.add('hidden');
        formAlert.innerHTML = '';

        const payload = {
            full_name: fields.full_name.value.trim(),
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            domicile_city: fields.domicile_city.value.trim(),
            current_activity: fields.current_activity.value.trim(),
            trial_schedule_id: fields.trial_schedule_id.value,
            trial_theme_id: fields.trial_theme_id.value || null,
            goal: fields.goal.value.trim(),
        };

        setSubmitLoading(true);

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
                setValidationErrors(result.errors || {});
                throw new Error(result.message || 'Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Pendaftaran webinar gagal dikirim.');
            }

            trialRegistrationForm.classList.add('hidden');
            successState.classList.remove('hidden');

            showToast(result.message || 'Pendaftaran webinar berhasil dikirim.', 'success');
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('hidden');
                formAlert.innerHTML = error.message || 'Terjadi kesalahan. Silakan coba lagi.';
                showToast(error.message || 'Terjadi kesalahan.', 'danger');
            }
        } finally {
            setSubmitLoading(false);
        }
    });
</script>
@endpush
