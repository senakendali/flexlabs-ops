@php
    $submitMode = $submitMode ?? 'create';
    $isEdit = $submitMode === 'edit';

    $formAction = $isEdit
        ? route('internal-memos.update', $memo)
        : route('internal-memos.store');

    $users = $users ?? collect();

    $approvalRows = $memo->relationLoaded('approvals') ? $memo->approvals : collect();

    $defaultApprovalSigners = $approvalSignersDefaults ?? $acknowledgementDefaults ?? [
        [
            'role_label' => 'Acknowledged by',
            'approver_id' => null,
            'name' => '',
            'position' => '',
        ],
        [
            'role_label' => 'Acknowledged by',
            'approver_id' => null,
            'name' => '',
            'position' => '',
        ],
        [
            'role_label' => 'Approved by',
            'approver_id' => null,
            'name' => '',
            'position' => '',
        ],
    ];

    $storedApprovalSigners = $approvalRows
        ->values()
        ->map(fn ($approval) => [
            'role_label' => $approval->role_label ?: 'Acknowledged by',
            'approver_id' => $approval->approver_id,
            'name' => $approval->approver_name,
            'position' => $approval->approver_position,
        ])
        ->toArray();

    $approvalSignerRows = old(
        'acknowledgements',
        count($storedApprovalSigners) >= 3 ? $storedApprovalSigners : $defaultApprovalSigners
    );

    $approvalSignerRows = array_slice(array_values($approvalSignerRows), 0, 3);

    for ($i = 0; $i < 3; $i++) {
        $approvalSignerRows[$i] = array_merge(
            $defaultApprovalSigners[$i] ?? [
                'role_label' => $i === 2 ? 'Approved by' : 'Acknowledged by',
                'approver_id' => null,
                'name' => '',
                'position' => '',
            ],
            $approvalSignerRows[$i] ?? []
        );
    }

    $memoItems = old(
        'items',
        $memo->relationLoaded('items') && $memo->items->isNotEmpty()
            ? $memo->items->map(fn ($item) => [
                'details' => $item->details,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'remarks' => $item->remarks,
            ])->values()->toArray()
            : [
                [
                    'details' => '',
                    'price' => 0,
                    'quantity' => 1,
                    'remarks' => '',
                ],
            ]
    );

    $paymentSources = $paymentSources ?? [
        'bank' => 'Bank',
        'cash' => 'Cash',
    ];

    $taxTreatments = $taxTreatments ?? [
        'not_include' => 'Tax Not Include',
        'include' => 'Tax Include',
    ];

    $taxEntityTypes = $taxEntityTypes ?? [
        'pkp' => 'PKP',
        'non_pkp' => 'Non PKP',
    ];

    $memoDateValue = old(
        'memo_date',
        optional($memo->memo_date)->format('Y-m-d') ?: $memo->memo_date
    );

    $dueDateValue = old(
        'due_date',
        optional($memo->due_date)->format('Y-m-d') ?: $memo->due_date
    );

    $paymentSourceValue = old('payment_source', $memo->payment_source ?? 'bank');
    $taxTreatmentValue = old('tax_treatment', $memo->tax_treatment ?? 'not_include');
    $taxEntityTypeValue = old('tax_entity_type', $memo->tax_entity_type ?? 'pkp');

    $purposeValue = old('purpose', $memo->purpose);
    $safePurposeValue = strip_tags(
        (string) $purposeValue,
        '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><a><span>'
    );

    $resolveUserPosition = function ($user) {
        foreach (['position', 'job_title', 'title', 'role_label', 'role'] as $field) {
            if (! empty($user->{$field})) {
                return \Illuminate\Support\Str::headline((string) $user->{$field});
            }
        }

        return '';
    };
@endphp

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">

    <style>
        .memo-quill-wrap .ql-toolbar {
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            border-color: #dee2e6;
            background: #fff;
        }

        .memo-quill-wrap .ql-container {
            min-height: 180px;
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
            border-color: #dee2e6;
            font-size: 0.95rem;
            background: #fff;
        }

        .memo-quill-wrap .ql-editor {
            min-height: 180px;
        }

        .memo-quill-wrap.is-invalid .ql-toolbar,
        .memo-quill-wrap.is-invalid .ql-container {
            border-color: #dc3545;
        }

        .amount-summary-value {
            min-height: 42px;
            display: flex;
            align-items: center;
        }

        .approval-signer-card {
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
        }

        .approval-signer-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            background: rgba(1, 150, 65, .1);
            color: #019641;
        }

        .memo-item-row {
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 1rem;
        }

        .memo-item-number {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            background: rgba(1, 150, 65, .1);
            color: #019641;
            flex: 0 0 auto;
        }

        .memo-item-row textarea {
            resize: vertical;
        }

        .memo-item-row .form-label {
            margin-bottom: .4rem;
        }

        .item-estimated-label {
            min-height: 38px;
            display: flex;
            align-items: center;
        }

        .budget-action-col {
            padding-top: 1.95rem;
        }

        @media (max-width: 1199.98px) {
            .budget-action-col {
                padding-top: 0;
            }
        }
    </style>
@endpush

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit Internal Memo' : 'Create Internal Memo' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Buat memo internal dengan budget item, payment detail, attachment link, dan approval signer.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('internal-memos.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if ($isEdit && $memo->exists)
                    <a href="{{ route('internal-memos.show', $memo) }}" class="btn btn-outline-light btn-modern">
                        <i class="bi bi-eye me-2"></i>View Memo
                    </a>
                @endif
            </div>
        </div>
    </div>

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

    <form method="POST" action="{{ $formAction }}" id="internalMemoForm">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-12">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Memo Information</h5>
                            <p class="content-card-subtitle mb-0">
                                Isi informasi dasar memo seperti subject, penerima, pengirim, due date, payment source, dan purpose.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Memo Date <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    name="memo_date"
                                    class="form-control @error('memo_date') is-invalid @enderror"
                                    value="{{ $memoDateValue }}"
                                    required
                                >
                                @error('memo_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Due Date</label>
                                <input
                                    type="date"
                                    name="due_date"
                                    class="form-control @error('due_date') is-invalid @enderror"
                                    value="{{ $dueDateValue }}"
                                >
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Source <span class="text-danger">*</span></label>
                                <select
                                    name="payment_source"
                                    class="form-select @error('payment_source') is-invalid @enderror"
                                    required
                                >
                                    @foreach ($paymentSources as $value => $label)
                                        <option value="{{ $value }}" @selected($paymentSourceValue === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_source')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="subject"
                                    class="form-control @error('subject') is-invalid @enderror"
                                    value="{{ old('subject', $memo->subject) }}"
                                    placeholder="Contoh: Learning Platform Experience Research"
                                    required
                                >
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Attachment Label</label>
                                <input
                                    type="text"
                                    name="attachment_label"
                                    class="form-control @error('attachment_label') is-invalid @enderror"
                                    value="{{ old('attachment_label', $memo->attachment_label) }}"
                                    placeholder="Contoh: Course Price"
                                >
                                @error('attachment_label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Attachment Google Drive Link</label>
                                <input
                                    type="url"
                                    name="attachment_url"
                                    class="form-control @error('attachment_url') is-invalid @enderror"
                                    value="{{ old('attachment_url', $memo->attachment_url ?? '') }}"
                                    placeholder="https://drive.google.com/..."
                                >
                                <div class="form-text">
                                    Masukkan link Google Drive untuk lampiran memo jika ada.
                                </div>
                                @error('attachment_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">To <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="to_name"
                                    class="form-control @error('to_name') is-invalid @enderror"
                                    value="{{ old('to_name', $memo->to_name) }}"
                                    placeholder="Nama penerima memo"
                                    required
                                >
                                @error('to_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">To Position</label>
                                <input
                                    type="text"
                                    name="to_position"
                                    class="form-control @error('to_position') is-invalid @enderror"
                                    value="{{ old('to_position', $memo->to_position) }}"
                                    placeholder="Jabatan penerima"
                                >
                                @error('to_position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">From <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="from_name"
                                    class="form-control @error('from_name') is-invalid @enderror"
                                    value="{{ old('from_name', $memo->from_name) }}"
                                    placeholder="Nama pengirim memo"
                                    required
                                >
                                @error('from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">From Position</label>
                                <input
                                    type="text"
                                    name="from_position"
                                    class="form-control @error('from_position') is-invalid @enderror"
                                    value="{{ old('from_position', $memo->from_position) }}"
                                    placeholder="Jabatan pengirim"
                                >
                                @error('from_position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Purpose <span class="text-danger">*</span>
                                </label>

                                <div class="memo-quill-wrap @error('purpose') is-invalid @enderror">
                                    <div id="purposeEditor">{!! $safePurposeValue !!}</div>
                                </div>

                                <textarea
                                    name="purpose"
                                    id="purposeInput"
                                    class="d-none @error('purpose') is-invalid @enderror"
                                >{{ $purposeValue }}</textarea>

                                <div class="form-text">
                                    Purpose wajib minimal 2 poin. Gunakan bullet list atau buat minimal 2 paragraf.
                                </div>

                                @error('purpose')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea
                                    name="notes"
                                    id="notesInput"
                                    rows="4"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="Catatan tambahan..."
                                >{{ old('notes', $memo->notes) }}</textarea>
                                <div class="form-text">
                                    Jika Tax Include dipilih, catatan pajak akan otomatis ditambahkan.
                                </div>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Approval Signers</h5>
                            <p class="content-card-subtitle mb-0">
                                Pilih tiga user yang akan menjadi signer approval dan menerima notifikasi email.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            @foreach ($approvalSignerRows as $index => $signer)
                                @php
                                    $roleLabel = $signer['role_label'] ?? ($index === 2 ? 'Approved by' : 'Acknowledged by');
                                    $selectedApproverId = (string) ($signer['approver_id'] ?? '');
                                    $selectedName = $signer['name'] ?? '';
                                    $selectedPosition = $signer['position'] ?? '';
                                @endphp

                                <div class="col-xl-4 col-lg-6">
                                    <div class="approval-signer-card p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <div class="approval-signer-badge">
                                                    <i class="bi bi-person-check"></i>
                                                    Signer {{ $index + 1 }}
                                                </div>
                                            </div>
                                        </div>

                                        <input
                                            type="hidden"
                                            name="acknowledgements[{{ $index }}][role_label]"
                                            value="{{ $roleLabel }}"
                                        >

                                        <input
                                            type="hidden"
                                            name="acknowledgements[{{ $index }}][name]"
                                            class="signer-name-hidden"
                                            value="{{ $selectedName }}"
                                        >

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Role Label <span class="text-danger">*</span></label>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="{{ $roleLabel }}"
                                                    readonly
                                                >
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">User <span class="text-danger">*</span></label>
                                                <select
                                                    name="acknowledgements[{{ $index }}][approver_id]"
                                                    class="form-select signer-user-select @error("acknowledgements.$index.approver_id") is-invalid @enderror"
                                                    data-index="{{ $index }}"
                                                    required
                                                >
                                                    <option value="">Select user</option>
                                                    @foreach ($users as $user)
                                                        @php
                                                            $userPosition = $resolveUserPosition($user);
                                                        @endphp
                                                        <option
                                                            value="{{ $user->id }}"
                                                            data-name="{{ $user->name }}"
                                                            data-position="{{ $userPosition }}"
                                                            @selected($selectedApproverId === (string) $user->id)
                                                        >
                                                            {{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @error("acknowledgements.$index.approver_id")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror

                                                @error("acknowledgements.$index.name")
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Position <span class="text-danger">*</span></label>
                                                <input
                                                    type="text"
                                                    name="acknowledgements[{{ $index }}][position]"
                                                    class="form-control signer-position-input @error("acknowledgements.$index.position") is-invalid @enderror"
                                                    value="{{ $selectedPosition }}"
                                                    placeholder="Jabatan signer"
                                                    required
                                                >
                                                @error("acknowledgements.$index.position")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('acknowledgements')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="content-card mb-4">
                    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h5 class="content-card-title mb-1">Budget Items</h5>
                            <p class="content-card-subtitle mb-0">
                                Budget item dibuat dalam kolom agar lebih ringkas dan mudah dicek.
                            </p>
                        </div>

                        <button type="button" class="btn btn-sm btn-primary btn-modern add-memo-item-btn">
                            <i class="bi bi-plus-circle me-1"></i>Add Item
                        </button>
                    </div>

                    <div class="content-card-body">
                        <div id="memoItemsContainer" class="d-grid gap-3">
                            @foreach ($memoItems as $index => $item)
                                <div class="memo-item-row p-3">
                                    <div class="row g-3 align-items-start">
                                        <div class="col-xl-4 col-lg-12">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                
                                                <div>
                                                    <div class="fw-semibold memo-item-title">Budget Item #{{ $index + 1 }}</div>
                                                    
                                                </div>
                                            </div>

                                            <textarea
                                                name="items[{{ $index }}][details]"
                                                rows="2"
                                                class="form-control item-details @error("items.$index.details") is-invalid @enderror"
                                                placeholder="Detail item..."
                                                required
                                            >{{ $item['details'] ?? '' }}</textarea>

                                            @error("items.$index.details")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-xl-2 col-md-4">
                                            <label class="form-label">Price <span class="text-danger">*</span></label>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][price]"
                                                class="form-control item-price @error("items.$index.price") is-invalid @enderror"
                                                value="{{ $item['price'] ?? 0 }}"
                                                min="0"
                                                step="0.01"
                                                required
                                            >
                                            @error("items.$index.price")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-xl-1 col-md-4">
                                            <label class="form-label">Qty <span class="text-danger">*</span></label>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][quantity]"
                                                class="form-control item-quantity @error("items.$index.quantity") is-invalid @enderror"
                                                value="{{ $item['quantity'] ?? 1 }}"
                                                min="1"
                                                step="1"
                                                required
                                            >
                                            @error("items.$index.quantity")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-xl-2 col-md-4">
                                            <label class="form-label">Estimated</label>
                                            <div class="form-control bg-white fw-semibold item-estimated-label">Rp 0</div>
                                        </div>

                                        <div class="col-xl-2 col-lg-10">
                                            <label class="form-label">Remarks</label>
                                            <textarea
                                                name="items[{{ $index }}][remarks]"
                                                rows="2"
                                                class="form-control item-remarks @error("items.$index.remarks") is-invalid @enderror"
                                                placeholder="Remarks..."
                                            >{{ $item['remarks'] ?? '' }}</textarea>
                                            @error("items.$index.remarks")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-xl-1 col-lg-2 budget-action-col">
                                            <button type="button" class="btn btn-outline-danger w-100 remove-item-btn">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('items')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary btn-modern add-memo-item-btn">
                                <i class="bi bi-plus-circle me-2"></i>Add Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Amount Summary</h5>
                            <p class="content-card-subtitle mb-0">
                                Summary dihitung otomatis dari budget items, tax treatment, dan status PKP / Non PKP.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3 align-items-stretch">
                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <label class="form-label">Tax Treatment <span class="text-danger">*</span></label>
                                    <select
                                        name="tax_treatment"
                                        id="taxTreatmentInput"
                                        class="form-select @error('tax_treatment') is-invalid @enderror"
                                        required
                                    >
                                        @foreach ($taxTreatments as $value => $label)
                                            <option value="{{ $value }}" @selected($taxTreatmentValue === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tax_treatment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <label class="form-label">Tax Entity <span class="text-danger">*</span></label>
                                    <select
                                        name="tax_entity_type"
                                        id="taxEntityTypeInput"
                                        class="form-select @error('tax_entity_type') is-invalid @enderror"
                                        required
                                    >
                                        @foreach ($taxEntityTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($taxEntityTypeValue === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tax_entity_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input
                                        type="number"
                                        name="tax_rate"
                                        id="taxRateInput"
                                        class="form-control @error('tax_rate') is-invalid @enderror"
                                        value="{{ old('tax_rate', $memo->tax_rate ?? 11) }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                    >
                                    @error('tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small mb-1">Subtotal</div>
                                    <div class="fs-5 fw-bold amount-summary-value" id="subtotalLabel">Rp 0</div>
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small mb-1">Tax</div>
                                    <div class="fs-5 fw-bold amount-summary-value" id="taxAmountLabel">Rp 0</div>
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small mb-1">Grand Total</div>
                                    <div class="fs-4 fw-bold amount-summary-value" id="grandTotalLabel">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success border-0 mt-3 mb-0 py-2 px-3 small" id="taxHelperText">
                            Tax summary akan mengikuti pilihan Tax Treatment dan Tax Entity.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap pb-3">
                    <a href="{{ route('internal-memos.index') }}" class="btn btn-secondary btn-modern px-4">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary btn-modern px-4">
                        <i class="bi bi-save me-2"></i>{{ $isEdit ? 'Update Memo' : 'Create Memo' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('internalMemoForm');
        const itemsContainer = document.getElementById('memoItemsContainer');
        const addButtons = document.querySelectorAll('.add-memo-item-btn');

        const taxInput = document.getElementById('taxRateInput');
        const taxTreatmentInput = document.getElementById('taxTreatmentInput');
        const taxEntityTypeInput = document.getElementById('taxEntityTypeInput');
        const taxHelperText = document.getElementById('taxHelperText');

        const notesInput = document.getElementById('notesInput');
        const purposeInput = document.getElementById('purposeInput');
        const purposeEditorElement = document.getElementById('purposeEditor');

        const taxIncludedLine = 'Tax is included in the submitted amount.';

        let purposeQuill = null;
        let lastPkpTaxRate = parseFloat(taxInput?.value || 11) || 11;

        const rupiahFormatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        });

        function formatRupiah(value) {
            return rupiahFormatter.format(Number(value || 0));
        }

        function initQuill() {
            if (! purposeEditorElement || ! purposeInput) {
                return;
            }

            if (typeof Quill === 'undefined') {
                purposeInput.classList.remove('d-none');
                purposeEditorElement.closest('.memo-quill-wrap')?.classList.add('d-none');
                return;
            }

            purposeQuill = new Quill('#purposeEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote'],
                        ['clean']
                    ]
                },
                placeholder: 'Tuliskan minimal 2 poin purpose memo...'
            });
        }

        function syncPurposeInput() {
            if (! purposeInput) {
                return;
            }

            if (purposeQuill) {
                purposeInput.value = purposeQuill.root.innerHTML.trim();
            }
        }

        function removeAutomaticTaxNote(value) {
            return String(value || '')
                .split(/\r?\n/)
                .filter(function (line) {
                    return line.trim() !== taxIncludedLine;
                })
                .join('\n')
                .trim();
        }

        function syncTaxNote() {
            if (! notesInput || ! taxTreatmentInput) {
                return;
            }

            const treatment = taxTreatmentInput.value;
            const cleanedNotes = removeAutomaticTaxNote(notesInput.value);

            if (treatment === 'include') {
                notesInput.value = (cleanedNotes ? cleanedNotes + '\n' : '') + taxIncludedLine;
                return;
            }

            notesInput.value = cleanedNotes;
        }

        function syncSignerFields() {
            document.querySelectorAll('.approval-signer-card').forEach(function (card) {
                const select = card.querySelector('.signer-user-select');
                const hiddenName = card.querySelector('.signer-name-hidden');
                const positionInput = card.querySelector('.signer-position-input');

                if (! select || ! hiddenName) {
                    return;
                }

                const selectedOption = select.options[select.selectedIndex];

                if (! selectedOption || ! selectedOption.value) {
                    hiddenName.value = '';
                    return;
                }

                hiddenName.value = selectedOption.dataset.name || selectedOption.textContent.trim();

                if (positionInput && ! positionInput.value.trim() && selectedOption.dataset.position) {
                    positionInput.value = selectedOption.dataset.position;
                }
            });
        }

        function bindSignerSelects() {
            document.querySelectorAll('.signer-user-select').forEach(function (select) {
                select.addEventListener('change', function () {
                    const card = select.closest('.approval-signer-card');
                    const hiddenName = card?.querySelector('.signer-name-hidden');
                    const positionInput = card?.querySelector('.signer-position-input');
                    const selectedOption = select.options[select.selectedIndex];

                    if (! hiddenName) {
                        return;
                    }

                    if (! selectedOption || ! selectedOption.value) {
                        hiddenName.value = '';

                        if (positionInput) {
                            positionInput.value = '';
                        }

                        return;
                    }

                    hiddenName.value = selectedOption.dataset.name || selectedOption.textContent.trim();

                    if (positionInput) {
                        positionInput.value = selectedOption.dataset.position || positionInput.value;
                    }
                });
            });

            syncSignerFields();
        }

        function refreshItemTitles() {
            itemsContainer?.querySelectorAll('.memo-item-row').forEach(function (row, index) {
                const title = row.querySelector('.memo-item-title');
                const number = row.querySelector('.memo-item-number');

                if (title) {
                    title.textContent = 'Budget Item #' + (index + 1);
                }

                if (number) {
                    number.textContent = index + 1;
                }
            });
        }

        function reindexRows() {
            itemsContainer?.querySelectorAll('.memo-item-row').forEach(function (row, index) {
                row.querySelectorAll('textarea, input').forEach(function (input) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                });
            });

            refreshItemTitles();
        }

        function applyTaxEntityState() {
            if (! taxInput || ! taxEntityTypeInput) {
                return;
            }

            const entityType = taxEntityTypeInput.value;

            if (entityType === 'non_pkp') {
                taxInput.value = 0;
                taxInput.setAttribute('readonly', 'readonly');
                taxInput.classList.add('bg-light');
                return;
            }

            taxInput.removeAttribute('readonly');
            taxInput.classList.remove('bg-light');

            if (parseFloat(taxInput.value || 0) <= 0) {
                taxInput.value = lastPkpTaxRate || 11;
            }
        }

        function calculateSummary() {
            let subtotal = 0;

            itemsContainer?.querySelectorAll('.memo-item-row').forEach(function (row) {
                const price = parseFloat(row.querySelector('.item-price')?.value || 0);
                const quantity = parseInt(row.querySelector('.item-quantity')?.value || 1, 10);
                const estimated = price * quantity;

                subtotal += estimated;

                const estimatedLabel = row.querySelector('.item-estimated-label');
                if (estimatedLabel) {
                    estimatedLabel.textContent = formatRupiah(estimated);
                }
            });

            const taxRate = parseFloat(taxInput?.value || 0);
            const treatment = taxTreatmentInput?.value || 'not_include';
            const entityType = taxEntityTypeInput?.value || 'pkp';

            let taxAmount = 0;
            let grandTotal = subtotal;

            if (entityType === 'pkp' && taxRate > 0) {
                if (treatment === 'include') {
                    taxAmount = subtotal - (subtotal / (1 + (taxRate / 100)));
                    grandTotal = subtotal;
                } else {
                    taxAmount = subtotal * (taxRate / 100);
                    grandTotal = subtotal + taxAmount;
                }
            }

            document.getElementById('subtotalLabel').textContent = formatRupiah(subtotal);
            document.getElementById('taxAmountLabel').textContent = formatRupiah(taxAmount);
            document.getElementById('grandTotalLabel').textContent = formatRupiah(grandTotal);

            if (taxHelperText) {
                if (entityType === 'non_pkp') {
                    taxHelperText.textContent = 'Non PKP dipilih, tax rate otomatis 0 dan grand total sama dengan subtotal.';
                } else if (treatment === 'include') {
                    taxHelperText.textContent = 'Tax Include dipilih, grand total mengikuti subtotal dan tax dihitung sebagai bagian dari nominal.';
                } else {
                    taxHelperText.textContent = 'Tax Not Include dipilih, tax ditambahkan ke subtotal.';
                }
            }
        }

        function refreshTaxState() {
            applyTaxEntityState();
            syncTaxNote();
            calculateSummary();
        }

        function createRow(index) {
            const wrapper = document.createElement('div');
            wrapper.className = 'memo-item-row p-3';
            wrapper.innerHTML = `
                <div class="row g-3 align-items-start">
                    <div class="col-xl-4 col-lg-12">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            
                            <div>
                                <div class="fw-semibold memo-item-title">Budget Item #${index + 1}</div>
                               
                            </div>
                        </div>

                        <textarea name="items[${index}][details]" rows="2" class="form-control item-details" placeholder="Detail item..." required></textarea>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" name="items[${index}][price]" class="form-control item-price" value="0" min="0" step="0.01" required>
                    </div>

                    <div class="col-xl-1 col-md-4">
                        <label class="form-label">Qty <span class="text-danger">*</span></label>
                        <input type="number" name="items[${index}][quantity]" class="form-control item-quantity" value="1" min="1" step="1" required>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Estimated</label>
                        <div class="form-control bg-white fw-semibold item-estimated-label">Rp 0</div>
                    </div>

                    <div class="col-xl-2 col-lg-10">
                        <label class="form-label">Remarks</label>
                        <textarea name="items[${index}][remarks]" rows="2" class="form-control item-remarks" placeholder="Remarks..."></textarea>
                    </div>

                    <div class="col-xl-1 col-lg-2 budget-action-col">
                        <button type="button" class="btn btn-outline-danger w-100 remove-item-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            return wrapper;
        }

        addButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const index = itemsContainer.querySelectorAll('.memo-item-row').length;
                const newRow = createRow(index);

                itemsContainer.appendChild(newRow);
                calculateSummary();

                window.requestAnimationFrame(function () {
                    newRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    const firstInput = newRow.querySelector('.item-details');
                    if (firstInput) {
                        firstInput.focus({ preventScroll: true });
                    }
                });
            });
        });

        itemsContainer?.addEventListener('input', function (event) {
            if (
                event.target.classList.contains('item-price') ||
                event.target.classList.contains('item-quantity')
            ) {
                calculateSummary();
            }
        });

        itemsContainer?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-item-btn');

            if (! removeButton) {
                return;
            }

            const rows = itemsContainer.querySelectorAll('.memo-item-row');

            if (rows.length <= 1) {
                alert('Minimal harus ada 1 budget item.');
                return;
            }

            removeButton.closest('.memo-item-row').remove();
            reindexRows();
            calculateSummary();
        });

        taxInput?.addEventListener('input', function () {
            if ((taxEntityTypeInput?.value || 'pkp') === 'pkp') {
                lastPkpTaxRate = parseFloat(taxInput.value || 11) || 11;
            }

            calculateSummary();
        });

        taxTreatmentInput?.addEventListener('change', refreshTaxState);
        taxEntityTypeInput?.addEventListener('change', refreshTaxState);

        form?.addEventListener('submit', function () {
            syncPurposeInput();
            syncTaxNote();
            syncSignerFields();
        });

        initQuill();
        bindSignerSelects();
        refreshItemTitles();
        refreshTaxState();
    });
</script>
@endpush