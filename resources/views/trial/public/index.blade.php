@extends('layouts.public')

@section('title', 'Trial Class | FlexLabs')

@section('content')
<section class="hero-section">
    <div class="container">
        <div
            id="toastContainer"
            class="toast-container position-fixed top-0 end-0 p-3"
        ></div>

        <div class="row align-items-center g-4">
            <div class="col-lg-7">
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

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <span class="feature-chip">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        Cocok untuk Pemula
                    </span>
                    <span class="feature-chip">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        Belajar Lebih Praktis
                    </span>
                    <span class="feature-chip">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        Terarah ke Industri
                    </span>
                </div>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="#registration-form" class="btn btn-brand">
                        <i class="bi bi-pencil-square me-2"></i>
                        Daftar Trial Class
                    </a>
                   
                </div>

                
            </div>

            <div class="col-lg-5">
                <div class="hero-card">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="hero-stat">
                                <div class="hero-stat-label">Metode Belajar</div>
                                <div class="hero-stat-value">Praktikal</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="hero-stat">
                                <div class="hero-stat-label">Cocok Untuk</div>
                                <div class="hero-stat-value">Pemula</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="hero-stat">
                                <div class="hero-stat-label">Pendekatan</div>
                                <div class="hero-stat-value">Terstruktur</div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="hero-stat">
                                <div class="hero-stat-label">Fokus</div>
                                <div class="hero-stat-value">Siap Industri</div>
                            </div>
                        </div>
                    </div>

                    <div class="about-card mt-3">
                        <div class="fw-bold mb-2">
                            Kenapa wajib coba trial class?
                        </div>
                        <div class="text-muted">
                            Karena kamu bisa lihat langsung gaya belajar di FlexLabs,
                            memahami arah programnya, dan merasakan bagaimana proses belajar
                            dibangun supaya lebih relevan dengan kebutuhan industri nyata.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content-section" id="registration-form">
    <div class="container">
        <div class="section-card">
            <div class="section-card-header">
                <h2 class="section-title">Daftar Trial Class Sekarang</h2>
                <p class="section-subtitle">
                    Isi data dirimu dan pilih jadwal trial yang paling cocok buat kamu.
                </p>
            </div>

            <div class="section-card-body">
                <div class="row g-4">
                    <div class="col-lg-8">
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

                   <div class="col-lg-4" id="about-flexlabs">
                        <div class="about-card h-100">
                            <div class="fw-bold fs-5 mb-3">
                                Tentang FlexLabs
                            </div>

                            <p class="text-muted mb-3">
                                Flexlabs adalah akademi digital pertama di Indonesia dengan kurikulum berstandar global
                                yang dirancang khusus agar pembelajar dapat bersaing di industri internasional
                                atau direkrut ke PT. System Ever Indonesia.
                            </p>

                            <p class="text-muted mb-4">
                                PT. System Ever Indonesia merupakan anak perusahaan dari perusahaan ERP terkemuka di Asia,
                                yaitu YoungLimWon Soft Lab Co Ltd.
                            </p>
                            <div class="fw-semibold mb-3">
                                Kenapa FlexLabs?
                            </div>
                            <div class="d-flex flex-column gap-3 mb-4">

                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check2-square text-primary mt-1"></i>
                                    <div>1:1 Mentoring</div>
                                </div>

                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check2-square text-primary mt-1"></i>
                                    <div>Opportunity to get hired by PT. System Ever Indonesia</div>
                                </div>
                            </div>
                            <hr class="my-4">

                            <div class="small text-muted">
                                Setelah kamu mengisi form, tim kami akan meninjau pendaftaranmu
                                dan menghubungi kamu untuk informasi berikutnya.
        </div>
    </div>
</div>
                </div>
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

    fields.trial_schedule_id.addEventListener('change', syncThemeFromSchedule);

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

            document.getElementById('registration-form').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
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
    });
</script>
@endpush