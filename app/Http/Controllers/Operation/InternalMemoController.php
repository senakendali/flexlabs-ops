<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\InternalMemo;
use App\Models\InternalMemoApproval;
use App\Models\User;
use App\Notifications\InternalMemoApprovalRequestedNotification;
use App\Notifications\InternalMemoApprovedNotification;
use App\Notifications\InternalMemoRejectedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InternalMemoController extends Controller
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_WAITING_ACKNOWLEDGEMENT = 'waiting_acknowledgement';
    private const STATUS_WAITING_APPROVAL = 'waiting_approval';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_CANCELLED = 'cancelled';

    private const APPROVAL_PENDING = 'pending';
    private const APPROVAL_APPROVED = 'approved';
    private const APPROVAL_REJECTED = 'rejected';

    private const PAYMENT_SOURCE_BANK = 'bank';
    private const PAYMENT_SOURCE_CASH = 'cash';

    private const TAX_TREATMENT_INCLUDE = 'include';
    private const TAX_TREATMENT_NOT_INCLUDE = 'not_include';

    private const TAX_ENTITY_PKP = 'pkp';
    private const TAX_ENTITY_NON_PKP = 'non_pkp';

    private const DEPARTMENT_CODES = [
        'MK' => 'Marketing',
        'BA' => 'BA',
        'SA' => 'Sales',
        'AC' => 'Academic',
    ];

    private const ROLE_DEPARTMENT_CODES = [
        'marketing' => 'MK',
        'ba' => 'BA',
        'sales' => 'SA',
        'academic' => 'AC',
    ];

    private const DEFAULT_APPROVAL_SIGNERS = [
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

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $memos = $this->visibleMemosQuery(Auth::user())
            ->with([
                'creator:id,name,email',
                'submitter:id,name,email',
                'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
                'approvals' => fn ($query) => $query->orderBy('step_order'),
                'approvals.approver:id,name,email,user_type',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('memo_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('to_name', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")
                        ->orWhere('payment_source', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('memo_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('memo_date', '<=', $dateTo))
            ->latest('memo_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('operation.internal-memos.index', [
            'memos' => $memos,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => $this->memoStatuses(),
        ]);
    }

    public function myMemos(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $memos = InternalMemo::query()
            ->with([
                'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
                'approvals' => fn ($query) => $query->orderBy('step_order'),
                'approvals.approver:id,name,email,user_type',
            ])
            ->where('created_by', Auth::id())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('memo_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('to_name', 'like', "%{$search}%")
                        ->orWhere('payment_source', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('memo_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('operation.internal-memos.index', [
            'memos' => $memos,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'scope' => 'my-memos',
            ],
            'statuses' => $this->memoStatuses(),
            'pageTitle' => 'My Internal Memos',
        ]);
    }

    public function pendingApprovals(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $userId = Auth::id();

        $memos = InternalMemo::query()
            ->with([
                'creator:id,name,email',
                'submitter:id,name,email',
                'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
                'approvals' => fn ($query) => $query->orderBy('step_order'),
                'approvals.approver:id,name,email,user_type',
            ])
            ->whereIn('status', [
                self::STATUS_SUBMITTED,
                self::STATUS_WAITING_ACKNOWLEDGEMENT,
                self::STATUS_WAITING_APPROVAL,
            ])
            ->whereHas('approvals', function ($approvalQuery) use ($userId) {
                $approvalQuery->where('status', self::APPROVAL_PENDING)
                    ->where('approver_id', $userId)
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('internal_memo_approvals as previous_approvals')
                            ->whereColumn('previous_approvals.internal_memo_id', 'internal_memo_approvals.internal_memo_id')
                            ->whereColumn('previous_approvals.step_order', '<', 'internal_memo_approvals.step_order')
                            ->where('previous_approvals.status', '!=', self::APPROVAL_APPROVED);
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('memo_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('to_name', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%");
                });
            })
            ->oldest('memo_date')
            ->oldest('id')
            ->paginate(15)
            ->withQueryString();

        return view('operation.internal-memos.index', [
            'memos' => $memos,
            'filters' => [
                'search' => $search,
                'scope' => 'pending-approvals',
            ],
            'statuses' => $this->memoStatuses(),
            'pageTitle' => 'Pending Internal Memo Approvals',
        ]);
    }

    public function create(): View
    {
        return view('operation.internal-memos.create', [
            'memo' => new InternalMemo([
                'memo_date' => now()->toDateString(),
                'due_date' => null,
                'from_name' => Auth::user()?->name,
                'from_position' => $this->resolveUserPosition(Auth::user()),
                'payment_source' => self::PAYMENT_SOURCE_BANK,
                'tax_rate' => 11,
                'tax_treatment' => self::TAX_TREATMENT_NOT_INCLUDE,
                'tax_entity_type' => self::TAX_ENTITY_PKP,
                'notes' => "Payment prices may change depending on the promo period.\nPayments can be made through Bank Mandiri.",
            ]),
            'users' => $this->userOptions(),
            'approvalSignersDefaults' => self::DEFAULT_APPROVAL_SIGNERS,
            'acknowledgementDefaults' => self::DEFAULT_APPROVAL_SIGNERS,
            'statuses' => $this->memoStatuses(),
            'paymentSources' => $this->paymentSources(),
            'taxTreatments' => $this->taxTreatments(),
            'taxEntityTypes' => $this->taxEntityTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $action = $this->validateFormAction($request);
        $validated = $this->validateMemoDraft($request, true);

        $amounts = $this->calculateAmounts(
            $validated['items'],
            (float) ($validated['tax_rate'] ?? 0),
            $validated['tax_treatment'],
            $validated['tax_entity_type']
        );

        $memo = DB::transaction(function () use ($validated, $amounts, $action) {
            $memo = InternalMemo::create([
                'memo_number' => $this->generateMemoNumber(
                    $validated['memo_date'],
                    $validated['department']
                ),
                'memo_date' => $validated['memo_date'],
                'due_date' => $validated['due_date'] ?? null,

                'subject' => $validated['subject'],
                'attachment_label' => $validated['attachment_label'] ?? null,
                'attachment_url' => $validated['attachment_url'] ?? null,

                'to_name' => $validated['to_name'],
                'to_position' => $validated['to_position'] ?? null,

                'from_name' => $validated['from_name'],
                'from_position' => $validated['from_position'] ?? null,

                'purpose' => $validated['purpose'],
                'notes' => $validated['notes'] ?? null,

                'payment_source' => $validated['payment_source'],

                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_rate' => $amounts['tax_rate'],
                'tax_treatment' => $validated['tax_treatment'],
                'tax_entity_type' => $validated['tax_entity_type'],
                'tax_amount' => $amounts['tax_amount'],
                'grand_total_amount' => $amounts['grand_total_amount'],

                'status' => self::STATUS_DRAFT,

                'created_by' => Auth::id(),
                'submitted_by' => null,
                'submitted_at' => null,

                'approved_at' => null,
                'rejected_at' => null,
                'cancelled_at' => null,
            ]);

            $this->syncItems($memo, $validated['items']);
            $this->syncApprovals($memo, $validated);

            if ($action === 'submit') {
                $memo->load(['items', 'approvals']);
                $this->validateMemoForSubmission($memo);
                $this->markMemoAsSubmitted($memo);
            }

            return $memo;
        });

        if ($action === 'submit') {
            $memo->refresh();
            $this->notifyActiveApproval($memo);
        }

        return redirect()
            ->route('internal-memos.show', $memo)
            ->with(
                'success',
                $action === 'submit'
                    ? 'Internal memo berhasil disubmit dan dikirim ke signer pertama.'
                    : 'Internal memo berhasil disimpan sebagai draft.'
            );
    }

    public function show(InternalMemo $internalMemo): View
    {
        $this->authorizeMemoVisibility($internalMemo);

        $internalMemo->load([
            'creator:id,name,email',
            'submitter:id,name,email',
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name,email,user_type',
        ]);

        return view('operation.internal-memos.show', [
            'memo' => $internalMemo,
            'activeApproval' => $this->activeApproval($internalMemo),
            'canEdit' => $this->canEditMemo($internalMemo),
            'canSubmit' => $this->canSubmitMemo($internalMemo),
            'canApprove' => $this->canActOnMemo($internalMemo),
            'statuses' => $this->memoStatuses(),
            'paymentSources' => $this->paymentSources(),
            'taxTreatments' => $this->taxTreatments(),
            'taxEntityTypes' => $this->taxEntityTypes(),
        ]);
    }

    public function edit(InternalMemo $internalMemo): View
    {
        $this->authorizeMemoVisibility($internalMemo);
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa diedit.');

        $internalMemo->load([
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
        ]);

        return view('operation.internal-memos.edit', [
            'memo' => $internalMemo,
            'users' => $this->userOptions(),
            'approvalSignersDefaults' => self::DEFAULT_APPROVAL_SIGNERS,
            'acknowledgementDefaults' => self::DEFAULT_APPROVAL_SIGNERS,
            'statuses' => $this->memoStatuses(),
            'paymentSources' => $this->paymentSources(),
            'taxTreatments' => $this->taxTreatments(),
            'taxEntityTypes' => $this->taxEntityTypes(),
        ]);
    }

    public function update(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa diedit.');

        $action = $this->validateFormAction($request);
        $validated = $this->validateMemoDraft($request);

        $amounts = $this->calculateAmounts(
            $validated['items'],
            (float) ($validated['tax_rate'] ?? 0),
            $validated['tax_treatment'],
            $validated['tax_entity_type']
        );

        DB::transaction(function () use ($internalMemo, $validated, $amounts, $action) {
            $internalMemo->update([
                'memo_date' => $validated['memo_date'],
                'due_date' => $validated['due_date'] ?? null,

                'subject' => $validated['subject'],
                'attachment_label' => $validated['attachment_label'] ?? null,
                'attachment_url' => $validated['attachment_url'] ?? null,

                'to_name' => $validated['to_name'],
                'to_position' => $validated['to_position'] ?? null,

                'from_name' => $validated['from_name'],
                'from_position' => $validated['from_position'] ?? null,

                'purpose' => $validated['purpose'],
                'notes' => $validated['notes'] ?? null,

                'payment_source' => $validated['payment_source'],

                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_rate' => $amounts['tax_rate'],
                'tax_treatment' => $validated['tax_treatment'],
                'tax_entity_type' => $validated['tax_entity_type'],
                'tax_amount' => $amounts['tax_amount'],
                'grand_total_amount' => $amounts['grand_total_amount'],

                'status' => self::STATUS_DRAFT,

                'submitted_by' => null,
                'submitted_at' => null,

                'approved_at' => null,
                'rejected_at' => null,
                'cancelled_at' => null,
            ]);

            $this->syncItems($internalMemo, $validated['items']);
            $this->syncApprovals($internalMemo, $validated);

            if ($action === 'submit') {
                $internalMemo->load(['items', 'approvals']);
                $this->validateMemoForSubmission($internalMemo);
                $this->markMemoAsSubmitted($internalMemo);
            }
        });

        if ($action === 'submit') {
            $internalMemo->refresh();
            $this->notifyActiveApproval($internalMemo);
        }

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with(
                'success',
                $action === 'submit'
                    ? 'Internal memo berhasil disubmit dan dikirim ke signer pertama.'
                    : 'Draft internal memo berhasil diperbarui.'
            );
    }

    public function destroy(InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);
        abort_unless($this->canDeleteMemo($internalMemo), 403, 'Memo ini tidak bisa dihapus.');

        $internalMemo->delete();

        return redirect()
            ->route('internal-memos.index')
            ->with('success', 'Internal memo berhasil dihapus.');
    }

    public function submit(InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);
        abort_unless($this->canSubmitMemo($internalMemo), 403, 'Memo ini tidak bisa disubmit.');

        $internalMemo->load(['items', 'approvals']);

        $this->validateMemoForSubmission($internalMemo);

        DB::transaction(function () use ($internalMemo) {
            $internalMemo->approvals()->update([
                'status' => self::APPROVAL_PENDING,
                'notes' => null,
                'notification_sent_at' => null,
                'reminder_sent_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
            ]);

            $internalMemo->update([
                'status' => self::STATUS_WAITING_ACKNOWLEDGEMENT,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'approved_at' => null,
                'rejected_at' => null,
                'cancelled_at' => null,
            ]);
        });

        $internalMemo->refresh();
        $this->notifyActiveApproval($internalMemo);

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil dipublish dan dikirim ke signer pertama.');
    }

    public function approve(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $internalMemo->load(['approvals' => fn ($query) => $query->orderBy('step_order')]);

        $approval = $this->activeApproval($internalMemo);

        if (! $approval) {
            return back()->with('error', 'Tidak ada approval aktif untuk memo ini.');
        }

        abort_unless($this->canActOnApproval($approval), 403, 'Anda bukan approver aktif untuk memo ini.');

        $hasNextApproval = false;
        $isFinalApproval = false;

        DB::transaction(function () use ($internalMemo, $approval, $validated, &$hasNextApproval, &$isFinalApproval) {
            $approval->update([
                'status' => self::APPROVAL_APPROVED,
                'notes' => $validated['notes'] ?? null,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            $nextApproval = $internalMemo->approvals()
                ->where('status', self::APPROVAL_PENDING)
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $hasNextApproval = true;

                $internalMemo->update([
                    'status' => $this->statusForNextApproval($nextApproval),
                    'rejected_at' => null,
                ]);

                return;
            }

            $isFinalApproval = true;

            $internalMemo->update([
                'status' => self::STATUS_APPROVED,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);
        });

        $internalMemo->refresh();
        $approval->refresh();

        if ($hasNextApproval) {
            $this->notifyActiveApproval($internalMemo);
        }

        if ($isFinalApproval) {
            $this->notifyMemoApproved($internalMemo, $approval);
        }

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil di-approve.');
    }

    public function reject(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ], [
            'notes.required' => 'Catatan rejection wajib diisi.',
        ]);

        $internalMemo->load(['approvals' => fn ($query) => $query->orderBy('step_order')]);

        $approval = $this->activeApproval($internalMemo);

        if (! $approval) {
            return back()->with('error', 'Tidak ada approval aktif untuk memo ini.');
        }

        abort_unless($this->canActOnApproval($approval), 403, 'Anda bukan approver aktif untuk memo ini.');

        DB::transaction(function () use ($internalMemo, $approval, $validated) {
            $approval->update([
                'status' => self::APPROVAL_REJECTED,
                'notes' => $validated['notes'],
                'approved_at' => null,
                'rejected_at' => now(),
            ]);

            $internalMemo->update([
                'status' => self::STATUS_REJECTED,
                'rejected_at' => now(),
                'approved_at' => null,
            ]);
        });

        $internalMemo->refresh();
        $approval->refresh();

        $this->notifyMemoRejected($internalMemo, $approval);

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil ditolak.');
    }

    public function cancel(InternalMemo $internalMemo): RedirectResponse
    {
        $this->authorizeMemoVisibility($internalMemo);
        abort_unless($this->canManageMemo($internalMemo), 403, 'Anda tidak berhak membatalkan memo ini.');

        abort_unless(
            in_array($internalMemo->status, [
                self::STATUS_DRAFT,
                self::STATUS_REJECTED,
                self::STATUS_WAITING_ACKNOWLEDGEMENT,
                self::STATUS_WAITING_APPROVAL,
                self::STATUS_APPROVED,
            ], true),
            403,
            'Memo ini tidak bisa dibatalkan.'
        );

        DB::transaction(function () use ($internalMemo) {
            $internalMemo->update([
                'status' => self::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
        });

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil dibatalkan.');
    }

    public function downloadPdf(InternalMemo $internalMemo)
    {
        $this->authorizeMemoVisibility($internalMemo);

        $internalMemo->load([
            'creator:id,name,email',
            'submitter:id,name,email',
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name,email,user_type',
        ]);

        $filename = Str::slug($internalMemo->memo_number ?: 'internal-memo') . '.pdf';
        $approvalSignatures = $this->buildApprovalSignatureDataUris($internalMemo);

        return Pdf::loadView('operation.internal-memos.pdf', [
                'memo' => $internalMemo,
                'approvalSignatures' => $approvalSignatures,
            ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * Prepare private signature PNG files for the PDF template.
     *
     * Signature mapping:
     * - step_order 1 => storage/app/private/signatures/1.png
     * - step_order 2 => storage/app/private/signatures/2.png
     * - step_order 3 => storage/app/private/signatures/3.png
     *
     * A signature is only returned when its own approval has been approved.
     * The private file is converted to a data URI, so no public URL is exposed.
     */
    private function buildApprovalSignatureDataUris(InternalMemo $memo): array
    {
        $signatures = [
            1 => null,
            2 => null,
            3 => null,
        ];

        foreach ($memo->approvals as $approval) {
            $stepOrder = (int) $approval->step_order;

            if (! array_key_exists($stepOrder, $signatures)) {
                continue;
            }

            if (
                $approval->status !== self::APPROVAL_APPROVED
                || ! $approval->approved_at
            ) {
                continue;
            }

            $signaturePath = storage_path(
                "app/private/signatures/{$stepOrder}.png"
            );

            if (! is_file($signaturePath) || ! is_readable($signaturePath)) {
                continue;
            }

            $mimeType = mime_content_type($signaturePath);

            if ($mimeType !== 'image/png') {
                continue;
            }

            $contents = file_get_contents($signaturePath);

            if ($contents === false || $contents === '') {
                continue;
            }

            $signatures[$stepOrder] = 'data:image/png;base64,'
                . base64_encode($contents);
        }

        return $signatures;
    }

    /**
     * Draft validation is intentionally permissive. Only memo_date is required
     * because it is used when the permanent memo number is generated.
     */
    private function validateMemoDraft(Request $request, bool $requireDepartment = false): array
    {
        $request->merge([
            'memo_date' => $request->input('memo_date', now()->toDateString()),
            'payment_source' => $request->input('payment_source', self::PAYMENT_SOURCE_BANK),
            'tax_treatment' => $request->input('tax_treatment', self::TAX_TREATMENT_NOT_INCLUDE),
            'tax_entity_type' => $request->input('tax_entity_type', self::TAX_ENTITY_PKP),
            'tax_rate' => $request->input('tax_rate', 11),
        ]);

        $validated = $request->validate([
            'department' => [
                Rule::requiredIf($requireDepartment),
                'nullable',
                'string',
                Rule::in(array_keys(self::DEPARTMENT_CODES)),
            ],
            'memo_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:memo_date'],

            'subject' => ['nullable', 'string', 'max:255'],
            'attachment_label' => ['nullable', 'string', 'max:255'],
            'attachment_url' => ['nullable', 'url', 'max:2048'],

            'to_name' => ['nullable', 'string', 'max:255'],
            'to_position' => ['nullable', 'string', 'max:255'],

            'from_name' => ['nullable', 'string', 'max:255'],
            'from_position' => ['nullable', 'string', 'max:255'],

            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'payment_source' => ['required', Rule::in(array_keys($this->paymentSources()))],

            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_treatment' => ['required', Rule::in(array_keys($this->taxTreatments()))],
            'tax_entity_type' => ['required', Rule::in(array_keys($this->taxEntityTypes()))],

            'acknowledgements' => ['nullable', 'array', 'max:3'],
            'acknowledgements.*.role_label' => ['nullable', 'string', 'max:255'],
            'acknowledgements.*.approver_id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('user_type', 'staff');
                }),
            ],
            'acknowledgements.*.name' => ['nullable', 'string', 'max:255'],
            'acknowledgements.*.position' => ['nullable', 'string', 'max:255'],

            'items' => ['nullable', 'array'],
            'items.*.details' => ['nullable', 'string', 'max:5000'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.remarks' => ['nullable', 'string', 'max:5000'],
        ], [
            'department.required' => 'Department wajib dipilih untuk membuat nomor memo.',
            'department.in' => 'Department yang dipilih tidak valid.',
            'due_date.after_or_equal' => 'Due date tidak boleh lebih awal dari memo date.',
            'attachment_url.url' => 'Attachment Google Drive Link harus berupa URL yang valid.',
            'acknowledgements.*.approver_id.distinct' => 'Signer tidak boleh orang yang sama.',
            'acknowledgements.*.approver_id.exists' => 'User signer harus merupakan staff yang valid.',
        ]);

        $validated['subject'] = trim((string) ($validated['subject'] ?? ''));
        $validated['to_name'] = trim((string) ($validated['to_name'] ?? ''));
        $validated['from_name'] = trim((string) ($validated['from_name'] ?? ''));
        $validated['purpose'] = $this->sanitizeQuillHtml($validated['purpose'] ?? null);
        $validated['items'] = $this->cleanDraftItems($validated['items'] ?? []);
        $validated['acknowledgements'] = $this->cleanDraftApprovals(
            $validated['acknowledgements'] ?? []
        );

        $validated['tax_rate'] = (float) ($validated['tax_rate'] ?? 0);

        if ($validated['tax_entity_type'] === self::TAX_ENTITY_NON_PKP) {
            $validated['tax_rate'] = 0;
        }

        $validated['notes'] = $this->applyAutomaticTaxNote(
            $validated['notes'] ?? null,
            $validated['tax_treatment']
        );

        foreach ($validated['acknowledgements'] as $index => $acknowledgement) {
            $validated['acknowledgements'][$index]['role_label'] = ($acknowledgement['role_label'] ?? null)
                ?: (self::DEFAULT_APPROVAL_SIGNERS[$index]['role_label'] ?? 'Acknowledged by');
        }

        return $validated;
    }

    private function validateFormAction(Request $request): string
    {
        return $request->validate([
            'action' => ['required', Rule::in(['draft', 'submit'])],
        ], [
            'action.required' => 'Pilih Save as Draft atau Submit Internal Memo.',
            'action.in' => 'Aksi penyimpanan internal memo tidak valid.',
        ])['action'];
    }

    private function markMemoAsSubmitted(InternalMemo $memo): void
    {
        $memo->approvals()->update([
            'status' => self::APPROVAL_PENDING,
            'notes' => null,
            'notification_sent_at' => null,
            'reminder_sent_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ]);

        $memo->update([
            'status' => self::STATUS_WAITING_ACKNOWLEDGEMENT,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);
    }

    private function cleanDraftItems(array $items): array
    {
        return collect($items)
            ->filter(function ($item) {
                return trim((string) ($item['details'] ?? '')) !== ''
                    || ($item['price'] ?? null) !== null
                    || ($item['quantity'] ?? null) !== null
                    || trim((string) ($item['remarks'] ?? '')) !== '';
            })
            ->map(function ($item) {
                return [
                    'details' => trim((string) ($item['details'] ?? '')),
                    'price' => (float) ($item['price'] ?? 0),
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'remarks' => $item['remarks'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanDraftApprovals(array $approvals): array
    {
        return collect(array_slice($approvals, 0, 3))
            ->filter(fn ($approval) => ! empty($approval['approver_id']))
            ->values()
            ->all();
    }

    /**
     * Run strict validation against the saved draft immediately before publish.
     */
    private function validateMemoForSubmission(InternalMemo $memo): void
    {
        $memo->loadMissing(['items', 'approvals']);

        $data = [
            'memo_date' => $memo->memo_date,
            'due_date' => $memo->due_date,
            'subject' => $memo->subject,
            'to_name' => $memo->to_name,
            'from_name' => $memo->from_name,
            'purpose' => $memo->purpose,
            'payment_source' => $memo->payment_source,
            'tax_treatment' => $memo->tax_treatment,
            'tax_entity_type' => $memo->tax_entity_type,
            'items' => $memo->items->map(fn ($item) => [
                'details' => $item->details,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ])->all(),
            'acknowledgements' => $memo->approvals->map(fn ($approval) => [
                'approver_id' => $approval->approver_id,
                'position' => $approval->approver_position,
            ])->all(),
        ];

        $validator = Validator::make($data, [
            'memo_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:memo_date'],
            'subject' => ['required', 'string', 'max:255'],
            'to_name' => ['required', 'string', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'payment_source' => ['required', Rule::in(array_keys($this->paymentSources()))],
            'tax_treatment' => ['required', Rule::in(array_keys($this->taxTreatments()))],
            'tax_entity_type' => ['required', Rule::in(array_keys($this->taxEntityTypes()))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.details' => ['required', 'string', 'max:5000'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'acknowledgements' => ['required', 'array', 'size:3'],
            'acknowledgements.*.approver_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('user_type', 'staff')),
            ],
            'acknowledgements.*.position' => ['required', 'string', 'max:255'],
        ], [
            'subject.required' => 'Subject wajib diisi sebelum memo disubmit.',
            'to_name.required' => 'Tujuan memo wajib diisi sebelum memo disubmit.',
            'from_name.required' => 'Pengirim memo wajib diisi sebelum memo disubmit.',
            'purpose.required' => 'Purpose wajib diisi minimal 2 poin.',
            'items.required' => 'Minimal harus ada 1 budget item.',
            'items.min' => 'Minimal harus ada 1 budget item.',
            'items.*.details.required' => 'Detail budget item wajib diisi.',
            'acknowledgements.size' => 'Approval signer harus berisi 3 orang.',
            'acknowledgements.*.approver_id.required' => 'User signer wajib dipilih.',
            'acknowledgements.*.approver_id.distinct' => 'Signer tidak boleh orang yang sama.',
            'acknowledgements.*.approver_id.exists' => 'User signer harus merupakan staff yang valid.',
            'acknowledgements.*.position.required' => 'Jabatan signer wajib diisi.',
        ]);

        $validator->after(function ($validator) use ($memo) {
            if ($this->countPurposePoints($memo->purpose) < 2) {
                $validator->errors()->add('purpose', 'Purpose minimal harus berisi 2 poin.');
            }
        });

        $validator->validate();
    }

    private function syncItems(InternalMemo $memo, array $items): void
    {
        $memo->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $price = (float) ($item['price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);

            $memo->items()->create([
                'details' => $item['details'],
                'price' => $price,
                'quantity' => $quantity,
                'estimated_price' => $price * $quantity,
                'remarks' => $item['remarks'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncApprovals(InternalMemo $memo, array $validated): void
    {
        $memo->approvals()->delete();

        $approverIds = collect($validated['acknowledgements'] ?? [])
            ->pluck('approver_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $approvers = User::query()
            ->where('user_type', 'staff')
            ->whereIn('id', $approverIds)
            ->get()
            ->keyBy('id');

        foreach (array_values($validated['acknowledgements'] ?? self::DEFAULT_APPROVAL_SIGNERS) as $index => $signer) {
            $approverId = (int) ($signer['approver_id'] ?? 0);
            $approver = $approvers->get($approverId);

            $roleLabel = $signer['role_label']
                ?? (self::DEFAULT_APPROVAL_SIGNERS[$index]['role_label'] ?? 'Acknowledged by');

            $memo->approvals()->create([
                'step_order' => $index + 1,
                'role_label' => $roleLabel,

                'approver_id' => $approver?->id,
                'approver_email' => $approver?->email,
                'approver_name' => $approver?->name ?: ($signer['name'] ?? null),
                'approver_position' => $signer['position'] ?? $this->resolveUserPosition($approver),

                'status' => self::APPROVAL_PENDING,
                'notes' => null,

                'notification_sent_at' => null,
                'reminder_sent_at' => null,

                'approved_at' => null,
                'rejected_at' => null,
            ]);
        }
    }

    private function calculateAmounts(array $items, float $taxRate, string $taxTreatment, string $taxEntityType): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            return (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1);
        });

        if ($taxEntityType === self::TAX_ENTITY_NON_PKP || $taxRate <= 0) {
            return [
                'subtotal_amount' => round($subtotal, 2),
                'tax_rate' => 0,
                'tax_amount' => 0,
                'grand_total_amount' => round($subtotal, 2),
            ];
        }

        if ($taxTreatment === self::TAX_TREATMENT_INCLUDE) {
            $taxAmount = $subtotal - ($subtotal / (1 + ($taxRate / 100)));

            return [
                'subtotal_amount' => round($subtotal, 2),
                'tax_rate' => round($taxRate, 2),
                'tax_amount' => round($taxAmount, 2),
                'grand_total_amount' => round($subtotal, 2),
            ];
        }

        $taxAmount = $subtotal * ($taxRate / 100);

        return [
            'subtotal_amount' => round($subtotal, 2),
            'tax_rate' => round($taxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total_amount' => round($subtotal + $taxAmount, 2),
        ];
    }

    private function notifyActiveApproval(InternalMemo $memo): void
    {
        $memo->loadMissing([
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name,email,user_type',
        ]);

        $approval = $this->activeApproval($memo);

        if (! $approval || ! $approval->approver) {
            return;
        }

        if ($approval->notification_sent_at) {
            return;
        }

        if ($approval->approver->user_type !== 'staff') {
            return;
        }

        $approval->approver->notify(
            new InternalMemoApprovalRequestedNotification($approval)
        );

        $approval->forceFill([
            'notification_sent_at' => now(),
        ])->save();
    }

    private function notifyMemoApproved(InternalMemo $memo, ?InternalMemoApproval $approval = null): void
    {
        $memo->loadMissing([
            'creator:id,name,email',
            'submitter:id,name,email',
        ]);

        collect([
            $memo->submitter,
            $memo->creator,
        ])
            ->filter()
            ->unique('id')
            ->each(function (User $user) use ($memo, $approval) {
                $user->notify(new InternalMemoApprovedNotification($memo, $approval));
            });
    }

    private function notifyMemoRejected(InternalMemo $memo, ?InternalMemoApproval $approval = null): void
    {
        $memo->loadMissing([
            'creator:id,name,email',
            'submitter:id,name,email',
        ]);

        collect([
            $memo->submitter,
            $memo->creator,
        ])
            ->filter()
            ->unique('id')
            ->each(function (User $user) use ($memo, $approval) {
                $user->notify(new InternalMemoRejectedNotification($memo, $approval));
            });
    }

    private function sanitizeQuillHtml(?string $html): string
    {
        $html = (string) $html;

        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;

        $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><a><span>');

        $html = preg_replace('/\s(on\w+|style)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/\s(href)\s*=\s*("|\')\s*javascript:.*?\2/i', '', $html) ?? $html;

        return trim($html);
    }

    private function countPurposePoints(?string $html): int
    {
        $html = trim((string) $html);

        if ($html === '') {
            return 0;
        }

        preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $html, $listMatches);

        $listCount = collect($listMatches[1] ?? [])
            ->map(fn ($item) => trim(html_entity_decode(strip_tags($item))))
            ->filter()
            ->count();

        if ($listCount > 0) {
            return $listCount;
        }

        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $paragraphMatches);

        $paragraphCount = collect($paragraphMatches[1] ?? [])
            ->map(fn ($item) => trim(html_entity_decode(strip_tags($item))))
            ->filter()
            ->count();

        if ($paragraphCount > 0) {
            return $paragraphCount;
        }

        $text = str_replace(['</p>', '<br>', '<br/>', '<br />', '</li>'], "\n", $html);
        $text = trim(html_entity_decode(strip_tags($text)));

        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->count();
    }

    private function applyAutomaticTaxNote(?string $notes, string $taxTreatment): ?string
    {
        $taxIncludedLine = 'Tax is included in the submitted amount.';

        $notes = trim((string) $notes);

        $notes = preg_replace(
            '/(^|\R)\s*' . preg_quote($taxIncludedLine, '/') . '\s*(?=\R|$)/u',
            "\n",
            $notes
        ) ?? $notes;

        $notes = trim($notes);

        if ($taxTreatment === self::TAX_TREATMENT_INCLUDE) {
            return trim($notes . ($notes !== '' ? "\n" : '') . $taxIncludedLine);
        }

        return $notes !== '' ? $notes : null;
    }

    /**
     * Format: 048/SEI-EDU/IM-MK/08-03
     *
     * The sequence is global and never resets. The MM-DD suffix follows the
     * memo date, while the sequence is permanently assigned on first save.
     */
    private function generateMemoNumber(string $memoDate, string $departmentCode): string
    {
        $latestMemo = InternalMemo::query()
            ->withTrashed()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first(['id', 'memo_number']);

        $latestSequence = 0;

        if ($latestMemo) {
            if (preg_match('/^(\d+)\//', (string) $latestMemo->memo_number, $matches)) {
                $latestSequence = (int) $matches[1];
            } else {
                // Migration fallback for existing IM-YYYYMM-0001 numbers.
                // Using the latest ID keeps the new sequence aligned with the
                // number of memo records already issued.
                $latestSequence = (int) $latestMemo->id;
            }
        }

        $nextSequence = $latestSequence + 1;
        $dateSuffix = date('m-d', strtotime($memoDate));

        return str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT)
            . '/SEI-EDU/IM-'
            . $departmentCode
            . '/'
            . $dateSuffix;
    }

    private function activeApproval(InternalMemo $memo): ?InternalMemoApproval
    {
        if (in_array($memo->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true)) {
            return null;
        }

        if (! $memo->relationLoaded('approvals')) {
            $memo->load(['approvals' => fn ($query) => $query->orderBy('step_order')]);
        }

        return $memo->approvals
            ->where('status', self::APPROVAL_PENDING)
            ->sortBy('step_order')
            ->first();
    }

    private function statusForNextApproval(InternalMemoApproval $approval): string
    {
        $roleLabel = Str::lower((string) $approval->role_label);

        if (Str::contains($roleLabel, 'approved') || $approval->step_order >= 3) {
            return self::STATUS_WAITING_APPROVAL;
        }

        return self::STATUS_WAITING_ACKNOWLEDGEMENT;
    }

    private function canEditMemo(InternalMemo $memo): bool
    {
        return $this->canManageMemo($memo)
            && in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canDeleteMemo(InternalMemo $memo): bool
    {
        return $this->canManageMemo($memo)
            && in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canSubmitMemo(InternalMemo $memo): bool
    {
        return $this->canManageMemo($memo)
            && in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function visibleMemosQuery(?User $user)
    {
        $query = InternalMemo::query();

        abort_unless($user, 403);

        if ($this->isPrivilegedUser($user)) {
            return $query;
        }

        $departmentCode = $this->departmentCodeForUser($user);

        return $query->where(function ($visibilityQuery) use ($user, $departmentCode) {
            $visibilityQuery->where('created_by', $user->id)
                ->orWhereHas('approvals', fn ($approvalQuery) => $approvalQuery
                    ->where('approver_id', $user->id));

            if ($departmentCode) {
                $visibilityQuery->orWhere(function ($departmentQuery) use ($departmentCode) {
                    $departmentQuery
                        ->where('status', '!=', self::STATUS_DRAFT)
                        ->where('memo_number', 'like', '%/IM-' . $departmentCode . '/%');
                });
            }
        });
    }

    private function authorizeMemoVisibility(InternalMemo $memo): void
    {
        abort_unless($this->canViewMemo($memo), 403, 'Anda tidak memiliki akses ke internal memo ini.');
    }

    private function canViewMemo(InternalMemo $memo): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($this->isPrivilegedUser($user) || (int) $memo->created_by === (int) $user->id) {
            return true;
        }

        if ($memo->approvals()->where('approver_id', $user->id)->exists()) {
            return true;
        }

        if ($memo->status === self::STATUS_DRAFT) {
            return false;
        }

        $departmentCode = $this->departmentCodeForUser($user);

        return $departmentCode !== null
            && Str::contains((string) $memo->memo_number, '/IM-' . $departmentCode . '/');
    }

    private function canManageMemo(InternalMemo $memo): bool
    {
        $user = Auth::user();

        return $user
            && ($this->isPrivilegedUser($user) || (int) $memo->created_by === (int) $user->id);
    }

    private function departmentCodeForUser(User $user): ?string
    {
        $role = Str::lower(trim((string) $user->role));

        return self::ROLE_DEPARTMENT_CODES[$role] ?? null;
    }

    private function isPrivilegedUser(User $user): bool
    {
        if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('HR'))) {
            return true;
        }

        foreach (['role', 'role_name', 'user_role'] as $field) {
            if (isset($user->{$field}) && in_array(Str::lower((string) $user->{$field}), ['admin', 'hr'], true)) {
                return true;
            }
        }

        return false;
    }

    private function canActOnMemo(InternalMemo $memo): bool
    {
        $approval = $this->activeApproval($memo);

        return $approval && $this->canActOnApproval($approval);
    }

    private function canActOnApproval(InternalMemoApproval $approval): bool
    {
        if ($approval->status !== self::APPROVAL_PENDING) {
            return false;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ((int) $approval->approver_id === (int) $user->id) {
            return true;
        }

        return $this->isAdminUser($user);
    }

    private function isAdminUser(User $user): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        foreach (['role', 'role_name', 'user_role'] as $field) {
            if (isset($user->{$field}) && strtolower((string) $user->{$field}) === 'admin') {
                return true;
            }
        }

        return false;
    }

    private function resolveUserPosition(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (['position', 'job_title', 'title', 'role_label'] as $field) {
            if (! empty($user->{$field})) {
                return (string) $user->{$field};
            }
        }

        if (! empty($user->role)) {
            return Str::headline((string) $user->role);
        }

        return null;
    }

    private function userOptions()
    {
        return User::query()
            ->where('user_type', 'staff')
            ->orderBy('name')
            ->get();
    }

    private function memoStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_WAITING_ACKNOWLEDGEMENT => 'Waiting Acknowledgement',
            self::STATUS_WAITING_APPROVAL => 'Waiting Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    private function paymentSources(): array
    {
        return [
            self::PAYMENT_SOURCE_BANK => 'Bank',
            self::PAYMENT_SOURCE_CASH => 'Cash',
        ];
    }

    private function taxTreatments(): array
    {
        return [
            self::TAX_TREATMENT_NOT_INCLUDE => 'Tax Not Include',
            self::TAX_TREATMENT_INCLUDE => 'Tax Include',
        ];
    }

    private function taxEntityTypes(): array
    {
        return [
            self::TAX_ENTITY_PKP => 'PKP',
            self::TAX_ENTITY_NON_PKP => 'Non PKP',
        ];
    }
}