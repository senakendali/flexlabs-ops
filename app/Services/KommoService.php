<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KommoService
{
    public function createSemLead(array $data): array
    {
        $this->ensureConfigured();

        $payload = [
            [
                'name' => $this->buildLeadName($data),
                'pipeline_id' => (int) config('services.kommo.pipeline_id'),
                'status_id' => (int) config('services.kommo.status_id'),
                'price' => 0,
                '_embedded' => [
                    'contacts' => [
                        [
                            'name' => $this->safeValue($data['name'] ?? null, 'SEM Lead'),
                            'custom_fields_values' => $this->buildContactCustomFields($data),
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->http()
            ->post('/api/v4/leads/complex', $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Kommo create lead failed. Status: ' . $response->status() . '. Body: ' . $response->body()
            );
        }

        $json = $response->json() ?? [];

        $leadId = $this->extractLeadId($json);

        if ($leadId) {
            $this->addSemLeadNote($leadId, $data);
        }

        return $json;
    }

    public function listPipelines(): array
    {
        $this->ensureConfigured(
            checkPipeline: false,
            checkStatus: false
        );

        $response = $this->http()
            ->get('/api/v4/leads/pipelines');

        if (!$response->successful()) {
            throw new RuntimeException(
                'Kommo list pipelines failed. Status: ' . $response->status() . '. Body: ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function listStatuses(int $pipelineId): array
    {
        $this->ensureConfigured(
            checkPipeline: false,
            checkStatus: false
        );

        $response = $this->http()
            ->get("/api/v4/leads/pipelines/{$pipelineId}/statuses");

        if (!$response->successful()) {
            throw new RuntimeException(
                'Kommo list statuses failed. Status: ' . $response->status() . '. Body: ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    private function addSemLeadNote(int $leadId, array $data): void
    {
        try {
            $payload = [
                [
                    'entity_id' => $leadId,
                    'note_type' => 'common',
                    'params' => [
                        'text' => $this->buildSemNoteText($data),
                    ],
                ],
            ];

            $response = $this->http()
                ->post('/api/v4/leads/notes', $payload);

            if (!$response->successful()) {
                Log::warning('Kommo lead created but note failed.', [
                    'lead_id' => $leadId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Kommo lead created but note exception occurred.', [
                'lead_id' => $leadId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function buildContactCustomFields(array $data): array
    {
        $fields = [];

        $phone = $this->normalizePhone($data['whatsapp_number'] ?? null);

        if ($phone !== null) {
            $fields[] = [
                'field_code' => 'PHONE',
                'values' => [
                    [
                        'value' => $phone,
                        'enum_code' => 'WORK',
                    ],
                ],
            ];
        }

        $email = $this->nullableString($data['email'] ?? null);

        if ($email !== null) {
            $fields[] = [
                'field_code' => 'EMAIL',
                'values' => [
                    [
                        'value' => $email,
                        'enum_code' => 'WORK',
                    ],
                ],
            ];
        }

        return $fields;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.kommo.long_lived_token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.kommo.timeout', 15));
    }

    private function baseUrl(): string
    {
        $baseUrl = $this->nullableString(config('services.kommo.base_url'));

        if ($baseUrl !== null) {
            return rtrim($baseUrl, '/');
        }

        $subdomain = $this->nullableString(config('services.kommo.subdomain'));

        if ($subdomain === null) {
            throw new RuntimeException('KOMMO_SUBDOMAIN is not configured.');
        }

        return 'https://' . $subdomain . '.kommo.com';
    }

    private function ensureConfigured(bool $checkPipeline = true, bool $checkStatus = true): void
    {
        if (!config('services.kommo.enabled')) {
            throw new RuntimeException('KOMMO_ENABLED is false.');
        }

        if (empty(config('services.kommo.long_lived_token'))) {
            throw new RuntimeException('KOMMO_LONG_LIVED_TOKEN is not configured.');
        }

        if ($checkPipeline && empty(config('services.kommo.pipeline_id'))) {
            throw new RuntimeException('KOMMO_PIPELINE_ID is not configured.');
        }

        if ($checkStatus && empty(config('services.kommo.status_id'))) {
            throw new RuntimeException('KOMMO_STATUS_ID is not configured.');
        }

        $this->baseUrl();
    }

    private function buildLeadName(array $data): string
    {
        $program = $this->safeValue($data['program_interest'] ?? null, 'Konsultasi Program');
        $name = $this->safeValue($data['name'] ?? null, 'Lead');

        return Str::limit("SEM - {$program} - {$name}", 250, '');
    }

    private function buildSemNoteText(array $data): string
    {
        $bestContactTime = $this->formatBestContactTime($data['best_contact_time'] ?? null);

        $rows = [
            'Lead dari Landing Page SEM FlexLabs',
            '',
            'Nama: ' . $this->safeValue($data['name'] ?? null),
            'WhatsApp: ' . $this->safeValue($data['whatsapp_number'] ?? null),
            'Program: ' . $this->safeValue($data['program_interest'] ?? null),
            'Kebutuhan: ' . $this->safeValue($data['help_need'] ?? null),
            'Waktu terbaik dihubungi: ' . $bestContactTime,
            '',
            'Tracking',
            'Source: ' . $this->safeValue($data['source'] ?? null, 'google_sem'),
            'Landing Page: ' . $this->safeValue($data['landing_page_url'] ?? null),
            'Referrer: ' . $this->safeValue($data['referrer_url'] ?? null),
            'UTM Source: ' . $this->safeValue($data['utm_source'] ?? null),
            'UTM Medium: ' . $this->safeValue($data['utm_medium'] ?? null),
            'UTM Campaign: ' . $this->safeValue($data['utm_campaign'] ?? null),
            'UTM Content: ' . $this->safeValue($data['utm_content'] ?? null),
            'UTM Term: ' . $this->safeValue($data['utm_term'] ?? null),
            'GCLID: ' . $this->safeValue($data['gclid'] ?? null),
            'GBRAID: ' . $this->safeValue($data['gbraid'] ?? null),
            'WBRAID: ' . $this->safeValue($data['wbraid'] ?? null),
        ];

        return implode("\n", $rows);
    }

    private function extractLeadId(array $response): ?int
    {
        $lead = Arr::get($response, '_embedded.leads.0')
            ?? Arr::get($response, '0')
            ?? $response;

        $id = Arr::get($lead, 'id')
            ?? Arr::get($lead, 'lead_id');

        return $id ? (int) $id : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        $number = $this->nullableString($value);

        if ($number === null) {
            return null;
        }

        $number = preg_replace('/[^0-9+]/', '', $number) ?: '';

        if (Str::startsWith($number, '+')) {
            $number = substr($number, 1);
        }

        if (Str::startsWith($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        if (Str::startsWith($number, '8')) {
            $number = '62' . $number;
        }

        return $number !== '' ? $number : null;
    }

    private function formatBestContactTime(?string $value): string
    {
        $labels = [
            'secepatnya' => 'Secepatnya',
            'pagi' => 'Pagi',
            'siang' => 'Siang',
            'sore' => 'Sore',
            'malam' => 'Malam',
        ];

        $value = $this->nullableString($value);

        if ($value === null) {
            return '-';
        }

        return $labels[$value] ?? Str::of($value)
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function safeValue(mixed $value, string $fallback = '-'): string
    {
        $value = $this->nullableString($value);

        return $value ?? $fallback;
    }
}