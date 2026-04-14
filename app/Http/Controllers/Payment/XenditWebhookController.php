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

            if ($expectedToken === '') {
                return response()->json([
                    'success' => false,
                    'stage' => 'token_validation',
                    'message' => 'XENDIT_WEBHOOK_TOKEN is empty in server environment.',
                    'debug' => [
                        'callback_token' => $callbackToken,
                        'expected_token' => $expectedToken,
                        'headers' => $headers,
                        'payload' => $payload,
                    ],
                ], 500);
            }

            if ($callbackToken !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'stage' => 'token_validation',
                    'message' => 'Invalid callback token.',
                    'debug' => [
                        'callback_token' => $callbackToken,
                        'expected_token' => $expectedToken,
                        'headers' => $headers,
                        'payload' => $payload,
                    ],
                ], 403);
            }

            $externalId = (string) ($payload['external_id'] ?? '');
            $invoiceStatus = strtoupper((string) ($payload['status'] ?? ''));

            if ($externalId === '') {
                return response()->json([
                    'success' => false,
                    'stage' => 'payload_validation',
                    'message' => 'Missing external_id.',
                    'debug' => [
                        'payload' => $payload,
                    ],
                ], 422);
            }

            $payment = Payment::where('invoice_number', $externalId)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'stage' => 'payment_lookup',
                    'message' => 'Payment not found.',
                    'debug' => [
                        'external_id' => $externalId,
                        'invoice_status' => $invoiceStatus,
                        'payload' => $payload,
                    ],
                ], 404);
            }

            $updateData = [
                'gateway_provider' => 'xendit',
                'gateway_transaction_id' => $payload['id'] ?? $payment->gateway_transaction_id,
                'gateway_payload' => $payload,
            ];

            if (!empty($payload['expiry_date'])) {
                $updateData['expired_at'] = Carbon::parse($payload['expiry_date']);
            }

            switch ($invoiceStatus) {
                case 'PAID':
                case 'SETTLED':
                    $updateData['status'] = 'paid';
                    $updateData['paid_at'] = !empty($payload['paid_at'])
                        ? Carbon::parse($payload['paid_at'])
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
                    'debug' => [
                        'payment_id' => $payment->id,
                        'invoice_number' => $payment->invoice_number,
                        'update_data' => $this->normalizeForDebug($updateData),
                    ],
                ], 500);
            }

            $freshPayment = $payment->fresh();

            $this->syncRelatedStatuses($freshPayment);

            return response()->json([
                'success' => true,
                'stage' => 'completed',
                'message' => 'Webhook processed successfully.',
                'debug' => [
                    'payment_id' => $freshPayment?->id,
                    'invoice_number' => $freshPayment?->invoice_number,
                    'incoming_status' => $invoiceStatus,
                    'final_payment_status' => $freshPayment?->status,
                    'gateway_transaction_id' => $freshPayment?->gateway_transaction_id,
                    'paid_at' => optional($freshPayment?->paid_at)->format('Y-m-d H:i:s'),
                    'expired_at' => optional($freshPayment?->expired_at)->format('Y-m-d H:i:s'),
                    'payload' => $payload,
                ],
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

        $updated = $order->update([
            'status' => $newStatus,
        ]);

        if (!$updated) {
            throw new \RuntimeException('Failed to update order status.');
        }
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