@extends('layouts.webinar')

@section('title', ($pageTitle ?? 'Konsultasi Program FlexLabs') . ' | FlexLabs')
@section('meta_description', 'Konsultasi program FlexLabs untuk membantu kamu memilih jalur belajar yang sesuai, mulai dari Software Engineering, AI Productivity, UI/UX Design, hingga workshop praktis.')
@section('brand_url', route('consultation.index'))

@section('content')
<section class="relative isolate min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-28 pb-16 sm:pt-32 lg:pt-36 lg:pb-20">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>
    <div class="pointer-events-none absolute -right-24 top-24 -z-10 h-72 w-72 rounded-full bg-flex-primary/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 -z-10 h-72 w-72 rounded-full bg-purple-300/20 blur-3xl"></div>

    <div class="mx-auto w-full max-w-6xl px-5 sm:px-7 lg:px-10">
        <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-5">
                <div class="sticky top-28">
                    <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/85 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary shadow-[0_12px_35px_rgba(91,62,142,0.10)] backdrop-blur">
                        <i class="bi bi-chat-dots text-sm"></i>
                        Konsultasi Program
                    </span>

                    <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                        Pilih program yang paling
                        <span class="text-flex-primary">pas buat kamu</span>
                    </h1>

                    <p class="mt-6 text-base font-medium leading-8 text-slate-600 sm:text-lg">
                        Isi data singkat di form ini. Tim FlexLabs akan bantu arahkan program yang cocok berdasarkan kebutuhan, level, dan tujuan belajar kamu.
                    </p>

                    <div class="mt-8 grid gap-3">
                        <div class="flex items-start gap-4 rounded-3xl border border-flex-primary/10 bg-white/80 p-5 shadow-[0_14px_35px_rgba(15,23,42,0.05)] backdrop-blur">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-flex-soft text-xl text-flex-primary">
                                <i class="bi bi-compass"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-950">Dibantu pilih jalur belajar</div>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-600">Cocok kalau kamu masih bingung memilih jalur belajar yang paling sesuai dengan kebutuhanmu.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 rounded-3xl border border-flex-primary/10 bg-white/80 p-5 shadow-[0_14px_35px_rgba(15,23,42,0.05)] backdrop-blur">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-flex-soft text-xl text-flex-primary">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-950">Follow up via WhatsApp</div>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-600">Tim akan menghubungi kamu sesuai waktu terbaik yang kamu pilih.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 rounded-3xl border border-flex-primary/10 bg-white/80 p-5 shadow-[0_14px_35px_rgba(15,23,42,0.05)] backdrop-blur">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-flex-soft text-xl text-flex-primary">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-950">Tanpa ribet</div>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-600">Cukup isi nama, WhatsApp, minat program, kebutuhan, dan waktu terbaik dihubungi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_28px_80px_rgba(91,62,142,0.16)]">
                    <div class="border-b border-slate-100 bg-flex-primary px-6 py-6 text-white sm:px-8">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-black uppercase tracking-[0.18em] text-[#FFBE04]">
                                    FlexLabs Consultation
                                </div>
                                <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] sm:text-3xl">
                                    Isi form konsultasi
                                </h2>
                            </div>

                            <div class="mt-1 inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl shadow-[0_14px_34px_rgba(21,12,36,0.16)] sm:h-14 sm:w-14 sm:text-2xl">
                                <i class="bi bi-person-lines-fill leading-none"></i>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        @if(session('success'))
                            <div class="mb-6 rounded-3xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold leading-6 text-green-800">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold leading-6 text-red-700">
                                Ada data yang belum sesuai. Cek lagi field yang ditandai ya.
                            </div>
                        @endif

                        <form action="{{ $formAction }}" method="POST" class="space-y-5">
                            @csrf

                            <input type="text" name="company_website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

                            @foreach(($trackingFields ?? []) as $trackingKey => $trackingValue)
                                <input type="hidden" name="{{ $trackingKey }}" value="{{ old($trackingKey, $trackingValue) }}">
                            @endforeach

                            <div>
                                <label for="name" class="mb-2 block text-sm font-black text-slate-900">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                        <i class="bi bi-person text-base leading-none"></i>
                                    </span>

                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        placeholder="Contoh: Sena Kendali"
                                        autocomplete="name"
                                        class="min-h-13 w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-5 text-sm font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10 @error('name') border-red-300 focus:border-red-400 focus:ring-red-100 @enderror"
                                        required
                                    >
                                </div>

                                @error('name')
                                    <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="whatsapp_number" class="mb-2 block text-sm font-black text-slate-900">
                                    Nomor WhatsApp <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                        <i class="bi bi-whatsapp text-base leading-none"></i>
                                    </span>

                                    <input
                                        type="tel"
                                        id="whatsapp_number"
                                        name="whatsapp_number"
                                        value="{{ old('whatsapp_number') }}"
                                        placeholder="Contoh: 081234567890"
                                        autocomplete="tel"
                                        class="min-h-13 w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-5 text-sm font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10 @error('whatsapp_number') border-red-300 focus:border-red-400 focus:ring-red-100 @enderror"
                                        required
                                    >
                                </div>

                                @error('whatsapp_number')
                                    <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="program_interest" class="mb-2 block text-sm font-black text-slate-900">
                                        Program yang diminati <span class="text-red-500">*</span>
                                    </label>

                                    @if(!empty($selectedProgram))
                                        <input type="hidden" name="program_interest" value="{{ $selectedProgram }}">

                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                                <i class="bi bi-mortarboard text-base leading-none"></i>
                                            </span>

                                            <div class="flex min-h-13 w-full items-center rounded-2xl border border-flex-primary/15 bg-flex-soft py-4 pl-12 pr-5 text-sm font-black text-flex-primary">
                                                {{ $selectedProgram }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                                <i class="bi bi-mortarboard text-base leading-none"></i>
                                            </span>

                                            <select
                                                id="program_interest"
                                                name="program_interest"
                                                class="min-h-13 w-full appearance-none rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-12 text-sm font-bold text-slate-900 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10 @error('program_interest') border-red-300 focus:border-red-400 focus:ring-red-100 @enderror"
                                                required
                                            >
                                                <option value="">Pilih program</option>
                                                @foreach(($programOptions ?? []) as $programValue => $programLabel)
                                                    <option value="{{ $programLabel }}" @selected(old('program_interest') === $programLabel)>
                                                        {{ $programLabel }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <span class="pointer-events-none absolute right-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-slate-400">
                                                <i class="bi bi-chevron-down text-sm leading-none"></i>
                                            </span>
                                        </div>
                                    @endif

                                    @error('program_interest')
                                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="best_contact_time" class="mb-2 block text-sm font-black text-slate-900">
                                        Waktu terbaik dihubungi
                                    </label>

                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                            <i class="bi bi-clock text-base leading-none"></i>
                                        </span>

                                        <select
                                            id="best_contact_time"
                                            name="best_contact_time"
                                            class="min-h-13 w-full appearance-none rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-12 text-sm font-bold text-slate-900 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10 @error('best_contact_time') border-red-300 focus:border-red-400 focus:ring-red-100 @enderror"
                                        >
                                            <option value="">Pilih waktu</option>
                                            @foreach(($bestContactTimeOptions ?? []) as $timeValue => $timeLabel)
                                                <option value="{{ $timeValue }}" @selected(old('best_contact_time') === $timeValue)>
                                                    {{ $timeLabel }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <span class="pointer-events-none absolute right-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-slate-400">
                                            <i class="bi bi-chevron-down text-sm leading-none"></i>
                                        </span>
                                    </div>

                                    @error('best_contact_time')
                                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="help_need" class="mb-2 block text-sm font-black text-slate-900">
                                    Kamu ingin dibantu untuk apa?
                                </label>

                                <div class="relative">
                                    <span class="pointer-events-none absolute left-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-flex-primary">
                                        <i class="bi bi-stars text-base leading-none"></i>
                                    </span>

                                    <select
                                        id="help_need"
                                        name="help_need"
                                        class="min-h-13 w-full appearance-none rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-12 text-sm font-bold text-slate-900 outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10 @error('help_need') border-red-300 focus:border-red-400 focus:ring-red-100 @enderror"
                                    >
                                        <option value="">Pilih kebutuhan</option>
                                        @foreach(($helpNeedOptions ?? []) as $needValue => $needLabel)
                                            <option value="{{ $needLabel }}" @selected(old('help_need') === $needLabel)>
                                                {{ $needLabel }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <span class="pointer-events-none absolute right-4 top-1/2 z-10 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center text-slate-400">
                                        <i class="bi bi-chevron-down text-sm leading-none"></i>
                                    </span>
                                </div>

                                @error('help_need')
                                    <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-3xl bg-slate-50 p-5">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-flex-soft text-flex-primary">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <p class="text-sm font-semibold leading-6 text-slate-600">
                                        Data kamu hanya digunakan untuk kebutuhan konsultasi program FlexLabs dan follow up melalui WhatsApp.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-4 text-center text-sm font-black text-white shadow-[0_18px_38px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_24px_50px_rgba(91,62,142,0.34)]"
                            >
                                <span>Saya Mau Konsultasi Program</span>
                                <i class="bi bi-arrow-right-short shrink-0 text-2xl leading-none"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
