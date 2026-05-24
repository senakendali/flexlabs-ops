<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $paymentSchedulesQuery = PaymentSchedule::query()
            ->with([
                'order:id,student_id,batch_id,workshop_id,order_type,final_price,status',
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
                'order.workshop',
                'order.paymentSchedules:id,order_id,amount,status',
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
            'order:id,student_id,batch_id,workshop_id,order_type,final_price,status',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'order.workshop',
            'order.paymentSchedules:id,order_id,amount,status',
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
                'order' => $this->formatOrderData($paymentSchedule->order),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateScheduleRequest($request);

        try {
            $paymentSchedule = DB::transaction(function () use ($validated) {
                $paymentSchedule = PaymentSchedule::create([
                    'order_id' => $validated['order_id'],
                    'title' => $validated['title'],
                    'amount' => $validated['amount'],
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
        $validated = $this->validateScheduleRequest($request);

        try {
            $paymentSchedule = DB::transaction(function () use ($validated, $paymentSchedule) {
                $oldOrderId = $paymentSchedule->order_id;

                $paymentSchedule->update([
                    'order_id' => $validated['order_id'],
                    'title' => $validated['title'],
                    'amount' => $validated['amount'],
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                ]);

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

            return response()->json([
                'success' => true,
                'message' => 'Payment schedule updated successfully.',
                'data' => $paymentSchedule,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment schedule.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(PaymentSchedule $paymentSchedule): JsonResponse
    {
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

        return [
            'id' => $order->id,
            'order_type' => $orderType,
            'final_price' => (float) $order->final_price,
            'status' => $order->status,
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