<?php

namespace App\Services;

use Carbon\CarbonImmutable;
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

    /*
    |--------------------------------------------------------------------------
    | Daily Lead Summary
    |--------------------------------------------------------------------------
    | Dipakai untuk dashboard dan Sales Daily Report.
    |
    | Definisi final FlexLabs:
    | - Total Leads      = semua lead yang dibuat pada tanggal tersebut.
    | - Sudah Follow-up  = lead yang sudah masuk status proses/interaksi sales.
    | - Belum Follow-up  = total leads - sudah follow-up.
    | - Filtered Out     = ignored + closed_lost + not_related.
    |
    | Important:
    | - Closed Lost, Not Related, dan Ignore tetap ditampilkan di breakdown,
    |   tapi tidak dihitung ke Sudah Follow-up.
    | - Status yang dihitung adalah current status lead saat data ditarik.
    |--------------------------------------------------------------------------
    */
    public function getDailyLeadSummary(string $date, ?string $timezone = null): array
    {
        $this->ensureConfigured();

        [$startAt, $endAt] = $this->dailyTimestampRange($date, $timezone);

        $leads = $this->fetchLeadsByCreatedDateRange($startAt, $endAt);

        $summary = $this->emptyDailyLeadSummary();

        foreach ($leads as $lead) {
            $summary['total_leads']++;

            $statusId = (int) ($lead['status_id'] ?? 0);
            $field = $this->dailyReportFieldByStatusId($statusId);

            if ($field !== null && array_key_exists($field, $summary)) {
                $summary[$field]++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Backward compatible alias
        |--------------------------------------------------------------------------
        | Controller/Blade lama mungkin masih baca lead_masuk.
        */
        $summary['lead_masuk'] = (int) $summary['incoming_leads'];

        /*
        |--------------------------------------------------------------------------
        | Derived metrics
        |--------------------------------------------------------------------------
        | Status yang masuk Sudah Follow-up:
        | - Initial Contact
        | - New Leads
        | - Interacted
        | - Warm Leads
        | - Hot Leads
        | - Consultation / Appointment
        | - Trial Class
        | - WA First Bubble
        | - Register
        | - Data Storage
        | - Paid
        |
        | Status yang tidak masuk Sudah Follow-up tapi tetap muncul detail:
        | - Incoming Leads / Lead masuk
        | - Ignore
        | - Closed Lost
        | - Not Related
        */
        $summary['filtered_out'] = (int) $summary['ignored']
            + (int) $summary['closed_lost']
            + (int) $summary['not_related'];

        $summary['followed_up'] = (int) $summary['initial_contact']
            + (int) $summary['new_leads']
            + (int) $summary['interacted']
            + (int) $summary['warm_leads']
            + (int) $summary['hot_leads']
            + (int) $summary['consultation']
            + (int) $summary['trial_class']
            + (int) $summary['wa_first_bubble']
            + (int) $summary['register']
            + (int) $summary['data_storage']
            + (int) $summary['paid'];

        $summary['followed_up'] = max(min((int) $summary['followed_up'], (int) $summary['total_leads']), 0);
        $summary['not_followed_up'] = max((int) $summary['total_leads'] - (int) $summary['followed_up'], 0);
        $summary['need_action'] = (int) $summary['not_followed_up'];

        $summary['follow_up_rate'] = (int) $summary['total_leads'] > 0
            ? (int) round(((int) $summary['followed_up'] / (int) $summary['total_leads']) * 100)
            : 0;

        $summary['date'] = $date;
        $summary['timezone'] = $timezone ?: config('app.timezone', 'Asia/Jakarta');
        $summary['pipeline_id'] = (int) config('services.kommo.pipeline_id');
        $summary['start_timestamp'] = $startAt;
        $summary['end_timestamp'] = $endAt;

        return $summary;
    }

    private function fetchLeadsByCreatedDateRange(int $startAt, int $endAt): array
    {
        $allLeads = [];
        $page = 1;
        $limit = 250;
        $maxPages = 50;

        do {
            $response = $this->http()
                ->get('/api/v4/leads', [
                    'page' => $page,
                    'limit' => $limit,
                    'filter[pipeline_id]' => (int) config('services.kommo.pipeline_id'),
                    'filter[created_at][from]' => $startAt,
                    'filter[created_at][to]' => $endAt,
                ]);

            if ($response->status() === 204) {
                break;
            }

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Kommo fetch daily leads failed. Status: ' . $response->status() . '. Body: ' . $response->body()
                );
            }

            $json = $response->json() ?? [];
            $leads = Arr::get($json, '_embedded.leads', []);

            if (empty($leads)) {
                break;
            }

            foreach ($leads as $lead) {
                $allLeads[] = $lead;
            }

            $hasNextPage = !empty(Arr::get($json, '_links.next.href'));
            $page++;
        } while ($hasNextPage && $page <= $maxPages);

        return $allLeads;
    }

    private function emptyDailyLeadSummary(): array
    {
        return [
            'total_leads' => 0,

            /*
            |--------------------------------------------------------------------------
            | Raw Kommo status counters
            |--------------------------------------------------------------------------
            */
            'incoming_leads' => 0,
            'lead_masuk' => 0,
            'initial_contact' => 0,
            'new_leads' => 0,

            'ignored' => 0,
            'interacted' => 0,
            'warm_leads' => 0,
            'hot_leads' => 0,
            'trial_class' => 0,
            'wa_first_bubble' => 0,
            'consultation' => 0,
            'register' => 0,
            'data_storage' => 0,
            'not_related' => 0,
            'paid' => 0,
            'closed_lost' => 0,

            /*
            |--------------------------------------------------------------------------
            | Derived metrics
            |--------------------------------------------------------------------------
            */
            'filtered_out' => 0,
            'followed_up' => 0,
            'not_followed_up' => 0,
            'need_action' => 0,
            'follow_up_rate' => 0,
        ];
    }

    private function dailyReportFieldByStatusId(int $statusId): ?string
    {
        foreach ($this->dailyReportStatusMap() as $field => $statusIds) {
            if (in_array($statusId, $statusIds, true)) {
                return $field;
            }
        }

        return null;
    }

    private function dailyReportStatusMap(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default status IDs from FlexLabs Kommo Pipeline
        |--------------------------------------------------------------------------
        | Pipeline ID: 13174499
        |
        | Lead masuk              : 101586651
        | Initial Contact         : 101586655
        | New Leads               : 101586659
        | Ignore                  : 101927851
        | Interacted              : 101927855
        | Follow Up / Warm Leads  : 101586663
        | Interested / Hot Leads  : 101586667
        | trial class             : 106095400
        | wa first bubble         : 106350312
        | Appointment             : 101927859
        | Register                : 102178515
        | Data storage            : 101927879
        | Not Related             : 102456323
        | Paid                    : 142
        | Closed Lost             : 143
        |--------------------------------------------------------------------------
        */
        return [
            'incoming_leads' => $this->statusIdsFromConfig('incoming_leads', [101586651]),

            'initial_contact' => $this->statusIdsFromConfig('initial_contact', [101586655]),
            'new_leads' => $this->statusIdsFromConfig('new_leads', [101586659]),

            'ignored' => $this->statusIdsFromConfig('ignored', [101927851]),
            'interacted' => $this->statusIdsFromConfig('interacted', [101927855]),
            'warm_leads' => $this->statusIdsFromConfig('warm_leads', [101586663]),
            'hot_leads' => $this->statusIdsFromConfig('hot_leads', [101586667]),
            'trial_class' => $this->statusIdsFromConfig('trial_class', [106095400]),
            'wa_first_bubble' => $this->statusIdsFromConfig('wa_first_bubble', [106350312]),
            'consultation' => $this->statusIdsFromConfig('consultation', [101927859]),
            'register' => $this->statusIdsFromConfig('register', [102178515]),
            'data_storage' => $this->statusIdsFromConfig('data_storage', [101927879]),
            'not_related' => $this->statusIdsFromConfig('not_related', [102456323]),
            'paid' => $this->statusIdsFromConfig('paid', [142]),
            'closed_lost' => $this->statusIdsFromConfig('closed_lost', [143]),
        ];
    }

    private function statusIdsFromConfig(string $key, array $fallback): array
    {
        $configured = config("services.kommo.status_ids.{$key}");

        if ($configured === null || $configured === '') {
            return array_values(array_unique(array_map('intval', $fallback)));
        }

        if (is_array($configured)) {
            return array_values(array_unique(array_filter(array_map('intval', $configured))));
        }

        return array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) $configured)
        ))));
    }

    private function dailyTimestampRange(string $date, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone', 'Asia/Jakarta');

        $start = CarbonImmutable::parse($date, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($date, $timezone)->endOfDay();

        return [
            $start->timestamp,
            $end->timestamp,
        ];
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
            'Email: ' . $this->safeValue($data['email'] ?? null),
            'Program: ' . $this->safeValue($data['program_interest'] ?? null),
            'Kebutuhan: ' . $this->safeValue($data['help_need'] ?? null),
            'Waktu terbaik dihubungi: ' . $bestContactTime,
            '',
            'Tracking',
            'Source: ' . $this->safeValue($data['source'] ?? null, 'google_sem'),
            'External Source: ' . $this->safeValue($data['external_source'] ?? null),
            'External Lead ID: ' . $this->safeValue($data['external_lead_id'] ?? null),
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
            '',
            'Meta Lead Form',
            'Ad Name: ' . $this->safeValue($data['meta_ad_name'] ?? null),
            'Adset Name: ' . $this->safeValue($data['meta_adset_name'] ?? null),
            'Campaign Name: ' . $this->safeValue($data['meta_campaign_name'] ?? null),
            'Form Name: ' . $this->safeValue($data['meta_form_name'] ?? null),
            'Platform: ' . $this->safeValue($data['meta_platform'] ?? null),
            'Education Level: ' . $this->safeValue($data['education_level'] ?? null),
            'Meta Lead Status: ' . $this->safeValue($data['meta_lead_status'] ?? null),
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
