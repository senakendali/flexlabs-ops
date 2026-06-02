@extends('layouts.webinar')

@section('title', 'Webinar | FlexLabs')

@section('content')
<div
    id="toastContainer"
    class="fixed right-4 top-[96px] z-[999999] grid w-[calc(100%-2rem)] max-w-sm gap-3 sm:right-6 sm:w-full"
    style="z-index: 999999;"
></div>

<section class="webinar-hero-section relative isolate overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-32 pb-16 sm:pt-36 lg:pt-40 lg:pb-20">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>

    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_12px_35px_rgba(91,62,142,0.10)] backdrop-blur">
                        <i class="bi bi-stars text-sm"></i>
                        Webinar FlexLabs
                    </span>

                    <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                        Mulai langkah pertamamu
                        <span class="text-flex-primary">di dunia digital</span>
                        bareng FlexLabs
                    </h1>

                    <p class="mt-6 max-w-2xl text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Ikuti webinar FlexLabs dan rasakan langsung pengalaman belajar yang terarah,
                        praktis, dan relevan dengan kebutuhan industri. Cocok buat pemula, career switcher,
                        maupun kamu yang ingin naik level lebih cepat.
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
                                    src="{{ asset('images/triall-hero.png') }}"
                                    alt="Hero FlexLabs"
                                    class="h-auto w-full rounded-[1.5rem] object-cover"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a
                            href="#registration-form"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)]"
                        >
                            Daftar Webinar
                            <i class="bi bi-arrow-down-short text-xl"></i>
                        </a>

                        <a
                            href="#about-flexlabs"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-3 text-sm font-black text-flex-primary shadow-[0_12px_30px_rgba(91,62,142,0.10)] transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/25 hover:bg-flex-soft"
                        >
                            Kenapa FlexLabs?
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
                            src="{{ asset('images/triall-hero.png') }}"
                            alt="Hero FlexLabs"
                            class="h-auto w-full max-w-[460px] rounded-[1.75rem] object-cover"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Stats bawah hero --}}
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
                        Cocok untuk teman-teman yang baru mulai belajar skill digital.
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
                        Belajar langsung lewat praktik sederhana dan mudah diikuti.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10 lg:pl-7">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-compass"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Guided Session
                    </div>

                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Peserta diarahkan step by step supaya tidak bingung saat mulai.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[150px] items-center gap-5 py-8 sm:pl-7 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-kanban"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Real Preview
                    </div>

                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Rasakan langsung seperti apa alur belajar di FlexLabs.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-16 lg:py-20" id="about-flexlabs">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-5">
                <span class="inline-flex rounded-full bg-flex-soft px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Why FlexLabs
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl lg:text-5xl">
                    Belajar lebih terarah untuk masuk ke dunia kerja digital
                </h2>
            </div>

            <div class="lg:col-span-7">
                <div class="rounded-[2rem] border border-slate-200 bg-slate-50/70 p-6 sm:p-8">
                    <p class="text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        FlexLabs adalah akademi digital dengan kurikulum yang dirancang agar peserta
                        belajar secara praktis, terstruktur, dan relevan dengan kebutuhan industri nyata.
                        Fokus kami bukan hanya membuat peserta memahami teori, tetapi juga membangun skill
                        yang benar-benar bisa dipakai dalam dunia kerja.
                    </p>

                    <p class="mt-5 text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Melalui pendekatan mentoring, pembelajaran berbasis praktik, dan arah kurikulum
                        yang selaras dengan kebutuhan industri, FlexLabs membantu peserta membangun fondasi
                        yang lebih kuat untuk berkembang dan membuka peluang karier, termasuk kesempatan
                        untuk direkrut oleh PT. System Ever Indonesia.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-16 lg:py-20" id="registration-form">
    <div class="mx-auto w-full max-w-5xl px-5 sm:px-7 lg:px-10">
        <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(91,62,142,0.16),transparent_34%),linear-gradient(135deg,#ffffff_0%,#faf8ff_100%)] p-6 sm:p-8 lg:p-10">
                <span class="inline-flex rounded-full bg-flex-primary/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Webinar Registration
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                    Daftar Webinar Sekarang
                </h2>

                <p class="mt-3 max-w-2xl text-base font-medium leading-7 text-slate-600">
                    Isi data dirimu dan pilih jadwal webinar yang paling cocok buat kamu.
                </p>
            </div>

            <div class="p-6 sm:p-8 lg:p-10">
                <form id="trialRegistrationForm">
                    @csrf

                    <div id="formContainer">
                        <div
                            id="formAlert"
                            class="mb-5 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold leading-6 text-red-700"
                        ></div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="full_name" class="mb-2 block text-sm font-black text-slate-800">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="full_name"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    placeholder="Masukkan nama lengkap"
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
                                    placeholder="Masukkan email aktif"
                                >

                                <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_email"></div>
                            </div>

                            <div>
                                <label for="phone" class="mb-2 block text-sm font-black text-slate-800">
                                    Nomor HP <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="phone"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    placeholder="Masukkan nomor WhatsApp aktif"
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

                            <div>
                                <label for="current_activity" class="mb-2 block text-sm font-black text-slate-800">
                                    Aktivitas Saat Ini <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="current_activity"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    placeholder="Contoh: Mahasiswa, Karyawan, Freelancer"
                                >

                                <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_current_activity"></div>
                            </div>

                            <div>
                                <label for="trial_schedule_id" class="mb-2 block text-sm font-black text-slate-800">
                                    Pilih Jadwal Webinar <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="trial_schedule_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                >
                                    <option value="">Pilih jadwal yang tersedia</option>

                                    @foreach ($schedules as $schedule)
                                        <option
                                            value="{{ $schedule->id }}"
                                            data-theme-id="{{ $schedule->trial_theme_id }}"
                                        >
                                            {{ $schedule->name }}
                                            - {{ \Illuminate\Support\Carbon::parse($schedule->schedule_date)->format('d M Y') }}
                                            ({{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                            - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }})
                                        </option>
                                    @endforeach
                                </select>

                                <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_trial_schedule_id"></div>
                            </div>

                            <div class="md:col-span-2">
                                <label for="trial_theme_id" class="mb-2 block text-sm font-black text-slate-800">
                                    Tema Webinar
                                </label>

                                <select
                                    id="trial_theme_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                >
                                    <option value="">Pilih tema webinar</option>

                                    @foreach ($themes as $theme)
                                        <option value="{{ $theme->id }}">
                                            {{ $theme->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_trial_theme_id"></div>
                            </div>

                            <div class="md:col-span-2">
                                <label for="goal" class="mb-2 block text-sm font-black text-slate-800">
                                    Tujuan Mengikuti Webinar <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    id="goal"
                                    rows="5"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                    placeholder="Ceritakan secara singkat kenapa kamu ingin ikut webinar ini"
                                ></textarea>

                                <div class="mt-2 hidden text-sm font-bold text-red-600" id="error_goal"></div>
                            </div>

                            <div class="md:col-span-2">
                                <button
                                    type="submit"
                                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                                    id="submitBtn"
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
                    </div>

                    <div id="successState" class="hidden text-center">
                        <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-[1.7rem] bg-emerald-100 text-4xl text-emerald-600">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <h4 class="mt-5 text-2xl font-black tracking-[-0.04em] text-slate-950">
                            Pendaftaran Berhasil Dikirim
                        </h4>

                        <p class="mx-auto mt-3 max-w-xl text-base font-medium leading-7 text-slate-600">
                            Terima kasih, data kamu sudah masuk. Tim FlexLabs akan segera menghubungi kamu untuk informasi webinar selanjutnya.
                        </p>

                        <button
                            type="button"
                            class="mt-7 inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-flex-soft px-6 py-3 text-sm font-black text-flex-primary transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_30px_rgba(91,62,142,0.12)]"
                            id="btnRegisterAgain"
                        >
                            <i class="bi bi-arrow-repeat"></i>
                            Isi Form Lagi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .webinar-hero-section {
        display: flex;
        align-items: center;
        min-height: 100vh;
        min-height: 100svh;
    }

    @supports (height: 100dvh) {
        .webinar-hero-section {
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
    });

    const trialRegistrationForm = document.getElementById('trialRegistrationForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('formAlert');
    const formContainer = document.getElementById('formContainer');
    const successState = document.getElementById('successState');
    const btnRegisterAgain = document.getElementById('btnRegisterAgain');

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

    function resetForm() {
        trialRegistrationForm.reset();
        clearValidationErrors();

        formAlert.classList.add('hidden');
        formAlert.innerHTML = '';

        formContainer.classList.remove('hidden');
        successState.classList.add('hidden');

        fields.trial_theme_id.value = '';

        setSubmitLoading(false);
    }

    function syncThemeFromSchedule() {
        const selectedOption = fields.trial_schedule_id.options[fields.trial_schedule_id.selectedIndex];

        if (!selectedOption) {
            return;
        }

        const themeId = selectedOption.dataset.themeId || '';

        if (themeId) {
            fields.trial_theme_id.value = themeId;
        }
    }

    function scrollToSection(selector) {
        const section = document.querySelector(selector);

        if (!section) {
            return;
        }

        const navOffset = 96;
        const top = section.getBoundingClientRect().top + window.pageYOffset - navOffset;

        window.scrollTo({
            top,
            behavior: 'smooth'
        });
    }

    fields.trial_schedule_id.addEventListener('change', syncThemeFromSchedule);

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = this.getAttribute('href');

            if (target && target.startsWith('#')) {
                const el = document.querySelector(target);

                if (el) {
                    e.preventDefault();
                    scrollToSection(target);
                }
            }
        });
    });

    trialRegistrationForm.addEventListener('submit', async function (e) {
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
            const response = await fetch(`{{ route('trial-class.store') }}`, {
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

            formContainer.classList.add('hidden');
            successState.classList.remove('hidden');

            showToast(result.message || 'Pendaftaran webinar berhasil dikirim.', 'success');
            scrollToSection('#registration-form');
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

    btnRegisterAgain.addEventListener('click', function () {
        resetForm();
        scrollToSection('#registration-form');
    });
</script>
@endpush