<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\PaymentGateway\XenditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected XenditService $xenditService
    ) {
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $students = Student::query()
            ->with([
                'user:id,name,email,user_type,role',
                'enrollments.batch.program',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $batches = Batch::query()
            ->with('program:id,name')
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get([
                'id',
                'program_id',
                'name',
                'start_date',
                'end_date',
                'price',
                'status',
            ]);

        $stats = [
            'total' => Student::count(),

            'active' => Student::where('status', 'active')->count(),

            'inactive' => Student::where('status', 'inactive')->count(),

            'enrolled' => StudentEnrollment::query()
                ->where('status', 'active')
                ->where('access_status', 'active')
                ->count(),

            'login_ready' => Student::query()
                ->whereNotNull('user_id')
                ->count(),
        ];

        return view('enrollment.students.index', compact(
            'students',
            'batches',
            'stats'
        ));
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,

                'nik' => $student->nik,
                'emergency_contact_name' => $student->emergency_contact_name,
                'emergency_contact_phone' => $student->emergency_contact_phone,
                'emergency_contact_relation' => $student->emergency_contact_relation,

                'city' => $student->city,
                'current_status' => $student->current_status,
                'goal' => $student->goal,
                'source' => $student->source,
                'status' => $student->status,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:students,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'nik' => [
                'nullable',
                'string',
                'regex:/^\d{16}$/',
            ],

            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'emergency_contact_relation' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'current_status' => [
                'nullable',
                'string',
                'max:255',
            ],

            'goal' => [
                'nullable',
                'string',
            ],

            'source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
            'initial_batch_id' => ['required', 'integer', 'exists:batches,id'],
            'discount_type' => ['required', Rule::in(['none', 'fixed', 'percentage'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_scheme' => ['required', Rule::in(['full', 'installment'])],
            'invoice_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'payment_notes' => ['nullable', 'string'],
            'payment_terms' => ['required', 'array', 'min:1', 'max:12'],
            'payment_terms.*.title' => ['required', 'string', 'max:255'],
            'payment_terms.*.amount' => ['required', 'numeric', 'gt:0'],
            'payment_terms.*.due_date' => ['required', 'date'],
        ], [
            'nik.regex' => 'NIK must contain exactly 16 digits.',
            'initial_batch_id.required' => 'Initial program and batch is required.',
            'payment_terms.required' => 'Payment schedule is required.',
            'payment_terms.*.amount.gt' => 'Every payment amount must be greater than zero.',
            'payment_terms.*.due_date.required' => 'Every payment due date is required.',
        ]);

        $batch = Batch::query()
            ->with('program:id,name')
            ->findOrFail($validated['initial_batch_id']);

        $originalPrice = round((float) $batch->price, 2);
        $discountValue = round((float) ($validated['discount_value'] ?? 0), 2);
        $discountAmount = match ($validated['discount_type']) {
            'percentage' => round($originalPrice * min($discountValue, 100) / 100, 2),
            'fixed' => min($discountValue, $originalPrice),
            default => 0.0,
        };
        $finalPrice = round(max($originalPrice - $discountAmount, 0), 2);

        if ($finalPrice <= 0) {
            throw ValidationException::withMessages([
                'final_price' => ['Final price must be greater than zero to create a payment.'],
            ]);
        }

        $paymentTerms = array_values($validated['payment_terms']);
        $expectedTermCount = $validated['payment_scheme'] === 'full' ? 1 : null;

        if ($expectedTermCount !== null && count($paymentTerms) !== $expectedTermCount) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Full payment must contain exactly one payment schedule.'],
            ]);
        }

        if ($validated['payment_scheme'] === 'installment' && count($paymentTerms) < 2) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Installment must contain at least two payment schedules.'],
            ]);
        }

        $termTotal = round(collect($paymentTerms)->sum(
            fn (array $term) => (float) $term['amount']
        ), 2);

        if (abs($termTotal - $finalPrice) > 0.009) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Total payment schedules must be equal to the final price.'],
            ]);
        }

        $dueDates = collect($paymentTerms)
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        if ($dueDates->sort()->values()->all() !== $dueDates->all()) {
            throw ValidationException::withMessages([
                'payment_terms' => ['Payment due dates must be ordered from earliest to latest.'],
            ]);
        }

        $studentFields = collect($validated)->only([
            'full_name',
            'email',
            'phone',
            'nik',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relation',
            'city',
            'current_status',
            'goal',
            'source',
            'status',
        ])->all();

        try {
            $result = DB::transaction(function () use (
                $studentFields,
                $validated,
                $batch,
                $originalPrice,
                $discountAmount,
                $finalPrice,
                $paymentTerms
            ) {
                $student = Student::create($studentFields);

                $order = Order::create([
                    'student_id' => $student->id,
                    'order_type' => 'program',
                    'batch_id' => $batch->id,
                    'workshop_id' => null,
                    'original_price' => $originalPrice,
                    'discount' => $discountAmount,
                    'final_price' => $finalPrice,
                    'status' => 'pending',
                    'notes' => $validated['payment_notes'] ?? null,
                ]);

                $order->setRelation('batch', $batch);
                $payments = collect();

                foreach ($paymentTerms as $index => $term) {
                    $paymentLabel = match (true) {
                        $validated['payment_scheme'] === 'full' => 'Full Payment',
                        $index === 0 => 'Down Payment (DP)',
                        default => 'Installment ' . ($index + 1),
                    };

                    $manualPaymentNotes = trim((string) ($validated['payment_notes'] ?? ''));
                    $paymentNotes = $manualPaymentNotes !== ''
                        ? $paymentLabel . ' - ' . $manualPaymentNotes
                        : $paymentLabel;

                    $schedule = PaymentSchedule::create([
                        'order_id' => $order->id,
                        'title' => $paymentLabel,
                        'amount' => round((float) $term['amount'], 2),
                        'due_date' => Carbon::parse($term['due_date'])->toDateString(),
                        'status' => 'pending',
                        'notes' => $validated['payment_notes'] ?? null,
                    ]);

                    $payment = Payment::create([
                        'order_id' => $order->id,
                        'payment_schedule_id' => $schedule->id,
                        'invoice_number' => $this->generateInvoiceNumber($order),
                        'public_token' => Str::uuid()->toString(),
                        'payment_url' => null,
                        'amount' => round((float) $term['amount'], 2),
                        'payment_date' => null,
                        'payment_method' => null,
                        'reference_number' => null,
                        'gateway_transaction_id' => null,
                        'gateway_provider' => null,
                        'gateway_payload' => null,
                        'status' => Payment::STATUS_PENDING,
                        'expired_at' => Carbon::parse($term['due_date'])
                            ->endOfDay()
                            ->addDays((int) $validated['invoice_expiry_days']),
                        'notes' => $paymentNotes,
                        'paid_at' => null,
                    ]);

                    $payment->setRelation('paymentSchedule', $schedule);
                    $payments->push($payment);
                }

                return compact('student', 'order', 'payments');
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create student and initial payment transaction.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        $order = $result['order'];
        $order->loadMissing([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name',
            'batch.program:id,name',
        ]);

        foreach ($result['payments'] as $payment) {
            $this->attachXenditPaymentLink(
                $payment,
                $order,
                $payment->paymentSchedule
            );
        }

        $student = $result['student'];
        $payments = $result['payments']->map(fn (Payment $payment) => $payment->fresh());
        $linkFailureCount = $payments->filter(fn (Payment $payment) => empty($payment->payment_url))->count();

        return response()->json([
            'success' => true,
            'message' => $linkFailureCount > 0
                ? 'Student and payment data created. Some payment links could not be generated and can be retried from Payments.'
                : 'Student, sales order, payment schedules, and payment links created successfully.',
            'data' => [
                'student' => $student,
                'order' => $order->fresh(),
                'payments' => $payments,
                'payment_link_failures' => $linkFailureCount,
            ],
        ], 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($student->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'nik' => [
                'nullable',
                'string',
                'regex:/^\d{16}$/',
            ],

            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'emergency_contact_relation' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'current_status' => [
                'nullable',
                'string',
                'max:255',
            ],

            'goal' => [
                'nullable',
                'string',
            ],

            'source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ], [
            'nik.regex' => 'NIK must contain exactly 16 digits.',
        ]);

        $student->update($validated);

        if ($student->user) {
            $student->user->forceFill([
                'name' => $student->full_name,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data' => $student,
        ]);
    }

    public function enroll(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => [
                'required',
                'exists:batches,id',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'completed',
                    'cancelled',
                    'on_hold',
                ]),
            ],

            'access_status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'expired',
                ]),
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'access_expires_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'create_user_account' => [
                'required',
                'boolean',
            ],

            'password' => [
                'nullable',
                'string',
                'min:6',
            ],
        ]);

        try {
            DB::transaction(function () use ($student, $validated) {
                $batch = Batch::query()
                    ->select([
                        'id',
                        'program_id',
                        'name',
                    ])
                    ->findOrFail($validated['batch_id']);

                if ((bool) $validated['create_user_account']) {
                    $this->createOrLinkStudentUser($student, $validated);
                }

                $enrollment = StudentEnrollment::query()->firstOrNew([
                    'student_id' => $student->id,
                    'batch_id' => $batch->id,
                ]);

                $enrollment->fill([
                    'program_id' => $batch->program_id,
                    'status' => $validated['status'],
                    'access_status' => $validated['access_status'],
                    'enrollment_source' => 'manual',
                    'enrolled_at' => $validated['enrolled_at'] ?? now(),

                    'started_at' => $validated['status'] === 'active'
                        ? ($enrollment->started_at ?? now())
                        : $enrollment->started_at,

                    'completed_at' => $validated['status'] === 'completed'
                        ? ($enrollment->completed_at ?? now())
                        : null,

                    'access_expires_at' => $validated['access_expires_at'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                if (!$enrollment->exists) {
                    $enrollment->created_by = auth()->id();
                }

                $enrollment->save();

                if ($validated['status'] === 'active' && $student->status !== 'active') {
                    $student->update([
                        'status' => 'active',
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll student.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
        ]);
    }

    private function createOrLinkStudentUser(Student $student, array $validated): void
    {
        if (empty($student->email)) {
            throw ValidationException::withMessages([
                'create_user_account' => [
                    'Student must have an email before LMS login account can be created.',
                ],
            ]);
        }

        $existingUser = User::query()
            ->where('email', $student->email)
            ->first();

        if ($existingUser && !$this->isAllowedStudentUser($existingUser)) {
            throw ValidationException::withMessages([
                'create_user_account' => [
                    'Email already belongs to non-student user account.',
                ],
            ]);
        }

        $password = $validated['password'] ?: 'password';

        if ($existingUser) {
            $user = $existingUser;

            $userPayload = [
                'name' => $student->full_name,
            ];

            if (Schema::hasColumn('users', 'user_type')) {
                $userPayload['user_type'] = 'student';
            }

            if (Schema::hasColumn('users', 'role')) {
                $userPayload['role'] = 'student';
            }

            $user->forceFill($userPayload)->save();
        } else {
            $userPayload = [
                'name' => $student->full_name,
                'email' => $student->email,
                'password' => Hash::make($password),
            ];

            if (Schema::hasColumn('users', 'user_type')) {
                $userPayload['user_type'] = 'student';
            }

            if (Schema::hasColumn('users', 'role')) {
                $userPayload['role'] = 'student';
            }

            $user = new User();
            $user->forceFill($userPayload)->save();
        }

        if ((int) $student->user_id !== (int) $user->id) {
            $student->update([
                'user_id' => $user->id,
            ]);
        }
    }

    private function isAllowedStudentUser(User $user): bool
    {
        $userType = $user->user_type ?? null;
        $role = $user->role ?? null;

        if ($userType === 'student') {
            return true;
        }

        if ($role === 'student') {
            return true;
        }

        return false;
    }

    private function attachXenditPaymentLink(
        Payment $payment,
        Order $order,
        PaymentSchedule $paymentSchedule
    ): void {
        try {
            $student = $order->student;
            $batch = $order->batch;
            $program = $batch?->program;

            $result = $this->xenditService->createPaymentLink($payment, [
                'full_name' => $student?->full_name,
                'email' => $student?->email,
                'phone' => $student?->phone,
                'program_name' => $program?->name,
                'batch_name' => $batch?->name,
                'item_name' => $paymentSchedule->title,
            ]);

            $payment->update([
                'payment_url' => $result['payment_url'] ?? null,
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'gateway_provider' => $result['gateway_provider'] ?? 'xendit',
                'gateway_payload' => $result['gateway_payload'] ?? null,
                'expired_at' => !empty($result['expired_at'])
                    ? Carbon::parse($result['expired_at'])
                    : $payment->expired_at,
            ]);
        } catch (Throwable $e) {
            report($e);

            $payment->update([
                'gateway_provider' => 'xendit',
                'gateway_payload' => [
                    'error' => $e->getMessage(),
                    'retryable' => true,
                ],
            ]);
        }
    }

    private function generateInvoiceNumber(Order $order): string
    {
        $batchCode = $this->resolveInvoiceBatchCode($order);
        $programCode = $this->resolveInvoiceProgramCode($order);
        $monthCode = now()->format('Ym');
        $sequencePrefix = 'FLX-' . $batchCode . '-' . $programCode . '-' . $monthCode;
        $documentPrefix = $sequencePrefix . '-';
        $pattern = '/^' . preg_quote($sequencePrefix, '/') . '(?:\d{2})?-(\d+)$/';

        $maxSequence = Payment::query()
            ->where(function ($query) use ($documentPrefix, $sequencePrefix) {
                $query
                    ->where('invoice_number', 'like', $documentPrefix . '%')
                    ->orWhere('invoice_number', 'like', $sequencePrefix . '__-%');
            })
            ->lockForUpdate()
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($pattern) {
                return preg_match($pattern, (string) $invoiceNumber, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?: 0;

        return $documentPrefix . str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function resolveInvoiceBatchCode(Order $order): string
    {
        $batchName = (string) data_get($order, 'batch.name', '');
        $batchId = (int) data_get($order, 'batch.id', 0);

        if (preg_match('/\bbatch\s*0*(\d+)\b/i', $batchName, $matches)) {
            return 'B' . ((int) $matches[1]);
        }

        if (preg_match('/\bb\s*0*(\d+)\b/i', $batchName, $matches)) {
            return 'B' . ((int) $matches[1]);
        }

        return 'B' . max(1, $batchId);
    }

    private function resolveInvoiceProgramCode(Order $order): string
    {
        $programName = (string) data_get($order, 'batch.program.name', '');
        $normalized = Str::of($programName)
            ->lower()
            ->replace(['/', '-', '&'], ' ')
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        if (Str::contains($normalized, ['software engineer', 'software engineering'])) {
            return 'SE';
        }

        if (Str::contains($normalized, 'artificial intelligence') || preg_match('/\bai\b/', $normalized)) {
            return 'AI';
        }

        if (Str::contains($normalized, [
            'ui ux',
            'ui ux design',
            'ui ux designer',
            'user interface user experience',
            'user experience design',
            'design',
        ])) {
            return 'DS';
        }

        $words = collect(explode(' ', $normalized))->filter()->values();
        $code = $words
            ->map(fn ($word) => Str::upper(Str::substr((string) $word, 0, 1)))
            ->implode('');

        if (strlen($code) < 2) {
            $code = Str::upper(Str::substr(
                preg_replace('/[^A-Za-z0-9]/', '', $programName) ?: 'FLX',
                0,
                3
            ));
        }

        return Str::substr($code, 0, 3) ?: 'FLX';
    }
}