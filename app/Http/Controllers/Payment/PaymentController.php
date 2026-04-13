<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $payments = Payment::with([
                'order:id,student_id,batch_id,final_price,status',
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
                'paymentSchedule:id,order_id,title,amount,due_date,status',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $orders = Order::with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name',
                'batch.program:id,name',
            ])
            ->whereIn('status', ['pending', 'partial'])
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'batch_id', 'final_price', 'status']);

        $paymentSchedules = PaymentSchedule::with([
                'order:id,student_id,batch_id,final_price,status',
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
            ])
            ->whereIn('status', ['pending', 'overdue'])
            ->orderByDesc('id')
            ->get(['id', 'order_id', 'title', 'amount', 'due_date', 'status']);

        return view('payments.index', compact('payments', 'orders', 'paymentSchedules'));
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_schedule_id' => $payment->payment_schedule_id,
                'invoice_number' => $payment->invoice_number,
                'amount' => (float) $payment->amount,
                'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'gateway_provider' => $payment->gateway_provider,
                'gateway_payload' => $payment->gateway_payload,
                'status' => $payment->status,
                'notes' => $payment->notes,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
                'order' => $payment->order ? [
                    'id' => $payment->order->id,
                    'final_price' => (float) $payment->order->final_price,
                    'status' => $payment->order->status,
                    'student' => $payment->order->student ? [
                        'id' => $payment->order->student->id,
                        'full_name' => $payment->order->student->full_name,
                        'email' => $payment->order->student->email,
                        'phone' => $payment->order->student->phone,
                    ] : null,
                    'batch' => $payment->order->batch ? [
                        'id' => $payment->order->batch->id,
                        'name' => $payment->order->batch->name,
                        'program' => $payment->order->batch->program ? [
                            'id' => $payment->order->batch->program->id,
                            'name' => $payment->order->batch->program->name,
                        ] : null,
                    ] : null,
                ] : null,
                'payment_schedule' => $payment->paymentSchedule ? [
                    'id' => $payment->paymentSchedule->id,
                    'title' => $payment->paymentSchedule->title,
                    'amount' => (float) $payment->paymentSchedule->amount,
                    'due_date' => optional($payment->paymentSchedule->due_date)->format('Y-m-d'),
                    'status' => $payment->paymentSchedule->status,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_schedule_id' => ['nullable', 'integer', 'exists:payment_schedules,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:255'],
            'gateway_provider' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'expired', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if (!empty($validated['payment_schedule_id'])) {
            $paymentSchedule = PaymentSchedule::findOrFail($validated['payment_schedule_id']);

            if ((int) $paymentSchedule->order_id !== (int) $order->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected payment schedule does not belong to the selected order.',
                    'errors' => [
                        'payment_schedule_id' => ['Selected payment schedule does not belong to the selected order.'],
                    ],
                ], 422);
            }
        }

        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'payment_schedule_id' => $validated['payment_schedule_id'] ?? null,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? null,
            'gateway_provider' => $validated['gateway_provider'] ?? null,
            'gateway_payload' => null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'paid_at' => $validated['status'] === 'paid' ? now() : null,
        ]);

        $this->syncRelatedStatuses($payment);

        $payment->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully.',
            'data' => $payment,
        ], 201);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_schedule_id' => ['nullable', 'integer', 'exists:payment_schedules,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:255'],
            'gateway_provider' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'expired', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if (!empty($validated['payment_schedule_id'])) {
            $paymentSchedule = PaymentSchedule::findOrFail($validated['payment_schedule_id']);

            if ((int) $paymentSchedule->order_id !== (int) $order->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected payment schedule does not belong to the selected order.',
                    'errors' => [
                        'payment_schedule_id' => ['Selected payment schedule does not belong to the selected order.'],
                    ],
                ], 422);
            }
        }

        $payment->update([
            'order_id' => $validated['order_id'],
            'payment_schedule_id' => $validated['payment_schedule_id'] ?? null,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? null,
            'gateway_provider' => $validated['gateway_provider'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'paid_at' => $validated['status'] === 'paid'
                ? ($payment->paid_at ?? now())
                : null,
        ]);

        $this->syncRelatedStatuses($payment);

        $payment->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => $payment,
        ]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $orderId = $payment->order_id;
        $scheduleId = $payment->payment_schedule_id;

        $payment->delete();

        if ($scheduleId) {
            $paymentSchedule = PaymentSchedule::find($scheduleId);
            if ($paymentSchedule) {
                $this->refreshScheduleStatus($paymentSchedule);
            }
        }

        $order = Order::find($orderId);
        if ($order) {
            $this->refreshOrderStatus($order);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully.',
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'FLX-INV-' . now()->format('Ymd');
        $lastPayment = Payment::whereDate('created_at', now()->toDateString())
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($lastPayment && preg_match('/(\d+)$/', $lastPayment->invoice_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function syncRelatedStatuses(Payment $payment): void
    {
        if ($payment->payment_schedule_id) {
            $paymentSchedule = PaymentSchedule::find($payment->payment_schedule_id);
            if ($paymentSchedule) {
                $this->refreshScheduleStatus($paymentSchedule);
            }
        }

        $order = Order::find($payment->order_id);
        if ($order) {
            $this->refreshOrderStatus($order);
        }
    }

    private function refreshScheduleStatus(PaymentSchedule $paymentSchedule): void
    {
        $paidAmount = Payment::where('payment_schedule_id', $paymentSchedule->id)
            ->where('status', 'paid')
            ->sum('amount');

        $newStatus = $paymentSchedule->status;

        if ($paidAmount >= (float) $paymentSchedule->amount) {
            $newStatus = 'paid';
        } elseif ($paymentSchedule->due_date && now()->toDateString() > $paymentSchedule->due_date->format('Y-m-d')) {
            $newStatus = 'overdue';
        } else {
            $newStatus = 'pending';
        }

        $paymentSchedule->update([
            'status' => $newStatus,
        ]);
    }

    private function refreshOrderStatus(Order $order): void
    {
        $paidAmount = Payment::where('order_id', $order->id)
            ->where('status', 'paid')
            ->sum('amount');

        $finalPrice = (float) $order->final_price;
        $newStatus = 'pending';

        if ($paidAmount >= $finalPrice && $finalPrice > 0) {
            $newStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $newStatus = 'partial';
        } else {
            $newStatus = 'pending';
        }

        $order->update([
            'status' => $newStatus,
        ]);
    }

    public function invoice(Payment $payment): View
    {
        $payment->load([
            'order:id,student_id,batch_id,final_price,status,notes',
            'order.student:id,full_name,email,phone,city',
            'order.batch:id,program_id,name,start_date,end_date',
            'order.batch.program:id,name',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
        ]);

        $student = $payment->order?->student;
        $batch = $payment->order?->batch;
        $program = $batch?->program;
        $schedule = $payment->paymentSchedule;
        $order = $payment->order;

        $items = [];

        if ($schedule) {
            $items[] = [
                'description' => $schedule->title,
                'qty' => 1,
                'rate' => (float) $schedule->amount,
                'amount' => (float) $schedule->amount,
            ];
        } else {
            $items[] = [
                'description' => 'Program Payment',
                'qty' => 1,
                'rate' => (float) $payment->amount,
                'amount' => (float) $payment->amount,
            ];
        }

        return view('payments.invoice', [
            'payment' => $payment,
            'order' => $order,
            'student' => $student,
            'batch' => $batch,
            'program' => $program,
            'schedule' => $schedule,
            'items' => $items,
            'subtotal' => (float) $payment->amount,
            'tax' => 0,
            'grandTotal' => (float) $payment->amount,
        ]);
    }
}