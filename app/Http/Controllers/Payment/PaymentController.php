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

        $payments = Payment::with([
                'order:id,student_id,batch_id,original_price,discount,final_price,status',
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
            ->get(['id', 'student_id', 'batch_id', 'original_price', 'discount', 'final_price', 'status']);

        $paymentSchedules = PaymentSchedule::with([
                'order:id,student_id,batch_id,original_price,discount,final_price,status',
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
            'order:id,student_id,batch_id,original_price,discount,final_price,status',
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
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
                'public_payment_link' => $payment->public_token
                    ? route('public.payments.show', $payment->public_token)
                    : null,
                'order' => $payment->order ? [
                    'id' => $payment->order->id,
                    'original_price' => (float) $payment->order->original_price,
                    'discount' => (float) $payment->order->discount,
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
                'payment_date' => $validated['payment_date'] ?? null,
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
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
            ]);
        });

        if ($payment->status === 'pending') {
            $this->attachXenditPaymentLink($payment, $order, $paymentSchedule);
        }

        $this->syncRelatedStatuses($payment);

        $payment->load([
            'order:id,student_id,batch_id,original_price,discount,final_price,status',
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
                'payment_date' => $validated['payment_date'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? $payment->gateway_transaction_id,
                'gateway_provider' => $validated['gateway_provider'] ?? $payment->gateway_provider,
                'status' => $validated['status'],
                'expired_at' => !empty($validated['expired_at'])
                    ? Carbon::parse($validated['expired_at'])
                    : $payment->expired_at,
                'notes' => $validated['notes'] ?? null,
                'paid_at' => $validated['status'] === 'paid'
                    ? ($payment->paid_at ?? now())
                    : null,
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
            'order:id,student_id,batch_id,original_price,discount,final_price,status',
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
        $receiptDate = $data['paidAt'] ?? $payment->paid_at ?? $payment->payment_date ?? $payment->updated_at ?? $payment->created_at;
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
                    'label' => 'Paid at',
                    'value' => $receiptDate ? Carbon::parse($receiptDate)->format('d F Y H:i') : '-',
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
            'documentStatusLabel' => Str::headline((string) $payment->status),
            'documentActionLabel' => $isPaid
                ? 'Already Paid'
                : ($isExpired ? 'Link Expired' : 'Pay Now'),
        ]);
    }

    private function paymentDocumentRelations(): array
    {
        return [
            'order:id,student_id,batch_id,original_price,discount,final_price,status,notes',
            'order.student:id,full_name,email,phone,city',
            'order.batch:id,program_id,name,start_date,end_date',
            'order.batch.program:id,name',
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

        return [
            'payment' => $payment,
            'order' => $order,
            'student' => $student,
            'batch' => $batch,
            'program' => $program,
            'schedule' => $schedule,
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
            'documentNote' => 'The final tuition fee reflects the approved program discount or payment adjustment. Remaining balance shows the outstanding amount after this invoice.',
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

        return [
            'payment' => $payment,
            'order' => $order,
            'student' => $student,
            'batch' => $batch,
            'program' => $program,
            'schedule' => $schedule,
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
            'paidAt' => $payment->paid_at ?: $payment->payment_date ?: $payment->updated_at,
            'documentNote' => 'The final tuition fee reflects the approved program discount or payment adjustment. Remaining balance shows the outstanding amount after this payment.',
            'companyName' => 'FlexLabs',
            'companyAddressLines' => $this->flexlabsAddressLines(),
        ];
    }

    private function buildPaymentFinancialSummary(Payment $payment, string $documentType = 'invoice'): array
    {
        $order = $this->resolveFullOrderForPayment($payment);
        $schedule = $payment->paymentSchedule;

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
            : 'Program payment installment';

        $programDescription = $this->resolveProgramDescription($payment);

        // Row diskon dibuat eksplisit dan selalu dikirim dari controller.
        // Jadi view tidak perlu nebak lagi dan label "Special Program Discount" tidak hilang.
        $rows = [
            $this->makeFinancialRow(
                label: 'Normal Program Fee',
                details: $programDescription,
                amount: $normalProgramFee,
                type: 'normal_fee'
            ),
            $this->makeFinancialRow(
                label: 'Special Program Discount',
                details: 'Approved program discount or payment adjustment',
                amount: -1 * abs($programDiscount),
                type: 'discount',
                isNegative: $programDiscount > 0
            ),
            $this->makeFinancialRow(
                label: 'Final Tuition Fee',
                details: 'Program fee after discount or adjustment',
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
            'previous_payment_received' => $previousPaymentReceived,
            'current_amount' => $currentAmount,
            'remaining_balance' => $remainingBalance,
            'remaining_balance_label' => $remainingLabel,
            'rows' => $rows,
            'pricing_rows' => array_slice($rows, 0, 3),
            'payment_rows' => array_slice($rows, 3),
            'items' => $this->financialRowsToDocumentItems($rows),
        ];
    }

    private function resolveFullOrderForPayment(Payment $payment): ?Order
    {
        $order = $payment->order;

        $hasCompletePricingColumns = $order
            && array_key_exists('original_price', $order->getAttributes())
            && array_key_exists('discount', $order->getAttributes())
            && array_key_exists('final_price', $order->getAttributes());

        if ($hasCompletePricingColumns) {
            return $order;
        }

        if (!$payment->order_id) {
            return $order;
        }

        $freshOrder = Order::with([
            'student:id,full_name,email,phone,city',
            'batch:id,program_id,name,start_date,end_date',
            'batch.program:id,name',
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

            $xenditResult = $this->xenditService->createPaymentLink($payment, [
                'full_name' => $student?->full_name,
                'email' => $student?->email,
                'phone' => $student?->phone,
                'program_name' => $program?->name,
                'batch_name' => $batch?->name,
                'item_name' => $paymentSchedule?->title ?: 'Program Payment',
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
        $dateCode = now()->format('Ymd');

        // Format: FLX-B1-SE-20260507-0001
        // Nomor urut dihitung per batch + program, bukan per tanggal/bulan.
        $sequencePrefix = 'FLX-' . $batchCode . '-' . $programCode . '-';
        $documentPrefix = $sequencePrefix . $dateCode . '-';
        $pattern = '/^' . preg_quote($sequencePrefix, '/') . '\d{8}-(\d+)$/';

        $maxSequence = Payment::where('invoice_number', 'like', $sequencePrefix . '%')
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
