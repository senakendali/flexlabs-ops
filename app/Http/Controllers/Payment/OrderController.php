<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Order;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $orders = Order::with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name,price,status',
                'batch.program:id,name',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $students = Student::orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'phone']);

        $batches = Batch::with('program:id,name')
            ->whereIn('status', ['open', 'ongoing'])
            ->orderBy('name')
            ->get(['id', 'program_id', 'name', 'price', 'status']);

        return view('payments.orders.index', compact('orders', 'students', 'batches'));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name,price,status',
            'batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'student_id' => $order->student_id,
                'batch_id' => $order->batch_id,
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
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'partial', 'paid', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $batch = Batch::findOrFail($validated['batch_id']);
        $originalPrice = (float) $batch->price;
        $discount = (float) ($validated['discount'] ?? 0);

        if ($discount > $originalPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Discount cannot be greater than original price.',
                'errors' => [
                    'discount' => ['Discount cannot be greater than original price.'],
                ],
            ], 422);
        }

        $finalPrice = $originalPrice - $discount;

        $order = Order::create([
            'student_id' => $validated['student_id'],
            'batch_id' => $validated['batch_id'],
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $order->load([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name,price,status',
            'batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'partial', 'paid', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $batch = Batch::findOrFail($validated['batch_id']);
        $originalPrice = (float) $batch->price;
        $discount = (float) ($validated['discount'] ?? 0);

        if ($discount > $originalPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Discount cannot be greater than original price.',
                'errors' => [
                    'discount' => ['Discount cannot be greater than original price.'],
                ],
            ], 422);
        }

        $finalPrice = $originalPrice - $discount;

        $order->update([
            'student_id' => $validated['student_id'],
            'batch_id' => $validated['batch_id'],
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $order->load([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name,price,status',
            'batch.program:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
            'data' => $order,
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully.',
        ]);
    }
}