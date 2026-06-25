<?php

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function cliArg(string $key, mixed $default = null): mixed
{
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if (str_starts_with($arg, "--{$key}=")) {
            return substr($arg, strlen("--{$key}="));
        }
    }

    return $default;
}

function printSection(string $title): void
{
    echo PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
    echo $title . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
}

function fetchAllKommoPages($http, string $endpoint, array $query = [], string $embeddedKey = ''): array
{
    $page = 1;
    $items = [];

    do {
        $response = $http->get($endpoint, array_merge($query, [
            'limit' => 250,
            'page' => $page,
        ]));

        if (!$response->successful()) {
            echo "ERROR {$endpoint} page {$page}: HTTP {$response->status()}" . PHP_EOL;
            echo $response->body() . PHP_EOL;
            break;
        }

        $json = $response->json() ?? [];

        $pageItems = Arr::get($json, "_embedded.{$embeddedKey}", []);

        if (!is_array($pageItems) || count($pageItems) === 0) {
            break;
        }

        $items = array_merge($items, $pageItems);

        $hasNext = Arr::has($json, '_links.next.href');

        $page++;
    } while ($hasNext);

    return $items;
}

$timezone = cliArg('timezone', 'Asia/Jakarta');
$date = cliArg('date', now($timezone)->toDateString());

$startAt = CarbonImmutable::parse($date, $timezone)->startOfDay()->timestamp;
$endAt = CarbonImmutable::parse($date, $timezone)->endOfDay()->timestamp;

$pipelineId = (int) config('services.kommo.pipeline_id');

$baseUrl = rtrim(
    config('services.kommo.base_url') ?: ('https://' . config('services.kommo.subdomain') . '.kommo.com'),
    '/'
);

$token = config('services.kommo.long_lived_token');

if (!$baseUrl || !$token || !$pipelineId) {
    echo "Kommo config belum lengkap." . PHP_EOL;
    echo "Cek config/services.php atau .env:" . PHP_EOL;
    echo "- KOMMO_BASE_URL / KOMMO_SUBDOMAIN" . PHP_EOL;
    echo "- KOMMO_LONG_LIVED_TOKEN" . PHP_EOL;
    echo "- KOMMO_PIPELINE_ID" . PHP_EOL;
    exit(1);
}

$http = Http::baseUrl($baseUrl)
    ->withToken($token)
    ->acceptJson()
    ->asJson();

echo PHP_EOL;
echo "Kommo Debug Lead Count" . PHP_EOL;
echo "Date      : {$date}" . PHP_EOL;
echo "Timezone  : {$timezone}" . PHP_EOL;
echo "Pipeline  : {$pipelineId}" . PHP_EOL;
echo "Base URL  : {$baseUrl}" . PHP_EOL;

$query = [
    'filter[pipeline_id]' => $pipelineId,
    'filter[created_at][from]' => $startAt,
    'filter[created_at][to]' => $endAt,
];

/*
|--------------------------------------------------------------------------
| 1. Regular pipeline leads
|--------------------------------------------------------------------------
*/
$regularLeads = fetchAllKommoPages(
    $http,
    '/api/v4/leads',
    $query,
    'leads'
);

printSection('1. Regular Pipeline Leads');

echo json_encode([
    'count' => count($regularLeads),
    'by_status_id' => collect($regularLeads)
        ->countBy(fn ($lead) => (string) ($lead['status_id'] ?? 'none'))
        ->all(),
    'items' => collect($regularLeads)
        ->map(fn ($lead) => [
            'id' => $lead['id'] ?? null,
            'name' => $lead['name'] ?? null,
            'status_id' => $lead['status_id'] ?? null,
            'pipeline_id' => $lead['pipeline_id'] ?? null,
            'created_at' => isset($lead['created_at'])
                ? CarbonImmutable::createFromTimestamp($lead['created_at'])->timezone($timezone)->format('Y-m-d H:i:s')
                : null,
        ])
        ->values()
        ->all(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 2. Unsorted / Incoming leads
|--------------------------------------------------------------------------
*/
$unsortedItems = fetchAllKommoPages(
    $http,
    '/api/v4/leads/unsorted',
    $query,
    'unsorted'
);

printSection('2. Unsorted / Incoming Leads');

echo json_encode([
    'count' => count($unsortedItems),

    'by_category' => collect($unsortedItems)
        ->countBy(fn ($item) => (string) ($item['category'] ?? 'none'))
        ->all(),

    'by_source_type' => collect($unsortedItems)
        ->countBy(fn ($item) => (string) ($item['source_type'] ?? 'none'))
        ->all(),

    'by_status' => collect($unsortedItems)
        ->countBy(fn ($item) => (string) ($item['status'] ?? 'none'))
        ->all(),

    'by_action' => collect($unsortedItems)
        ->countBy(fn ($item) => (string) ($item['action'] ?? 'none'))
        ->all(),

    'sample' => collect($unsortedItems)
        ->take(30)
        ->map(fn ($item) => [
            'uid' => $item['uid'] ?? null,
            'source_uid' => $item['source_uid'] ?? null,
            'category' => $item['category'] ?? null,
            'source_type' => $item['source_type'] ?? null,
            'status' => $item['status'] ?? null,
            'action' => $item['action'] ?? null,
            'pipeline_id' => $item['pipeline_id'] ?? null,
            'created_at' => isset($item['created_at'])
                ? CarbonImmutable::createFromTimestamp($item['created_at'])->timezone($timezone)->format('Y-m-d H:i:s')
                : null,
        ])
        ->values()
        ->all(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 3. Unsorted summary
|--------------------------------------------------------------------------
*/
$summaryResponse = $http->get('/api/v4/leads/unsorted/summary', $query);

printSection('3. Unsorted Summary');

echo json_encode([
    'status' => $summaryResponse->status(),
    'json' => $summaryResponse->json(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 4. Current OPS KommoService summary
|--------------------------------------------------------------------------
*/
printSection('4. Current OPS KommoService Summary');

try {
    $serviceSummary = app(\App\Services\KommoService::class)
        ->getDailyLeadSummary($date, $timezone);

    echo json_encode(collect($serviceSummary)->only([
        'total_leads',
        'pipeline_leads',
        'regular_pipeline_leads',

        'incoming_leads',
        'regular_incoming_leads',

        'unsorted_total',
        'unsorted_accepted',
        'unsorted_declined',
        'unsorted_pending',
        'external_unsorted_pending',
        'unsorted_pending_source',
        'unsorted_kpi_leads',

        'initial_contact',
        'new_leads',
        'ignored',
        'not_related',
        'closed_lost',

        'filtered_out',
        'total_filtered_out',
        'followed_up',
        'need_action',
    ])->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    echo "ERROR KommoService: {$e->getMessage()}" . PHP_EOL;
}

printSection('5. Quick Formula Check');

$regularCount = count($regularLeads);
$unsortedCount = count($unsortedItems);

echo json_encode([
    'regular_pipeline_count' => $regularCount,
    'unsorted_list_count' => $unsortedCount,
    'regular_plus_unsorted_list' => $regularCount + $unsortedCount,
    'note' => 'Bandingin angka ini dengan kanan atas Kommo dan kolom Incoming Requests.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;