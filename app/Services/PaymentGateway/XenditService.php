<?php

namespace App\Services\PaymentGateway;

use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class XenditService
{
    public function createPaymentLink(
        Payment $payment,
        array $customer = []
    ): array {
        $secretKey = $this->resolveSecretKey();
        $apiBase = $this->resolveApiBase();

        $descriptionParts = array_filter([
            'Payment for ' . ($payment->invoice_number ?? 'Invoice'),
            $customer['program_name'] ?? null,
            $customer['batch_name'] ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Xendit external ID
        |--------------------------------------------------------------------------
        | Normal payment tetap memakai invoice number seperti logic sebelumnya.
        | Replacement link dapat mengirim external_id yang unik melalui customer.
        */
        $externalId = $customer['external_id']
            ?? $payment->invoice_number;

        if (empty($externalId)) {
            throw new RuntimeException(
                'Payment invoice number is required to create a Xendit payment link.'
            );
        }

        $payload = [
            'external_id' => $externalId,
            'amount' => (float) $payment->amount,
            'description' => implode(' - ', $descriptionParts),
            'invoice_duration' => $this->resolveInvoiceDurationInSeconds(
                $payment
            ),
            'customer' => [
                'given_names' => $customer['full_name'] ?? 'Customer',
                'email' => $customer['email']
                    ?? 'no-email@flexlabs.local',
                'mobile_number' => $this->normalizePhoneNumber(
                    $customer['phone'] ?? null
                ),
            ],
            'success_redirect_url' => route(
                'public.payments.show',
                $payment->public_token
            ) . '?payment_status=success',
            'failure_redirect_url' => route(
                'public.payments.show',
                $payment->public_token
            ) . '?payment_status=failed',
            'currency' => 'IDR',
            'items' => [
                [
                    'name' => $customer['item_name']
                        ?? ('Payment ' . $payment->invoice_number),
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

            if (
                !is_array($data)
                || empty($data['invoice_url'])
            ) {
                throw new RuntimeException(
                    'Xendit did not return a valid invoice URL.'
                );
            }

            return [
                'payment_url' => $data['invoice_url'] ?? null,
                'gateway_transaction_id' => $data['id'] ?? null,
                'gateway_provider' => 'xendit',
                'gateway_payload' => $data,
                'expired_at' => $data['expiry_date'] ?? null,
                'external_id' => $data['external_id']
                    ?? $externalId,
                'raw' => $data,
            ];
        } catch (RequestException $exception) {
            $responseBody = $exception->response?->json();

            throw new RuntimeException(
                'Failed to create Xendit payment link: '
                . json_encode(
                    $responseBody ?: $exception->getMessage()
                )
            );
        }
    }

    /**
     * Expire invoice Xendit yang sedang tersimpan pada payment.
     *
     * Method ini tidak mengubah status atau data pada database. Jika endpoint
     * expire tidak menemukan invoice PENDING, status invoice diverifikasi
     * terlebih dahulu sebelum update schedule diizinkan untuk dilanjutkan.
     */
    public function expirePaymentLink(Payment $payment): ?array
    {
        $invoiceId = trim(
            (string) $payment->gateway_transaction_id
        );

        if ($invoiceId === '') {
            return null;
        }

        $secretKey = $this->resolveSecretKey();
        $apiBase = $this->resolveApiBase();

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(
                    $apiBase
                    . '/invoices/'
                    . rawurlencode($invoiceId)
                    . '/expire!'
                )
                ->throw();

            $data = $response->json();

            return is_array($data) ? $data : [];
        } catch (RequestException $exception) {
            $responseBody = $exception->response?->json();
            $errorCode = is_array($responseBody)
                ? ($responseBody['error_code'] ?? null)
                : null;

            if ($errorCode === 'INVOICE_NOT_FOUND_ERROR') {
                return $this->resolveNonPendingInvoice(
                    $invoiceId,
                    $secretKey,
                    $apiBase,
                    is_array($responseBody) ? $responseBody : []
                );
            }

            throw new RuntimeException(
                'Failed to expire Xendit payment link: '
                . json_encode(
                    $responseBody ?: $exception->getMessage()
                )
            );
        }
    }

    /**
     * Membuat payment link pengganti dengan external ID baru.
     *
     * Invoice number FlexLabs tidak berubah. External ID unik ini hanya
     * digunakan oleh Xendit agar replacement invoice tidak dianggap duplicate.
     * Controller harus memanggil expirePaymentLink() terlebih dahulu.
     */
    public function createReplacementPaymentLink(
        Payment $payment,
        array $customer = []
    ): array {
        $customer['external_id'] = $this->generateReplacementExternalId(
            $payment
        );

        return $this->createPaymentLink($payment, $customer);
    }

    /**
     * Memastikan invoice yang gagal di-expire memang sudah tidak aktif.
     */
    private function resolveNonPendingInvoice(
        string $invoiceId,
        string $secretKey,
        string $apiBase,
        array $expireError
    ): array {
        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->timeout(30)
                ->get(
                    $apiBase
                    . '/v2/invoices/'
                    . rawurlencode($invoiceId)
                )
                ->throw();

            $invoice = $response->json();

            if (!is_array($invoice)) {
                throw new RuntimeException(
                    'Xendit returned invalid invoice data.'
                );
            }

            $status = strtoupper(
                (string) ($invoice['status'] ?? '')
            );

            if ($status === 'EXPIRED') {
                return [
                    'status' => 'EXPIRED',
                    'already_inactive' => true,
                    'invoice' => $invoice,
                ];
            }

            if (in_array($status, ['PAID', 'SETTLED'], true)) {
                throw new RuntimeException(
                    'The existing Xendit invoice has already been paid. '
                    . 'Payment schedule cannot be changed.'
                );
            }

            throw new RuntimeException(
                'The existing Xendit invoice could not be expired. '
                . 'Current invoice status: '
                . ($status ?: 'UNKNOWN')
                . '. Please verify the Xendit account/API key.'
            );
        } catch (RequestException $exception) {
            $responseBody = $exception->response?->json();

            throw new RuntimeException(
                'Could not verify the existing Xendit invoice. '
                . 'It may belong to another Xendit account or environment: '
                . json_encode(
                    $responseBody
                    ?: $expireError
                    ?: $exception->getMessage()
                )
            );
        }
    }

    private function generateReplacementExternalId(
        Payment $payment
    ): string {
        $invoiceNumber = trim(
            (string) $payment->invoice_number
        );

        if ($invoiceNumber === '') {
            $invoiceNumber = 'PAYMENT';
        }

        return implode('-', [
            $invoiceNumber,
            'R',
            $payment->id,
            now()->format('YmdHis'),
            Str::lower(Str::random(8)),
        ]);
    }

    private function resolveInvoiceDurationInSeconds(
        Payment $payment
    ): int {
        if ($payment->expired_at) {
            $seconds = now()->diffInSeconds(
                $payment->expired_at,
                false
            );

            if ($seconds > 0) {
                return $seconds;
            }
        }

        return 86400;
    }

    private function normalizePhoneNumber(
        ?string $phone
    ): ?string {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace(
            '/[^\d+]/',
            '',
            trim($phone)
        );

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

    private function resolveSecretKey(): string
    {
        $secretKey = (string) config(
            'services.xendit.secret_key'
        );

        if ($secretKey === '') {
            throw new RuntimeException(
                'Xendit secret key is not configured.'
            );
        }

        return $secretKey;
    }

    private function resolveApiBase(): string
    {
        $apiBase = rtrim(
            (string) config('services.xendit.api_base'),
            '/'
        );

        if ($apiBase === '') {
            throw new RuntimeException(
                'Xendit API base URL is not configured.'
            );
        }

        return $apiBase;
    }
}