<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Company;
use App\Models\GroupRegistration;
use App\Models\Student;
use App\Services\GroupRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GroupRegistrationController extends Controller
{
    public function __construct(
        protected GroupRegistrationService $groupRegistrationService
    ) {
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = GroupRegistration::query()
            ->with([
                'company:id,name,pic_name,pic_email,pic_phone',
                'buyerStudent:id,full_name,email,phone',
                'batch:id,program_id,name,start_date,end_date',
                'batch.program:id,name',
                'order:id,group_registration_id,final_price,status',
            ])
            ->withCount([
                'activeParticipants as assigned_seats',
            ])
            ->latest();

        if ($request->filled('buyer_type')) {
            $query->where('buyer_type', $request->string('buyer_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int) $request->get('batch_id'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->get('keyword'));

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('registration_number', 'like', "%{$keyword}%")
                    ->orWhere('buyer_name', 'like', "%{$keyword}%")
                    ->orWhere('buyer_email', 'like', "%{$keyword}%")
                    ->orWhereHas('company', function ($companyQuery) use ($keyword) {
                        $companyQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('batch', function ($batchQuery) use ($keyword) {
                        $batchQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('batch.program', function ($programQuery) use ($keyword) {
                        $programQuery->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        $groupRegistrations = $query
            ->paginate($perPage)
            ->withQueryString();

        $batches = Batch::query()
            ->with('program:id,name')
            ->orderByDesc('start_date')
            ->get(['id', 'program_id', 'name', 'start_date', 'status']);

        $stats = [
            'total' => GroupRegistration::count(),
            'pending' => GroupRegistration::where('status', GroupRegistration::STATUS_PENDING)->count(),
            'confirmed' => GroupRegistration::where('status', GroupRegistration::STATUS_CONFIRMED)->count(),
            'company' => GroupRegistration::where('buyer_type', GroupRegistration::BUYER_COMPANY)->count(),
        ];

        return view('group-registrations.index', compact(
            'groupRegistrations',
            'batches',
            'stats'
        ));
    }

    public function create(): View
    {
        return view('group-registrations.create', $this->formOptions());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCreateRequest($request);

        try {
            $groupRegistration = $this->groupRegistrationService->create(
                $validated,
                $request->user()
            );

            $payments = $groupRegistration->order
                ? $groupRegistration->order->paymentSchedules->flatMap(
                    fn ($schedule) => $schedule->payments
                )
                : collect();
            $linkFailureCount = $payments
                ->filter(fn ($payment) => empty($payment->payment_url))
                ->count();

            return response()->json([
                'success' => true,
                'message' => $linkFailureCount > 0
                    ? "Group Registration created. {$linkFailureCount} payment link(s) need to be retried."
                    : 'Group Registration created successfully.',
                'data' => [
                    'id' => $groupRegistration->id,
                    'registration_number' => $groupRegistration->registration_number,
                    'redirect_url' => route('group-registrations.show', $groupRegistration),
                    'link_failure_count' => $linkFailureCount,
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Group Registration.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(GroupRegistration $groupRegistration): View
    {
        $groupRegistration->load([
            'company',
            'buyerStudent:id,full_name,email,phone',
            'batch.program',
            'participants.student:id,full_name,email,phone,status',
            'participants.studentEnrollment',
            'order.paymentSchedules.payments',
            'createdBy:id,name,email',
            'updatedBy:id,name,email',
        ]);

        return view('group-registrations.show', compact('groupRegistration'));
    }

    public function edit(GroupRegistration $groupRegistration): View
    {
        $groupRegistration->load([
            'company',
            'buyerStudent:id,full_name,email,phone',
            'batch.program',
            'participants.student:id,full_name,email,phone,status',
            'order.paymentSchedules.payments',
        ]);

        return view('group-registrations.edit', array_merge(
            $this->formOptions(),
            compact('groupRegistration')
        ));
    }

    public function update(Request $request, GroupRegistration $groupRegistration): JsonResponse
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $groupRegistration = $this->groupRegistrationService->updateMetadata(
            $groupRegistration,
            $validated,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group Registration updated successfully.',
            'data' => $groupRegistration,
        ]);
    }

    public function destroy(Request $request, GroupRegistration $groupRegistration): JsonResponse
    {
        $groupRegistration = $this->groupRegistrationService->cancel(
            $groupRegistration,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Group Registration cancelled successfully.',
            'data' => $groupRegistration,
        ]);
    }

    private function validateCreateRequest(Request $request): array
    {
        $validated = $request->validate([
            'buyer_type' => ['required', Rule::in([
                GroupRegistration::BUYER_INDIVIDUAL,
                GroupRegistration::BUYER_COMPANY,
            ])],
            'buyer_student_id' => ['exclude_unless:buyer_type,individual', 'nullable', 'integer', 'exists:students,id'],
            'buyer_name' => ['exclude_unless:buyer_type,individual', 'nullable', 'string', 'max:255'],
            'buyer_email' => ['exclude_unless:buyer_type,individual', 'nullable', 'email', 'max:255'],
            'buyer_phone' => ['exclude_unless:buyer_type,individual', 'nullable', 'string', 'max:30'],
            'company_id' => ['exclude_unless:buyer_type,company', 'nullable', 'integer', 'exists:companies,id'],
            'company' => ['exclude_unless:buyer_type,company', 'nullable', 'array'],
            'company.name' => ['exclude_unless:buyer_type,company', 'nullable', 'string', 'max:255'],
            'company.tax_id' => ['exclude_unless:buyer_type,company', 'nullable', 'string', 'max:32', 'unique:companies,tax_id'],
            'company.email' => ['exclude_unless:buyer_type,company', 'nullable', 'email', 'max:255'],
            'company.phone' => ['exclude_unless:buyer_type,company', 'nullable', 'string', 'max:30'],
            'company.address' => ['exclude_unless:buyer_type,company', 'nullable', 'string'],
            'company.pic_name' => ['exclude_unless:buyer_type,company', 'nullable', 'string', 'max:255'],
            'company.pic_email' => ['exclude_unless:buyer_type,company', 'nullable', 'email', 'max:255'],
            'company.pic_phone' => ['exclude_unless:buyer_type,company', 'nullable', 'string', 'max:30'],
            'company.notes' => ['exclude_unless:buyer_type,company', 'nullable', 'string'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'quantity' => ['required', 'integer', 'min:2', 'max:1000'],
            'discount_type' => ['required', Rule::in(['none', 'fixed', 'percentage'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_scheme' => ['required', Rule::in(['full', 'installment'])],
            'invoice_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'payment_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'payment_terms' => ['required', 'array', 'min:1', 'max:12'],
            'payment_terms.*.amount' => ['required', 'numeric', 'gt:0'],
            'payment_terms.*.due_date' => ['required', 'date'],
            'participants' => ['nullable', 'array'],
            'participants.*.student_id' => [
                'required',
                'integer',
                'distinct',
                'exists:students,id',
            ],
        ]);

        $errors = [];

        if ($validated['buyer_type'] === GroupRegistration::BUYER_INDIVIDUAL
            && blank($validated['buyer_name'] ?? null)) {
            $errors['buyer_name'][] = 'Buyer name is required for an individual buyer.';
        }

        if ($validated['buyer_type'] === GroupRegistration::BUYER_COMPANY
            && empty($validated['company_id'])
            && blank(data_get($validated, 'company.name'))) {
            $errors['company.name'][] = 'Select an existing company or enter a new company name.';
        }

        if (count($validated['participants'] ?? []) > (int) $validated['quantity']) {
            $errors['participants'][] = 'Assigned participants cannot exceed purchased seats.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    private function formOptions(): array
    {
        return [
            'students' => Student::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email', 'phone', 'status']),
            'companies' => Company::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'tax_id',
                    'email',
                    'phone',
                    'pic_name',
                    'pic_email',
                    'pic_phone',
                ]),
            'batches' => Batch::query()
                ->with('program:id,name')
                ->whereIn('status', ['open', 'ongoing'])
                ->orderByDesc('start_date')
                ->get([
                    'id',
                    'program_id',
                    'name',
                    'start_date',
                    'end_date',
                    'quota',
                    'price',
                    'status',
                ]),
        ];
    }
}