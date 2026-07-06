@extends('layouts.webinar')

@section('title', (($form->slug ?? null) === 'default-program-feedback' ? 'Feedback Program Belajar FlexLabs' : ($form->title ?? 'Feedback Program')) . ' | FlexLabs')
@section('meta_description', 'Bantu FlexLabs memahami pengalaman belajar kamu dan meningkatkan kualitas program berikutnya.')
@section('brand_url', url('/'))

@section('content')
@php
    $displayTitle = ($form->slug ?? null) === 'default-program-feedback'
        ? 'Feedback Program Belajar FlexLabs'
        : ($form->title ?? 'Feedback Program Belajar FlexLabs');

    $displayDescription = ($form->slug ?? null) === 'default-program-feedback'
        ? 'Bantu kami memahami pengalaman belajar kamu selama mengikuti program ini. Jawaban kamu akan digunakan untuk meningkatkan kualitas materi, instructor, platform, support, dan pengalaman belajar student berikutnya.'
        : ($form->description ?? 'Bantu kami memahami pengalaman belajar kamu selama mengikuti program ini. Jawaban kamu akan digunakan untuk meningkatkan kualitas materi, instructor, platform, support, dan pengalaman belajar student berikutnya.');
@endphp

<section class="bg-white pt-32 pb-12 sm:pt-36 lg:pt-40 lg:pb-14">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="max-w-4xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-flex-primary/5 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-flex-primary">
                <i class="bi bi-chat-square-heart text-sm"></i>
                FlexLabs Feedback
            </span>

            <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-[-0.06em] text-slate-950 sm:text-5xl lg:text-6xl">
                {{ $displayTitle }}
            </h1>

            <p class="mt-6 max-w-3xl text-base font-medium leading-8 text-slate-600 sm:text-lg">
                {{ $displayDescription }}
            </p>
        </div>
    </div>
</section>

<section class="bg-[#F2F4FA] py-10 lg:py-14">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-7 lg:px-10">
        <div class="mb-6 overflow-hidden rounded-[2rem] border border-flex-primary/10 bg-white shadow-[0_18px_48px_rgba(91,62,142,0.10)]">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between lg:p-8">
                <div>
                    <div class="text-sm font-black uppercase tracking-[0.14em] text-flex-primary">
                        Student Feedback
                    </div>

                    <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">
                        Hi, {{ $response->student_name ?: 'Student FlexLabs' }}
                    </h2>

                    <div class="mt-2 text-sm font-semibold leading-6 text-slate-500">
                        @if($response->student_email)
                            {{ $response->student_email }}
                        @else
                            Terima kasih sudah meluangkan waktu untuk mengisi feedback ini.
                        @endif
                    </div>
                </div>

                @if($response->status === 'submitted')
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">
                        <i class="bi bi-check-circle-fill"></i>
                        Submitted
                    </span>
                @else
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-black text-amber-700">
                        <i class="bi bi-clock-history"></i>
                        Pending Feedback
                    </span>
                @endif
            </div>

            @if($response->status !== 'submitted')
                <div class="border-b border-slate-100 p-6 lg:p-8">
                    <div class="rounded-[2rem] border border-flex-primary/10 bg-gradient-to-br from-flex-primary/5 via-white to-[#FFBE04]/10 p-6 lg:p-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl">
                                <div class="inline-flex items-center gap-2 rounded-full border border-flex-primary/15 bg-white/85 px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-flex-primary shadow-sm">
                                    <i class="bi bi-info-circle"></i>
                                    Cara Mengisi
                                </div>

                                <h2 class="mt-4 text-2xl font-black tracking-[-0.04em] text-slate-950">
                                    Isi berdasarkan pengalaman belajar kamu
                                </h2>

                                <p class="mt-3 text-sm font-semibold leading-7 text-slate-600">
                                    Pilih nilai yang paling sesuai dengan pengalaman kamu selama mengikuti program.
                                    Tidak ada jawaban benar atau salah. Feedback kamu akan membantu FlexLabs memperbaiki kualitas program berikutnya.
                                </p>
                            </div>

                            <div class="grid w-full gap-3 sm:grid-cols-2 lg:w-[500px] lg:shrink-0">
                                <div class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-[0_10px_28px_rgba(15,23,42,0.04)]">
                                    <div class="text-sm font-black text-slate-950">
                                        Arti nilai 1–5
                                    </div>

                                    <div class="mt-3 space-y-2 text-sm font-semibold text-slate-600">
                                        <div><span class="font-black text-slate-900">1</span> = Sangat kurang</div>
                                        <div><span class="font-black text-slate-900">2</span> = Kurang</div>
                                        <div><span class="font-black text-slate-900">3</span> = Cukup</div>
                                        <div><span class="font-black text-slate-900">4</span> = Baik</div>
                                        <div><span class="font-black text-slate-900">5</span> = Sangat baik</div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-[0_10px_28px_rgba(15,23,42,0.04)]">
                                    <div class="text-sm font-black text-slate-950">
                                        Arti nilai 0–10
                                    </div>

                                    <div class="mt-3 space-y-2 text-sm font-semibold text-slate-600">
                                        <div><span class="font-black text-slate-900">0</span> = Sangat tidak mungkin merekomendasikan</div>
                                        <div><span class="font-black text-slate-900">5</span> = Netral / masih ragu</div>
                                        <div><span class="font-black text-slate-900">10</span> = Sangat mungkin merekomendasikan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="mx-6 mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold leading-6 text-emerald-700 lg:mx-8">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-6 rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold leading-6 text-red-700 lg:mx-8">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if($response->status === 'submitted')
                <div class="p-6 lg:p-8">
                    <div class="rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-8 text-center sm:p-10">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.7rem] bg-emerald-100 text-4xl font-black text-emerald-600 shadow-[0_18px_40px_rgba(22,163,74,0.14)]">
                            <i class="bi bi-check-lg"></i>
                        </div>

                        <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Terima kasih!
                        </h2>

                        <p class="mx-auto mt-4 max-w-2xl text-base font-medium leading-8 text-slate-600">
                            Feedback kamu sudah kami terima. Masukan kamu membantu FlexLabs meningkatkan kualitas program,
                            materi, instructor, platform, dan support untuk student berikutnya.
                        </p>
                    </div>
                </div>
            @else
                <form
                    method="POST"
                    action="{{ route('feedback.public.store', $response->token) }}"
                    id="publicFeedbackForm"
                    class="p-6 lg:p-8"
                >
                    @csrf

                    @php
                        $groupedQuestions = $questions->groupBy(fn ($question) => $question->section ?: 'Feedback');
                    @endphp

                    <div class="space-y-6">
                        @foreach($groupedQuestions as $section => $sectionQuestions)
                            <div class="rounded-[2rem] border border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:p-7">
                                <div class="mb-5 flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-flex-primary/10 text-xl text-flex-primary">
                                        @if($section === 'Next Step')
                                            <i class="bi bi-arrow-up-right-circle"></i>
                                        @elseif($section === 'NPS')
                                            <i class="bi bi-megaphone"></i>
                                        @elseif($section === 'Testimonial')
                                            <i class="bi bi-chat-quote"></i>
                                        @elseif($section === 'Insight')
                                            <i class="bi bi-lightbulb"></i>
                                        @else
                                            <i class="bi bi-ui-checks"></i>
                                        @endif
                                    </div>

                                    <div>
                                        <h3 class="text-xl font-black tracking-[-0.03em] text-slate-950">
                                            {{ $section }}
                                        </h3>

                                        <p class="mt-1 text-sm font-semibold text-slate-500">
                                            @if($section === 'Next Step')
                                                Bantu kami memahami kebutuhan belajar kamu berikutnya.
                                            @elseif($section === 'NPS')
                                                Beri penilaian seberapa besar kemungkinan kamu merekomendasikan FlexLabs.
                                            @elseif($section === 'Testimonial')
                                                Bagikan pengalaman belajar kamu secara singkat.
                                            @else
                                                Isi bagian ini sesuai pengalaman belajar kamu.
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    @foreach($sectionQuestions as $question)
                                        @php
                                            $fieldName = "answers[{$question->id}]";
                                            $fieldKey = "answers.{$question->id}";
                                            $oldValue = old("answers.{$question->id}");
                                            $scale = (int) ($question->rating_scale ?: ($question->question_type === 'rating_0_10' ? 10 : 5));
                                            $minRating = $question->question_type === 'rating_0_10' ? 0 : 1;

                                            $choiceOptions = $question->options;

                                            if ($choiceOptions instanceof \Illuminate\Support\Collection) {
                                                $choiceOptions = $choiceOptions->all();
                                            }

                                            if (is_string($choiceOptions)) {
                                                $decodedOptions = json_decode($choiceOptions, true);
                                                $choiceOptions = json_last_error() === JSON_ERROR_NONE ? $decodedOptions : [];
                                            }

                                            $choiceOptions = is_array($choiceOptions) ? array_values($choiceOptions) : [];
                                        @endphp

                                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                            <label class="block text-base font-black leading-7 text-slate-950">
                                                {{ $question->question_text }}

                                                @if($question->is_required)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>

                                            @if($question->help_text)
                                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">
                                                    {{ $question->help_text }}
                                                </p>
                                            @endif

                                            <div class="mt-4">
                                                @if(in_array($question->question_type, ['rating_1_5', 'rating_0_10'], true))
                                                    <div class="flex flex-wrap gap-2">
                                                        @for($i = $minRating; $i <= $scale; $i++)
                                                            <label class="feedback-rating-option">
                                                                <input
                                                                    type="radio"
                                                                    name="{{ $fieldName }}"
                                                                    value="{{ $i }}"
                                                                    class="peer sr-only"
                                                                    {{ (string) $oldValue === (string) $i ? 'checked' : '' }}
                                                                    {{ $question->is_required ? 'required' : '' }}
                                                                >

                                                                <span class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/40 hover:bg-flex-soft peer-checked:border-flex-primary peer-checked:bg-flex-primary peer-checked:text-white peer-checked:shadow-[0_12px_26px_rgba(91,62,142,0.24)] sm:h-12 sm:w-12">
                                                                    {{ $i }}
                                                                </span>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                @elseif($question->question_type === 'single_choice')
                                                    @if(count($choiceOptions))
                                                        <div class="grid gap-3">
                                                            @foreach($choiceOptions as $option)
                                                                @php
                                                                    $optionValue = is_array($option)
                                                                        ? ($option['value'] ?? $option['label'] ?? $loop->iteration)
                                                                        : $option;

                                                                    $optionLabel = is_array($option)
                                                                        ? ($option['label'] ?? $option['value'] ?? $loop->iteration)
                                                                        : $option;

                                                                    $optionId = 'question_' . $question->id . '_option_' . $loop->index;
                                                                @endphp

                                                                <label
                                                                    for="{{ $optionId }}"
                                                                    class="group flex cursor-pointer items-start gap-3 rounded-3xl border border-slate-200 bg-white px-5 py-4 transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/40 hover:bg-flex-primary/5"
                                                                >
                                                                    <input
                                                                        id="{{ $optionId }}"
                                                                        type="radio"
                                                                        name="{{ $fieldName }}"
                                                                        value="{{ $optionValue }}"
                                                                        class="peer mt-1 h-4 w-4 shrink-0 border-slate-300 text-flex-primary focus:ring-flex-primary"
                                                                        {{ (string) $oldValue === (string) $optionValue ? 'checked' : '' }}
                                                                        {{ $question->is_required ? 'required' : '' }}
                                                                    >

                                                                    <span class="text-sm font-bold leading-6 text-slate-700 transition group-hover:text-flex-primary peer-checked:text-flex-primary">
                                                                        {{ $optionLabel }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <input
                                                            type="text"
                                                            name="{{ $fieldName }}"
                                                            value="{{ $oldValue }}"
                                                            placeholder="Tulis jawaban kamu di sini..."
                                                            class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold leading-7 text-slate-700 outline-none transition duration-200 placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                                            {{ $question->is_required ? 'required' : '' }}
                                                        >
                                                    @endif
                                                @elseif($question->question_type === 'checkbox')
                                                    @if(count($choiceOptions))
                                                        <div class="grid gap-3">
                                                            @foreach($choiceOptions as $option)
                                                                @php
                                                                    $optionValue = is_array($option)
                                                                        ? ($option['value'] ?? $option['label'] ?? $loop->iteration)
                                                                        : $option;

                                                                    $optionLabel = is_array($option)
                                                                        ? ($option['label'] ?? $option['value'] ?? $loop->iteration)
                                                                        : $option;

                                                                    $optionId = 'question_' . $question->id . '_checkbox_' . $loop->index;
                                                                    $oldArrayValue = old("answers.{$question->id}", []);
                                                                    $oldArrayValue = is_array($oldArrayValue) ? $oldArrayValue : [];
                                                                @endphp

                                                                <label
                                                                    for="{{ $optionId }}"
                                                                    class="group flex cursor-pointer items-start gap-3 rounded-3xl border border-slate-200 bg-white px-5 py-4 transition duration-200 hover:-translate-y-0.5 hover:border-flex-primary/40 hover:bg-flex-primary/5"
                                                                >
                                                                    <input
                                                                        id="{{ $optionId }}"
                                                                        type="checkbox"
                                                                        name="{{ $fieldName }}[]"
                                                                        value="{{ $optionValue }}"
                                                                        class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-flex-primary focus:ring-flex-primary"
                                                                        {{ in_array((string) $optionValue, array_map('strval', $oldArrayValue), true) ? 'checked' : '' }}
                                                                    >

                                                                    <span class="text-sm font-bold leading-6 text-slate-700 transition group-hover:text-flex-primary">
                                                                        {{ $optionLabel }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <input
                                                            type="text"
                                                            name="{{ $fieldName }}"
                                                            value="{{ $oldValue }}"
                                                            placeholder="Tulis jawaban kamu di sini..."
                                                            class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold leading-7 text-slate-700 outline-none transition duration-200 placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                                            {{ $question->is_required ? 'required' : '' }}
                                                        >
                                                    @endif
                                                @elseif($question->question_type === 'textarea')
                                                    <textarea
                                                        name="{{ $fieldName }}"
                                                        rows="4"
                                                        placeholder="Tulis jawaban kamu di sini..."
                                                        class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold leading-7 text-slate-700 outline-none transition duration-200 placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                                        {{ $question->is_required ? 'required' : '' }}
                                                    >{{ $oldValue }}</textarea>
                                                @else
                                                    <input
                                                        type="text"
                                                        name="{{ $fieldName }}"
                                                        value="{{ $oldValue }}"
                                                        placeholder="Tulis jawaban kamu di sini..."
                                                        class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold leading-7 text-slate-700 outline-none transition duration-200 placeholder:text-slate-400 focus:border-flex-primary focus:ring-4 focus:ring-flex-primary/10"
                                                        {{ $question->is_required ? 'required' : '' }}
                                                    >
                                                @endif
                                            </div>

                                            @error($fieldKey)
                                                <div class="mt-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-bold text-red-600">
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-semibold leading-6 text-slate-500">
                            Feedback kamu akan digunakan untuk evaluasi internal FlexLabs.
                        </p>

                        <button
                            type="submit"
                            id="submitFeedbackBtn"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-flex-primary px-7 py-3 text-sm font-black text-white shadow-[0_16px_35px_rgba(91,62,142,0.28)] transition duration-200 hover:-translate-y-0.5 hover:bg-flex-primaryDark hover:shadow-[0_22px_44px_rgba(91,62,142,0.34)] disabled:cursor-not-allowed disabled:opacity-75 disabled:hover:translate-y-0"
                        >
                            <span class="submit-default inline-flex items-center gap-2">
                                Kirim Feedback
                                <i class="bi bi-send-fill"></i>
                            </span>

                            <span class="submit-loading hidden items-center gap-2">
                                <span class="feedback-spinner"></span>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <div class="text-center text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
            © {{ date('Y') }} FlexLabs
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .feedback-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border-radius: 9999px;
        border: 2px solid rgba(255, 255, 255, 0.45);
        border-top-color: #ffffff;
        animation: feedbackSpin 700ms linear infinite;
    }

    @keyframes feedbackSpin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('publicFeedbackForm');
        const submitBtn = document.getElementById('submitFeedbackBtn');

        if (!form || !submitBtn) {
            return;
        }

        const submitDefault = submitBtn.querySelector('.submit-default');
        const submitLoading = submitBtn.querySelector('.submit-loading');

        form.addEventListener('submit', function () {
            submitBtn.disabled = true;

            if (submitDefault) {
                submitDefault.classList.add('hidden');
            }

            if (submitLoading) {
                submitLoading.classList.remove('hidden');
                submitLoading.classList.add('inline-flex');
            }
        });
    });
</script>
@endpush