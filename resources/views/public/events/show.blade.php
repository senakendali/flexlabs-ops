@extends('layouts.webinar')

@section('title', $event->title . ' | Event FlexLabs')
@section('meta_description', $event->short_description ?? 'Event FlexLabs untuk informasi kegiatan dan peluang belajar digital bersama FlexLabs.')
@section('brand_url', route('events.index'))

@section('content')
@php
    $eventHeroImage = asset('images/event.png');
    $eventHeroImageMobile = asset('images/event-mobile.png');
@endphp

<div
    id="eventToast"
    class="fixed right-5 top-24 z-[9999] hidden max-w-sm rounded-2xl border bg-white p-4 shadow-[0_24px_70px_rgba(15,23,42,0.18)]"
>
    <div class="flex items-start gap-3">
        <div
            id="eventToastIcon"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-lg text-emerald-600"
        >
            <i class="bi bi-check-lg"></i>
        </div>

        <div class="min-w-0">
            <div id="eventToastTitle" class="text-sm font-black text-slate-950">
                Berhasil
            </div>
            <div id="eventToastMessage" class="mt-1 text-sm font-semibold leading-6 text-slate-600">
                Data berhasil dikirim.
            </div>
        </div>

        <button
            type="button"
            id="eventToastClose"
            class="ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            aria-label="Tutup notifikasi"
        >
            <i class="bi bi-x-lg text-xs"></i>
        </button>
    </div>
</div>

<section class="workshop-hero-section relative isolate overflow-hidden bg-slate-950">
    {{-- Full responsive background image tanpa overlay full --}}
    <picture class="absolute inset-0 -z-20 block h-full w-full">
        <source
            media="(max-width: 767px)"
            srcset="{{ $eventHeroImageMobile }}"
        >

        <img
            src="{{ $eventHeroImage }}"
            alt="{{ $event->title }}"
            class="h-full w-full object-cover object-center"
        >
    </picture>

    {{-- Decorative glow tipis, bukan overlay full --}}
    <div class="pointer-events-none absolute -left-28 top-20 -z-10 h-96 w-96 rounded-full bg-flex-primary/18 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 bottom-10 -z-10 h-96 w-96 rounded-full bg-[#FFBE04]/18 blur-3xl"></div>

    <div class="relative mx-auto flex min-h-[100vh] min-h-[100svh] w-full max-w-7xl items-end justify-center px-5 pb-10 pt-32 sm:px-7 sm:pb-14 sm:pt-36 lg:min-h-[100dvh] lg:px-10 lg:pb-16 lg:pt-40">
        <div class="w-full max-w-5xl rounded-[2rem] border border-white/40 bg-white/75 px-6 py-5 text-center shadow-[0_28px_90px_rgba(15,23,42,0.20)] backdrop-blur-xl sm:px-8 sm:py-7 lg:px-10 lg:py-8">
            <h1 class="text-3xl font-black leading-[1.08] tracking-[-0.05em] text-slate-950 sm:text-5xl lg:text-6xl">
                {{ $event->title }}
            </h1>

            <div class="mt-6 flex justify-center">
                <a
                    href="#event-registration-form"
                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-flex-primary px-7 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)]"
                >
                    Isi Data
                    <i class="bi bi-arrow-down-short text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</section>
<section class="relative z-10 bg-flex-primary">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="grid overflow-hidden border-white/10 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex min-h-[170px] items-start gap-5 border-b border-white/10 py-8 sm:border-r lg:border-b-0 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-globe2"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Global Curriculum
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Kuasai keterampilan digital dengan kurikulum tailor-made untuk bekerja di perusahaan teknologi,
                        terutama Korea Selatan.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[170px] items-start gap-5 border-b border-white/10 py-8 sm:pl-7 lg:border-b-0 lg:border-r lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-signpost-split"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Dual Career Pathway
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Dapatkan kesempatan untuk bekerja di PT. System Ever Indonesia atau di industri teknologi lainnya.
                    </p>
                </div>
            </div>

            <div class="flex min-h-[170px] items-start gap-5 py-8 sm:border-r lg:border-r-0 lg:pl-7 lg:py-10">
                <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-white/15 text-3xl text-white shadow-[0_16px_36px_rgba(21,12,36,0.14)]">
                    <i class="bi bi-kanban"></i>
                </div>

                <div>
                    <div class="text-lg font-black leading-tight text-white">
                        Industry-integrated projects
                    </div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-white/65">
                        Hasilkan portofolio siap kerja dari proyek nyata dengan klien.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="scroll-mt-24 bg-[#F2F4FA] py-16 lg:py-20" id="event-registration-form">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(91,62,142,0.14),transparent_34%),linear-gradient(135deg,#ffffff_0%,#faf8ff_100%)] p-6 sm:p-8">
                <span class="inline-flex rounded-full bg-flex-primary/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                    Registration Form
                </span>

                <h2 class="mt-4 text-3xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-4xl">
                    Daftar untuk event ini
                </h2>

                <p class="mt-3 max-w-2xl text-base font-medium leading-7 text-slate-600">
                    Isi data di bawah ini. Tim FlexLabs akan menghubungi kamu setelah data diterima.
                </p>
            </div>

            <div class="p-6 sm:p-8">
                <div
                    id="eventFormAlert"
                    class="mb-6 hidden rounded-2xl border p-4 text-sm font-bold leading-7"
                ></div>

                <form
                    id="eventLeadForm"
                    action="{{ route('events.leads.store', $event->slug) }}"
                    method="POST"
                    class="grid gap-5"
                >
                    @csrf

                    <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
                    <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
                    <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">
                    <input type="hidden" name="utm_term" value="{{ request('utm_term') }}">
                    <input type="hidden" name="utm_content" value="{{ request('utm_content') }}">

                    <div>
                        <label for="name" class="mb-2 block text-sm font-black text-slate-700">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                            placeholder="Contoh: Sena Kendali"
                        >
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="phone" class="mb-2 block text-sm font-black text-slate-700">
                                WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                placeholder="Contoh: 081234567890"
                            >
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-black text-slate-700">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                placeholder="nama@email.com"
                            >
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="institution" class="mb-2 block text-sm font-black text-slate-700">
                                Sekolah / Kampus / Instansi
                            </label>
                            <input
                                id="institution"
                                type="text"
                                name="institution"
                                value="{{ old('institution') }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                placeholder="Contoh: SMK Media Informatika"
                            >
                        </div>

                        <div>
                            <label for="city" class="mb-2 block text-sm font-black text-slate-700">
                                Kota
                            </label>
                            <input
                                id="city"
                                type="text"
                                name="city"
                                value="{{ old('city') }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                placeholder="Contoh: Jakarta"
                            >
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="position" class="mb-2 block text-sm font-black text-slate-700">
                                Status
                            </label>
                            <select
                                id="position"
                                name="position"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                            >
                                <option value="">Pilih status</option>
                                <option value="student" @selected(old('position') === 'student')>Siswa / Mahasiswa</option>
                                <option value="alumni" @selected(old('position') === 'alumni')>Alumni</option>
                                <option value="teacher" @selected(old('position') === 'teacher')>Guru / Pengajar</option>
                                <option value="parent" @selected(old('position') === 'parent')>Orang Tua</option>
                                <option value="employee" @selected(old('position') === 'employee')>Karyawan</option>
                                <option value="business_owner" @selected(old('position') === 'business_owner')>Pemilik Bisnis</option>
                                <option value="other" @selected(old('position') === 'other')>Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label for="interest" class="mb-2 block text-sm font-black text-slate-700">
                                Minat Program
                            </label>
                            <select
                                id="interest"
                                name="interest"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                            >
                                <option value="">Pilih minat</option>
                                <option value="Software Engineering" @selected(old('interest') === 'Software Engineering')>Software Engineering</option>
                                <option value="UI/UX Design" @selected(old('interest') === 'UI/UX Design')>UI/UX Design</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="mb-2 block text-sm font-black text-slate-700">
                            Catatan / Pertanyaan
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                            placeholder="Contoh: Saya tertarik belajar programming dari dasar."
                        >{{ old('notes') }}</textarea>
                    </div>

                    <label class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm font-semibold leading-6 text-slate-600">
                        <input
                            type="checkbox"
                            name="is_consent_given"
                            value="1"
                            required
                            @checked(old('is_consent_given'))
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-flex-primary focus:ring-flex-primary"
                        >
                        <span>
                            Saya bersedia dihubungi oleh tim FlexLabs terkait informasi program, event, atau penawaran yang relevan.
                        </span>
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button
                            type="submit"
                            id="eventSubmitButton"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-8 py-4 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                        >
                            <span data-submit-text>Daftar</span>
                            <i class="bi bi-send" data-submit-icon></i>
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
    html {
        scroll-behavior: smooth;
    }

    .workshop-hero-section {
        display: flex;
        align-items: stretch;
        min-height: 100vh;
        min-height: 100svh;
    }

    @supports (height: 100dvh) {
        .workshop-hero-section {
            min-height: 100dvh;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('eventLeadForm');
        const submitButton = document.getElementById('eventSubmitButton');
        const submitText = submitButton?.querySelector('[data-submit-text]');
        const submitIcon = submitButton?.querySelector('[data-submit-icon]');
        const alertBox = document.getElementById('eventFormAlert');
        const toast = document.getElementById('eventToast');
        const toastIcon = document.getElementById('eventToastIcon');
        const toastTitle = document.getElementById('eventToastTitle');
        const toastMessage = document.getElementById('eventToastMessage');
        const toastClose = document.getElementById('eventToastClose');

        let toastTimer = null;

        const showToast = (type, title, message) => {
            if (!toast || !toastIcon || !toastTitle || !toastMessage) {
                return;
            }

            clearTimeout(toastTimer);

            const isSuccess = type === 'success';

            toast.classList.remove('hidden');
            toast.classList.toggle('border-emerald-200', isSuccess);
            toast.classList.toggle('border-red-200', !isSuccess);

            toastIcon.className = isSuccess
                ? 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-lg text-emerald-600'
                : 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-lg text-red-600';

            toastIcon.innerHTML = isSuccess
                ? '<i class="bi bi-check-lg"></i>'
                : '<i class="bi bi-exclamation-lg"></i>';

            toastTitle.textContent = title;
            toastMessage.textContent = message;

            toastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 4500);
        };

        const showAlert = (type, message) => {
            if (!alertBox) {
                return;
            }

            const isSuccess = type === 'success';

            alertBox.className = isSuccess
                ? 'mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold leading-7 text-emerald-700'
                : 'mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold leading-7 text-red-700';

            alertBox.textContent = message;
            alertBox.classList.remove('hidden');
        };

        const setLoading = (isLoading) => {
            if (!submitButton || !submitText || !submitIcon) {
                return;
            }

            submitButton.disabled = isLoading;
            submitText.textContent = isLoading ? 'Mengirim...' : 'Daftar';
            submitIcon.className = isLoading ? 'bi bi-arrow-repeat animate-spin' : 'bi bi-send';
        };

        toastClose?.addEventListener('click', () => {
            toast?.classList.add('hidden');
        });


        form?.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (alertBox) {
                alertBox.classList.add('hidden');
                alertBox.textContent = '';
            }

            setLoading(true);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json')
                    ? await response.json()
                    : {};

                if (!response.ok) {
                    const errors = payload.errors
                        ? Object.values(payload.errors).flat()
                        : [];

                    const message = errors.length > 0
                        ? errors[0]
                        : (payload.message || 'Data belum berhasil dikirim. Coba cek lagi formnya.');

                    showAlert('error', message);
                    showToast('error', 'Gagal', message);
                    return;
                }

                const message = payload.message || 'Terima kasih! Data kamu sudah kami terima. Tim FlexLabs akan menghubungi kamu segera.';

                form.reset();
                showAlert('success', message);
                showToast('success', 'Berhasil', message);
            } catch (error) {
                const message = 'Terjadi gangguan koneksi. Coba lagi beberapa saat lagi.';

                showAlert('error', message);
                showToast('error', 'Gagal', message);
            } finally {
                setLoading(false);
            }
        });
    });
</script>
@endpush
