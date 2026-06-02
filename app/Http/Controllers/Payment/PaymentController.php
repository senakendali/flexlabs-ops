<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\PaymentGateway\XenditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
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

        $status = $request->filled('status') ? (string) $request->get('status') : null;
        $orderType = $request->filled('order_type') ? (string) $request->get('order_type') : null;
        $keyword = trim((string) $request->get('keyword', ''));

        $allowedPaymentStatuses = ['pending', 'paid', 'failed', 'expired', 'cancelled'];
        $allowedOrderTypes = ['program', 'workshop', 'trial_class', 'trial', 'webinar'];

        if ($status && !in_array($status, $allowedPaymentStatuses, true)) {
            $status = null;
        }

        if ($orderType && !in_array($orderType, $allowedOrderTypes, true)) {
            $orderType = null;
        }

        $workshopTable = (new Order())->workshop()->getRelated()->getTable();
        $workshopTitleColumns = collect(['title', 'name', 'workshop_title', 'theme', 'topic'])
            ->filter(fn ($column) => Schema::hasColumn($workshopTable, $column))
            ->values()
            ->all();

        $payments = Payment::with([
                'order' => function ($query) {
                    $query->select([
                        'id',
                        'student_id',
                        'batch_id',
                        'workshop_id',
                        'order_type',
                        'original_price',
                        'discount',
                        'final_price',
                        'status',
                        'notes',
                    ]);
                },
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
                'order.workshop',
                'paymentSchedule:id,order_id,title,amount,due_date,status',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($orderType, function ($query) use ($orderType) {
                $normalizedType = $orderType === 'trial_class' ? 'trial' : $orderType;

                $query->whereHas('order', function ($orderQuery) use ($orderType, $normalizedType) {
                    if ($orderType === 'trial_class') {
                        $orderQuery->whereIn('order_type', ['trial', 'trial_class', 'trial_schedule', 'trial_theme']);
                        return;
                    }

                    $orderQuery->where('order_type', $normalizedType);
                });
            })
            ->when($keyword !== '', function ($query) use ($keyword, $workshopTitleColumns) {
                $like = '%' . $keyword . '%';

                $query->where(function ($paymentQuery) use ($like, $workshopTitleColumns) {
                    $paymentQuery
                        ->where('invoice_number', 'like', $like)
                        ->orWhere('reference_number', 'like', $like)
                        ->orWhere('payment_method', 'like', $like)
                        ->orWhere('gateway_provider', 'like', $like)
                        ->orWhere('gateway_transaction_id', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('order.student', function ($studentQuery) use ($like) {
                            $studentQuery
                                ->where('full_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        })
                        ->orWhereHas('order.batch', function ($batchQuery) use ($like) {
                            $batchQuery->where('name', 'like', $like)
                                ->orWhereHas('program', function ($programQuery) use ($like) {
                                    $programQuery->where('name', 'like', $like);
                                });
                        })
                        ->orWhereHas('order.workshop', function ($workshopQuery) use ($like, $workshopTitleColumns) {
                            if (empty($workshopTitleColumns)) {
                                return;
                            }

                            $workshopQuery->where(function ($nestedWorkshopQuery) use ($like, $workshopTitleColumns) {
                                foreach ($workshopTitleColumns as $index => $column) {
                                    $method = $index === 0 ? 'where' : 'orWhere';
                                    $nestedWorkshopQuery->{$method}($column, 'like', $like);
                                }
                            });
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $orders = Order::with([
                'student:id,full_name,email,phone',
                'batch:id,program_id,name',
                'batch.program:id,name',
                'workshop',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'student_id',
                'batch_id',
                'workshop_id',
                'order_type',
                'original_price',
                'discount',
                'final_price',
                'status',
                'notes',
            ]);

        $paymentSchedules = PaymentSchedule::with([
                'order' => function ($query) {
                    $query->select([
                        'id',
                        'student_id',
                        'batch_id',
                        'workshop_id',
                        'order_type',
                        'original_price',
                        'discount',
                        'final_price',
                        'status',
                        'notes',
                    ]);
                },
                'order.student:id,full_name,email,phone',
                'order.batch:id,program_id,name',
                'order.batch.program:id,name',
                'order.workshop',
            ])
            ->orderByDesc('id')
            ->get(['id', 'order_id', 'title', 'amount', 'due_date', 'status']);

        return view('payments.index', compact('payments', 'orders', 'paymentSchedules'));
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load([
            'order:id,student_id,batch_id,workshop_id,order_type,original_price,discount,final_price,status,notes',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'order.workshop',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
            'paymentSchedule.order' => function ($query) {
                $query->select([
                    'id',
                    'student_id',
                    'batch_id',
                    'workshop_id',
                    'order_type',
                    'original_price',
                    'discount',
                    'final_price',
                    'status',
                    'notes',
                ]);
            },
            'paymentSchedule.order.student:id,full_name,email,phone',
            'paymentSchedule.order.batch:id,program_id,name',
            'paymentSchedule.order.batch.program:id,name',
            'paymentSchedule.order.workshop',
        ]);

        $orderOption = $this->formatOrderOption($payment->order);
        $paymentScheduleOption = $this->formatPaymentScheduleOption($payment->paymentSchedule);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_schedule_id' => $payment->payment_schedule_id,
                'invoice_number' => $payment->invoice_number,
                'public_token' => $payment->public_token,
                'payment_url' => $payment->payment_url,
                'amount' => (float) $payment->amount,
                'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'gateway_provider' => $payment->gateway_provider,
                'gateway_payload' => $payment->gateway_payload,
                'status' => $payment->status,
                'expired_at' => optional($payment->expired_at)->format('Y-m-d H:i:s'),
                'notes' => $payment->notes,
                'public_payment_link' => $payment->public_token
                    ? route('public.payments.show', $payment->public_token)
                    : null,
                'order' => $orderOption,
                'payment_schedule' => $paymentScheduleOption,

                /*
                |--------------------------------------------------------------------------
                | Select option aliases untuk modal edit
                |--------------------------------------------------------------------------
                | Beberapa frontend mengisi select dari array option global yang dibawa dari
                | halaman index. Kalau order / payment schedule sudah paid, option tersebut
                | sebelumnya bisa tidak ada karena query index dibatasi status tertentu.
                | Alias ini membantu frontend append selected option saat open edit modal.
                */
                'selected_order_option' => $orderOption,
                'selected_payment_schedule_option' => $paymentScheduleOption,
                'source_context' => $this->resolvePaymentSourceContext($payment),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('invoice_number')) {
            $request->merge([
                'invoice_number' => $this->normalizeManualInvoiceNumber($request->input('invoice_number')),
            ]);
        }

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'invoice_number' => ['nullable', 'string', 'max:100', Rule::unique('payments', 'invoice_number')],
            'payment_schedule_id' => ['nullable', 'integer', 'exists:payment_schedules,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:255'],
            'gateway_provider' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'expired', 'cancelled'])],
            'expired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = Order::with([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name',
            'batch.program:id,name',
            'workshop',
        ])->findOrFail($validated['order_id']);

        $paymentSchedule = null;

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

        $payment = DB::transaction(function () use ($validated, $order) {
            $manualInvoiceNumber = $validated['invoice_number'] ?? null;

            return Payment::create([
                'order_id' => $validated['order_id'],
                'payment_schedule_id' => $validated['payment_schedule_id'] ?? null,
                'invoice_number' => $manualInvoiceNumber ?: $this->generateInvoiceNumber($order),
                'public_token' => Str::uuid()->toString(),
                'payment_url' => null,
                'amount' => $validated['amount'],
                'payment_date' => $validated['status'] === 'paid'
                    ? ($validated['payment_date'] ?? now()->toDateString())
                    : ($validated['payment_date'] ?? null),
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? null,
                'gateway_provider' => $validated['gateway_provider'] ?? null,
                'gateway_payload' => null,
                'status' => $validated['status'],
                'expired_at' => !empty($validated['expired_at'])
                    ? Carbon::parse($validated['expired_at'])
                    : now()->addDay(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        if ($payment->status === 'pending') {
            $this->attachXenditPaymentLink($payment, $order, $paymentSchedule);
        }

        $this->syncRelatedStatuses($payment);

        $payment->load([
            'order:id,student_id,batch_id,workshop_id,order_type,original_price,discount,final_price,status,notes',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'order.workshop',
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
        if ($request->has('invoice_number')) {
            $request->merge([
                'invoice_number' => $this->normalizeManualInvoiceNumber($request->input('invoice_number')),
            ]);
        }

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('payments', 'invoice_number')->ignore($payment->id),
            ],
            'payment_schedule_id' => ['nullable', 'integer', 'exists:payment_schedules,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:255'],
            'gateway_provider' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'expired', 'cancelled'])],
            'expired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = Order::with([
            'student:id,full_name,email,phone',
            'batch:id,program_id,name',
            'batch.program:id,name',
            'workshop',
        ])->findOrFail($validated['order_id']);

        $paymentSchedule = null;

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

        $previousStatus = $payment->status;
        $hadPaymentUrl = !empty($payment->payment_url);

        $invoiceNumberWasSent = $request->has('invoice_number');

        DB::transaction(function () use ($payment, $validated, $order, $invoiceNumberWasSent) {
            $payload = [
                'order_id' => $validated['order_id'],
                'payment_schedule_id' => $validated['payment_schedule_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_date' => $validated['status'] === 'paid'
                    ? ($validated['payment_date'] ?? ($payment->payment_date ? Carbon::parse($payment->payment_date)->toDateString() : now()->toDateString()))
                    : ($validated['payment_date'] ?? null),
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? $payment->gateway_transaction_id,
                'gateway_provider' => $validated['gateway_provider'] ?? $payment->gateway_provider,
                'status' => $validated['status'],
                'expired_at' => !empty($validated['expired_at'])
                    ? Carbon::parse($validated['expired_at'])
                    : $payment->expired_at,
                'notes' => $validated['notes'] ?? null,
            ];

            if ($invoiceNumberWasSent) {
                $payload['invoice_number'] = $validated['invoice_number']
                    ?: ($payment->invoice_number ?: $this->generateInvoiceNumber($order));
            }

            $payment->update($payload);
        });

        $shouldGenerateLink = $payment->status === 'pending' && (
            !$hadPaymentUrl ||
            $previousStatus !== 'pending'
        );

        if ($shouldGenerateLink) {
            $this->attachXenditPaymentLink($payment->fresh(), $order, $paymentSchedule);
        }

        $this->syncRelatedStatuses($payment->fresh());

        $payment->load([
            'order:id,student_id,batch_id,workshop_id,order_type,original_price,discount,final_price,status,notes',
            'order.student:id,full_name,email,phone',
            'order.batch:id,program_id,name',
            'order.batch.program:id,name',
            'order.workshop',
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

    public function invoice(Payment $payment): View
    {
        return view('payments.invoice', $this->buildInvoiceViewData($payment));
    }

    public function downloadInvoicePdf(Payment $payment)
    {
        $data = $this->buildInvoiceViewData($payment);
        $invoiceDate = $data['invoiceDate'] ?? ($payment->payment_date ?: $payment->created_at);
        $paymentMethod = $payment->payment_method
            ?: ($payment->gateway_provider ? ucfirst($payment->gateway_provider) : 'Payment Link');

        $pdf = Pdf::loadView('payments.document-pdf', array_merge($data, [
            'documentTitle' => 'INVOICE',
            'documentNumber' => $payment->invoice_number,
            'documentDate' => $invoiceDate,
            'leftPartyTitle' => 'Billed to',
            'rightPartyTitle' => 'From',
            'totalLabel' => 'Current Invoice Amount',
            'paymentRows' => array_values(array_filter([
                [
                    'label' => 'Payment method',
                    'value' => $paymentMethod,
                ],
                $payment->expired_at ? [
                    'label' => 'Payment due',
                    'value' => Carbon::parse($payment->expired_at)->format('d F Y H:i'),
                ] : null,
                [
                    'label' => 'Status',
                    'value' => Str::headline((string) $payment->status),
                ],
                [
                    'label' => 'Note',
                    'value' => $payment->notes ?: ($data['documentNote'] ?? 'Thank you for choosing FlexLabs.'),
                ],
            ])),
            'documentCss' => $this->paymentDocumentCss(),
            'logoPath' => public_path('images/logo-black.png'),
        ]))->setPaper('a4', 'portrait');

        $fileName = Str::slug($payment->invoice_number ?: 'invoice-' . $payment->id) . '.pdf';

        return $pdf->download($fileName);
    }

    public function receipt(Payment $payment): View
    {
        abort_unless($payment->status === 'paid', 404, 'Receipt is only available for paid payments.');

        return view('payments.receipt', $this->buildReceiptViewData($payment));
    }

    public function downloadReceiptPdf(Payment $payment)
    {
        abort_unless($payment->status === 'paid', 404, 'Receipt is only available for paid payments.');

        $data = $this->buildReceiptViewData($payment);
        $receiptNumber = $data['receiptNumber'] ?? $this->resolveReceiptNumber($payment);
        $receiptDate = $data['paymentDate'] ?? $payment->payment_date ?? $payment->updated_at ?? $payment->created_at;
        $paymentMethod = $payment->payment_method
            ?: ($payment->gateway_provider ? ucfirst($payment->gateway_provider) : '-');

        $pdf = Pdf::loadView('payments.document-pdf', array_merge($data, [
            'documentTitle' => 'RECEIPT',
            'documentNumber' => $receiptNumber,
            'documentDate' => $receiptDate,
            'leftPartyTitle' => 'Received from',
            'rightPartyTitle' => 'Received by',
            'totalLabel' => 'Total Paid',
            'paymentRows' => array_values(array_filter([
                [
                    'label' => 'Invoice no',
                    'value' => $payment->invoice_number ?: '-',
                ],
                [
                    'label' => 'Payment method',
                    'value' => $paymentMethod,
                ],
                [
                    'label' => 'Payment date',
                    'value' => $receiptDate ? Carbon::parse($receiptDate)->format('d F Y') : '-',
                ],
                !empty($payment->reference_number) ? [
                    'label' => 'Reference no',
                    'value' => $payment->reference_number,
                ] : null,
                !empty($payment->gateway_transaction_id) ? [
                    'label' => 'Transaction ID',
                    'value' => $payment->gateway_transaction_id,
                ] : null,
                [
                    'label' => 'Note',
                    'value' => $payment->notes ?: ($data['documentNote'] ?? 'Payment has been received. Thank you for choosing FlexLabs.'),
                ],
            ])),
            'documentCss' => $this->paymentDocumentCss(),
            'logoPath' => public_path('images/logo-black.png'),
        ]))->setPaper('a4', 'portrait');

        $fileName = Str::slug($receiptNumber ?: 'receipt-' . $payment->id) . '.pdf';

        return $pdf->download($fileName);
    }


    public function publicShow(string $token): View
    {
        $payment = $this->findPaymentByPublicToken($token);

        return view('public.payments.show', $this->buildPublicPaymentViewData($payment));
    }

    public function showPublicPayment(string $token): View
    {
        return $this->publicShow($token);
    }

    public function publicPayment(string $token): View
    {
        return $this->publicShow($token);
    }

    public function pay(string $token): View
    {
        return $this->publicShow($token);
    }

    public function downloadPublicInvoicePdf(string $token)
    {
        $payment = $this->findPaymentByPublicToken($token);

        return $this->downloadInvoicePdf($payment);
    }



    private function formatOrderOption(?Order $order): ?array
    {
        if (!$order) {
            return null;
        }

        $student = $order->student;
        $batch = $order->batch;
        $program = $batch?->program;
        $workshopTitle = $order->workshop
            ? $this->firstFilledAttribute($order->workshop, [
                'title',
                'name',
                'workshop_title',
                'theme_name',
                'theme',
                'topic',
                'subject',
            ])
            : null;

        $sourceTitle = $workshopTitle
            ?: collect([
                $program?->name,
                $batch?->name,
            ])
                ->filter(fn ($value) => filled($value))
                ->implode(' · ');

        $studentName = $student?->full_name ?: 'Unknown Student';
        $orderType = Str::headline((string) ($order->order_type ?: 'order'));
        $sourceTitle = $sourceTitle ?: 'FlexLabs Order #' . $order->id;

        return [
            'id' => $order->id,
            'value' => $order->id,
            'label' => trim($studentName . ' - ' . $sourceTitle . ' (' . $orderType . ')'),
            'student_id' => $order->student_id,
            'batch_id' => $order->batch_id,
            'workshop_id' => $order->workshop_id,
            'order_type' => $order->order_type,
            'original_price' => (float) $order->original_price,
            'discount' => (float) $order->discount,
            'final_price' => (float) $order->final_price,
            'status' => $order->status,
            'notes' => $order->notes,
            'student' => $student ? [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
            ] : null,
            'batch' => $batch ? [
                'id' => $batch->id,
                'name' => $batch->name,
                'program' => $program ? [
                    'id' => $program->id,
                    'name' => $program->name,
                ] : null,
            ] : null,
            'workshop' => $order->workshop ? [
                'id' => $order->workshop->id,
                'title' => $workshopTitle,
            ] : null,
        ];
    }

    private function formatPaymentScheduleOption(?PaymentSchedule $paymentSchedule): ?array
    {
        if (!$paymentSchedule) {
            return null;
        }

        $orderOption = $this->formatOrderOption($paymentSchedule->order);
        $title = $paymentSchedule->title ?: 'Payment Schedule #' . $paymentSchedule->id;
        $orderLabel = $orderOption['label'] ?? ('Order #' . $paymentSchedule->order_id);

        return [
            'id' => $paymentSchedule->id,
            'value' => $paymentSchedule->id,
            'label' => trim($title . ' - ' . $orderLabel),
            'order_id' => $paymentSchedule->order_id,
            'title' => $paymentSchedule->title,
            'amount' => (float) $paymentSchedule->amount,
            'due_date' => optional($paymentSchedule->due_date)->format('Y-m-d'),
            'status' => $paymentSchedule->status,
            'order' => $orderOption,
        ];
    }


    private function findPaymentByPublicToken(string $token): Payment
    {
        return Payment::query()
            ->with($this->paymentDocumentRelations())
            ->where('public_token', $token)
            ->firstOrFail();
    }

    private function buildPublicPaymentViewData(Payment $payment): array
    {
        $data = $this->buildInvoiceViewData($payment);
        $publicToken = (string) $payment->public_token;
        $isPaid = $payment->status === 'paid';
        $isExpired = $this->isPaymentExpired($payment);
        $publicPaymentLink = $publicToken !== ''
            ? route('public.payments.show', $publicToken)
            : null;

        return array_merge($data, [
            'isPaid' => $isPaid,
            'isExpired' => $isExpired,
            'canPay' => !$isPaid && !$isExpired && filled($payment->payment_url),
            'publicPaymentLink' => $publicPaymentLink,
            'publicInvoicePdfLink' => $publicToken !== '' && Route::has('public.payments.invoice.download')
                ? route('public.payments.invoice.download', $publicToken)
                : null,

            // Alias supaya public Blade bisa pakai struktur detail yang sama
            // dengan admin invoice tanpa fallback manual di view.
            'financialRows' => $data['financialSummaryRows'] ?? $data['items'] ?? [],
            'invoiceBreakdownRows' => $data['financialSummaryRows'] ?? $data['items'] ?? [],
            'currentDocumentAmount' => $data['currentInvoiceAmount'] ?? $data['grandTotal'] ?? 0,
            'currentDocumentAmountLabel' => 'Current Invoice Amount',
            'showRemainingBalance' => (bool) ($data['showRemainingBalance'] ?? true),
            'isWorkshopDocument' => (bool) ($data['isWorkshopDocument'] ?? false),
            'isSimpleWorkshopDocument' => (bool) ($data['isSimpleWorkshopDocument'] ?? false),
            'shouldShowPaymentBreakdown' => (bool) ($data['shouldShowPaymentBreakdown'] ?? true),
            'documentStatusLabel' => Str::headline((string) $payment->status),
            'documentActionLabel' => $isPaid
                ? 'Already Paid'
                : ($isExpired ? 'Link Expired' : 'Pay Now'),
        ]);
    }

    private function paymentDocumentRelations(): array
    {
        return [
            'order:id,student_id,batch_id,workshop_id,order_type,original_price,discount,final_price,status,notes',
            'order.student:id,full_name,email,phone,city',
            'order.batch:id,program_id,name,start_date,end_date',
            'order.batch.program:id,name',
            'order.workshop',
            'paymentSchedule:id,order_id,title,amount,due_date,status',
        ];
    }

    private function isPaymentExpired(Payment $payment): bool
    {
        if ($payment->status === 'expired') {
            return true;
        }

        if ($payment->status === 'paid') {
            return false;
        }

        if (empty($payment->expired_at)) {
            return false;
        }

        return Carbon::parse($payment->expired_at)->isPast();
    }

    private function buildInvoiceViewData(Payment $payment): array
    {
        $payment->load($this->paymentDocumentRelations());

        $student = $payment->order?->student;
        $batch = $payment->order?->batch;
        $program = $batch?->program;
        $schedule = $payment->paymentSchedule;
        $order = $payment->order;
        $summary = $this->buildPaymentFinancialSummary($payment, 'invoice');
        $sourceContext = $this->resolvePaymentSourceContext($payment, $order, $schedule);

        return [
            'payment' => $payment,
            'order' => $order,
            'student' => $student,
            'batch' => $batch,
            'program' => $program,
            'schedule' => $schedule,
            'sourceContext' => $sourceContext,
            'sourceType' => $sourceContext['source_type'],
            'sourceTypeLabel' => $sourceContext['source_type_label'],
            'sourceItemName' => $sourceContext['source_item_name'],
            'sourceDescription' => $sourceContext['source_description'],
            'isWorkshopDocument' => (bool) ($summary['is_workshop_document'] ?? false),
            'isSimpleWorkshopDocument' => (bool) ($summary['is_simple_workshop_document'] ?? false),
            'shouldShowPaymentBreakdown' => (bool) ($summary['should_show_breakdown'] ?? true),
            'showRemainingBalance' => (bool) ($summary['show_remaining_balance'] ?? true),
            'workshopName' => $summary['workshop_name'] ?? null,
            'items' => $summary['items'],
            'financialSummaryRows' => $summary['rows'],
            'financialRows' => $summary['rows'],
            'invoiceBreakdownRows' => $summary['rows'],
            'pricingRows' => $summary['pricing_rows'],
            'paymentSummaryRows' => $summary['payment_rows'],
            'normalProgramFee' => $summary['normal_program_fee'],
            'programDiscount' => $summary['program_discount'],
            'finalTuitionFee' => $summary['final_tuition_fee'],
            'previousPaymentReceived' => $summary['previous_payment_received'],
            'currentInvoiceAmount' => $summary['current_amount'],
            'remainingBalance' => $summary['remaining_balance'],
            'remainingBalanceLabel' => $summary['remaining_balance_label'],
            'subtotal' => $summary['current_amount'],
            'tax' => 0,
            'grandTotal' => $summary['current_amount'],
            'invoiceDate' => $payment->payment_date ?: $payment->created_at,
            'documentNote' => $this->buildDocumentNote($sourceContext, 'invoice'),
            'companyName' => 'FlexLabs',
            'companyAddressLines' => $this->flexlabsAddressLines(),
        ];
    }

    private function buildReceiptViewData(Payment $payment): array
    {
        $payment->load($this->paymentDocumentRelations());

        $student = $payment->order?->student;
        $batch = $payment->order?->batch;
        $program = $batch?->program;
        $schedule = $payment->paymentSchedule;
        $order = $payment->order;
        $summary = $this->buildPaymentFinancialSummary($payment, 'receipt');
        $sourceContext = $this->resolvePaymentSourceContext($payment, $order, $schedule);

        return [
            'payment' => $payment,
            'order' => $order,
            'student' => $student,
            'batch' => $batch,
            'program' => $program,
            'schedule' => $schedule,
            'sourceContext' => $sourceContext,
            'sourceType' => $sourceContext['source_type'],
            'sourceTypeLabel' => $sourceContext['source_type_label'],
            'sourceItemName' => $sourceContext['source_item_name'],
            'sourceDescription' => $sourceContext['source_description'],
            'isWorkshopDocument' => (bool) ($summary['is_workshop_document'] ?? false),
            'isSimpleWorkshopDocument' => (bool) ($summary['is_simple_workshop_document'] ?? false),
            'shouldShowPaymentBreakdown' => (bool) ($summary['should_show_breakdown'] ?? true),
            'showRemainingBalance' => (bool) ($summary['show_remaining_balance'] ?? true),
            'workshopName' => $summary['workshop_name'] ?? null,
            'items' => $summary['items'],
            'financialSummaryRows' => $summary['rows'],
            'financialRows' => $summary['rows'],
            'invoiceBreakdownRows' => $summary['rows'],
            'receiptBreakdownRows' => $summary['rows'],
            'pricingRows' => $summary['pricing_rows'],
            'paymentSummaryRows' => $summary['payment_rows'],
            'receiptNumber' => $this->resolveReceiptNumber($payment),
            'normalProgramFee' => $summary['normal_program_fee'],
            'programDiscount' => $summary['program_discount'],
            'finalTuitionFee' => $summary['final_tuition_fee'],
            'previousPaymentReceived' => $summary['previous_payment_received'],
            'currentPaymentReceived' => $summary['current_amount'],
            'remainingBalance' => $summary['remaining_balance'],
            'remainingBalanceLabel' => $summary['remaining_balance_label'],
            'subtotal' => $summary['current_amount'],
            'tax' => 0,
            'grandTotal' => $summary['current_amount'],
            'paymentDate' => $payment->payment_date ?: $payment->updated_at,
            'paidAt' => $payment->payment_date ?: $payment->updated_at,
            'documentNote' => $this->buildDocumentNote($sourceContext, 'receipt'),
            'companyName' => 'FlexLabs',
            'companyAddressLines' => $this->flexlabsAddressLines(),
        ];
    }

    private function buildPaymentFinancialSummary(Payment $payment, string $documentType = 'invoice'): array
    {
        $order = $this->resolveFullOrderForPayment($payment);
        $schedule = $this->resolveFullPaymentScheduleForPayment($payment);
        $sourceContext = $this->resolvePaymentSourceContext($payment, $order, $schedule);

        // Khusus workshop: jangan tampilkan breakdown normal fee / discount / final fee.
        // Invoice/receipt cukup menjelaskan peserta ikut workshop apa dan nominal yang harus dibayar/dibayar.
        if ($this->isWorkshopSourceContext($sourceContext, $order)) {
            return $this->buildWorkshopFinancialSummary($payment, $documentType, $order, $schedule, $sourceContext);
        }

        $sourceTypeLabel = $sourceContext['source_type_label'] ?: 'Order';
        $normalFeeLabel = $this->buildSourceMoneyLabel('Normal', $sourceTypeLabel, 'Fee');
        $discountLabel = $this->buildSourceDiscountLabel($sourceTypeLabel);
        $finalFeeLabel = $this->buildSourceMoneyLabel('Final', $sourceTypeLabel, 'Fee');

        $normalProgramFee = $this->moneyValue($order?->original_price);
        $explicitDiscount = $this->moneyValue($order?->discount);
        $finalTuitionFee = $this->moneyValue($order?->final_price);
        $currentAmount = $this->moneyValue($payment->amount);

        // Guard tambahan:
        // - original_price kadang kosong, tapi discount + final_price ada
        // - discount kadang kosong, tapi original_price > final_price
        // - final_price kadang kosong pada data lama, fallback ke amount/payment schedule
        if ($finalTuitionFee <= 0) {
            $finalTuitionFee = $this->moneyValue($schedule?->amount) ?: $currentAmount;
        }

        if ($normalProgramFee <= 0) {
            $normalProgramFee = $finalTuitionFee + $explicitDiscount;
        }

        if ($normalProgramFee <= 0) {
            $normalProgramFee = $finalTuitionFee ?: $currentAmount;
        }

        $derivedDiscount = 0;

        if ($normalProgramFee > $finalTuitionFee && $finalTuitionFee > 0) {
            $derivedDiscount = $normalProgramFee - $finalTuitionFee;
        }

        $programDiscount = max($explicitDiscount, $derivedDiscount, 0);

        if ($finalTuitionFee <= 0 && $normalProgramFee > 0) {
            $finalTuitionFee = max($normalProgramFee - $programDiscount, 0);
        }

        if ($finalTuitionFee <= 0) {
            $finalTuitionFee = $currentAmount;
        }

        $previousPaymentReceived = $this->resolvePreviousPaidAmount($payment);
        $currentAmountAppliedToBalance = $documentType === 'receipt' && $payment->status !== 'paid'
            ? 0
            : $currentAmount;
        $remainingBalance = max($finalTuitionFee - $previousPaymentReceived - $currentAmountAppliedToBalance, 0);

        $currentLabel = $documentType === 'receipt'
            ? 'Current Payment Received'
            : 'Current Invoice Amount';
        $remainingLabel = $documentType === 'receipt'
            ? 'Remaining Balance'
            : 'Remaining Balance After This Invoice';

        $currentDescription = $schedule?->title
            ? trim($schedule->title . ($schedule->due_date ? ' · Due ' . Carbon::parse($schedule->due_date)->format('d F Y') : ''))
            : $this->buildGenericInstallmentDescription($sourceContext);

        $programDescription = $sourceContext['source_description'] ?: $this->resolveProgramDescription($payment);

        // Row diskon dibuat eksplisit dan selalu dikirim dari controller.
        // Jadi view tidak perlu nebak lagi dan label "Special Program Discount" tidak hilang.
        $rows = [
            $this->makeFinancialRow(
                label: $normalFeeLabel,
                details: $programDescription,
                amount: $normalProgramFee,
                type: 'normal_fee'
            ),
            $this->makeFinancialRow(
                label: $discountLabel,
                details: 'Approved discount or payment adjustment for this order source',
                amount: -1 * abs($programDiscount),
                type: 'discount',
                isNegative: $programDiscount > 0
            ),
            $this->makeFinancialRow(
                label: $finalFeeLabel,
                details: $sourceTypeLabel . ' fee after discount or adjustment',
                amount: $finalTuitionFee,
                type: 'final_fee',
                isEmphasis: true
            ),
            $this->makeFinancialRow(
                label: 'Previous Payment Received',
                details: 'Confirmed paid amount recorded before this document',
                amount: -1 * abs($previousPaymentReceived),
                type: 'previous_payment',
                isNegative: $previousPaymentReceived > 0
            ),
            $this->makeFinancialRow(
                label: $currentLabel,
                details: $currentDescription,
                amount: $currentAmount,
                type: $documentType === 'receipt' ? 'current_payment' : 'current_invoice',
                isEmphasis: true
            ),
            $this->makeFinancialRow(
                label: $remainingLabel,
                details: $documentType === 'receipt'
                    ? 'Outstanding amount after this payment'
                    : 'Outstanding amount assuming this invoice is completed',
                amount: $remainingBalance,
                type: 'remaining_balance',
                isEmphasis: true
            ),
        ];

        return [
            'normal_program_fee' => $normalProgramFee,
            'program_discount' => $programDiscount,
            'final_tuition_fee' => $finalTuitionFee,
            'normal_source_fee' => $normalProgramFee,
            'source_discount' => $programDiscount,
            'final_source_fee' => $finalTuitionFee,
            'source_context' => $sourceContext,
            'source_type_label' => $sourceTypeLabel,
            'previous_payment_received' => $previousPaymentReceived,
            'current_amount' => $currentAmount,
            'remaining_balance' => $remainingBalance,
            'remaining_balance_label' => $remainingLabel,
            'rows' => $rows,
            'pricing_rows' => array_slice($rows, 0, 3),
            'payment_rows' => array_slice($rows, 3),
            'items' => $this->financialRowsToDocumentItems($rows),
            'is_workshop_document' => false,
            'is_simple_workshop_document' => false,
            'should_show_breakdown' => true,
            'show_remaining_balance' => true,
        ];
    }

    private function buildWorkshopFinancialSummary(
        Payment $payment,
        string $documentType,
        ?Order $order,
        ?PaymentSchedule $schedule,
        array $sourceContext
    ): array {
        $finalWorkshopFee = $this->moneyValue($order?->final_price)
            ?: $this->moneyValue($schedule?->amount)
            ?: $this->moneyValue($payment->amount)
            ?: $this->moneyValue($order?->original_price);

        $currentAmount = $this->moneyValue($payment->amount)
            ?: $this->moneyValue($schedule?->amount)
            ?: $finalWorkshopFee;

        if ($finalWorkshopFee <= 0) {
            $finalWorkshopFee = $currentAmount;
        }

        // Workshop document dibuat sederhana:
        // cukup tampilkan nama workshop dan nominal payment saat ini.
        // Tidak perlu previous payment, cicilan, atau remaining balance.
        $previousPaymentReceived = 0;
        $remainingBalance = 0;

        $workshopName = $this->resolveWorkshopDocumentName($payment, $order, $sourceContext);
        $rowLabel = $documentType === 'receipt'
            ? 'Workshop Payment Received'
            : 'Workshop Fee';

        $rows = [
            $this->makeFinancialRow(
                label: $rowLabel,
                details: $workshopName,
                amount: $currentAmount,
                type: $documentType === 'receipt' ? 'workshop_payment' : 'workshop_fee',
                isEmphasis: true
            ),
        ];

        return [
            'normal_program_fee' => $finalWorkshopFee,
            'program_discount' => 0,
            'final_tuition_fee' => $finalWorkshopFee,
            'normal_source_fee' => $finalWorkshopFee,
            'source_discount' => 0,
            'final_source_fee' => $finalWorkshopFee,
            'source_context' => $sourceContext,
            'source_type_label' => 'Workshop',
            'previous_payment_received' => $previousPaymentReceived,
            'current_amount' => $currentAmount,
            'remaining_balance' => $remainingBalance,
            'remaining_balance_label' => null,
            'rows' => $rows,
            'pricing_rows' => $rows,
            'payment_rows' => [],
            'items' => $this->financialRowsToDocumentItems($rows),
            'is_workshop_document' => true,
            'is_simple_workshop_document' => true,
            'should_show_breakdown' => false,
            'show_remaining_balance' => false,
            'workshop_name' => $workshopName,
        ];
    }

    private function isWorkshopSourceContext(array $sourceContext, ?Order $order = null): bool
    {
        $sourceType = Str::of((string) ($sourceContext['source_type'] ?? ''))
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        $orderType = Str::of((string) data_get($order, 'order_type', ''))
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        $sourceTypeLabel = Str::lower((string) ($sourceContext['source_type_label'] ?? ''));

        return in_array($sourceType, ['workshop', 'workshops'], true)
            || in_array($orderType, ['workshop', 'workshops'], true)
            || filled(data_get($order, 'workshop_id'))
            || filled(data_get($order, 'workshop.id'))
            || Str::contains($sourceTypeLabel, 'workshop');
    }

    private function resolveWorkshopDocumentName(Payment $payment, ?Order $order, array $sourceContext): string
    {
        $candidate = collect([
            $sourceContext['source_item_name'] ?? null,
            $this->firstFilledAttribute($order, [
                'workshop.title',
                'workshop.name',
                'workshop.workshop_title',
                'workshop.theme_name',
                'workshop.theme',
                'workshop.topic',
                'workshop.subject',
            ]),
            $this->firstFilledAttribute($order, [
                'source_name',
                'source_item',
                'source_item_name',
                'item_name',
                'title',
                'name',
            ]),
            $sourceContext['source_description'] ?? null,
        ])
            ->filter(function ($value) {
                if (!filled($value)) {
                    return false;
                }

                $normalized = Str::of((string) $value)
                    ->lower()
                    ->squish()
                    ->toString();

                return !in_array($normalized, ['workshop', 'workshops', 'program', 'payment', 'flexlabs payment'], true);
            })
            ->first();

        if (filled($candidate)) {
            return (string) $candidate;
        }

        $workshopId = data_get($order, 'workshop_id') ?: data_get($order, 'workshop.id');

        if ($workshopId) {
            return 'Workshop #' . $workshopId;
        }

        return 'FlexLabs Workshop #' . ($payment->order_id ?: $payment->id);
    }

    private function resolveFullOrderForPayment(Payment $payment): ?Order
    {
        $order = $payment->order;

        $hasCompletePricingColumns = $order
            && array_key_exists('original_price', $order->getAttributes())
            && array_key_exists('discount', $order->getAttributes())
            && array_key_exists('final_price', $order->getAttributes());

        $hasSourceColumns = $order
            && array_key_exists('order_type', $order->getAttributes())
            && array_key_exists('workshop_id', $order->getAttributes());

        $needsWorkshopRelation = $order
            && filled($order->getAttribute('workshop_id'))
            && !$order->relationLoaded('workshop');

        if ($hasCompletePricingColumns && $hasSourceColumns && !$needsWorkshopRelation) {
            return $order;
        }

        if (!$payment->order_id) {
            return $order;
        }

        $freshOrder = Order::with([
            'student:id,full_name,email,phone,city',
            'batch:id,program_id,name,start_date,end_date',
            'batch.program:id,name',
            'workshop',
        ])->find($payment->order_id);

        if ($freshOrder) {
            $payment->setRelation('order', $freshOrder);

            return $freshOrder;
        }

        return $order;
    }

    private function makeFinancialRow(
        string $label,
        string $details,
        float $amount,
        string $type,
        bool $isNegative = false,
        bool $isEmphasis = false
    ): array {
        return [
            'label' => $label,
            'description' => $label,
            'details' => $details,
            'meta' => $details,
            'amount' => $amount,
            'rate' => $amount,
            'type' => $type,
            'is_negative' => $isNegative,
            'is_emphasis' => $isEmphasis,
        ];
    }

    private function financialRowsToDocumentItems(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row) {
                $label = (string) ($row['label'] ?? $row['description'] ?? '-');
                $details = (string) ($row['details'] ?? $row['meta'] ?? '');
                $amount = $this->moneyValue($row['amount'] ?? 0);

                return [
                    'label' => $label,
                    'description' => $label,
                    'details' => $details,
                    'meta' => $details,
                    'qty' => 1,
                    'rate' => $amount,
                    'amount' => $amount,
                    'type' => $row['type'] ?? null,
                    'is_negative' => (bool) ($row['is_negative'] ?? $amount < 0),
                    'is_emphasis' => (bool) ($row['is_emphasis'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    private function resolvePreviousPaidAmount(Payment $payment): float
    {
        if (!$payment->order_id) {
            return 0;
        }

        return $this->moneyValue(
            Payment::query()
                ->where('order_id', $payment->order_id)
                ->where('status', 'paid')
                ->when($payment->id, function ($query) use ($payment) {
                    $query->where('id', '<', $payment->id);
                })
                ->sum('amount')
        );
    }

    private function resolveProgramDescription(Payment $payment): string
    {
        $programName = data_get($payment, 'order.batch.program.name');
        $batchName = data_get($payment, 'order.batch.name');

        return collect([$programName, $batchName])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ') ?: 'FlexLabs Program';
    }

    private function resolveFullPaymentScheduleForPayment(Payment $payment): ?PaymentSchedule
    {
        $schedule = $payment->paymentSchedule;

        if (!$payment->payment_schedule_id) {
            return $schedule;
        }

        $hasSourceColumns = $schedule
            && (
                array_key_exists('source_type', $schedule->getAttributes())
                || array_key_exists('type', $schedule->getAttributes())
                || array_key_exists('source_item', $schedule->getAttributes())
                || array_key_exists('source_item_id', $schedule->getAttributes())
                || array_key_exists('item_name', $schedule->getAttributes())
            );

        if ($hasSourceColumns) {
            return $schedule;
        }

        $freshSchedule = PaymentSchedule::find($payment->payment_schedule_id);

        if ($freshSchedule) {
            $payment->setRelation('paymentSchedule', $freshSchedule);

            return $freshSchedule;
        }

        return $schedule;
    }

    private function resolvePaymentSourceContext(
        Payment $payment,
        ?Order $order = null,
        ?PaymentSchedule $schedule = null
    ): array {
        $order = $order ?: $this->resolveFullOrderForPayment($payment);
        $schedule = $schedule ?: $this->resolveFullPaymentScheduleForPayment($payment);

        $scheduleContext = $this->resolveScheduleSourceContext($schedule);
        $orderItemContext = $this->resolveOrderItemSourceContext($order);
        $orderContext = $this->resolveOrderColumnSourceContext($order);

        $sourceType = $orderItemContext['source_type']
            ?: $orderContext['source_type']
            ?: $scheduleContext['source_type']
            ?: $this->resolveProgramSourceType($payment);

        $sourceItemName = $orderItemContext['source_item_name']
            ?: $orderContext['source_item_name']
            ?: $this->resolveProgramSourceName($payment)
            ?: $scheduleContext['source_item_name'];

        $sourceDescription = $orderItemContext['source_description']
            ?: $orderContext['source_description']
            ?: $this->resolveProgramDescription($payment)
            ?: $scheduleContext['source_description'];

        $sourceTypeLabel = $this->humanizeSourceType($sourceType);

        if (!$sourceTypeLabel && $sourceItemName) {
            $sourceTypeLabel = 'Order';
        }

        if (!$sourceTypeLabel) {
            $sourceTypeLabel = 'Program';
        }

        if (!$sourceItemName) {
            $sourceItemName = $sourceDescription ?: 'FlexLabs Payment';
        }

        if (!$sourceDescription) {
            $sourceDescription = $sourceItemName;
        }

        return [
            'source_type' => $sourceType ?: Str::slug($sourceTypeLabel, '_'),
            'source_type_label' => $sourceTypeLabel,
            'source_item_name' => $sourceItemName,
            'source_description' => $sourceDescription,
            'schedule_title' => $schedule?->title,
            'order_item' => $orderItemContext['raw'] ?? null,
        ];
    }

    private function resolveScheduleSourceContext(?PaymentSchedule $schedule): array
    {
        if (!$schedule) {
            return $this->emptySourceContext();
        }

        $sourceType = $this->firstFilledAttribute($schedule, [
            'source_type',
            'type',
            'item_type',
            'source',
            'category',
        ]);

        $sourceItemName = $this->firstFilledAttribute($schedule, [
            'source_item',
            'source_item_name',
            'item_name',
            'name',
            'title',
            'description',
        ]);

        $sourceItemId = $this->firstFilledAttribute($schedule, [
            'source_item_id',
            'source_id',
            'item_id',
            'reference_id',
        ]);

        if (!$sourceItemName && $sourceType && $sourceItemId) {
            $sourceItemName = $this->resolveSourceModelName($sourceType, $sourceItemId);
        }

        $sourceDescription = collect([
            $this->humanizeSourceType($sourceType),
            $sourceItemName,
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');

        return [
            'source_type' => $sourceType,
            'source_type_label' => $this->humanizeSourceType($sourceType),
            'source_item_name' => $sourceItemName,
            'source_description' => $sourceDescription,
            'raw' => $schedule->getAttributes(),
        ];
    }

    private function resolveOrderItemSourceContext(?Order $order): array
    {
        if (!$order?->id) {
            return $this->emptySourceContext();
        }

        $itemRows = $this->resolveOrderItemRows($order);

        if (empty($itemRows)) {
            return $this->emptySourceContext();
        }

        $firstItem = $itemRows[0];

        $sourceType = $firstItem['source_type'] ?? null;
        $sourceItemName = $firstItem['source_item_name'] ?? null;

        if (count($itemRows) > 1) {
            $sourceItemName = collect($itemRows)
                ->pluck('source_item_name')
                ->filter(fn ($value) => filled($value))
                ->take(3)
                ->implode(', ');

            if (count($itemRows) > 3) {
                $sourceItemName .= ' +' . (count($itemRows) - 3) . ' more';
            }
        }

        $sourceDescription = collect([
            $this->humanizeSourceType($sourceType),
            $sourceItemName,
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');

        return [
            'source_type' => $sourceType,
            'source_type_label' => $this->humanizeSourceType($sourceType),
            'source_item_name' => $sourceItemName,
            'source_description' => $sourceDescription,
            'raw' => $firstItem,
        ];
    }

    private function resolveOrderItemRows(Order $order): array
    {
        $candidateTables = [
            'order_items' => ['order_id'],
            'sales_order_items' => ['order_id', 'sales_order_id'],
            'sales_order_details' => ['order_id', 'sales_order_id'],
            'order_details' => ['order_id'],
        ];

        foreach ($candidateTables as $table => $orderColumns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $orderColumn = collect($orderColumns)
                ->first(fn ($column) => Schema::hasColumn($table, $column));

            if (!$orderColumn) {
                continue;
            }

            $rows = DB::table($table)
                ->where($orderColumn, $order->id)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            return $rows
                ->map(function ($row) use ($table) {
                    return $this->normalizeOrderItemRow((array) $row, $table);
                })
                ->filter(fn ($row) => filled($row['source_item_name'] ?? null) || filled($row['source_type'] ?? null))
                ->values()
                ->all();
        }

        return [];
    }

    private function normalizeOrderItemRow(array $row, string $table): array
    {
        $sourceType = $this->firstFilledArrayValue($row, [
            'source_type',
            'item_type',
            'type',
            'category',
            'source',
        ]);

        $sourceItemName = $this->firstFilledArrayValue($row, [
            'source_item',
            'source_item_name',
            'item_name',
            'name',
            'title',
            'description',
            'label',
        ]);

        $sourceItemId = $this->firstFilledArrayValue($row, [
            'source_item_id',
            'source_id',
            'item_id',
            'reference_id',
            'program_id',
            'batch_id',
            'workshop_id',
            'trial_schedule_id',
            'trial_theme_id',
        ]);

        if (!$sourceType) {
            $sourceType = $this->inferSourceTypeFromRow($row);
        }

        if (!$sourceItemName && $sourceType && $sourceItemId) {
            $sourceItemName = $this->resolveSourceModelName($sourceType, $sourceItemId);
        }

        return [
            'source_type' => $sourceType,
            'source_type_label' => $this->humanizeSourceType($sourceType),
            'source_item_name' => $sourceItemName,
            'amount' => $this->firstFilledArrayValue($row, [
                'amount',
                'total_amount',
                'subtotal',
                'line_total',
                'total',
                'price',
                'unit_price',
            ]),
            'qty' => $this->firstFilledArrayValue($row, [
                'qty',
                'quantity',
            ]) ?: 1,
            'table' => $table,
            'raw' => $row,
        ];
    }

    private function resolveOrderColumnSourceContext(?Order $order): array
    {
        if (!$order) {
            return $this->emptySourceContext();
        }

        $sourceType = $this->firstFilledAttribute($order, [
            'source_type',
            'order_type',
            'type',
            'item_type',
            'category',
        ]);

        $sourceItemName = $this->firstFilledAttribute($order, [
            'source_item',
            'source_item_name',
            'item_name',
            'name',
            'title',
            'description',
        ]);

        $sourceItemId = $this->firstFilledAttribute($order, [
            'source_item_id',
            'source_id',
            'item_id',
            'reference_id',
            'workshop_id',
            'public_workshop_id',
            'workshop_schedule_id',
            'trial_schedule_id',
            'trial_theme_id',
            'batch_id',
        ]);

        if (!$sourceType) {
            $sourceType = $this->inferSourceTypeFromRow($order->getAttributes());
        }

        if (!$sourceItemName && $sourceType && $sourceItemId) {
            $sourceItemName = $this->resolveSourceModelName($sourceType, $sourceItemId);
        }

        $sourceDescription = collect([
            $this->humanizeSourceType($sourceType),
            $sourceItemName,
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ');

        return [
            'source_type' => $sourceType,
            'source_type_label' => $this->humanizeSourceType($sourceType),
            'source_item_name' => $sourceItemName,
            'source_description' => $sourceDescription,
            'raw' => $order->getAttributes(),
        ];
    }

    private function resolveProgramSourceType(Payment $payment): ?string
    {
        return data_get($payment, 'order.batch.program.name') || data_get($payment, 'order.batch.name')
            ? 'program'
            : null;
    }

    private function resolveProgramSourceName(Payment $payment): ?string
    {
        $programName = data_get($payment, 'order.batch.program.name');
        $batchName = data_get($payment, 'order.batch.name');

        return collect([$programName, $batchName])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ') ?: null;
    }

    private function resolveSourceModelName(?string $sourceType, mixed $sourceItemId): ?string
    {
        if (!$sourceType || !$sourceItemId) {
            return null;
        }

        $normalizedType = Str::of($sourceType)
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        $tableCandidates = match ($normalizedType) {
            'program', 'course' => ['programs'],
            'batch', 'class_batch' => ['batches'],
            'workshop' => ['workshops', 'workshop_schedules', 'public_workshops'],
            'webinar' => ['webinars', 'workshops', 'workshop_schedules'],
            'trial', 'trial_class' => ['trial_schedules', 'trial_themes'],
            default => [
                Str::plural($normalizedType),
                $normalizedType,
            ],
        };

        foreach ($tableCandidates as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) {
                continue;
            }

            $nameColumn = collect(['title', 'name', 'workshop_title', 'theme_title', 'theme_name', 'theme', 'topic', 'subject', 'program_name'])
                ->first(fn ($column) => Schema::hasColumn($table, $column));

            if (!$nameColumn) {
                continue;
            }

            $name = DB::table($table)
                ->where('id', $sourceItemId)
                ->value($nameColumn);

            if (filled($name)) {
                return (string) $name;
            }
        }

        return null;
    }

    private function inferSourceTypeFromRow(array $row): ?string
    {
        $map = [
            'program_id' => 'program',
            'batch_id' => 'batch',
            'workshop_id' => 'workshop',
            'webinar_id' => 'webinar',
            'trial_schedule_id' => 'trial',
            'trial_theme_id' => 'trial',
        ];

        foreach ($map as $column => $sourceType) {
            if (filled($row[$column] ?? null)) {
                return $sourceType;
            }
        }

        return null;
    }

    private function firstFilledAttribute(object $model, array $keys): ?string
    {
        $attributes = method_exists($model, 'getAttributes') ? $model->getAttributes() : [];

        foreach ($keys as $key) {
            $value = null;

            if (str_contains($key, '.')) {
                $value = data_get($model, $key);
            } elseif (array_key_exists($key, $attributes)) {
                $value = $attributes[$key];
            }

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function firstFilledArrayValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $row[$key];

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function humanizeSourceType(?string $sourceType): ?string
    {
        if (!filled($sourceType)) {
            return null;
        }

        $normalized = Str::of($sourceType)
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();

        return match ($normalized) {
            'program', 'course' => 'Program',
            'batch', 'class batch' => 'Batch',
            'workshop' => 'Workshop',
            'webinar' => 'Webinar',
            'trial', 'trial class' => 'Trial Class',
            default => Str::headline($normalized),
        };
    }

    private function buildSourceMoneyLabel(string $prefix, string $sourceTypeLabel, string $suffix): string
    {
        if ($sourceTypeLabel === 'Program') {
            return $prefix === 'Final'
                ? 'Final Tuition Fee'
                : $prefix . ' Program ' . $suffix;
        }

        if ($sourceTypeLabel === 'Order') {
            return $prefix . ' Order Amount';
        }

        return trim($prefix . ' ' . $sourceTypeLabel . ' ' . $suffix);
    }

    private function buildSourceDiscountLabel(string $sourceTypeLabel): string
    {
        if ($sourceTypeLabel === 'Program') {
            return 'Special Program Discount';
        }

        if ($sourceTypeLabel === 'Order') {
            return 'Order Discount / Adjustment';
        }

        return $sourceTypeLabel . ' Discount / Adjustment';
    }

    private function buildGenericInstallmentDescription(array $sourceContext): string
    {
        $sourceTypeLabel = $sourceContext['source_type_label'] ?? 'Order';

        return $sourceTypeLabel === 'Program'
            ? 'Program payment installment'
            : $sourceTypeLabel . ' payment installment';
    }

    private function buildDocumentNote(array $sourceContext, string $documentType): string
    {
        if ($this->isWorkshopSourceContext($sourceContext)) {
            return $documentType === 'receipt'
                ? 'Payment has been received for the selected FlexLabs workshop.'
                : 'This invoice is for the selected FlexLabs workshop registration.';
        }

        $sourceTypeLabel = $sourceContext['source_type_label'] ?? 'order';
        $sourceTypeText = Str::lower($sourceTypeLabel);

        return $documentType === 'receipt'
            ? 'The final amount reflects the approved ' . $sourceTypeText . ' discount or payment adjustment. Remaining balance shows the outstanding amount after this payment.'
            : 'The final amount reflects the approved ' . $sourceTypeText . ' discount or payment adjustment. Remaining balance shows the outstanding amount after this invoice.';
    }

    private function emptySourceContext(): array
    {
        return [
            'source_type' => null,
            'source_type_label' => null,
            'source_item_name' => null,
            'source_description' => null,
            'raw' => null,
        ];
    }

    private function moneyValue(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function flexlabsAddressLines(): array
    {
        return [
            'MyRepublic Plaza Wing B 2nd Floor',
            'Jl. BSD Grand Boulevard',
            'BSD Green Office Park BSD City',
            'Desa Sampora, Kec. Cisauk',
            'Tangerang 15345',
        ];
    }

    private function paymentDocumentCss(): string
    {
        $cssPath = public_path('css/payments/invoice.css');

        if (!is_file($cssPath)) {
            return '';
        }

        return (string) file_get_contents($cssPath);
    }

    private function attachXenditPaymentLink(
        Payment $payment,
        Order $order,
        ?PaymentSchedule $paymentSchedule = null
    ): void {
        try {
            $student = $order->student;
            $batch = $order->batch;
            $program = $batch?->program;
            $sourceContext = $this->resolvePaymentSourceContext($payment, $order, $paymentSchedule);
            $itemName = $this->isWorkshopSourceContext($sourceContext, $order)
                ? (($sourceContext['source_item_name'] ?? null) ?: 'Workshop Payment')
                : ($paymentSchedule?->title
                    ?: ($sourceContext['source_item_name'] ?? null)
                    ?: ($sourceContext['source_type_label'] ? $sourceContext['source_type_label'] . ' Payment' : 'FlexLabs Payment'));

            $xenditResult = $this->xenditService->createPaymentLink($payment, [
                'full_name' => $student?->full_name,
                'email' => $student?->email,
                'phone' => $student?->phone,
                'program_name' => $program?->name,
                'batch_name' => $batch?->name,
                'source_type' => $sourceContext['source_type'],
                'source_type_label' => $sourceContext['source_type_label'],
                'source_item_name' => $sourceContext['source_item_name'],
                'item_name' => $itemName,
            ]);

            $payment->update([
                'payment_url' => $xenditResult['payment_url'] ?? $payment->payment_url,
                'gateway_transaction_id' => $xenditResult['gateway_transaction_id'] ?? $payment->gateway_transaction_id,
                'gateway_provider' => $xenditResult['gateway_provider'] ?? 'xendit',
                'gateway_payload' => $xenditResult['gateway_payload'] ?? null,
                'expired_at' => !empty($xenditResult['expired_at'])
                    ? Carbon::parse($xenditResult['expired_at'])
                    : $payment->expired_at,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $payment->update([
                'gateway_provider' => 'xendit',
                'gateway_payload' => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }

    private function normalizeManualInvoiceNumber(mixed $invoiceNumber): ?string
    {
        $invoiceNumber = trim((string) $invoiceNumber);

        return $invoiceNumber !== '' ? $invoiceNumber : null;
    }

    private function generateInvoiceNumber(Order $order): string
    {
        $batchCode = $this->resolveInvoiceBatchCode($order);
        $programCode = $this->resolveInvoiceProgramCode($order);
        $monthCode = now()->format('Ym');

        /*
        |--------------------------------------------------------------------------
        | Format invoice bulanan
        |--------------------------------------------------------------------------
        | Contoh: FLX-B1-SE-202606-0001
        |
        | Nomor urut sekarang dihitung per bulan untuk kombinasi batch + program.
        | Jadi bulan baru akan mulai lagi dari 0001.
        |
        | Pattern lama harian seperti FLX-B1-SE-20260602-0001 tetap ikut dibaca
        | agar invoice yang sudah dibuat pada bulan berjalan tidak diabaikan.
        */
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
                if (preg_match($pattern, (string) $invoiceNumber, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?: 0;

        $nextNumber = $maxSequence + 1;

        return $documentPrefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function resolveInvoiceBatchCode(Order $order): string
    {
        $orderType = Str::of((string) data_get($order, 'order_type', ''))
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        $workshopId = (int) (data_get($order, 'workshop_id') ?: data_get($order, 'workshop.id') ?: 0);

        if ($orderType === 'workshop' || $workshopId > 0) {
            return 'W' . max(1, $workshopId);
        }

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
        $orderType = Str::of((string) data_get($order, 'order_type', ''))
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        $workshopId = (int) (data_get($order, 'workshop_id') ?: data_get($order, 'workshop.id') ?: 0);

        if ($orderType === 'workshop' || $workshopId > 0) {
            $workshopName = $this->firstFilledAttribute($order, [
                'workshop.title',
                'workshop.name',
                'workshop.workshop_title',
                'workshop.theme_name',
                'workshop.theme',
                'workshop.topic',
                'workshop.subject',
            ]);

            return $workshopName ? $this->makeProgramCodeFromName($workshopName) : 'WS';
        }

        $programName = (string) data_get($order, 'batch.program.name', '');

        $normalizedProgramName = Str::of($programName)
            ->lower()
            ->replace(['/', '-', '&'], ' ')
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        if (Str::contains($normalizedProgramName, [
            'software engineer',
            'software engineering',
        ])) {
            return 'SE';
        }

        if (Str::contains($normalizedProgramName, [
            'ui ux',
            'ui ux design',
            'ui ux designer',
            'user interface user experience',
            'user experience design',
            'design',
        ])) {
            return 'DS';
        }

        return $this->makeProgramCodeFromName($programName);
    }

    private function makeProgramCodeFromName(?string $programName): string
    {
        $cleanName = Str::of($programName ?: 'Flexlabs')
            ->replace(['/', '-', '&'], ' ')
            ->replaceMatches('/[^A-Za-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        $words = collect(explode(' ', $cleanName))
            ->filter()
            ->values();

        $code = $words
            ->map(fn ($word) => Str::upper(Str::substr((string) $word, 0, 1)))
            ->implode('');

        if (strlen($code) < 2) {
            $code = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $cleanName) ?: 'FLX', 0, 3));
        }

        $code = Str::substr($code, 0, 3);

        return $code ?: 'FLX';
    }

    private function resolveReceiptNumber(Payment $payment): string
    {
        $storedReceiptNumber = (string) ($payment->getAttribute('receipt_number') ?? '');

        if ($storedReceiptNumber !== '') {
            return $storedReceiptNumber;
        }

        $invoiceNumber = (string) $payment->invoice_number;

        if ($invoiceNumber !== '') {
            if (Str::startsWith($invoiceNumber, 'FLX-')) {
                return 'FLX-RCPT-' . Str::after($invoiceNumber, 'FLX-');
            }

            return 'RCPT-' . $invoiceNumber;
        }

        return 'FLX-RCPT-' . now()->format('Ymd') . '-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);
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
}
