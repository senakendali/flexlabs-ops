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
                        ->orWhere('from_name', 'like', "%{$search}%");
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
                        ->orWhere('to_name', 'like', "%{$search}%");
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
                'from_name' => Auth::user()?->name,
                'from_position' => $this->resolveUserPosition(Auth::user()),
                'tax_rate' => 11,
                'notes' => "Payment prices may change depending on the promo period.\nPayments can be made through Bank Mandiri.",
            ]),
            'users' => $this->userOptions(),
            'acknowledgementDefaults' => self::DEFAULT_ACKNOWLEDGEMENTS,
            'statuses' => $this->memoStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMemo($request);
        $amounts = $this->calculateAmounts($validated['items'], (float) ($validated['tax_rate'] ?? 0));

        $memo = DB::transaction(function () use ($validated, $amounts) {
            $memo = InternalMemo::create([
                'memo_number' => $this->generateMemoNumber(),
                'memo_date' => $validated['memo_date'],
                'subject' => $validated['subject'],
                'attachment_label' => $validated['attachment_label'] ?? null,
                'to_name' => $validated['to_name'],
                'to_position' => $validated['to_position'] ?? null,
                'from_name' => $validated['from_name'],
                'from_position' => $validated['from_position'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_rate' => $amounts['tax_rate'],
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
        ]);
    }

    public function edit(InternalMemo $internalMemo): View
    {
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa diedit karena sudah masuk approval atau sudah final.');

        $internalMemo->load([
            'items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'approvals' => fn ($query) => $query->orderBy('step_order'),
        ]);

        return view('operation.internal-memos.edit', [
            'memo' => $internalMemo,
            'users' => $this->userOptions(),
            'acknowledgementDefaults' => self::DEFAULT_ACKNOWLEDGEMENTS,
            'statuses' => $this->memoStatuses(),
        ]);
    }

    public function update(Request $request, InternalMemo $internalMemo): RedirectResponse
    {
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa diedit karena sudah masuk approval atau sudah final.');

        $validated = $this->validateMemo($request, $internalMemo);
        $amounts = $this->calculateAmounts($validated['items'], (float) ($validated['tax_rate'] ?? 0));

        DB::transaction(function () use ($internalMemo, $validated, $amounts) {
            $internalMemo->update([
                'memo_date' => $validated['memo_date'],
                'subject' => $validated['subject'],
                'attachment_label' => $validated['attachment_label'] ?? null,
                'to_name' => $validated['to_name'],
                'to_position' => $validated['to_position'] ?? null,
                'from_name' => $validated['from_name'],
                'from_position' => $validated['from_position'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal_amount' => $amounts['subtotal_amount'],
                'tax_rate' => $amounts['tax_rate'],
                'tax_amount' => $amounts['tax_amount'],
                'grand_total_amount' => $amounts['grand_total_amount'],
                'status' => self::AUTO_APPROVE_MEMO ? self::STATUS_APPROVED : $internalMemo->status,
                'approved_at' => self::AUTO_APPROVE_MEMO ? ($internalMemo->approved_at ?: now()) : $internalMemo->approved_at,
                'rejected_at' => self::AUTO_APPROVE_MEMO ? null : $internalMemo->rejected_at,
                'cancelled_at' => self::AUTO_APPROVE_MEMO ? null : $internalMemo->cancelled_at,
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
        abort_unless($this->canEditMemo($internalMemo), 403, 'Memo ini tidak bisa dihapus karena sudah masuk approval atau sudah final.');

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

        if (self::AUTO_APPROVE_MEMO) {
            DB::transaction(function () use ($internalMemo) {
                $internalMemo->approvals()->update([
                    'status' => self::APPROVAL_APPROVED,
                    'notes' => null,
                    'approved_at' => now(),
                    'rejected_at' => null,
                ]);

                $internalMemo->update([
                    'status' => self::STATUS_APPROVED,
                    'submitted_by' => Auth::id(),
                    'submitted_at' => $internalMemo->submitted_at ?: now(),
                    'approved_at' => now(),
                    'rejected_at' => null,
                    'cancelled_at' => null,
                ]);
            });

            return redirect()
                ->route('internal-memos.show', $internalMemo)
                ->with('success', 'Internal memo berhasil disubmit dan otomatis approved.');
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
            in_array($internalMemo->status, [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_WAITING_ACKNOWLEDGEMENT, self::STATUS_WAITING_APPROVAL], true),
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
        return $request->validate([
            'memo_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'attachment_label' => ['nullable', 'string', 'max:255'],
            'to_name' => ['required', 'string', 'max:255'],
            'to_position' => ['nullable', 'string', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'from_position' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'acknowledgements' => ['nullable', 'array'],
            'acknowledgements.*.name' => ['nullable', 'string', 'max:255'],
            'acknowledgements.*.position' => ['nullable', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.details' => ['required', 'string', 'max:5000'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.remarks' => ['nullable', 'string', 'max:5000'],
        ], [
            'items.required' => 'Minimal harus ada 1 budget item.',
            'items.*.details.required' => 'Detail budget item wajib diisi.',
        ]);
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
        $acknowledgements = $this->normalizeAcknowledgements($validated['acknowledgements'] ?? []);

        $memo->approvals()->delete();

        foreach ($acknowledgements as $index => $acknowledgement) {
            $memo->approvals()->create([
                'step_order' => $index + 1,
                'role_label' => 'Acknowledged by',
                'approver_id' => null,
                'approver_name' => $acknowledgement['name'],
                'approver_position' => $acknowledgement['position'],
                'status' => self::AUTO_APPROVE_MEMO ? self::APPROVAL_APPROVED : self::APPROVAL_PENDING,
                'approved_at' => self::AUTO_APPROVE_MEMO ? now() : null,
                'rejected_at' => null,
            ]);
        }
    }

    private function normalizeAcknowledgements(array $acknowledgements): array
    {
        $normalized = [];

        foreach (self::DEFAULT_ACKNOWLEDGEMENTS as $index => $default) {
            $row = $acknowledgements[$index] ?? [];

            $normalized[] = [
                'name' => trim((string) ($row['name'] ?? '')) ?: $default['name'],
                'position' => trim((string) ($row['position'] ?? '')) ?: $default['position'],
            ];
        }

        return $normalized;
    }

    private function calculateAmounts(array $items, float $taxRate): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            return (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1);
        });

        $taxAmount = $subtotal * ($taxRate / 100);

        return [
            'subtotal_amount' => round($subtotal, 2),
            'tax_rate' => round($taxRate, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total_amount' => round($subtotal + $taxAmount, 2),
        ];
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

    private function canEditMemo(InternalMemo $memo): bool
    {
        if (self::AUTO_APPROVE_MEMO) {
            return $memo->status !== self::STATUS_CANCELLED;
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
}
