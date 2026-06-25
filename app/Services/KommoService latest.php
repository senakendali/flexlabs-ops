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
    | - Total Leads      = semua regular pipeline lead yang dibuat pada tanggal tersebut.
    | - Incoming Leads   = regular pipeline lead yang masih berada di status Incoming/Lead Masuk.
    | - Belum Follow-up  = Incoming Leads yang masih perlu action sales.
    | - Need Action      = Incoming Leads yang masih perlu action sales.
    | - Sudah Follow-up  = Total Leads - Incoming Leads.
    | - Filtered Out     = ignored + closed_lost + not_related, hanya untuk
    |   breakdown/status detail, bukan untuk mengurangi total lead.
    |
    | Important:
    | - Closed Lost, Not Related, dan Ignore tetap dihitung sebagai Sudah Follow-up
    |   karena lead tersebut sudah diproses/diputuskan oleh tim sales.
    | - Satu-satunya status yang dianggap Belum Follow-up adalah Incoming Leads.
    | - Status yang dihitung adalah current status lead saat data ditarik.
    |--------------------------------------------------------------------------
    */
    public function getDailyLeadSummary(string $date, ?string $timezone = null): array
    {
        $this->ensureConfigured();

        [$startAt, $endAt] = $this->dailyTimestampRange($date, $timezone);

        $leads = $this->fetchLeadsByCreatedDateRange($startAt, $endAt);

        $summary = $this->emptyDailyLeadSummary();

        $seenPipelineLeadIds = [];

        foreach ($leads as $index => $lead) {
            /*
            |--------------------------------------------------------------------------
            | Dedupe regular pipeline leads
            |--------------------------------------------------------------------------
            | Defensive guard: kalau Kommo mengembalikan lead yang sama lebih dari
            | sekali di pagination / filter edge-case, dashboard tidak ikut double count.
            */
            $leadUniqueKey = (string) ($lead['id'] ?? 'row_' . $index);

            if (isset($seenPipelineLeadIds[$leadUniqueKey])) {
                continue;
            }

            $seenPipelineLeadIds[$leadUniqueKey] = true;
            $summary['total_leads']++;

            $statusId = (int) ($lead['status_id'] ?? 0);
            $field = $this->dailyReportFieldByStatusId($statusId);

            if ($field !== null && array_key_exists($field, $summary)) {
                $summary[$field]++;
            }
        }

        $regularPipelineLeads = (int) $summary['total_leads'];
        $summary['pipeline_leads'] = $regularPipelineLeads;
        $summary['regular_pipeline_leads'] = $regularPipelineLeads;

        /*
        |--------------------------------------------------------------------------
        | Kommo Incoming / Unsorted Leads
        |--------------------------------------------------------------------------
        | Di Kommo, incoming bisa muncul dari 2 tempat:
        | 1. Regular pipeline lead dengan status "Lead masuk / Incoming Leads".
        | 2. Unsorted inbox metadata dari Kommo. Untuk KPI dashboard, unsorted
        |    tidak ikut menambah total karena bisa double count setelah accepted.
        |
        | Masalah yang kita cegah:
        | - Saat unsorted lead di-accept / dipindah ke Initial Contact, lead tersebut
        |   sudah muncul sebagai regular pipeline lead.
        | - Tetapi summary unsorted kadang masih menyimpan total historis hari itu.
        | - Kalau unsorted total ikut ditambahkan, dashboard bisa naik 9 -> 10
        |   walaupun real total Kommo tetap 9.
        |
        | Rule final:
        | - total_leads = regular pipeline leads dari endpoint leads.
        | - incoming_leads = incoming regular pipeline saja.
        | - unsorted_* disimpan sebagai metadata/debug, bukan KPI utama.
        */
        $regularIncomingLeads = (int) $summary['incoming_leads'];
        $unsortedSummary = $this->fetchUnsortedLeadSummary($startAt, $endAt);
        $normalizedUnsortedSummary = $this->normalizeUnsortedSummary($unsortedSummary);

        $unsortedPendingFromList = $this->fetchUnsortedPendingLeadCount($startAt, $endAt);

        $unsortedTotal = (int) $normalizedUnsortedSummary['total'];
        $unsortedAccepted = (int) $normalizedUnsortedSummary['accepted'];
        $unsortedDeclined = (int) $normalizedUnsortedSummary['declined'];
        $unsortedPending = $unsortedPendingFromList !== null
            ? (int) $unsortedPendingFromList
            : (int) $normalizedUnsortedSummary['pending'];

        /*
        |--------------------------------------------------------------------------
        | Unsorted is diagnostic only for dashboard KPI
        |--------------------------------------------------------------------------
        | Fix final untuk kasus total 9 berubah jadi 10:
        |
        | Kommo bisa tetap mengembalikan item di /leads/unsorted walaupun lead
        | tersebut sudah accepted dan sudah muncul sebagai regular pipeline lead.
        | Karena dashboard ingin sama dengan angka total di pipeline Kommo, maka
        | KPI utama dashboard harus memakai regular pipeline leads saja.
        |
        | Rule final dashboard FlexLabs:
        | - total_leads     = regular pipeline leads dari /api/v4/leads.
        | - incoming_leads  = regular pipeline leads dengan status Lead Masuk.
        | - unsorted_*      = metadata/debug/reference, tidak menambah total KPI.
        |
        | Dengan rule ini:
        | - Saat 1 lead masih di Lead Masuk: total 9, incoming 1, followed up 8.
        | - Saat lead dipindah ke Initial Contact: total tetap 9, incoming 0,
        |   followed up 9.
        */
        $externalUnsortedPending = 0;

        $summary['regular_incoming_leads'] = $regularIncomingLeads;
        $summary['unsorted_total'] = $unsortedTotal;
        $summary['unsorted_accepted'] = $unsortedAccepted;
        $summary['unsorted_declined'] = $unsortedDeclined;
        $summary['unsorted_pending'] = $unsortedPending;
        $summary['external_unsorted_pending'] = $externalUnsortedPending;
        $summary['unsorted_pending_source'] = $unsortedPendingFromList !== null ? 'list' : 'summary';
        $summary['unsorted_average_sort_time'] = (int) $normalizedUnsortedSummary['average_sort_time'];
        $summary['unsorted_forms_total'] = (int) $normalizedUnsortedSummary['forms_total'];
        $summary['unsorted_chats_total'] = (int) $normalizedUnsortedSummary['chats_total'];

        /*
        |--------------------------------------------------------------------------
        | Backward compatible alias
        |--------------------------------------------------------------------------
        | Controller/Blade lama mungkin masih baca lead_masuk.
        */
        $summary['total_leads'] = $regularPipelineLeads;
        $summary['incoming_leads'] = $regularIncomingLeads;
        $summary['lead_masuk'] = (int) $summary['incoming_leads'];

        /*
        |--------------------------------------------------------------------------
        | Derived metrics
        |--------------------------------------------------------------------------
        | Definisi final dashboard FlexLabs:
        | - Filtered Out tetap disimpan sebagai detail breakdown.
        | - Belum Follow-up / Need Action hanya Incoming Leads.
        | - Sudah Follow-up adalah semua lead selain Incoming Leads.
        |
        | Dengan rule ini:
        | Total Leads 9, Incoming Leads 1
        | => Sudah Follow-up 8
        | => Belum Follow-up 1
        | => Follow-up Rate 89%
        |
        | Closed Lost, Not Related, dan Ignored tetap dihitung sebagai Sudah
        | Follow-up karena lead tersebut sudah diproses/diputuskan.
        */
        $summary['filtered_out'] = (int) $summary['ignored']
            + (int) $summary['closed_lost']
            + (int) $summary['not_related'];

        $totalLeads = max((int) $summary['total_leads'], 0);
        $incomingLeads = max((int) $summary['incoming_leads'], 0);

        $summary['not_followed_up'] = $incomingLeads;
        $summary['need_action'] = $incomingLeads;

        $summary['followed_up'] = max($totalLeads - min($incomingLeads, $totalLeads), 0);

        $summary['follow_up_rate'] = $totalLeads > 0
            ? (int) round(((int) $summary['followed_up'] / $totalLeads) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Processing progress aliases
        |--------------------------------------------------------------------------
        | Dipakai Blade progress bar. Karena rule baru menganggap semua selain
        | Incoming sebagai sudah diproses, processed_leads sama dengan followed_up.
        */
        $summary['processed_leads'] = (int) $summary['followed_up'];
        $summary['processing_progress'] = (int) $summary['follow_up_rate'];

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

    private function fetchUnsortedLeadSummary(int $startAt, int $endAt): array
    {
        try {
            $response = $this->http()
                ->get('/api/v4/leads/unsorted/summary', [
                    'filter[pipeline_id]' => (int) config('services.kommo.pipeline_id'),
                    'filter[created_at][from]' => $startAt,
                    'filter[created_at][to]' => $endAt,
                ]);

            if ($response->status() === 204) {
                return [];
            }

            if (!$response->successful()) {
                Log::warning('Kommo fetch unsorted lead summary failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $exception) {
            Log::warning('Kommo fetch unsorted lead summary exception occurred.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function fetchUnsortedPendingLeadCount(int $startAt, int $endAt): ?int
    {
        $pendingCount = 0;
        $page = 1;
        $limit = 250;
        $maxPages = 50;

        try {
            do {
                $response = $this->http()
                    ->get('/api/v4/leads/unsorted', [
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
                    Log::warning('Kommo fetch unsorted pending lead list failed.', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return null;
                }

                $json = $response->json() ?? [];
                $items = Arr::get($json, '_embedded.unsorted', []);

                if (empty($items)) {
                    $items = Arr::get($json, '_embedded.leads', []);
                }

                if (empty($items)) {
                    break;
                }

                foreach ($items as $item) {
                    if ($this->isPendingUnsortedLead($item)) {
                        $pendingCount++;
                    }
                }

                $hasNextPage = !empty(Arr::get($json, '_links.next.href'));
                $page++;
            } while ($hasNextPage && $page <= $maxPages);

            return $pendingCount;
        } catch (Throwable $exception) {
            Log::warning('Kommo fetch unsorted pending lead list exception occurred.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function isPendingUnsortedLead(array $item): bool
    {
        $status = Str::of((string) (
            Arr::get($item, 'status')
            ?? Arr::get($item, 'request_status')
            ?? Arr::get($item, 'account_status')
            ?? Arr::get($item, 'state')
            ?? ''
        ))->lower()->toString();

        if (in_array($status, [
            'accepted',
            'declined',
            'deleted',
            'processed',
            'sorted',
            'closed',
        ], true)) {
            return false;
        }

        foreach ([
            'accepted_at',
            'declined_at',
            'processed_at',
            'sorted_at',
            'deleted_at',
        ] as $timestampKey) {
            if (!empty(Arr::get($item, $timestampKey))) {
                return false;
            }
        }

        return true;
    }

    private function normalizeUnsortedSummary(array $summary): array
    {
        $categoryTotal = $this->sumUnsortedCategoryMetric($summary, ['total', 'count']);
        $categoryAccepted = $this->sumUnsortedCategoryMetric($summary, ['accepted', 'accepted_total', 'accepted.count', 'accepted.total']);
        $categoryDeclined = $this->sumUnsortedCategoryMetric($summary, ['declined', 'declined_total', 'declined.count', 'declined.total']);
        $categoryPending = $this->sumUnsortedCategoryMetric($summary, ['pending', 'not_sorted', 'unsorted', 'new', 'new_total']);

        $total = $this->firstIntFromArray($summary, [
            'total',
            'count',
            'summary.total',
            'summary.count',
            'data.total',
            'result.total',
        ], $categoryTotal);

        $accepted = $this->firstIntFromArray($summary, [
            'accepted',
            'accepted_total',
            'accepted.count',
            'accepted.total',
            'summary.accepted',
            'summary.accepted_total',
            'summary.accepted.count',
            'summary.accepted.total',
            'data.accepted',
            'result.accepted',
        ], $categoryAccepted);

        $declined = $this->firstIntFromArray($summary, [
            'declined',
            'declined_total',
            'declined.count',
            'declined.total',
            'summary.declined',
            'summary.declined_total',
            'summary.declined.count',
            'summary.declined.total',
            'data.declined',
            'result.declined',
        ], $categoryDeclined);

        $pending = $this->firstIntFromArray($summary, [
            'pending',
            'pending_total',
            'pending.count',
            'pending.total',
            'not_sorted',
            'not_sorted_total',
            'unsorted',
            'unsorted_total',
            'new',
            'new_total',
            'summary.pending',
            'summary.pending_total',
            'summary.not_sorted',
            'summary.unsorted',
            'data.pending',
            'result.pending',
        ], $categoryPending);

        if ($pending <= 0 && $total > 0) {
            $pending = max($total - $accepted - $declined, 0);
        }

        return [
            'total' => max($total, 0),
            'accepted' => max($accepted, 0),
            'declined' => max($declined, 0),
            'pending' => max($pending, 0),
            'average_sort_time' => $this->firstIntFromArray($summary, [
                'average_sort_time',
                'avg_sort_time',
                'summary.average_sort_time',
            ]),
            'forms_total' => $this->firstIntFromArray($summary, [
                'categories.forms.total',
                'categories.form.total',
                'categories.forms.count',
            ]),
            'chats_total' => $this->firstIntFromArray($summary, [
                'categories.chats.total',
                'categories.chat.total',
                'categories.chats.count',
            ]),
        ];
    }

    private function sumUnsortedCategoryMetric(array $summary, array $paths): int
    {
        $categories = Arr::get($summary, 'categories', []);

        if (!is_array($categories)) {
            return 0;
        }

        $total = 0;

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            foreach ($paths as $path) {
                $value = Arr::get($category, $path);

                if ($value !== null && $value !== '') {
                    $total += (int) $value;
                    break;
                }
            }
        }

        return $total;
    }

    private function firstIntFromArray(array $array, array $paths, int $fallback = 0): int
    {
        foreach ($paths as $path) {
            $value = Arr::get($array, $path);

            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        return $fallback;
    }

    private function emptyDailyLeadSummary(): array
    {
        return [
            'total_leads' => 0,
            'pipeline_leads' => 0,
            'regular_pipeline_leads' => 0,

            /*
            |--------------------------------------------------------------------------
            | Raw Kommo status counters
            |--------------------------------------------------------------------------
            */
            'incoming_leads' => 0,
            'lead_masuk' => 0,
            'regular_incoming_leads' => 0,
            'unsorted_total' => 0,
            'unsorted_accepted' => 0,
            'unsorted_declined' => 0,
            'unsorted_pending' => 0,
            'external_unsorted_pending' => 0,
            'unsorted_pending_source' => null,
            'unsorted_average_sort_time' => 0,
            'unsorted_forms_total' => 0,
            'unsorted_chats_total' => 0,
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
            'processed_leads' => 0,
            'processing_progress' => 0,
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