<?php

namespace App\Services\PaymentGateway;

use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditService
{
    public function createPaymentLink(Payment $payment, array $customer = []): array
    {
        $secretKey = (string) config('services.xendit.secret_key');
        $apiBase = rtrim((string) config('services.xendit.api_base'), '/');

        if ($secretKey === '') {
            throw new RuntimeException('Xendit secret key is not configured.');
        }

        $descriptionParts = array_filter([
            'Payment for ' . ($payment->invoice_number ?? 'Invoice'),
            $customer['program_name'] ?? null,
            $customer['batch_name'] ?? null,
        ]);

        $payload = [
            'external_id' => $payment->invoice_number,
            'amount' => (float) $payment->amount,
            'description' => implode(' - ', $descriptionParts),
            'invoice_duration' => $this->resolveInvoiceDurationInSeconds($payment),
            'customer' => [
                'given_names' => $customer['full_name'] ?? 'Customer',
                'email' => $customer['email'] ?? 'no-email@flexlabs.local',
                'mobile_number' => $this->normalizePhoneNumber($customer['phone'] ?? null),
            ],
            'success_redirect_url' => route('public.payments.show', $payment->public_token) . '?payment_status=success',
            'failure_redirect_url' => route('public.payments.show', $payment->public_token) . '?payment_status=failed',
            'currency' => 'IDR',
            'items' => [
                [
                    'name' => $customer['item_name'] ?? ('Payment ' . $payment->invoice_number),
                    'quantity' => 1,
                    'price' => (float) $payment->amount,
                    'category' => 'Education',
                ],
            ],
        ];

        $payload['customer'] = array_filter(
            $payload['customer'],
            fn ($value) => $value !== null && $value !== ''
        );

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($apiBase . '/v2/invoices', $payload)
                ->throw();

            $data = $response->json();

            if (!is_array($data) || empty($data['invoice_url'])) {
                throw new RuntimeException('Xendit did not return a valid invoice URL.');
            }

            return [
                'payment_url' => $data['invoice_url'] ?? null,
                'gateway_transaction_id' => $data['id'] ?? null,
                'gateway_provider' => 'xendit',
                'gateway_payload' => $data,
                'expired_at' => $data['expiry_date'] ?? null,
                'raw' => $data,
            ];
        } catch (RequestException $e) {
            $responseBody = $e->response?->json();

            throw new RuntimeException(
                'Failed to create Xendit payment link: ' . json_encode($responseBody ?: $e->getMessage())
            );
        }
    }

    private function resolveInvoiceDurationInSeconds(Payment $payment): int
    {
        if ($payment->expired_at) {
            $seconds = now()->diffInSeconds($payment->expired_at, false);

            if ($seconds > 0) {
                return $seconds;
            }
        }

        return 86400;
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', trim($phone));

        if (!$normalized) {
            return null;
        }

        if (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }

        if (str_starts_with($normalized, '0')) {
            return '62' . substr($normalized, 1);
        }

        if (str_starts_with($normalized, '8')) {
            return '62' . $normalized;
        }

        return $normalized;
    }
}