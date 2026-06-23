<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\KommoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MetaLeadGoogleSheetWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        $payload = $this->extractPayload($request);

        $externalLeadId = $this->nullableText($payload['id'] ?? null);

        if (!$externalLeadId) {
            return response()->json([
                'success' => false,
                'message' => 'Meta lead id is required.',
            ], 422);
        }

        $whatsappNumber = $this->normalizeWhatsappNumber($payload['whatsapp_number'] ?? null);
        $name = $this->nullableText($payload['full_name'] ?? null)
            ?: $this->nullableText($payload['name'] ?? null)
            ?: 'Meta Lead';

        $externalCreatedAt = $this->parseExternalCreatedAt($payload['created_time'] ?? null);
        $programInterest = $this->resolveProgramInterest($payload);

        $leadData = [
            'name' => $name,
            'whatsapp_number' => $whatsappNumber ?: '-',
            'email' => $this->nullableText($payload['email'] ?? null),

            'program_interest' => $programInterest,
            'help_need' => $this->buildHelpNeed($payload),
            'best_contact_time' => null,

            'source' => 'meta_lead_form',
            'external_source' => 'meta',
            'external_lead_id' => $externalLeadId,
            'external_created_at' => $externalCreatedAt,

            'landing_page_url' => null,
            'referrer_url' => null,

            'utm_source' => $this->nullableText($payload['platform'] ?? null) ?: 'meta',
            'utm_medium' => 'lead_form',
            'utm_campaign' => $this->nullableText($payload['campaign_name'] ?? null),
            'utm_content' => $this->nullableText($payload['adset_name'] ?? null),
            'utm_term' => $this->nullableText($payload['ad_name'] ?? null),

            'gclid' => null,
            'gbraid' => null,
            'wbraid' => null,

            'meta_ad_id' => $this->nullableText($payload['ad_id'] ?? null),
            'meta_ad_name' => $this->nullableText($payload['ad_name'] ?? null),
            'meta_adset_id' => $this->nullableText($payload['adset_id'] ?? null),
            'meta_adset_name' => $this->nullableText($payload['adset_name'] ?? null),
            'meta_campaign_id' => $this->nullableText($payload['campaign_id'] ?? null),
            'meta_campaign_name' => $this->nullableText($payload['campaign_name'] ?? null),
            'meta_form_id' => $this->nullableText($payload['form_id'] ?? null),
            'meta_form_name' => $this->nullableText($payload['form_name'] ?? null),
            'meta_platform' => $this->nullableText($payload['platform'] ?? null),
            'meta_is_organic' => $this->toBoolean($payload['is_organic'] ?? false),
            'meta_lead_status' => $this->nullableText($payload['lead_status'] ?? null),
            'education_level' => $this->nullableText($payload['education_level'] ?? null),
            'external_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ];

        try {
            $result = DB::transaction(function () use ($leadData, $externalLeadId) {
                $existingLead = DB::table('sem_leads')
                    ->where('external_source', 'meta')
                    ->where('external_lead_id', $externalLeadId)
                    ->lockForUpdate()
                    ->first();

                if ($existingLead) {
                    DB::table('sem_leads')
                        ->where('id', $existingLead->id)
                        ->update(array_merge($leadData, [
                            'updated_at' => now(),
                        ]));

                    return [
                        'sem_lead_id' => (int) $existingLead->id,
                        'created' => false,
                        'should_sync_kommo' => $existingLead->kommo_sync_status !== 'synced',
                    ];
                }

                $semLeadId = DB::table('sem_leads')->insertGetId(array_merge($leadData, [
                    'kommo_sync_status' => 'pending',
                    'kommo_lead_id' => null,
                    'kommo_contact_id' => null,
                    'kommo_synced_at' => null,
                    'kommo_error' => null,
                    'created_at' => $leadData['external_created_at'] ?: now(),
                    'updated_at' => now(),
                ]));

                return [
                    'sem_lead_id' => (int) $semLeadId,
                    'created' => true,
                    'should_sync_kommo' => true,
                ];
            });

            if ($result['should_sync_kommo']) {
                $this->syncLeadToKommo($result['sem_lead_id']);
            }

            $lead = DB::table('sem_leads')
                ->where('id', $result['sem_lead_id'])
                ->first();

            return response()->json([
                'success' => true,
                'message' => $result['created']
                    ? 'Meta lead created successfully.'
                    : 'Meta lead updated successfully.',
                'data' => [
                    'sem_lead_id' => $result['sem_lead_id'],
                    'created' => $result['created'],
                    'kommo_sync_status' => $lead?->kommo_sync_status,
                    'kommo_lead_id' => $lead?->kommo_lead_id,
                    'kommo_contact_id' => $lead?->kommo_contact_id,
                    'kommo_error' => $lead?->kommo_error,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to process Meta lead webhook.', [
                'external_lead_id' => $externalLeadId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process Meta lead webhook.',
                'error' => app()->environment('production')
                    ? null
                    : $exception->getMessage(),
            ], 500);
        }
    }

    private function syncLeadToKommo(int $semLeadId): void
    {
        if (!class_exists(KommoService::class)) {
            return;
        }

        if (!config('services.kommo.enabled')) {
            return;
        }

        $lead = DB::table('sem_leads')
            ->where('id', $semLeadId)
            ->first();

        if (!$lead) {
            return;
        }

        try {
            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => 'processing',
                    'kommo_error' => null,
                    'updated_at' => now(),
                ]);

            /** @var KommoService $kommoService */
            $kommoService = app(KommoService::class);

            $response = $kommoService->createSemLead((array) $lead);

            $kommoLeadId = $this->extractKommoLeadId($response);
            $kommoContactId = $this->extractKommoContactId($response);

            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => $kommoLeadId ? 'synced' : 'failed',
                    'kommo_lead_id' => $kommoLeadId,
                    'kommo_contact_id' => $kommoContactId,
                    'kommo_synced_at' => $kommoLeadId ? now() : null,
                    'kommo_error' => $kommoLeadId ? null : 'Kommo response received, but lead ID was not found.',
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            Log::error('Failed to sync Meta lead to Kommo.', [
                'sem_lead_id' => $semLeadId,
                'message' => $exception->getMessage(),
            ]);

            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => 'failed',
                    'kommo_error' => Str::limit($exception->getMessage(), 3000, ''),
                    'updated_at' => now(),
                ]);
        }
    }

    private function extractPayload(Request $request): array
    {
        $payload = $request->input('payload');

        if (is_array($payload)) {
            return $payload;
        }

        $row = $request->input('row');

        if (is_array($row)) {
            return $row;
        }

        return $request->all();
    }

    private function isAuthorized(Request $request): bool
    {
        $expectedSecret = config('services.meta_leads.webhook_secret');

        if (!$expectedSecret) {
            return false;
        }

        $givenSecret = $request->header('X-Webhook-Secret')
            ?: $request->header('X-Meta-Webhook-Secret')
            ?: $request->input('secret');

        if (!$givenSecret) {
            return false;
        }

        return hash_equals((string) $expectedSecret, (string) $givenSecret);
    }

    private function extractKommoLeadId(array $response): ?int
    {
        $lead = Arr::get($response, '0')
            ?? Arr::get($response, '_embedded.leads.0')
            ?? $response;

        $id = Arr::get($lead, 'id')
            ?? Arr::get($lead, 'lead_id');

        return $id ? (int) $id : null;
    }

    private function extractKommoContactId(array $response): ?int
    {
        $lead = Arr::get($response, '0')
            ?? Arr::get($response, '_embedded.leads.0')
            ?? $response;

        $contactId = Arr::get($lead, 'contact_id')
            ?? Arr::get($lead, '_embedded.contacts.0.id')
            ?? Arr::get($response, '_embedded.contacts.0.id');

        return $contactId ? (int) $contactId : null;
    }

    private function parseExternalCreatedAt(mixed $value): ?string
    {
        $value = $this->nullableText($value);

        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveProgramInterest(array $payload): string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $payload['ad_name'] ?? null,
            $payload['adset_name'] ?? null,
            $payload['campaign_name'] ?? null,
            $payload['form_name'] ?? null,
        ])));

        return match (true) {
            Str::contains($haystack, ['ui/ux', 'ui ux', 'ui-ux', 'design']) => 'UI/UX Design',
            Str::contains($haystack, ['software', 'coding', 'web', 'programming', 'developer']) => 'Software Engineering',
            Str::contains($haystack, ['ai', 'artificial intelligence', 'productivity']) => 'AI Productivity',
            default => 'Konsultasi Program',
        };
    }

    private function buildHelpNeed(array $payload): ?string
    {
        $rows = [];

        if (!empty($payload['education_level'])) {
            $rows[] = 'Education Level: ' . $payload['education_level'];
        }

        if (!empty($payload['lead_status'])) {
            $rows[] = 'Meta Lead Status: ' . $payload['lead_status'];
        }

        return empty($rows) ? null : implode("\n", $rows);
    }

    private function normalizeWhatsappNumber(mixed $value): ?string
    {
        $number = $this->nullableText($value);

        if (!$number) {
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

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = Str::lower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'y'], true);
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}