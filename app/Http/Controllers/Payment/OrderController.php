<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Order::query()
            ->with([
                'student:id,full_name,email,phone',
                'groupRegistration:id,registration_number,buyer_type,buyer_student_id,company_id,batch_id,buyer_name,buyer_email,buyer_phone,quantity,wht_rate,wht_amount,invoice_total,net_payable,wht_status,status',
                'groupRegistration.company:id,name,tax_id,pic_name,pic_email,pic_phone',
                'batch:id,program_id,name,price,status',
                'batch.program:id,name',
                'workshop',
                'paymentSchedules',
                'payments',
            ])
            ->latest();

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->whereHas('student', function ($studentQuery) use ($keyword) {
                    $studentQuery
                        ->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });

                $q->orWhereHas('groupRegistration', function ($registrationQuery) use ($keyword) {
                    $registrationQuery
                        ->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('buyer_name', 'like', "%{$keyword}%")
                        ->orWhere('buyer_email', 'like', "%{$keyword}%")
                        ->orWhere('buyer_phone', 'like', "%{$keyword}%")
                        ->orWhereHas('company', function ($companyQuery) use ($keyword) {
                            $companyQuery
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('tax_id', 'like', "%{$keyword}%");
                        });
                });

                $q->orWhereHas('batch', function ($batchQuery) use ($keyword) {
                    $batchQuery->where('name', 'like', "%{$keyword}%");
                });

                $q->orWhereHas('batch.program', function ($programQuery) use ($keyword) {
                    $programQuery->where('name', 'like', "%{$keyword}%");
                });

                $this->applyWorkshopKeywordSearch($q, $keyword);
            });
        }

        $orders = $query
            ->paginate($perPage)
            ->withQueryString();

        $students = Student::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'phone']);

        $batches = Batch::query()
            ->with('program:id,name')
            ->whereIn('status', ['open', 'ongoing'])
            ->orderBy('name')
            ->get(['id', 'program_id', 'name', 'price', 'status']);

        $workshops = Workshop::query()
            ->latest()
            ->get();

        return view('payments.orders.index', compact(
            'orders',
            'students',
            'batches',
            'workshops'
        ));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'student:id,full_name,email,phone',
            'groupRegistration:id,registration_number,buyer_type,buyer_student_id,company_id,batch_id,buyer_name,buyer_email,buyer_phone,quantity,wht_rate,wht_amount,invoice_total,net_payable,wht_status,status',
            'groupRegistration.company:id,name,tax_id,pic_name,pic_email,pic_phone',
            'batch:id,program_id,name,price,status',
            'batch.program:id,name',
            'workshop',
            'paymentSchedules',
            'payments',
        ]);

        $groupRegistration = $order->groupRegistration;
        $customerName = $groupRegistration?->buyer_name
            ?? $order->student?->full_name;
        $customerEmail = $groupRegistration?->buyer_email
            ?? $order->student?->email;
        $customerPhone = $groupRegistration?->buyer_phone
            ?? $order->student?->phone;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'student_id' => $order->student_id,
                'group_registration_id' => $order->group_registration_id,
                'is_group_registration' => $groupRegistration !== null,
                'order_type' => $order->order_type ?: 'program',
                'batch_id' => $order->batch_id,
                'workshop_id' => $order->workshop_id,
                'original_price' => (float) $order->original_price,
                'discount' => (float) $order->discount,
                'final_price' => (float) $order->final_price,
                'status' => $order->status,
                'notes' => $order->notes,
                'student' => $order->student ? [
                    'id' => $order->student->id,
                    'full_name' => $order->student->full_name,
                    'email' => $order->student->email,
                    'phone' => $order->student->phone,
                ] : null,
                'customer' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                    'source' => $groupRegistration ? 'group_registration' : 'student',
                ],
                'group_registration' => $groupRegistration ? [
                    'id' => $groupRegistration->id,
                    'registration_number' => $groupRegistration->registration_number,
                    'buyer_type' => $groupRegistration->buyer_type,
                    'buyer_name' => $groupRegistration->buyer_name,
                    'buyer_email' => $groupRegistration->buyer_email,
                    'buyer_phone' => $groupRegistration->buyer_phone,
                    'quantity' => (int) $groupRegistration->quantity,
                    'invoice_total' => (float) $groupRegistration->invoice_total,
                    'net_payable' => (float) $groupRegistration->net_payable,
                    'wht_rate' => (float) $groupRegistration->wht_rate,
                    'wht_amount' => (float) $groupRegistration->wht_amount,
                    'wht_status' => $groupRegistration->wht_status,
                    'status' => $groupRegistration->status,
                    'company' => $groupRegistration->company ? [
                        'id' => $groupRegistration->company->id,
                        'name' => $groupRegistration->company->name,
                        'tax_id' => $groupRegistration->company->tax_id,
                    ] : null,
                ] : null,
                'batch' => $order->batch ? [
                    'id' => $order->batch->id,
                    'name' => $order->batch->name,
                    'price' => (float) $order->batch->price,
                    'status' => $order->batch->status,
                    'program' => $order->batch->program ? [
                        'id' => $order->batch->program->id,
                        'name' => $order->batch->program->name,
                    ] : null,
                ] : null,
                'workshop' => $order->workshop ? [
                    'id' => $order->workshop->id,
                    'title' => $this->getWorkshopTitle($order->workshop),
                    'price' => $this->getWorkshopPrice($order->workshop),
                ] : null,
                'payment_schedules' => $order->paymentSchedules,
                'payments' => $order->payments,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateOrderRequest($request);

        try {
            $order = DB::transaction(function () use ($validated) {
                $orderType = $validated['order_type'] ?? 'program';

                [$originalPrice, $batchId, $workshopId] = $this->resolveOrderSource($validated, $orderType);

                $discount = (float) ($validated['discount'] ?? 0);

                if ($discount > $originalPrice) {
                    throw $this->discountValidationException();
                }

                $finalPrice = max($originalPrice - $discount, 0);

                $order = Order::create([
                    'student_id' => $validated['student_id'],
                    'order_type' => $orderType,
                    'batch_id' => $batchId,
                    'workshop_id' => $workshopId,
                    'original_price' => $originalPrice,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                return $order->fresh([
                    'student:id,full_name,email,phone',
                    'groupRegistration.company',
                    'batch:id,program_id,name,price,status',
                    'batch.program:id,name',
                    'workshop',
                    'paymentSchedules',
                    'payments',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Group Registration orders must be updated from the Group Registration page.',
            ], 422);
        }

        $validated = $this->validateOrderRequest($request);

        try {
            $order = DB::transaction(function () use ($validated, $order) {
                $orderType = $validated['order_type'] ?? 'program';

                [$originalPrice, $batchId, $workshopId] = $this->resolveOrderSource($validated, $orderType);

                $discount = (float) ($validated['discount'] ?? 0);

                if ($discount > $originalPrice) {
                    throw $this->discountValidationException();
                }

                $finalPrice = max($originalPrice - $discount, 0);

                $order->update([
                    'student_id' => $validated['student_id'],
                    'order_type' => $orderType,
                    'batch_id' => $batchId,
                    'workshop_id' => $workshopId,
                    'original_price' => $originalPrice,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->syncPendingPaymentScheduleAmount($order, $finalPrice);

                return $order->fresh([
                    'student:id,full_name,email,phone',
                    'groupRegistration.company',
                    'batch:id,program_id,name,price,status',
                    'batch.program:id,name',
                    'workshop',
                    'paymentSchedules',
                    'payments',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'data' => $order,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Order $order): JsonResponse
    {
        if ($order->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Group Registration orders must be cancelled from the Group Registration page.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($order) {
                $hasPaidPayment = Payment::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'paid')
                    ->exists();

                if ($hasPaidPayment || $order->status === 'paid') {
                    $order->update([
                        'status' => 'cancelled',
                    ]);

                    PaymentSchedule::query()
                        ->where('order_id', $order->id)
                        ->where('status', '!=', 'paid')
                        ->update([
                            'status' => 'cancelled',
                        ]);

                    return;
                }

                Payment::query()
                    ->where('order_id', $order->id)
                    ->whereIn('status', ['pending', 'failed', 'expired', 'cancelled'])
                    ->delete();

                PaymentSchedule::query()
                    ->where('order_id', $order->id)
                    ->delete();

                $order->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    private function validateOrderRequest(Request $request): array
    {
        return $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id'),
            ],
            'order_type' => [
                'nullable',
                Rule::in(['program', 'workshop']),
            ],
            'batch_id' => [
                Rule::requiredIf(fn () => ($request->input('order_type', 'program') === 'program')),
                'nullable',
                'integer',
                Rule::exists('batches', 'id'),
            ],
            'workshop_id' => [
                Rule::requiredIf(fn () => ($request->input('order_type') === 'workshop')),
                'nullable',
                'integer',
                Rule::exists('workshops', 'id'),
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                Rule::in(['pending', 'partial', 'paid', 'cancelled']),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);
    }

    private function resolveOrderSource(array $validated, string $orderType): array
    {
        if ($orderType === 'workshop') {
            $workshop = Workshop::query()->findOrFail($validated['workshop_id']);

            return [
                $this->getWorkshopPrice($workshop),
                null,
                $workshop->id,
            ];
        }

        $batch = Batch::query()->findOrFail($validated['batch_id']);

        return [
            (float) $batch->price,
            $batch->id,
            null,
        ];
    }

    private function syncPendingPaymentScheduleAmount(Order $order, float $finalPrice): void
    {
        $paymentSchedule = PaymentSchedule::query()
            ->where('order_id', $order->id)
            ->oldest()
            ->first();

        if (! $paymentSchedule) {
            return;
        }

        if ($paymentSchedule->status === 'paid') {
            return;
        }

        $paymentSchedule->update([
            'amount' => $finalPrice,
        ]);
    }

    private function applyWorkshopKeywordSearch($query, string $keyword): void
    {
        if (! Schema::hasTable('workshops')) {
            return;
        }

        $searchableColumns = [];

        if (Schema::hasColumn('workshops', 'title')) {
            $searchableColumns[] = 'title';
        }

        if (Schema::hasColumn('workshops', 'name')) {
            $searchableColumns[] = 'name';
        }

        if (empty($searchableColumns)) {
            return;
        }

        $query->orWhereHas('workshop', function ($workshopQuery) use ($keyword, $searchableColumns) {
            $workshopQuery->where(function ($innerQuery) use ($keyword, $searchableColumns) {
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $innerQuery->where($column, 'like', "%{$keyword}%");
                    } else {
                        $innerQuery->orWhere($column, 'like', "%{$keyword}%");
                    }
                }
            });
        });
    }

    private function getWorkshopTitle(Workshop $workshop): string
    {
        return $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';
    }

    private function getWorkshopPrice(Workshop $workshop): float
    {
        return (float) (
            $workshop->price
            ?? $workshop->final_price
            ?? $workshop->registration_fee
            ?? 0
        );
    }

    private function discountValidationException(): \Illuminate\Validation\ValidationException
    {
        return \Illuminate\Validation\ValidationException::withMessages([
            'discount' => ['Discount cannot be greater than original price.'],
        ]);
    }
}