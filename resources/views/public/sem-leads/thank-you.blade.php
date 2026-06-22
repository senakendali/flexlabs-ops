@extends('layouts.webinar')

@section('title', ($pageTitle ?? 'Terima Kasih - FlexLabs') . ' | FlexLabs')
@section('meta_description', 'Terima kasih sudah mengisi form konsultasi program FlexLabs. Tim FlexLabs akan menghubungi kamu melalui WhatsApp.')
@section('brand_url', route('consultation.index'))

@section('content')
<section class="relative isolate flex min-h-screen items-center overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(91,62,142,0.16),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f7f4ff_100%)] pt-28 pb-16 sm:pt-32 lg:pt-36 lg:pb-20">
    <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:linear-gradient(rgba(91,62,142,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(91,62,142,0.06)_1px,transparent_1px)] [background-size:42px_42px]"></div>
    <div class="pointer-events-none absolute -right-24 top-24 -z-10 h-72 w-72 rounded-full bg-flex-primary/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 -z-10 h-72 w-72 rounded-full bg-purple-300/20 blur-3xl"></div>

    <div class="mx-auto w-full max-w-3xl px-5 sm:px-7 lg:px-10">
        <div class="overflow-hidden rounded-[2.25rem] border border-flex-primary/10 bg-white text-center shadow-[0_28px_80px_rgba(91,62,142,0.16)]">
            <div class="bg-flex-primary px-6 py-10 text-white sm:px-10">
                <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-[2rem] bg-white/15 text-4xl shadow-[0_16px_36px_rgba(21,12,36,0.16)]">
                    <i class="bi bi-check2-circle"></i>
                </div>

                <div class="mt-6 text-xs font-black uppercase tracking-[0.18em] text-[#FFBE04]">
                    Form Terkirim
                </div>

                <h1 class="mt-3 text-4xl font-black leading-tight tracking-[-0.06em] sm:text-5xl">
                    Terima kasih!
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-base font-semibold leading-8 text-white/75">
                    Data konsultasi kamu sudah masuk. Tim FlexLabs akan menghubungi kamu melalui WhatsApp.
                </p>
            </div>

            <div class="p-6 sm:p-8">
                @if(session('program_interest'))
                    <div class="mb-5 rounded-3xl bg-flex-soft p-5 text-left">
                        <div class="text-xs font-black uppercase tracking-[0.14em] text-flex-primary">
                            Program yang diminati
                        </div>
                        <div class="mt-2 text-lg font-black text-slate-950">
                            {{ session('program_interest') }}
                        </div>
                    </div>
                @endif

                <p class="text-base font-medium leading-8 text-slate-600">
                    Sambil menunggu follow up, kamu juga bisa langsung lanjut chat admin FlexLabs kalau ingin konsultasi lebih cepat.
                </p>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a
                        href="{{ $whatsappUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-flex-primary px-6 py-4 text-sm font-black text-white shadow-[0_18px_38px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_24px_50px_rgba(91,62,142,0.34)] sm:w-auto"
                    >
                        <i class="bi bi-whatsapp"></i>
                        Chat Admin FlexLabs
                    </a>

                    <a
                        href="{{ route('consultation.index') }}"
                        class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full border border-flex-primary/15 bg-white px-6 py-4 text-sm font-black text-flex-primary shadow-[0_12px_30px_rgba(91,62,142,0.10)] transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/25 hover:bg-flex-soft sm:w-auto"
                    >
                        Kembali ke Form
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
