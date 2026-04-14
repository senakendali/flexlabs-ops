<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $callbackToken = (string) $request->header('x-callback-token');
        $expectedToken = (string) config('services.xendit.webhook_token');

        if ($expectedToken === '' || $callbackToken !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback token.',
            ], 403);
        }

        $payload = $request->all();
        $externalId = (string) ($payload['external_id'] ?? '');
        $invoiceStatus = strtoupper((string) ($payload['status'] ?? ''));

        if ($externalId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing external_id.',
            ], 422);
        }

        $payment = Payment::where('invoice_number', $externalId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
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

        $payment->update($updateData);

        $this->syncRelatedStatuses($payment->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully.',
        ]);
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