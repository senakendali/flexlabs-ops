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
                                    src="{{ asset('images/workshop.png') }}"
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
                            src="{{ asset('images/workshop.png') }}"
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
        <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_10px_28px_rgba(91,62,142,0.10)]">
                Workshop List
            </span>

            <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl lg:text-5xl">
                Pilih workshop yang mau kamu ikutin
            </h2>

            <p class="mx-auto mt-4 max-w-2xl text-base font-medium leading-7 text-slate-600">
                Klik salah satu workshop untuk lihat detail lengkap, materi, harga, dan benefit yang akan kamu dapatkan.
            </p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($workshops as $workshop)
                @php
                    $priceText = 'Rp ' . number_format($workshop['price'] ?? 0, 0, ',', '.');

                    $oldPriceText = !empty($workshop['old_price'])
                        ? 'Rp ' . number_format($workshop['old_price'], 0, ',', '.')
                        : null;

                    $rating = (int) ($workshop['rating'] ?? 0);
                    $ratingCount = (int) ($workshop['rating_count'] ?? 0);
                    $image = $workshop['image'] ?? 'images/workshop.png';
                @endphp

                <a
                    href="{{ route('workshop.show', $workshop['slug']) }}"
                    class="group block h-full no-underline"
                >
                    <article class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] transition duration-300 hover:-translate-y-1 hover:border-flex-primary/20 hover:shadow-[0_28px_70px_rgba(91,62,142,0.18)]">
                        <div class="relative overflow-hidden bg-flex-primary/5 p-3">
                            <img
                                src="{{ asset($image) }}"
                                alt="{{ $workshop['title'] }}"
                                class="h-56 w-full rounded-[1.45rem] object-cover transition duration-500 group-hover:scale-[1.04]"
                                onerror="this.src='{{ asset('images/workshop.png') }}'"
                            >

                            @if (!empty($workshop['badge']))
                                <span class="absolute left-6 top-6 inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-flex-primary shadow-[0_12px_30px_rgba(15,23,42,0.14)]">
                                    {{ $workshop['badge'] }}
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
                            </div>

                            <h3 class="mt-4 text-xl font-black leading-snug tracking-[-0.04em] text-slate-950 transition group-hover:text-flex-primary">
                                {{ $workshop['title'] }}
                            </h3>

                            <p class="mt-3 line-clamp-3 text-sm font-medium leading-7 text-slate-600">
                                {{ $workshop['short_description'] }}
                            </p>

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