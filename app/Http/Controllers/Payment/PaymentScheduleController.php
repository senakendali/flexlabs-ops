<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $paymentSchedules = PaymentSchedule::with([
                'order:id,student_id,batch_id,final_price,status',
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $orders = Order::with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name',
                'batch.program:id,name',
            ])
            ->withSum('paymentSchedules', 'amount')
            ->whereIn('status', ['pending', 'partial'])
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'batch_id', 'final_price', 'status']);

        return view('payments.schedules.index', compact('paymentSchedules', 'orders'));
    }

    public function show(PaymentSchedule $paymentSchedule): JsonResponse
    {
        $paymentSchedule->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $paymentSchedule->id,
                'order_id' => $paymentSchedule->order_id,
                'title' => $paymentSchedule->title,
                'amount' => (float) $paymentSchedule->amount,
                'due_date' => optional($paymentSchedule->due_date)->format('Y-m-d'),
                'status' => $paymentSchedule->status,
                'notes' => $paymentSchedule->notes,
                'order' => $paymentSchedule->order ? [
                    'id' => $paymentSchedule->order->id,
                    'final_price' => (float) $paymentSchedule->order->final_price,
                    'status' => $paymentSchedule->order->status,
                    'student' => $paymentSchedule->order->student ? [
                        'id' => $paymentSchedule->order->student->id,
                        'full_name' => $paymentSchedule->order->student->full_name,
                        'email' => $paymentSchedule->order->student->email,
                        'phone' => $paymentSchedule->order->student->phone,
                    ] : null,
                    'batch' => $paymentSchedule->order->batch ? [
                        'id' => $paymentSchedule->order->batch->id,
                        'name' => $paymentSchedule->order->batch->name,
                        'program' => $paymentSchedule->order->batch->program ? [
                            'id' => $paymentSchedule->order->batch->program->id,
                            'name' => $paymentSchedule->order->batch->program->name,
                        ] : null,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentSchedule = PaymentSchedule::create([
            'order_id' => $validated['order_id'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $paymentSchedule->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment schedule created successfully.',
            'data' => $paymentSchedule,
        ], 201);
    }

    public function update(Request $request, PaymentSchedule $paymentSchedule): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentSchedule->update([
            'order_id' => $validated['order_id'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $paymentSchedule->load([
            'order:id,student_id,batch_id,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment schedule updated successfully.',
            'data' => $paymentSchedule,
        ]);
    }

    public function destroy(PaymentSchedule $paymentSchedule): JsonResponse
    {
        $paymentSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment schedule deleted successfully.',
        ]);
    }
}