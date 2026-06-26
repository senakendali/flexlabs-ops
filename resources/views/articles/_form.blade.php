@php
    $submitMode = $submitMode ?? 'create';
    $isEdit = $submitMode === 'edit';

    $formAction = $isEdit
        ? route('articles.update', $article)
        : route('articles.store');

    $backUrl = route('articles.index');

    $articleTypes = $options['articleTypes'] ?? [];
    $tones = $options['tones'] ?? [];
    $lengths = $options['lengths'] ?? [];
    $categories = $options['categories'] ?? [];

    $oldOrArticle = function (string $key, $default = null) use ($article) {
        return old($key, $article->{$key} ?? $default);
    };

    $sourceTypeValue = $oldOrArticle('source_type', 'manual');
    $sourceIdValue = $oldOrArticle('source_id');
    $articleTypeValue = $oldOrArticle('article_type');
    $categoryValue = $oldOrArticle('category');
    $toneValue = $oldOrArticle('tone', $options['defaultTone'] ?? 'professional_educative');
    $languageValue = $oldOrArticle('language', $options['defaultLanguage'] ?? 'id');
    $lengthValue = $oldOrArticle('length_preset', $options['defaultLength'] ?? 'medium');

    $primarySubmitLabel = $isEdit ? 'Update & Regenerate Article' : 'Create & Generate Article';
    $primarySubmitIcon = 'bi-stars';
@endphp

<div class="container-fluid px-4 py-4 article-form-page workshops-form-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing / Article Generator</div>
                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit & Regenerate Article' : 'Create Article' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Isi atau update brief artikel. Setelah submit, FlexOps akan generate artikel, SEO, creative direction, dan caption untuk dicopy ke website FlexLabs.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $backUrl }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if($isEdit && $article->exists)
                    <a href="{{ route('articles.show', $article) }}" class="btn btn-light btn-modern border">
                        <i class="bi bi-eye me-1"></i> View Result
                    </a>
                @endif

                <button type="button" id="submitArticleBtn" class="btn btn-light btn-modern">
                    <i class="bi {{ $primarySubmitIcon }} me-1"></i> {{ $primarySubmitLabel }}
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container article-toast-container"></div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-triangle me-2"></i>Form belum bisa disimpan.
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(! empty($sourceWorkshop))
        <div class="alert alert-info rounded-4 border-0 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-easel2-fill mt-1"></i>
                <div>
                    <div class="fw-bold">Draft dari data workshop</div>
                    <div class="small">
                        Beberapa field sudah diisi otomatis dari workshop yang dipilih. Silakan cek dan sesuaikan sebelum membuat artikel.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-stars mt-1"></i>
            <div>
                <div class="fw-bold">
                    {{ $isEdit ? 'Update & Regenerate Article' : 'Create & Generate Article' }}
                </div>
                <div class="small">
                    {{ $isEdit
                        ? 'Setelah tombol utama diklik, sistem akan menyimpan perubahan brief lalu generate ulang outline, artikel, SEO, creative suggestion, dan caption.'
                        : 'Setelah tombol utama diklik, sistem akan menyimpan brief lalu otomatis membuat outline, artikel, SEO, creative suggestion, dan caption.'
                    }}
                    Setelah selesai, halaman akan masuk ke result.
                </div>
            </div>
        </div>
    </div>

    <form id="articleForm" action="{{ $formAction }}" method="POST" data-submit-mode="{{ $submitMode }}">
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="source_type" value="{{ $sourceTypeValue }}">
        <input type="hidden" name="source_id" value="{{ $sourceIdValue }}">
        <input type="hidden" name="generate_ai" value="1">

        <div class="row g-4">
            <div class="col-12">
                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Article Brief</h5>
                            <p class="content-card-subtitle mb-0">
                                Isi informasi utama artikel sebagai dasar bantuan penulisan AI.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="title"
                                    id="articleTitleInput"
                                    class="form-control"
                                    value="{{ $oldOrArticle('title') }}"
                                    placeholder="Contoh: Kenapa Belajar Laravel Lebih Mudah Lewat Project Nyata?"
                                    required
                                >
                                <div class="invalid-feedback error-text" data-error-for="title"></div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Slug</label>
                                <input
                                    type="text"
                                    name="slug"
                                    id="articleSlugInput"
                                    class="form-control"
                                    value="{{ $oldOrArticle('slug') }}"
                                    placeholder="otomatis dari judul"
                                >
                                <div class="form-text">Kosongkan kalau ingin slug dibuat otomatis dari title.</div>
                                <div class="invalid-feedback error-text" data-error-for="slug"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Primary Keyword</label>
                                <input
                                    type="text"
                                    name="primary_keyword"
                                    class="form-control"
                                    value="{{ $oldOrArticle('primary_keyword') }}"
                                    placeholder="Contoh: belajar Laravel untuk pemula"
                                >
                                <div class="form-text">Keyword utama membantu artikel lebih fokus.</div>
                                <div class="invalid-feedback error-text" data-error-for="primary_keyword"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Target Audience</label>
                                <input
                                    type="text"
                                    name="target_audience"
                                    class="form-control"
                                    value="{{ $oldOrArticle('target_audience') }}"
                                    placeholder="Contoh: Pemula yang ingin belajar web development"
                                >
                                <div class="form-text">Bantu artikel menyesuaikan bahasa dengan pembaca.</div>
                                <div class="invalid-feedback error-text" data-error-for="target_audience"></div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Tone</label>
                                <select name="tone" class="form-select">
                                    @foreach($tones as $value => $label)
                                        <option value="{{ $value }}" @selected($toneValue === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="tone"></div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Article Type</label>
                                <select name="article_type" class="form-select">
                                    <option value="">Select type</option>
                                    @foreach($articleTypes as $value => $label)
                                        <option value="{{ $value }}" @selected($articleTypeValue === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="article_type"></div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">Select category</option>
                                    @foreach($categories as $value => $label)
                                        <option value="{{ $value }}" @selected($categoryValue === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="category"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Length</label>
                                <select name="length_preset" class="form-select">
                                    @foreach($lengths as $value => $label)
                                        <option value="{{ $value }}" @selected($lengthValue === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="length_preset"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Language</label>
                                <input
                                    type="text"
                                    name="language"
                                    class="form-control"
                                    value="{{ $languageValue }}"
                                    placeholder="id"
                                >
                                <div class="form-text">Gunakan <strong>id</strong> untuk Bahasa Indonesia.</div>
                                <div class="invalid-feedback error-text" data-error-for="language"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Main Angle</label>
                                <textarea
                                    name="main_angle"
                                    rows="3"
                                    class="form-control"
                                    placeholder="Contoh: Problem-solution tentang pemula yang kesulitan belajar coding tanpa project nyata."
                                >{{ $oldOrArticle('main_angle') }}</textarea>
                                <div class="form-text">Sudut pandang utama membantu artikel tidak melebar.</div>
                                <div class="invalid-feedback error-text" data-error-for="main_angle"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Must Include</label>
                                <textarea
                                    name="must_include"
                                    rows="5"
                                    class="form-control"
                                    placeholder="- Jelaskan manfaat project-based learning&#10;- Arahkan pembaca ke workshop FlexLabs"
                                >{{ $oldOrArticle('must_include') }}</textarea>
                                <div class="form-text">Tuliskan poin penting yang wajib muncul di artikel.</div>
                                <div class="invalid-feedback error-text" data-error-for="must_include"></div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label">Avoid Points</label>
                                <textarea
                                    name="avoid_points"
                                    rows="5"
                                    class="form-control"
                                    placeholder="- Jangan terlalu hard selling&#10;- Jangan menjanjikan hasil instan"
                                >{{ $oldOrArticle('avoid_points') }}</textarea>
                                <div class="form-text">Tuliskan hal yang perlu dihindari.</div>
                                <div class="invalid-feedback error-text" data-error-for="avoid_points"></div>
                            </div>

                            <input type="hidden" name="brief_notes" value="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="content-card">
                    <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="footer-note">
                            <div class="footer-note-title">
                                {{ $isEdit ? 'Ready to Regenerate?' : 'Ready to Generate?' }}
                            </div>
                            <div class="footer-note-subtitle">
                                {{ $isEdit
                                    ? 'Klik Update & Regenerate Article untuk menyimpan brief dan membuat ulang seluruh hasil AI.'
                                    : 'Klik Create & Generate Article untuk membuat seluruh bahan artikel secara otomatis.'
                                }}
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ $backUrl }}" class="btn btn-light btn-modern">Cancel</a>

                            <button type="button" id="submitArticleBtnBottom" class="btn btn-primary btn-modern">
                                <i class="bi {{ $primarySubmitIcon }} me-1"></i> {{ $primarySubmitLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .article-toast-container {
        right: 24px;
        bottom: 24px;
        top: auto;
        z-index: 99999;
        min-width: 280px;
        max-width: 420px;
    }

    .article-toast-container .toast {
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
        overflow: hidden;
    }

    @media (max-width: 767.98px) {
        .article-toast-container {
            left: 16px;
            right: 16px;
            bottom: 20px;
            top: auto;
            min-width: 0;
            max-width: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('articleForm');

    const submitArticleBtn = document.getElementById('submitArticleBtn');
    const submitArticleBtnBottom = document.getElementById('submitArticleBtnBottom');

    const titleInput = document.getElementById('articleTitleInput');
    const slugInput = document.getElementById('articleSlugInput');

    const toastContainer = document.getElementById('toastContainer');

    const csrfToken = @json(csrf_token());
    const isEdit = @json($isEdit);

    const creatingMessage = @json('Artikel sedang dibuat dan AI sedang generate konten. Jangan tutup halaman dulu.');
    const updateMessage = @json('Artikel sedang diperbarui dan AI sedang generate ulang konten. Jangan tutup halaman dulu.');

    let slugTouched = Boolean(slugInput?.value);
    let redirectTimeout = null;

    function showToast(message, type = 'success') {
        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: type === 'info' ? 3200 : 1800 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function scheduleRedirect(url) {
        if (!url) return;
        if (redirectTimeout) clearTimeout(redirectTimeout);

        redirectTimeout = setTimeout(function () {
            window.location.href = url;
        }, 700);
    }

    function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        return response.text().then(function (text) {
            return {
                success: false,
                message: text || 'Unexpected server response.'
            };
        });
    }

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');
    }

    if (slugInput) {
        slugInput.addEventListener('input', function () {
            slugTouched = true;
            slugInput.value = slugify(slugInput.value);
        });
    }

    if (titleInput) {
        titleInput.addEventListener('input', function () {
            if (!slugTouched && slugInput) {
                slugInput.value = slugify(titleInput.value);
            }
        });
    }

    function cssEscapeName(name) {
        return String(name).replaceAll('[', '\\[').replaceAll(']', '\\]');
    }

    function clearValidationErrors() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        form.querySelectorAll('.error-text').forEach(function (el) {
            el.textContent = '';
        });
    }

    function applyValidationErrors(errors) {
        let firstFieldElement = null;

        Object.entries(errors || {}).forEach(function ([key, messages]) {
            const fieldBase = key.split('.')[0];
            const message = Array.isArray(messages) ? messages[0] : messages;
            const field = form.querySelector(`[name="${cssEscapeName(fieldBase)}"]`);

            if (field) {
                field.classList.add('is-invalid');

                if (!firstFieldElement) {
                    firstFieldElement = field;
                }
            }

            const errorHolder = form.querySelector(`[data-error-for="${fieldBase}"]`);

            if (errorHolder) {
                errorHolder.textContent = message;
            }
        });

        if (firstFieldElement) {
            firstFieldElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            window.setTimeout(function () {
                firstFieldElement.focus({ preventScroll: true });
            }, 250);
        }
    }

    function buildFormData() {
        const formData = new FormData(form);

        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        formData.set('generate_ai', '1');

        return formData;
    }

    async function submitArticle() {
        clearValidationErrors();

        const formData = buildFormData();
        const submitButtons = [submitArticleBtn, submitArticleBtnBottom];

        submitButtons.forEach(function (btn) {
            if (btn) btn.disabled = true;
        });

        submitButtons.forEach(function (btn) {
            if (!btn) return;

            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = isEdit
                ? '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Regenerating...'
                : '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Generating...';
        });

        showToast(isEdit ? updateMessage : creatingMessage, 'info');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok || !data.success) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                }

                showToast(data.message || 'Terjadi kesalahan saat menyimpan artikel.', 'danger');
                return null;
            }

            const fallbackMessage = isEdit
                ? 'Artikel berhasil diperbarui dan hasil AI sudah digenerate ulang.'
                : 'Artikel berhasil dibuat dan siap dicopy ke website FlexLabs.';

            showToast(data.message || fallbackMessage, data.warning ? 'warning' : 'success');

            if (data.redirect_url) {
                scheduleRedirect(data.redirect_url);
            }

            return data;
        } catch (error) {
            showToast(error.message || 'Terjadi kesalahan saat mengirim data.', 'danger');
            return null;
        } finally {
            submitButtons.forEach(function (btn) {
                if (btn) btn.disabled = false;
            });

            submitButtons.forEach(function (btn) {
                if (btn && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    }

    submitArticleBtn?.addEventListener('click', function () {
        submitArticle();
    });

    submitArticleBtnBottom?.addEventListener('click', function () {
        submitArticle();
    });

    form?.addEventListener('submit', function (event) {
        event.preventDefault();
        submitArticle();
    });
});
</script>
@endpush