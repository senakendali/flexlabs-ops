<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class XenditWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $headers = $request->headers->all();

            $callbackToken = (string) $request->header('x-callback-token');
            $expectedToken = (string) config('services.xendit.webhook_token');

            $externalId = (string) ($payload['external_id'] ?? '');
            $incomingStatus = strtoupper((string) ($payload['status'] ?? ''));
            $paidAtRaw = $payload['paid_at'] ?? null;
            $expiryDateRaw = $payload['expiry_date'] ?? null;
            $transactionId = $payload['id'] ?? null;

            $debug = [
                'expected' => [
                    'callback_token' => '[must match XENDIT_WEBHOOK_TOKEN]',
                    'external_id' => '[must match payments.invoice_number]',
                    'status' => '[PAID | SETTLED | EXPIRED | FAILED | VOIDED | PENDING | UNPAID]',
                ],
                'received' => [
                    'callback_token' => $callbackToken,
                    'external_id' => $externalId,
                    'status' => $incomingStatus,
                    'paid_at' => $paidAtRaw,
                    'expiry_date' => $expiryDateRaw,
                    'transaction_id' => $transactionId,
                ],
                'headers' => $headers,
                'payload' => $payload,
            ];

            if ($expectedToken === '') {
                return response()->json([
                    'success' => false,
                    'stage' => 'token_validation',
                    'message' => 'XENDIT_WEBHOOK_TOKEN is empty in server environment.',
                    'debug' => $debug,
                ], 500);
            }

            if ($callbackToken !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'stage' => 'token_validation',
                    'message' => 'Invalid callback token.',
                    'debug' => array_merge($debug, [
                        'comparison' => [
                            'expected_token' => $expectedToken,
                            'received_token' => $callbackToken,
                            'matched' => false,
                        ],
                    ]),
                ], 403);
            }

            if ($externalId === '') {
                return response()->json([
                    'success' => false,
                    'stage' => 'payload_validation',
                    'message' => 'Missing external_id from webhook payload.',
                    'debug' => $debug,
                ], 422);
            }

            $payment = Payment::where('invoice_number', $externalId)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'stage' => 'payment_lookup',
                    'message' => 'Payment not found using external_id.',
                    'debug' => array_merge($debug, [
                        'lookup' => [
                            'query_field' => 'invoice_number',
                            'query_value' => $externalId,
                            'matched' => false,
                            'latest_payments' => Payment::latest('id')
                                ->take(10)
                                ->get([
                                    'id',
                                    'invoice_number',
                                    'status',
                                    'gateway_transaction_id',
                                    'created_at',
                                ])
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'invoice_number' => $item->invoice_number,
                                        'status' => $item->status,
                                        'gateway_transaction_id' => $item->gateway_transaction_id,
                                        'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
                                    ];
                                })
                                ->values()
                                ->all(),
                        ],
                    ]),
                ], 404);
            }

            $before = [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'status' => $payment->status,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
                'expired_at' => optional($payment->expired_at)->format('Y-m-d H:i:s'),
                'gateway_transaction_id' => $payment->gateway_transaction_id,
            ];

            $updateData = [
                'gateway_provider' => 'xendit',
                'gateway_transaction_id' => $transactionId ?? $payment->gateway_transaction_id,
                'gateway_payload' => $payload,
            ];

            if (!empty($expiryDateRaw)) {
                $updateData['expired_at'] = Carbon::parse($expiryDateRaw);
            }

            switch ($incomingStatus) {
                case 'PAID':
                case 'SETTLED':
                    $updateData['status'] = 'paid';
                    $updateData['paid_at'] = !empty($paidAtRaw)
                        ? Carbon::parse($paidAtRaw)
                        : now();
                    $updateData['payment_date'] = $payment->payment_date ?? now();
                    break;

                case 'EXPIRED':
                    $updateData['status'] = 'expired';
                    $updateData['paid_at'] = null;
                    break;

                case 'FAILED':
                case 'VOIDED':
                    $updateData['status'] = 'failed';
                    $updateData['paid_at'] = null;
                    break;

                case 'PENDING':
                case 'UNPAID':
                default:
                    $updateData['status'] = 'pending';
                    break;
            }

            $updated = $payment->update($updateData);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'stage' => 'payment_update',
                    'message' => 'Payment update returned false.',
                    'debug' => array_merge($debug, [
                        'lookup' => [
                            'matched' => true,
                            'payment_id' => $payment->id,
                        ],
                        'before' => $before,
                        'update_data' => $this->normalizeForDebug($updateData),
                    ]),
                ], 500);
            }

            $freshPayment = $payment->fresh();

            $scheduleDebug = null;
            $orderDebug = null;

            if ($freshPayment->payment_schedule_id) {
                $paymentSchedule = PaymentSchedule::find($freshPayment->payment_schedule_id);

                if ($paymentSchedule) {
                    $scheduleDebug = $this->refreshScheduleStatus($paymentSchedule);
                }
            }

            $order = Order::find($freshPayment->order_id);

            if ($order) {
                $orderDebug = $this->refreshOrderStatus($order);
            }

            return response()->json([
                'success' => true,
                'stage' => 'completed',
                'message' => 'Webhook processed successfully.',
                'debug' => array_merge($debug, [
                    'lookup' => [
                        'matched' => true,
                        'payment_id' => $freshPayment->id,
                    ],
                    'before' => $before,
                    'update_data' => $this->normalizeForDebug($updateData),
                    'after' => [
                        'id' => $freshPayment->id,
                        'invoice_number' => $freshPayment->invoice_number,
                        'status' => $freshPayment->status,
                        'paid_at' => optional($freshPayment->paid_at)->format('Y-m-d H:i:s'),
                        'payment_date' => optional($freshPayment->payment_date)->format('Y-m-d'),
                        'expired_at' => optional($freshPayment->expired_at)->format('Y-m-d H:i:s'),
                        'gateway_transaction_id' => $freshPayment->gateway_transaction_id,
                        'gateway_provider' => $freshPayment->gateway_provider,
                    ],
                    'related_updates' => [
                        'payment_schedule' => $scheduleDebug,
                        'order' => $orderDebug,
                    ],
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'stage' => 'exception',
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())
                    ->take(10)
                    ->map(function ($item) {
                        return [
                            'file' => $item['file'] ?? null,
                            'line' => $item['line'] ?? null,
                            'function' => $item['function'] ?? null,
                            'class' => $item['class'] ?? null,
                        ];
                    })
                    ->values()
                    ->all(),
            ], 500);
        }
    }

    private function refreshScheduleStatus(PaymentSchedule $paymentSchedule): array
    {
        $beforeStatus = $paymentSchedule->status;

        $paidAmount = Payment::where('payment_schedule_id', $paymentSchedule->id)
            ->where('status', 'paid')
            ->sum('amount');

        if ($paidAmount >= (float) $paymentSchedule->amount) {
            $newStatus = 'paid';
        } elseif (
            $paymentSchedule->due_date &&
            now()->toDateString() > $paymentSchedule->due_date->format('Y-m-d')
        ) {
            $newStatus = 'overdue';
        } else {
            $newStatus = 'pending';
        }

        $updated = $paymentSchedule->update([
            'status' => $newStatus,
        ]);

        if (!$updated) {
            throw new \RuntimeException('Failed to update payment schedule status.');
        }

        return [
            'id' => $paymentSchedule->id,
            'before_status' => $beforeStatus,
            'after_status' => $paymentSchedule->fresh()?->status,
            'paid_amount' => (float) $paidAmount,
            'schedule_amount' => (float) $paymentSchedule->amount,
        ];
    }

    private function refreshOrderStatus(Order $order): array
    {
        $beforeStatus = $order->status;

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

        $updated = $order->update([
            'status' => $newStatus,
        ]);

        if (!$updated) {
            throw new \RuntimeException('Failed to update order status.');
        }

        return [
            'id' => $order->id,
            'before_status' => $beforeStatus,
            'after_status' => $order->fresh()?->status,
            'paid_amount' => (float) $paidAmount,
            'final_price' => (float) $finalPrice,
        ];
    }

    private function normalizeForDebug(array $data): array
    {
        return collect($data)->map(function ($value) {
            if ($value instanceof Carbon) {
                return $value->format('Y-m-d H:i:s');
            }

            return $value;
        })->all();
    }
}