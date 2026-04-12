@extends('layouts.public')

@section('title', 'Trial Class | FlexLabs')

@section('content')
<section class="hero-section">
    <div class="container">
        <div
            id="toastContainer"
            class="toast-container position-fixed top-0 end-0 p-3"
        ></div>

        <div class="row g-4 hero-row">
            <div class="col-lg-7">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        Trial Class FlexLabs
                    </span>

                    <h1 class="hero-title">
                        Mulai langkah pertamamu
                        <span class="highlight">di dunia digital</span>
                        bareng FlexLabs
                    </h1>

                    <p class="hero-desc">
                        Ikuti trial class FlexLabs dan rasakan langsung pengalaman belajar yang terarah,
                        praktis, dan relevan dengan kebutuhan industri. Cocok buat pemula, career switcher,
                        maupun kamu yang ingin naik level lebih cepat.
                    </p>

                    {{-- Hero image mobile --}}
                    <div class="hero-mobile-image-wrap d-lg-none">
                        <img
                            src="{{ asset('images/hero.png') }}"
                            alt="Hero FlexLabs"
                            class="hero-image hero-image-mobile"
                        >
                    </div>


                    <div class="hero-cta-wrap d-flex flex-wrap gap-3 mt-4">
                        <a href="#registration-form" class="btn btn-brand btn-lg">
                            Daftar Trial Class
                        </a>
                    </div>
                </div>
            </div>

            {{-- Hero image desktop --}}
            <div class="col-lg-5 hero-visual-col d-none d-lg-flex">
                <div class="hero-visual">
                    <img
                        src="{{ asset('images/hero.png') }}"
                        alt="Hero FlexLabs"
                        class="hero-image hero-image-desktop"
                    >
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-highlight-section">
    <div class="container-fluid px-0">
        <div class="stats-highlight-full">
            <div class="row g-0">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Beginner-Friendly
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Hands-On Learning
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-compass"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Industry-Focused
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item stats-highlight-item-last">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-kanban"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Engaging Projects
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="about-section" id="about-flexlabs">
    <div class="about-full">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <span class="section-label">Why FlexLabs</span>
                    <h2 class="section-title-large mt-3">
                        Belajar lebih terarah untuk masuk ke dunia kerja digital
                    </h2>
                </div>

                <div class="col-lg-7">
                    <p class="about-main-text">
                        FlexLabs adalah akademi digital dengan kurikulum yang dirancang agar peserta
                        belajar secara praktis, terstruktur, dan relevan dengan kebutuhan industri nyata.
                        Fokus kami bukan hanya membuat peserta memahami teori, tetapi juga membangun skill
                        yang benar-benar bisa dipakai dalam dunia kerja.
                    </p>

                    <p class="about-main-text mb-0">
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

<section class="content-section" id="registration-form">
    <div class="container">
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-label">Registration Form</span>
                <h2 class="section-title mt-3">Daftar Trial Class Sekarang</h2>
                <p class="section-subtitle">
                    Isi data dirimu dan pilih jadwal trial yang paling cocok buat kamu.
                </p>
            </div>

            <div class="section-card-body">
                <form id="trialRegistrationForm">
                    @csrf

                    <div id="formContainer">
                        <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="full_name" class="form-control form-control-lg" placeholder="Masukkan nama lengkap">
                                <div class="invalid-feedback" id="error_full_name"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" class="form-control form-control-lg" placeholder="Masukkan email aktif">
                                <div class="invalid-feedback" id="error_email"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">
                                    Nomor HP <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="phone" class="form-control form-control-lg" placeholder="Masukkan nomor WhatsApp aktif">
                                <div class="invalid-feedback" id="error_phone"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="domicile_city" class="form-label">
                                    Domisili <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="domicile_city" class="form-control form-control-lg" placeholder="Contoh: Jakarta">
                                <div class="invalid-feedback" id="error_domicile_city"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="current_activity" class="form-label">
                                    Aktivitas Saat Ini <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="current_activity" class="form-control form-control-lg" placeholder="Contoh: Mahasiswa, Karyawan, Freelancer, Fresh Graduate">
                                <div class="invalid-feedback" id="error_current_activity"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="trial_schedule_id" class="form-label">
                                    Pilih Jadwal Trial <span class="text-danger">*</span>
                                </label>
                                <select id="trial_schedule_id" class="form-select form-select-lg">
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
                                <div class="invalid-feedback" id="error_trial_schedule_id"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="trial_theme_id" class="form-label">
                                    Tema Trial
                                </label>
                                <select id="trial_theme_id" class="form-select form-select-lg">
                                    <option value="">Pilih tema trial</option>
                                    @foreach ($themes as $theme)
                                        <option value="{{ $theme->id }}">
                                            {{ $theme->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error_trial_theme_id"></div>
                            </div>

                            <div class="col-12">
                                <label for="goal" class="form-label">
                                    Tujuan Mengikuti Trial <span class="text-danger">*</span>
                                </label>
                                <textarea id="goal" rows="5" class="form-control form-control-lg" placeholder="Ceritakan secara singkat kenapa kamu ingin ikut trial class ini"></textarea>
                                <div class="invalid-feedback" id="error_goal"></div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-brand btn-lg px-4" id="submitBtn">
                                    <span class="default-text">
                                        Kirim Pendaftaran
                                    </span>
                                    <span class="loading-text d-none">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Mengirim...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="successState" class="success-state">
                        <div class="success-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Pendaftaran Berhasil Dikirim</h4>
                        <p class="text-muted mb-4">
                            Terima kasih, data kamu sudah masuk. Tim FlexLabs akan segera menghubungi kamu untuk informasi selanjutnya.
                        </p>
                        <button type="button" class="btn btn-soft" id="btnRegisterAgain">
                            <i class="bi bi-arrow-repeat me-2"></i>
                            Isi Form Lagi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
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

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const id = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = type === 'warning' || type === 'info'
            ? 'btn-close'
            : 'btn-close btn-close-white';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field && field.classList) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function setSubmitLoading(isLoading) {
        submitBtn.disabled = isLoading;
        submitBtn.querySelector('.default-text').classList.toggle('d-none', isLoading);
        submitBtn.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function resetForm() {
        trialRegistrationForm.reset();
        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        formContainer.style.display = 'block';
        successState.style.display = 'none';
        fields.trial_theme_id.value = '';
        setSubmitLoading(false);
    }

    function syncThemeFromSchedule() {
        const selectedOption = fields.trial_schedule_id.options[fields.trial_schedule_id.selectedIndex];
        if (!selectedOption) return;

        const themeId = selectedOption.dataset.themeId || '';
        if (themeId) {
            fields.trial_theme_id.value = themeId;
        }
    }

    function scrollToSection(selector) {
        const section = document.querySelector(selector);
        if (!section) return;

        const navOffset = 90;
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
        formAlert.classList.add('d-none');
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
                throw new Error(result.message || 'Pendaftaran gagal dikirim.');
            }

            formContainer.style.display = 'none';
            successState.style.display = 'block';

            showToast(result.message || 'Pendaftaran berhasil dikirim.', 'success');
            scrollToSection('#registration-form');
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('d-none');
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