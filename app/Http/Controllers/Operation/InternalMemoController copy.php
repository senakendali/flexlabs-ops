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

    /**
     * Create memo now means create + publish.
     * Signer 1 will receive notification right after memo is created.
     */
    private const AUTO_PUBLISH_ON_CREATE = true;

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

        $memos = InternalMemo::query()
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
        $validated = $this->validateMemo($request);

        $amounts = $this->calculateAmounts(
            $validated['items'],
            (float) ($validated['tax_rate'] ?? 0),
            $validated['tax_treatment'],
            $validated['tax_entity_type']
        );

        $memo = DB::transaction(function () use ($validated, $amounts) {
            $memo = InternalMemo::create([
                'memo_number' => $this->generateMemoNumber(),
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

                'status' => self::AUTO_PUBLISH_ON_CREATE
                    ? self::STATUS_WAITING_ACKNOWLEDGEMENT
                    : self::STATUS_DRAFT,

                'created_by' => Auth::id(),
                'submitted_by' => self::AUTO_PUBLISH_ON_CREATE ? Auth::id() : null,
                'submitted_at' => self::AUTO_PUBLISH_ON_CREATE ? now() : null,

                'approved_at' => null,
                'rejected_at' => null,
                'cancelled_at' => null,
            ]);

            $this->syncItems($memo, $validated['items']);
            $this->syncApprovals($memo, $validated);

            return $memo;
        });

        if (self::AUTO_PUBLISH_ON_CREATE) {
            $memo->refresh();
            $this->notifyActiveApproval($memo);
        }

        return redirect()
            ->route('internal-memos.show', $memo)
            ->with('success', self::AUTO_PUBLISH_ON_CREATE
                ? 'Internal memo berhasil dibuat dan dikirim ke signer pertama.'
                : 'Internal memo berhasil dibuat sebagai draft.'
            );
    }

    public function show(InternalMemo $internalMemo): View
    {
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
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa diedit.');

        $validated = $this->validateMemo($request, $internalMemo);

        $amounts = $this->calculateAmounts(
            $validated['items'],
            (float) ($validated['tax_rate'] ?? 0),
            $validated['tax_treatment'],
            $validated['tax_entity_type']
        );

        DB::transaction(function () use ($internalMemo, $validated, $amounts) {
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

                'status' => self::AUTO_PUBLISH_ON_CREATE
                    ? self::STATUS_WAITING_ACKNOWLEDGEMENT
                    : self::STATUS_DRAFT,

                'submitted_by' => self::AUTO_PUBLISH_ON_CREATE ? Auth::id() : null,
                'submitted_at' => self::AUTO_PUBLISH_ON_CREATE ? now() : null,

                'approved_at' => null,
                'rejected_at' => null,
                'cancelled_at' => null,
            ]);

            $this->syncItems($internalMemo, $validated['items']);
            $this->syncApprovals($internalMemo, $validated);
        });

        if (self::AUTO_PUBLISH_ON_CREATE) {
            $internalMemo->refresh();
            $this->notifyActiveApproval($internalMemo);
        }

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', self::AUTO_PUBLISH_ON_CREATE
                ? 'Internal memo berhasil diperbarui dan dikirim ke signer pertama.'
                : 'Internal memo berhasil diperbarui.'
            );
    }

    public function destroy(InternalMemo $internalMemo): RedirectResponse
    {
        abort_unless($this->canDeleteMemo($internalMemo), 403, 'Memo ini tidak bisa dihapus.');

        $internalMemo->delete();

        return redirect()
            ->route('internal-memos.index')
            ->with('success', 'Internal memo berhasil dihapus.');
    }

    public function submit(InternalMemo $internalMemo): RedirectResponse
    {
        abort_unless($this->canSubmitMemo($internalMemo), 403, 'Memo ini tidak bisa disubmit.');

        $internalMemo->load(['items', 'approvals']);

        if ($internalMemo->items->isEmpty()) {
            return back()->with('error', 'Memo belum bisa disubmit karena budget item masih kosong.');
        }

        if ($internalMemo->approvals->count() < 3) {
            return back()->with('error', 'Memo belum bisa disubmit karena approval signer belum lengkap.');
        }

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
        $internalMemo->load([
            'creator:id,name,email',
            'submitter:id,name,email',
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name,email,user_type',
        ]);

        $filename = Str::slug($internalMemo->memo_number ?: 'internal-memo') . '.pdf';

        return Pdf::loadView('operation.internal-memos.pdf', [
                'memo' => $internalMemo,
            ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function validateMemo(Request $request, ?InternalMemo $memo = null): array
    {
        $request->merge([
            'payment_source' => $request->input('payment_source', self::PAYMENT_SOURCE_BANK),
            'tax_treatment' => $request->input('tax_treatment', self::TAX_TREATMENT_NOT_INCLUDE),
            'tax_entity_type' => $request->input('tax_entity_type', self::TAX_ENTITY_PKP),
            'tax_rate' => $request->input('tax_rate', 11),
        ]);

        $validated = $request->validate([
            'memo_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:memo_date'],

            'subject' => ['required', 'string', 'max:255'],
            'attachment_label' => ['nullable', 'string', 'max:255'],
            'attachment_url' => ['nullable', 'url', 'max:2048'],

            'to_name' => ['required', 'string', 'max:255'],
            'to_position' => ['nullable', 'string', 'max:255'],

            'from_name' => ['required', 'string', 'max:255'],
            'from_position' => ['nullable', 'string', 'max:255'],

            'purpose' => ['required', 'string'],
            'notes' => ['nullable', 'string'],

            'payment_source' => ['required', Rule::in(array_keys($this->paymentSources()))],

            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_treatment' => ['required', Rule::in(array_keys($this->taxTreatments()))],
            'tax_entity_type' => ['required', Rule::in(array_keys($this->taxEntityTypes()))],

            'acknowledgements' => ['required', 'array', 'size:3'],
            'acknowledgements.*.role_label' => ['required', 'string', 'max:255'],
            'acknowledgements.*.approver_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('user_type', 'staff');
                }),
            ],
            'acknowledgements.*.name' => ['nullable', 'string', 'max:255'],
            'acknowledgements.*.position' => ['required', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.details' => ['required', 'string', 'max:5000'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.remarks' => ['nullable', 'string', 'max:5000'],
        ], [
            'due_date.after_or_equal' => 'Due date tidak boleh lebih awal dari memo date.',
            'purpose.required' => 'Purpose wajib diisi minimal 2 poin.',
            'attachment_url.url' => 'Attachment Google Drive Link harus berupa URL yang valid.',

            'acknowledgements.required' => 'Approval signer wajib diisi.',
            'acknowledgements.size' => 'Approval signer harus berisi 3 orang.',
            'acknowledgements.*.role_label.required' => 'Role label signer wajib diisi.',
            'acknowledgements.*.approver_id.required' => 'User signer wajib dipilih.',
            'acknowledgements.*.approver_id.distinct' => 'Signer tidak boleh orang yang sama.',
            'acknowledgements.*.approver_id.exists' => 'User signer harus merupakan staff yang valid.',
            'acknowledgements.*.position.required' => 'Jabatan signer wajib diisi.',

            'items.required' => 'Minimal harus ada 1 budget item.',
            'items.*.details.required' => 'Detail budget item wajib diisi.',
        ]);

        $validated['purpose'] = $this->sanitizeQuillHtml($validated['purpose']);

        if ($this->countPurposePoints($validated['purpose']) < 2) {
            throw ValidationException::withMessages([
                'purpose' => 'Purpose minimal harus berisi 2 poin.',
            ]);
        }

        $validated['tax_rate'] = (float) ($validated['tax_rate'] ?? 0);

        if ($validated['tax_entity_type'] === self::TAX_ENTITY_NON_PKP) {
            $validated['tax_rate'] = 0;
        }

        $validated['notes'] = $this->applyAutomaticTaxNote(
            $validated['notes'] ?? null,
            $validated['tax_treatment']
        );

        $validated['acknowledgements'] = array_values(array_slice($validated['acknowledgements'], 0, 3));

        foreach ($validated['acknowledgements'] as $index => $acknowledgement) {
            $validated['acknowledgements'][$index]['role_label'] = $acknowledgement['role_label']
                ?: (self::DEFAULT_APPROVAL_SIGNERS[$index]['role_label'] ?? 'Acknowledged by');
        }

        return $validated;
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

    private function generateMemoNumber(): string
    {
        $prefix = 'IM-' . now()->format('Ym');

        $latestNumber = InternalMemo::query()
            ->withTrashed()
            ->where('memo_number', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('memo_number');

        $nextNumber = 1;

        if ($latestNumber) {
            $lastSequence = (int) Str::afterLast($latestNumber, '-');
            $nextNumber = $lastSequence + 1;
        }

        return $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
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
        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canDeleteMemo(InternalMemo $memo): bool
    {
        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canSubmitMemo(InternalMemo $memo): bool
    {
        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
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