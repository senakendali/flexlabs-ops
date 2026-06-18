<?php

namespace App\Http\Controllers\Operation;

use App\Http\Controllers\Controller;
use App\Models\InternalMemo;
use App\Models\InternalMemoApproval;
use App\Models\User;
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
     * Temporary mode:
     * Approval workflow is hidden and every memo is auto-approved after save.
     */
    private const AUTO_APPROVE_MEMO = true;

    private const DEFAULT_ACKNOWLEDGEMENTS = [
        [
            'name' => 'Andres Dony Wijaya',
            'position' => 'Business Admin Manager',
        ],
        [
            'name' => 'Awalokita Garnierit',
            'position' => 'Academic Business Unit Head',
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
                'creator:id,name',
                'submitter:id,name',
                'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
                'approvals' => fn ($query) => $query->orderBy('step_order'),
                'approvals.approver:id,name',
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
                'approvals.approver:id,name',
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

        $memos = InternalMemo::query()
            ->with([
                'creator:id,name',
                'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
                'approvals' => fn ($query) => $query->orderBy('step_order'),
                'approvals.approver:id,name',
            ])
            ->whereIn('status', [
                self::STATUS_SUBMITTED,
                self::STATUS_WAITING_ACKNOWLEDGEMENT,
                self::STATUS_WAITING_APPROVAL,
            ])
            ->where(function ($query) {
                $query->whereHas('approvals', function ($approvalQuery) {
                    $approvalQuery->where('step_order', 1)
                        ->where('status', self::APPROVAL_PENDING)
                        ->where('approver_id', Auth::id());
                })->orWhere(function ($subQuery) {
                    $subQuery->whereHas('approvals', function ($approvalQuery) {
                        $approvalQuery->where('step_order', 2)
                            ->where('status', self::APPROVAL_PENDING)
                            ->where('approver_id', Auth::id());
                    })->whereHas('approvals', function ($approvalQuery) {
                        $approvalQuery->where('step_order', 1)
                            ->where('status', self::APPROVAL_APPROVED);
                    });
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
            'acknowledgementDefaults' => self::DEFAULT_ACKNOWLEDGEMENTS,
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

                'status' => self::AUTO_APPROVE_MEMO ? self::STATUS_APPROVED : self::STATUS_DRAFT,

                'created_by' => Auth::id(),
                'submitted_by' => self::AUTO_APPROVE_MEMO ? Auth::id() : null,
                'submitted_at' => self::AUTO_APPROVE_MEMO ? now() : null,
                'approved_at' => self::AUTO_APPROVE_MEMO ? now() : null,
            ]);

            $this->syncItems($memo, $validated['items']);
            $this->syncApprovals($memo, $validated);

            return $memo;
        });

        return redirect()
            ->route('internal-memos.show', $memo)
            ->with('success', self::AUTO_APPROVE_MEMO
                ? 'Internal memo berhasil dibuat dan otomatis approved.'
                : 'Internal memo berhasil dibuat sebagai draft.'
            );
    }

    public function show(InternalMemo $internalMemo): View
    {
        $internalMemo->load([
            'creator:id,name',
            'submitter:id,name',
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name',
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
            'acknowledgementDefaults' => self::DEFAULT_ACKNOWLEDGEMENTS,
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

                'status' => self::AUTO_APPROVE_MEMO ? self::STATUS_APPROVED : $internalMemo->status,
                'submitted_by' => self::AUTO_APPROVE_MEMO ? ($internalMemo->submitted_by ?: Auth::id()) : $internalMemo->submitted_by,
                'submitted_at' => self::AUTO_APPROVE_MEMO ? ($internalMemo->submitted_at ?: now()) : $internalMemo->submitted_at,
                'approved_at' => self::AUTO_APPROVE_MEMO ? ($internalMemo->approved_at ?: now()) : $internalMemo->approved_at,
                'rejected_at' => self::AUTO_APPROVE_MEMO ? null : $internalMemo->rejected_at,
                'cancelled_at' => null,
            ]);

            $this->syncItems($internalMemo, $validated['items']);
            $this->syncApprovals($internalMemo, $validated);
        });

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil diperbarui.');
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
        if (self::AUTO_APPROVE_MEMO) {
            return back()->with('error', 'Approval workflow sementara sedang di-hide. Memo otomatis approved saat disimpan.');
        }

        abort_unless($this->canSubmitMemo($internalMemo), 403, 'Memo ini tidak bisa disubmit.');

        $internalMemo->load(['items', 'approvals']);

        if ($internalMemo->items->isEmpty()) {
            return back()->with('error', 'Memo belum bisa disubmit karena budget item masih kosong.');
        }

        if ($internalMemo->approvals->count() < 2) {
            return back()->with('error', 'Memo belum bisa disubmit karena approval belum lengkap.');
        }

        DB::transaction(function () use ($internalMemo) {
            $internalMemo->approvals()->update([
                'status' => self::APPROVAL_PENDING,
                'notes' => null,
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

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil disubmit dan menunggu acknowledgement.');
    }

    public function approve(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        if (self::AUTO_APPROVE_MEMO) {
            return back()->with('error', 'Approval workflow sementara sedang di-hide. Memo otomatis approved saat disimpan.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $internalMemo->load(['approvals' => fn ($query) => $query->orderBy('step_order')]);

        $approval = $this->activeApproval($internalMemo);

        if (! $approval) {
            return back()->with('error', 'Tidak ada approval aktif untuk memo ini.');
        }

        abort_unless($this->canActOnApproval($approval), 403, 'Kamu bukan approver aktif untuk memo ini.');

        DB::transaction(function () use ($internalMemo, $approval, $validated) {
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
                $internalMemo->update([
                    'status' => $nextApproval->step_order === 2
                        ? self::STATUS_WAITING_APPROVAL
                        : self::STATUS_WAITING_ACKNOWLEDGEMENT,
                ]);

                return;
            }

            $internalMemo->update([
                'status' => self::STATUS_APPROVED,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);
        });

        return redirect()
            ->route('internal-memos.show', $internalMemo)
            ->with('success', 'Internal memo berhasil di-approve.');
    }

    public function reject(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        if (self::AUTO_APPROVE_MEMO) {
            return back()->with('error', 'Approval workflow sementara sedang di-hide. Memo otomatis approved saat disimpan.');
        }

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

        abort_unless($this->canActOnApproval($approval), 403, 'Kamu bukan approver aktif untuk memo ini.');

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
            'creator:id,name',
            'submitter:id,name',
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
            'approvals.approver:id,name',
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

            'acknowledgements' => ['required', 'array', 'size:2'],
            'acknowledgements.*.name' => ['required', 'string', 'max:255'],
            'acknowledgements.*.position' => ['required', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.details' => ['required', 'string', 'max:5000'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.remarks' => ['nullable', 'string', 'max:5000'],
        ], [
            'due_date.after_or_equal' => 'Due date tidak boleh lebih awal dari memo date.',
            'purpose.required' => 'Purpose wajib diisi minimal 2 poin.',
            'acknowledgements.required' => 'Acknowledgement signer wajib diisi.',
            'acknowledgements.size' => 'Acknowledgement signer harus berisi 2 orang.',
            'acknowledgements.*.name.required' => 'Nama acknowledgement signer wajib diisi.',
            'acknowledgements.*.position.required' => 'Jabatan acknowledgement signer wajib diisi.',
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

        $validated['acknowledgements'] = array_values(array_slice($validated['acknowledgements'], 0, 2));

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

        foreach (array_values($validated['acknowledgements'] ?? self::DEFAULT_ACKNOWLEDGEMENTS) as $index => $acknowledgement) {
            $memo->approvals()->create([
                'step_order' => $index + 1,
                'role_label' => 'Acknowledged by',
                'approver_id' => null,
                'approver_name' => $acknowledgement['name'] ?? self::DEFAULT_ACKNOWLEDGEMENTS[$index]['name'],
                'approver_position' => $acknowledgement['position'] ?? self::DEFAULT_ACKNOWLEDGEMENTS[$index]['position'],
                'status' => self::AUTO_APPROVE_MEMO ? self::APPROVAL_APPROVED : self::APPROVAL_PENDING,
                'approved_at' => self::AUTO_APPROVE_MEMO ? now() : null,
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
        if (self::AUTO_APPROVE_MEMO) {
            return null;
        }

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

    private function canEditMemo(InternalMemo $memo): bool
    {
        if (self::AUTO_APPROVE_MEMO) {
            return ! in_array($memo->status, [self::STATUS_CANCELLED], true);
        }

        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canDeleteMemo(InternalMemo $memo): bool
    {
        if (self::AUTO_APPROVE_MEMO) {
            return ! in_array($memo->status, [self::STATUS_CANCELLED], true);
        }

        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canSubmitMemo(InternalMemo $memo): bool
    {
        if (self::AUTO_APPROVE_MEMO) {
            return false;
        }

        return in_array($memo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    private function canActOnMemo(InternalMemo $memo): bool
    {
        if (self::AUTO_APPROVE_MEMO) {
            return false;
        }

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
            ->select('id', 'name')
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