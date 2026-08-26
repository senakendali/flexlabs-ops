<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\PaymentGateway\XenditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentScheduleController extends Controller
{
    public function __construct(
        protected XenditService $xenditService
    ) {
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $paymentSchedulesQuery = PaymentSchedule::query()
            ->with([
                'order:id,student_id,group_registration_id,batch_id,workshop_id,order_type,final_price,status',
                'order.student:id,full_name,email,phone',
                'order.groupRegistration:id,registration_number,buyer_type,company_id,buyer_name,buyer_email,buyer_phone,quantity,wht_rate,wht_amount,invoice_total,net_payable,wht_status,status',
                'order.groupRegistration.company:id,name,tax_id,pic_name,pic_email,pic_phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
                'order.workshop',
                'order.paymentSchedules:id,order_id,amount,gross_amount,wht_rate,wht_amount,net_amount,status',
            ])
            ->latest();

        if ($request->filled('order_type')) {
            $paymentSchedulesQuery->whereHas('order', function ($orderQuery) use ($request) {
                $orderQuery->where('order_type', $request->order_type);
            });
        }

        if ($request->filled('status')) {
            $paymentSchedulesQuery->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $paymentSchedulesQuery->where(function ($query) use ($keyword) {
                $query
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('notes', 'like', "%{$keyword}%")
                    ->orWhereHas('order.student', function ($studentQuery) use ($keyword) {
                        $studentQuery
                            ->where('full_name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('order.groupRegistration', function ($registrationQuery) use ($keyword) {
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
                    })
                    ->orWhereHas('order.batch', function ($batchQuery) use ($keyword) {
                        $batchQuery->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('order.batch.program', function ($programQuery) use ($keyword) {
                        $programQuery->where('name', 'like', "%{$keyword}%");
                    });

                $this->applyWorkshopKeywordSearch($query, $keyword);
            });
        }

        $paymentSchedules = $paymentSchedulesQuery
            ->paginate($perPage)
            ->withQueryString();

        $orders = Order::query()
            ->with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name',
                'batch.program:id,name',
                'workshop',
                'paymentSchedules:id,order_id,amount,status',
            ])
            ->withSum('paymentSchedules', 'amount')
            ->whereNull('group_registration_id')
            ->whereIn('status', ['pending', 'partial'])
            ->orderByDesc('id')
            ->get([
                'id',
                'student_id',
                'batch_id',
                'workshop_id',
                'order_type',
                'final_price',
                'status',
            ]);

        return view('payments.schedules.index', compact(
            'paymentSchedules',
            'orders'
        ));
    }

    public function show(PaymentSchedule $paymentSchedule): JsonResponse
    {
        $paymentSchedule->load([
            'order:id,student_id,group_registration_id,batch_id,workshop_id,order_type,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.groupRegistration:id,registration_number,buyer_type,company_id,buyer_name,buyer_email,buyer_phone,quantity,wht_rate,wht_amount,invoice_total,net_payable,wht_status,status',
            'order.groupRegistration.company:id,name,tax_id,pic_name,pic_email,pic_phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'order.workshop',
            'order.paymentSchedules:id,order_id,amount,gross_amount,wht_rate,wht_amount,net_amount,status',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $paymentSchedule->id,
                'order_id' => $paymentSchedule->order_id,
                'title' => $paymentSchedule->title,
                'amount' => (float) $paymentSchedule->amount,
                'gross_amount' => (float) ($paymentSchedule->gross_amount ?? $paymentSchedule->amount),
                'wht_rate' => (float) $paymentSchedule->wht_rate,
                'wht_amount' => (float) $paymentSchedule->wht_amount,
                'net_amount' => (float) ($paymentSchedule->net_amount ?? $paymentSchedule->amount),
                'due_date' => optional($paymentSchedule->due_date)->format('Y-m-d'),
                'status' => $paymentSchedule->status,
                'notes' => $paymentSchedule->notes,
                'order' => $this->formatOrderData($paymentSchedule->order),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateScheduleRequest($request);

        $targetOrder = Order::query()->findOrFail($validated['order_id']);

        if ($targetOrder->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Group Registration schedules are generated and managed from the Group Registration page.',
            ], 422);
        }

        try {
            $paymentSchedule = DB::transaction(function () use ($validated) {
                $paymentSchedule = PaymentSchedule::create([
                    'order_id' => $validated['order_id'],
                    'title' => $validated['title'],
                    'amount' => $validated['amount'],
                    'gross_amount' => $validated['amount'],
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'net_amount' => $validated['amount'],
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->syncOrderStatus($paymentSchedule->order_id);

                return $paymentSchedule->fresh([
                    'order:id,student_id,batch_id,workshop_id,order_type,final_price,status',
                    'order.student:id,full_name,email,phone',
                    'order.batch:id,program_id,name',
                    'order.batch.program:id,name',
                    'order.workshop',
                    'order.paymentSchedules:id,order_id,amount,status',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment schedule created successfully.',
                'data' => $paymentSchedule,
            ], 201);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment schedule.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request, PaymentSchedule $paymentSchedule): JsonResponse
    {
        $paymentSchedule->loadMissing('order:id,group_registration_id');

        if ($paymentSchedule->order?->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Group Registration schedules must be updated from the Group Registration page.',
            ], 422);
        }

        $validated = $this->validateScheduleRequest($request);

        $targetOrder = Order::query()->findOrFail($validated['order_id']);

        if ($targetOrder->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'A manual schedule cannot be moved to a Group Registration order.',
            ], 422);
        }

        $oldDueDate = $paymentSchedule->due_date
            ? Carbon::parse($paymentSchedule->due_date)->toDateString()
            : null;
        $newDueDate = !empty($validated['due_date'])
            ? Carbon::parse($validated['due_date'])->toDateString()
            : null;
        $amountChanged = (float) $paymentSchedule->amount
            !== (float) $validated['amount'];
        $dueDateChanged = $oldDueDate !== $newDueDate;
        $gatewayDataChanged = $amountChanged || $dueDateChanged;
        $newExpiredAt = $newDueDate
            ? Carbon::parse($newDueDate)->endOfDay()
            : null;

        $linkedPayments = collect();

        if ($gatewayDataChanged) {
            $linkedPayments = Payment::query()
                ->where('payment_schedule_id', $paymentSchedule->id)
                ->where('status', '!=', 'paid')
                ->get();

            $hasPaidPayment = Payment::query()
                ->where('payment_schedule_id', $paymentSchedule->id)
                ->where('status', 'paid')
                ->exists();

            if ($hasPaidPayment || $paymentSchedule->status === 'paid') {
                throw ValidationException::withMessages([
                    $amountChanged ? 'amount' : 'due_date' => [
                        'Amount or due date cannot be changed because this payment schedule already has a paid payment.',
                    ],
                ]);
            }
        }

        try {
            /*
            |------------------------------------------------------------------
            | Expire active Xendit invoices before changing local gateway data
            |------------------------------------------------------------------
            */
            if ($gatewayDataChanged) {
                foreach ($linkedPayments as $linkedPayment) {
                    if (
                        $linkedPayment->status === 'pending'
                        && !empty($linkedPayment->gateway_transaction_id)
                    ) {
                        $this->xenditService->expirePaymentLink(
                            $linkedPayment
                        );
                    }
                }
            }

            $paymentSnapshots = $linkedPayments
                ->map(fn (Payment $linkedPayment) => [
                    'id' => $linkedPayment->id,
                    'had_gateway_invoice' => filled($linkedPayment->payment_url)
                        || filled($linkedPayment->gateway_transaction_id),
                ])
                ->values();

            $paymentSchedule = DB::transaction(function () use (
                $validated,
                $paymentSchedule,
                $gatewayDataChanged,
                $newExpiredAt
            ) {
                $oldOrderId = $paymentSchedule->order_id;

                $paymentSchedule->update([
                    'order_id' => $validated['order_id'],
                    'title' => $validated['title'],
                    'amount' => $validated['amount'],
                    'gross_amount' => $validated['amount'],
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'net_amount' => $validated['amount'],
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($gatewayDataChanged) {
                    Payment::query()
                        ->where('payment_schedule_id', $paymentSchedule->id)
                        ->where('status', '!=', 'paid')
                        ->update([
                            'order_id' => $paymentSchedule->order_id,
                            'amount' => $validated['amount'],
                            'payment_url' => null,
                            'gateway_transaction_id' => null,
                            'gateway_provider' => null,
                            'gateway_payload' => null,
                            'status' => 'pending',
                            'expired_at' => $newExpiredAt,
                        ]);
                }

                $this->syncOrderStatus($oldOrderId);

                if ((int) $oldOrderId !== (int) $paymentSchedule->order_id) {
                    $this->syncOrderStatus($paymentSchedule->order_id);
                }

                return $paymentSchedule->fresh([
                    'order:id,student_id,batch_id,workshop_id,order_type,final_price,status',
                    'order.student:id,full_name,email,phone',
                    'order.batch:id,program_id,name',
                    'order.batch.program:id,name',
                    'order.workshop',
                    'order.paymentSchedules:id,order_id,amount,status',
                ]);
            });

            if ($gatewayDataChanged && $paymentSnapshots->isNotEmpty()) {
                $this->regenerateXenditPaymentLinks(
                    $paymentSchedule,
                    $paymentSnapshots->all()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment schedule updated successfully.',
                'data' => $paymentSchedule,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment schedule.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    private function regenerateXenditPaymentLinks(
        PaymentSchedule $paymentSchedule,
        array $paymentSnapshots
    ): void {
        $order = Order::query()
            ->with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name',
                'batch.program:id,name',
                'workshop',
            ])
            ->find($paymentSchedule->order_id);

        if (! $order) {
            return;
        }

        foreach ($paymentSnapshots as $snapshot) {
            $payment = Payment::query()->find($snapshot['id']);

            if (! $payment || $payment->status !== 'pending') {
                continue;
            }

            $customerData = [
                'full_name' => $order->student?->full_name,
                'email' => $order->student?->email,
                'phone' => $order->student?->phone,
                'program_name' => $order->batch?->program?->name,
                'batch_name' => $order->batch?->name,
                'item_name' => $paymentSchedule->title
                    ?: ($order->workshop
                        ? $this->getWorkshopTitle($order->workshop)
                        : 'FlexLabs Payment'),
            ];

            try {
                $xenditResult = !empty($snapshot['had_gateway_invoice'])
                    ? $this->xenditService
                        ->createReplacementPaymentLink(
                            $payment,
                            $customerData
                        )
                    : $this->xenditService->createPaymentLink(
                        $payment,
                        $customerData
                    );

                $payment->update([
                    'payment_url' => $xenditResult['payment_url']
                        ?? $payment->payment_url,
                    'gateway_transaction_id' => $xenditResult['gateway_transaction_id']
                        ?? $payment->gateway_transaction_id,
                    'gateway_provider' => $xenditResult['gateway_provider']
                        ?? 'xendit',
                    'gateway_payload' => $xenditResult['gateway_payload']
                        ?? null,
                    'expired_at' => !empty($xenditResult['expired_at'])
                        ? Carbon::parse($xenditResult['expired_at'])
                        : $payment->expired_at,
                ]);
            } catch (\Throwable $exception) {
                report($exception);

                $payment->update([
                    'gateway_provider' => 'xendit',
                    'gateway_payload' => [
                        'error' => $exception->getMessage(),
                    ],
                ]);
            }
        }
    }

    public function destroy(PaymentSchedule $paymentSchedule): JsonResponse
    {
        $paymentSchedule->loadMissing('order:id,group_registration_id');

        if ($paymentSchedule->order?->group_registration_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Group Registration schedules must be cancelled from the Group Registration page.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($paymentSchedule) {
                $orderId = $paymentSchedule->order_id;

                $hasPaidPayment = Payment::query()
                    ->where('payment_schedule_id', $paymentSchedule->id)
                    ->where('status', 'paid')
                    ->exists();

                if ($hasPaidPayment || $paymentSchedule->status === 'paid') {
                    $paymentSchedule->update([
                        'status' => 'cancelled',
                    ]);

                    $this->syncOrderStatus($orderId);

                    return;
                }

                Payment::query()
                    ->where('payment_schedule_id', $paymentSchedule->id)
                    ->whereIn('status', ['pending', 'failed', 'expired', 'cancelled'])
                    ->delete();

                $paymentSchedule->delete();

                $this->syncOrderStatus($orderId);
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment schedule deleted successfully.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment schedule.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    private function validateScheduleRequest(Request $request): array
    {
        return $request->validate([
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id'),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'status' => [
                'required',
                Rule::in(['pending', 'paid', 'overdue', 'cancelled']),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);
    }

    private function syncOrderStatus(int $orderId): void
    {
        $order = Order::query()
            ->with('paymentSchedules:id,order_id,amount,status')
            ->find($orderId);

        if (! $order) {
            return;
        }

        $activeSchedules = $order->paymentSchedules
            ->where('status', '!=', 'cancelled');

        if ($activeSchedules->isEmpty()) {
            $order->update([
                'status' => 'pending',
            ]);

            return;
        }

        $paidAmount = (float) $activeSchedules
            ->where('status', 'paid')
            ->sum('amount');

        $finalPrice = (float) $order->final_price;

        if ($finalPrice <= 0) {
            $order->update([
                'status' => 'paid',
            ]);

            return;
        }

        if ($paidAmount >= $finalPrice) {
            $order->update([
                'status' => 'paid',
            ]);

            return;
        }

        if ($paidAmount > 0) {
            $order->update([
                'status' => 'partial',
            ]);

            return;
        }

        if ($activeSchedules->where('status', 'overdue')->isNotEmpty()) {
            $order->update([
                'status' => 'pending',
            ]);

            return;
        }

        $order->update([
            'status' => 'pending',
        ]);
    }

    private function formatOrderData(?Order $order): ?array
    {
        if (! $order) {
            return null;
        }

        $orderType = $order->order_type ?: 'program';
        $groupRegistration = $order->groupRegistration;

        return [
            'id' => $order->id,
            'order_type' => $orderType,
            'final_price' => (float) $order->final_price,
            'status' => $order->status,
            'group_registration_id' => $order->group_registration_id,
            'is_group_registration' => $groupRegistration !== null,
            'customer' => [
                'name' => $groupRegistration?->buyer_name ?? $order->student?->full_name,
                'email' => $groupRegistration?->buyer_email ?? $order->student?->email,
                'phone' => $groupRegistration?->buyer_phone ?? $order->student?->phone,
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
            ] : null,
            'student' => $order->student ? [
                'id' => $order->student->id,
                'full_name' => $order->student->full_name,
                'email' => $order->student->email,
                'phone' => $order->student->phone,
            ] : null,
            'batch' => $order->batch ? [
                'id' => $order->batch->id,
                'name' => $order->batch->name,
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
        ];
    }

    private function applyWorkshopKeywordSearch($query, string $keyword): void
    {
        if (! Schema::hasTable('workshops')) {
            return;
        }

        $columns = [];

        if (Schema::hasColumn('workshops', 'title')) {
            $columns[] = 'title';
        }

        if (Schema::hasColumn('workshops', 'name')) {
            $columns[] = 'name';
        }

        if (empty($columns)) {
            return;
        }

        $query->orWhereHas('order.workshop', function ($workshopQuery) use ($keyword, $columns) {
            $workshopQuery->where(function ($innerQuery) use ($keyword, $columns) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $innerQuery->where($column, 'like', "%{$keyword}%");
                    } else {
                        $innerQuery->orWhere($column, 'like', "%{$keyword}%");
                    }
                }
            });
        });
    }

    private function getWorkshopTitle($workshop): string
    {
        return $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';
    }

    private function getWorkshopPrice($workshop): float
    {
        return (float) (
            $workshop->price
            ?? $workshop->final_price
            ?? $workshop->registration_fee
            ?? 0
        );
    }
}