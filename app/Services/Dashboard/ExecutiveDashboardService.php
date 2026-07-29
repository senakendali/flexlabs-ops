<?php

namespace App\Services\Dashboard;

use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Models\SalesDailyReport;
use App\Services\Trello\TrelloDashboardStatsService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ExecutiveDashboardService
{
    /**
     * KPI yang ditampilkan pada Executive Dashboard MVP.
     *
     * @var array<int, string>
     */
    private const KPI_CODES = [
        'confirmed_revenue',
        'total_leads',
        'closed_deals',
        'paid_students',
        'marketing_spend',
        'student_completion_rate',
    ];

    /**
     * Seluruh Trello source yang saat ini disinkronkan ke FlexOps.
     *
     * Source tambahan dapat dimasukkan melalui:
     * services.trello.dashboard_source_keys
     *
     * @var array<int, string>
     */
    private const TRELLO_SOURCE_KEYS = [
        'academic',
        'marketing',
        'sei',
    ];

    /**
     * Source ownership untuk Business Health.
     *
     * Talent Hub tetap ditampilkan sebagai bagian dari blueprint Executive
     * Center tanpa diberi KPI palsu. Operations Centre dihitung terpisah dari
     * seluruh workload Trello yang tersinkron ke FlexOps.
     *
     * @var array<string, array<string, mixed>>
     */
    private const CENTRES = [
        'growth_engine' => [
            'name' => 'Growth Engine',
            'description' => 'Akuisisi, conversion, dan efisiensi pertumbuhan.',
            'icon' => 'bi-graph-up-arrow',
            'kpi_codes' => [
                'total_leads',
                'marketing_spend',
                'closed_deals',
                'paid_students',
            ],
        ],
        'learning_centre' => [
            'name' => 'Learning Centre',
            'description' => 'Delivery pembelajaran dan keberhasilan student.',
            'icon' => 'bi-mortarboard-fill',
            'kpi_codes' => [
                'student_completion_rate',
            ],
        ],
        'talent_hub' => [
            'name' => 'Talent Hub',
            'description' => 'Kapasitas, attendance, dan performa talent.',
            'icon' => 'bi-people-fill',
            'kpi_codes' => [],
        ],
        'finance_centre' => [
            'name' => 'Finance Centre',
            'description' => 'Revenue terkonfirmasi dan kesehatan finansial.',
            'icon' => 'bi-cash-stack',
            'kpi_codes' => [
                'confirmed_revenue',
            ],
        ],
        'operations_centre' => [
            'name' => 'Operations Centre',
            'description' => 'Penyelesaian workload dan kontrol task overdue.',
            'icon' => 'bi-gear-fill',
            'kpi_codes' => [],
        ],
    ];

    public function __construct(
        private readonly TrelloDashboardStatsService $trelloDashboardStatsService,
        private readonly ExecutiveBriefAiService $executiveBriefAiService,
        private readonly StudentOnTrackRateCalculator $studentOnTrackRateCalculator
    ) {
    }

    /**
     * Menyiapkan seluruh data Executive Dashboard untuk satu bulan.
     *
     * @param  array{month?: string|null}  $filters
     */
    public function getData(array $filters = []): array
    {
        $period = $this->resolvePeriod($filters);
        $definitions = $this->getKpiDefinitions();
        $targets = $this->getTargets($definitions, $period['month_start']);

        $actuals = $period['is_future']
            ? $this->futureActuals()
            : $this->calculateActuals(
                $period['date_from'],
                $period['actual_date_to']
            );

        $previousActuals = $this->calculateActuals(
            $period['previous_date_from'],
            $period['previous_date_to']
        );

        $scorecard = $this->buildScorecard(
            definitions: $definitions,
            targets: $targets,
            actuals: $actuals,
            previousActuals: $previousActuals,
            period: $period,
        );

        $highlights = $this->buildHighlights($scorecard);
        $operationsHealth = $this->buildOperationsHealth();
        $businessHealth = $this->buildBusinessHealth(
            $scorecard,
            $operationsHealth
        );
        $businessAttention = $this->buildBusinessAttention($scorecard);
        $fallbackBrief = $this->buildExecutiveBriefFallback(
            $scorecard,
            $businessAttention,
            $period
        );
        $executiveBrief = $this->executiveBriefAiService->generate(
            $scorecard,
            $period,
            $fallbackBrief
        );

        $summary = [
            'total_kpis' => count($scorecard),
            'healthy_kpis' => collect($scorecard)->where('status', 'healthy')->count(),
            'watch_kpis' => collect($scorecard)->where('status', 'watch')->count(),
            'critical_kpis' => collect($scorecard)->where('status', 'critical')->count(),
            'unavailable_kpis' => collect($scorecard)
                ->whereIn('status', ['unavailable', 'no_data'])
                ->count(),
            'without_target_kpis' => collect($scorecard)
                ->where('status', 'not_configured')
                ->count(),
        ];

        return [
            'filters' => [
                'month' => $period['month'],
            ],
            'period' => $period,
            'summary' => $summary,
            'executiveHighlights' => $highlights,
            'kpiScorecard' => $scorecard,
            'businessHealth' => $businessHealth,
            'executiveBrief' => $executiveBrief,
            'businessAttention' => $businessAttention,
            'dataFreshness' => $this->buildDataFreshness(
                $scorecard,
                $operationsHealth
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePeriod(array $filters): array
    {
        $month = trim((string) ($filters['month'] ?? ''));

        try {
            $selectedMonth = $month !== ''
                ? Carbon::createFromFormat('!Y-m', $month)->startOfMonth()
                : now()->startOfMonth();
        } catch (Throwable) {
            $selectedMonth = now()->startOfMonth();
        }

        $today = now()->startOfDay();
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth()->startOfDay();
        $isCurrent = $monthStart->isSameMonth($today);
        $isFuture = $monthStart->greaterThan($today->copy()->startOfMonth());

        $actualDateTo = $isFuture
            ? null
            : ($isCurrent ? $today->copy() : $monthEnd->copy());

        $elapsedRatio = match (true) {
            $isFuture => 0.0,
            $isCurrent => min(
                1,
                max(0, $today->day / $monthEnd->day)
            ),
            default => 1.0,
        };

        $previousStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth()->startOfDay();

        return [
            'month' => $monthStart->format('Y-m'),
            'month_start' => $monthStart->toDateString(),
            'month_end' => $monthEnd->toDateString(),
            'date_from' => $monthStart->toDateString(),
            'date_to' => $monthEnd->toDateString(),
            'actual_date_to' => $actualDateTo?->toDateString(),
            'label' => $monthStart->translatedFormat('F Y'),
            'actual_label' => $actualDateTo
                ? $monthStart->translatedFormat('d M Y')
                    . ' – '
                    . $actualDateTo->translatedFormat('d M Y')
                : 'Periode belum dimulai',
            'is_current' => $isCurrent,
            'is_future' => $isFuture,
            'is_closed' => ! $isCurrent && ! $isFuture,
            'elapsed_ratio' => round($elapsedRatio, 6),
            'elapsed_percentage' => round($elapsedRatio * 100, 1),
            'previous_month' => $previousStart->format('Y-m'),
            'previous_label' => $previousStart->translatedFormat('F Y'),
            'previous_date_from' => $previousStart->toDateString(),
            'previous_date_to' => $previousEnd->toDateString(),
        ];
    }

    private function getKpiDefinitions(): Collection
    {
        if (! Schema::hasTable('kpi_definitions')) {
            return collect();
        }

        return KpiDefinition::query()
            ->active()
            ->whereIn('code', self::KPI_CODES)
            ->ordered()
            ->get();
    }

    private function getTargets(
        Collection $definitions,
        string $periodMonth
    ): Collection {
        if (
            $definitions->isEmpty()
            || ! Schema::hasTable('kpi_targets')
        ) {
            return collect();
        }

        return KpiTarget::query()
            ->whereIn(
                'kpi_definition_id',
                $definitions->pluck('id')->all()
            )
            ->forPeriod($periodMonth)
            ->whereIn('status', [
                KpiTarget::STATUS_ACTIVE,
                KpiTarget::STATUS_LOCKED,
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->unique('kpi_definition_id')
            ->keyBy('kpi_definition_id');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function calculateActuals(
        string $dateFrom,
        string $dateTo
    ): array {
        $sales = $this->safeSourceCalculation(
            'sales_daily_reports',
            fn () => $this->calculateSalesMetrics($dateFrom, $dateTo),
            ['total_leads', 'closed_deals']
        );

        $payments = $this->safeSourceCalculation(
            'payments',
            fn () => $this->calculatePaymentMetrics($dateFrom, $dateTo),
            ['confirmed_revenue', 'paid_students']
        );

        $marketing = $this->safeSourceCalculation(
            'marketing_platforms',
            fn () => [
                'marketing_spend' => $this->calculateMarketingSpend(
                    $dateFrom,
                    $dateTo
                ),
            ],
            ['marketing_spend']
        );

        $learning = $this->safeSourceCalculation(
            'student_progress',
            fn () => [
                'student_completion_rate' => $this->calculateStudentOnTrackRate(
                    $dateTo
                ),
            ],
            ['student_completion_rate']
        );

        return array_replace($sales, $payments, $marketing, $learning);
    }

    /**
     * @param  callable(): array<string, array<string, mixed>>  $callback
     * @param  array<int, string>  $codes
     * @return array<string, array<string, mixed>>
     */
    private function safeSourceCalculation(
        string $sourceKey,
        callable $callback,
        array $codes
    ): array {
        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::warning('Executive Dashboard source calculation failed.', [
                'source_key' => $sourceKey,
                'message' => $exception->getMessage(),
            ]);

            return collect($codes)
                ->mapWithKeys(fn (string $code) => [
                    $code => $this->unavailableActual(
                        sourceKey: $sourceKey,
                        sourceLabel: $this->sourceLabel($sourceKey),
                        message: 'Sumber data belum dapat dihitung. Periksa struktur tabel atau proses sinkronisasi.'
                    ),
                ])
                ->all();
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function calculateSalesMetrics(
        string $dateFrom,
        string $dateTo
    ): array {
        $defaults = [
            'total_leads' => $this->unavailableActual(
                'sales_daily_reports',
                'Sales Daily Report',
                'Tabel atau kolom Total Leads belum tersedia.'
            ),
            'closed_deals' => $this->unavailableActual(
                'sales_daily_reports',
                'Sales Daily Report',
                'Tabel atau kolom Closed Deals belum tersedia.'
            ),
        ];

        if (! class_exists(SalesDailyReport::class)) {
            return $defaults;
        }

        $model = new SalesDailyReport();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return $defaults;
        }

        $dateColumn = $this->firstExistingColumn($table, [
            'report_date',
            'date',
            'created_at',
        ]);
        $leadsColumn = $this->firstExistingColumn($table, [
            'total_leads',
            'leads',
            'lead_count',
        ]);
        $dealsColumn = $this->firstExistingColumn($table, [
            'closed_deal',
            'closed_deals',
            'closing',
            'deal',
        ]);

        if (! $dateColumn) {
            return $defaults;
        }

        $query = DB::table($table)
            ->whereDate($dateColumn, '>=', $dateFrom)
            ->whereDate($dateColumn, '<=', $dateTo);

        $reportCount = (int) (clone $query)->count();
        $latestDate = (clone $query)->max($dateColumn);
        $hasData = $reportCount > 0;

        return [
            'total_leads' => $leadsColumn
                ? $this->availableActual(
                    value: (float) (clone $query)->sum($leadsColumn),
                    sourceKey: 'sales_daily_reports',
                    sourceLabel: 'Sales Daily Report',
                    lastRecordedAt: $latestDate,
                    hasData: $hasData,
                    message: $hasData
                        ? null
                        : 'Belum ada Sales Daily Report pada periode ini.',
                    meta: ['report_count' => $reportCount]
                )
                : $defaults['total_leads'],
            'closed_deals' => $dealsColumn
                ? $this->availableActual(
                    value: (float) (clone $query)->sum($dealsColumn),
                    sourceKey: 'sales_daily_reports',
                    sourceLabel: 'Sales Daily Report',
                    lastRecordedAt: $latestDate,
                    hasData: $hasData,
                    message: $hasData
                        ? null
                        : 'Belum ada Sales Daily Report pada periode ini.',
                    meta: ['report_count' => $reportCount]
                )
                : $defaults['closed_deals'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function calculatePaymentMetrics(
        string $dateFrom,
        string $dateTo
    ): array {
        $revenueUnavailable = $this->unavailableActual(
            'payments',
            'Confirmed Payments',
            'Tabel atau kolom pembayaran terkonfirmasi belum tersedia.'
        );
        $studentsUnavailable = $this->unavailableActual(
            'payments',
            'First Confirmed Payment',
            'Relasi student ke pembayaran belum tersedia.'
        );

        if (! Schema::hasTable('payments')) {
            return [
                'confirmed_revenue' => $revenueUnavailable,
                'paid_students' => $studentsUnavailable,
            ];
        }

        $dateExpression = $this->paymentDateExpression('p');
        $statusColumn = $this->firstExistingColumn('payments', ['status']);

        if (! $dateExpression || ! $statusColumn) {
            return [
                'confirmed_revenue' => $revenueUnavailable,
                'paid_students' => $studentsUnavailable,
            ];
        }

        $statuses = ['paid', 'confirmed', 'settled'];

        $periodQuery = DB::table('payments as p')
            ->whereIn('p.' . $statusColumn, $statuses)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        $paymentCount = (int) (clone $periodQuery)->count();
        $latestDate = (clone $periodQuery)
            ->selectRaw('MAX(' . $dateExpression . ') as latest_date')
            ->value('latest_date');

        $revenue = Schema::hasColumn('payments', 'amount')
            ? $this->availableActual(
                value: (float) (clone $periodQuery)->sum('p.amount'),
                sourceKey: 'payments',
                sourceLabel: 'Confirmed Payments',
                lastRecordedAt: $latestDate,
                hasData: true,
                meta: ['payment_count' => $paymentCount]
            )
            : $revenueUnavailable;

        [$studentExpression, $requiresOrderJoin] = $this->studentPaymentReference();

        if (! $studentExpression) {
            return [
                'confirmed_revenue' => $revenue,
                'paid_students' => $studentsUnavailable,
            ];
        }

        $firstPaymentQuery = DB::table('payments as p');

        if ($requiresOrderJoin) {
            $firstPaymentQuery->join('orders as o', 'o.id', '=', 'p.order_id');
        }

        $firstPaymentQuery
            ->whereIn('p.' . $statusColumn, $statuses)
            ->whereRaw($studentExpression . ' IS NOT NULL')
            ->selectRaw($studentExpression . ' as student_id')
            ->selectRaw('MIN(' . $dateExpression . ') as first_paid_at')
            ->groupBy(DB::raw($studentExpression));

        $paidStudents = (int) DB::query()
            ->fromSub($firstPaymentQuery, 'first_payments')
            ->whereRaw('DATE(first_paid_at) >= ?', [$dateFrom])
            ->whereRaw('DATE(first_paid_at) <= ?', [$dateTo])
            ->count();

        return [
            'confirmed_revenue' => $revenue,
            'paid_students' => $this->availableActual(
                value: $paidStudents,
                sourceKey: 'payments',
                sourceLabel: 'First Confirmed Payment',
                lastRecordedAt: $latestDate,
                hasData: true,
                meta: [
                    'first_payment_only' => true,
                    'distinct_by' => 'student_id',
                ]
            ),
        ];
    }

    /**
     * @return array{0: string|null, 1: bool}
     */
    private function studentPaymentReference(): array
    {
        if (Schema::hasColumn('payments', 'student_id')) {
            return ['p.student_id', false];
        }

        if (
            Schema::hasColumn('payments', 'order_id')
            && Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'student_id')
        ) {
            return ['o.student_id', true];
        }

        return [null, false];
    }

    private function paymentDateExpression(string $alias): ?string
    {
        $columns = [];

        foreach (['paid_at', 'payment_date', 'created_at'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $columns[] = $this->wrap($alias . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    /**
     * Marketing Spend hanya dinilai available bila seluruh source yang
     * didefinisikan KPI memiliki data yang dapat mewakili periode terpilih.
     *
     * Meta Ads dibaca dari insight harian. Google Ads masih dibaca dari
     * snapshot periode eksplisit sampai proses sinkronisasinya menyimpan
     * cost harian.
     */
    private function calculateMarketingSpend(
        string $dateFrom,
        string $dateTo
    ): array {
        $meta = $this->calculateMetaAdsSpend($dateFrom, $dateTo);
        $google = $this->calculateGoogleAdsSpend($dateFrom, $dateTo);
        $sources = [$meta, $google];

        $missingSources = collect($sources)
            ->reject(fn (array $source) => $source['available'])
            ->pluck('source_label')
            ->values()
            ->all();

        $value = (float) collect($sources)->sum('value');
        $isComplete = empty($missingSources);
        $hasData = collect($sources)
            ->filter(fn (array $source) => $source['available'])
            ->contains(fn (array $source) => $source['has_data']);

        $freshness = collect($sources)
            ->pluck('last_recorded_at')
            ->filter()
            ->sortDesc()
            ->first();

        $missingMessages = collect($sources)
            ->reject(fn (array $source) => $source['available'])
            ->map(function (array $source) {
                $message = trim((string) ($source['message'] ?? ''));

                return $message !== ''
                    ? $source['source_label'] . ': ' . $message
                    : $source['source_label'] . ': data periode belum tersedia.';
            })
            ->values()
            ->all();

        return [
            'value' => $value,
            'available' => $isComplete,
            'has_data' => $hasData,
            'source_key' => 'marketing_platforms',
            'source_label' => 'Meta Ads + Google Ads',
            'last_recorded_at' => $freshness,
            'message' => $isComplete
                ? null
                : implode(' ', $missingMessages),
            'meta' => [
                'is_partial' => ! $isComplete && $hasData,
                'missing_sources' => $missingSources,
                'sources' => [
                    'meta_ads' => $meta,
                    'google_ads' => $google,
                ],
            ],
        ];
    }

    private function calculateMetaAdsSpend(
        string $dateFrom,
        string $dateTo
    ): array {
        $table = 'meta_ads_campaign_insights';

        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'spend')
            || ! Schema::hasColumn($table, 'date_start')
            || ! Schema::hasColumn($table, 'date_stop')
        ) {
            return $this->platformSpendUnavailable(
                'meta_ads',
                'Meta Ads',
                'Tabel atau kolom insight harian Meta Ads belum tersedia.'
            );
        }

        $dailyRows = DB::table($table)
            ->whereColumn('date_start', 'date_stop')
            ->whereDate('date_start', '>=', $dateFrom)
            ->whereDate('date_start', '<=', $dateTo);

        if (! (clone $dailyRows)->exists()) {
            return $this->platformSpendUnavailable(
                'meta_ads',
                'Meta Ads',
                'Insight harian belum tersedia pada periode terpilih. Jalankan sinkronisasi Meta Ads dengan time_increment=1.'
            );
        }

        $coverageStart = (string) (clone $dailyRows)->min('date_start');
        $coverageEnd = (string) (clone $dailyRows)->max('date_stop');
        $coverageComplete = $coverageStart <= $dateFrom
            && $coverageEnd >= $dateTo;

        if (
            Schema::hasColumn($table, 'id')
            && Schema::hasColumn($table, 'campaign_id')
        ) {
            $identityColumns = ['campaign_id', 'date_start', 'date_stop'];

            if (Schema::hasColumn($table, 'ad_account_id')) {
                array_unshift($identityColumns, 'ad_account_id');
            }

            $latestIds = (clone $dailyRows)
                ->selectRaw('MAX(id) as id')
                ->groupBy($identityColumns)
                ->pluck('id')
                ->filter()
                ->all();

            $value = (float) DB::table($table)
                ->whereIn('id', $latestIds)
                ->sum('spend');
            $rowCount = count($latestIds);
        } else {
            $value = (float) (clone $dailyRows)->sum('spend');
            $rowCount = (int) (clone $dailyRows)->count();
        }

        $freshnessColumn = $this->firstExistingColumn($table, [
            'updated_at',
            'created_at',
            'date_stop',
        ]);

        return [
            'value' => $value,
            'available' => $coverageComplete,
            'has_data' => $rowCount > 0,
            'source_key' => 'meta_ads',
            'source_label' => 'Meta Ads',
            'last_recorded_at' => $freshnessColumn
                ? (clone $dailyRows)->max($freshnessColumn)
                : $coverageEnd,
            'message' => $coverageComplete
                ? null
                : sprintf(
                    'Data harian baru mencakup %s sampai %s, sedangkan periode dashboard %s sampai %s. Lakukan backfill sebelum dipakai sebagai actual bulanan.',
                    $coverageStart,
                    $coverageEnd,
                    $dateFrom,
                    $dateTo
                ),
            'meta' => [
                'row_count' => $rowCount,
                'granularity' => 'daily',
                'coverage_start' => $coverageStart,
                'coverage_end' => $coverageEnd,
                'coverage_complete' => $coverageComplete,
            ],
        ];
    }

    private function calculateGoogleAdsSpend(
        string $dateFrom,
        string $dateTo
    ): array {
        $table = 'google_ads_dashboard_snapshots';

        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'total_cost')
            || ! Schema::hasColumn($table, 'date_start')
            || ! Schema::hasColumn($table, 'date_stop')
        ) {
            return $this->platformSpendUnavailable(
                'google_ads',
                'Google Ads',
                'Tabel atau kolom snapshot harian Google Ads belum tersedia.'
            );
        }

        $dailyRows = DB::table($table)
            ->whereColumn('date_start', 'date_stop')
            ->whereDate('date_start', '>=', $dateFrom)
            ->whereDate('date_start', '<=', $dateTo);

        if (Schema::hasColumn($table, 'date_preset')) {
            $dailyRows->where('date_preset', 'daily');
        }

        if (Schema::hasColumn($table, 'is_available')) {
            $dailyRows->where('is_available', true);
        }

        $customerId = preg_replace(
            '/\D+/',
            '',
            (string) config('services.google_ads.customer_id')
        );

        if (
            $customerId !== ''
            && Schema::hasColumn($table, 'customer_id')
        ) {
            $dailyRows->where('customer_id', $customerId);
        }

        if (! (clone $dailyRows)->exists()) {
            return $this->platformSpendUnavailable(
                'google_ads',
                'Google Ads',
                'Snapshot harian belum tersedia pada periode terpilih. Jalankan sinkronisasi Google Ads harian.'
            );
        }

        if (Schema::hasColumn($table, 'id')) {
            $identityColumns = ['date_start', 'date_stop'];

            if (Schema::hasColumn($table, 'customer_id')) {
                array_unshift($identityColumns, 'customer_id');
            }

            $latestIds = (clone $dailyRows)
                ->selectRaw('MAX(id) as id')
                ->groupBy($identityColumns)
                ->pluck('id')
                ->filter()
                ->all();

            $resolvedRows = DB::table($table)->whereIn('id', $latestIds);
            $value = (float) (clone $resolvedRows)->sum('total_cost');
            $rowCount = count($latestIds);
            $coverageStart = (string) (clone $resolvedRows)->min('date_start');
            $coverageEnd = (string) (clone $resolvedRows)->max('date_stop');
            $coveredDateCount = (int) (clone $resolvedRows)
                ->distinct()
                ->count('date_start');
        } else {
            $resolvedRows = clone $dailyRows;
            $value = (float) (clone $resolvedRows)->sum('total_cost');
            $rowCount = (int) (clone $resolvedRows)->count();
            $coverageStart = (string) (clone $resolvedRows)->min('date_start');
            $coverageEnd = (string) (clone $resolvedRows)->max('date_stop');
            $coveredDateCount = (int) (clone $resolvedRows)
                ->distinct()
                ->count('date_start');
        }

        $expectedDateCount = Carbon::parse($dateFrom)
            ->diffInDays(Carbon::parse($dateTo)) + 1;
        $coverageComplete = $coverageStart <= $dateFrom
            && $coverageEnd >= $dateTo
            && $coveredDateCount >= $expectedDateCount;

        $freshnessColumn = $this->firstExistingColumn($table, [
            'synced_at',
            'updated_at',
            'created_at',
            'date_stop',
        ]);

        return [
            'value' => $value,
            'available' => $coverageComplete,
            'has_data' => $rowCount > 0,
            'source_key' => 'google_ads',
            'source_label' => 'Google Ads',
            'last_recorded_at' => $freshnessColumn
                ? (clone $resolvedRows)->max($freshnessColumn)
                : $coverageEnd,
            'message' => $coverageComplete
                ? null
                : sprintf(
                    'Data harian baru mencakup %s sampai %s (%d dari %d tanggal), sedangkan periode dashboard %s sampai %s. Lakukan backfill Google Ads sebelum dipakai sebagai actual bulanan.',
                    $coverageStart,
                    $coverageEnd,
                    $coveredDateCount,
                    $expectedDateCount,
                    $dateFrom,
                    $dateTo
                ),
            'meta' => [
                'row_count' => $rowCount,
                'granularity' => 'daily',
                'coverage_start' => $coverageStart,
                'coverage_end' => $coverageEnd,
                'covered_date_count' => $coveredDateCount,
                'expected_date_count' => $expectedDateCount,
                'coverage_complete' => $coverageComplete,
            ],
        ];
    }

    private function platformSpendUnavailable(
        string $sourceKey,
        string $sourceLabel,
        string $message
    ): array {
        return [
            'value' => 0.0,
            'available' => false,
            'has_data' => false,
            'source_key' => $sourceKey,
            'source_label' => $sourceLabel,
            'last_recorded_at' => null,
            'message' => $message,
            'meta' => [],
        ];
    }

    private function calculateStudentOnTrackRate(string $evaluationDate): array
    {
        foreach (['batches', 'student_enrollments', 'student_lesson_progresses'] as $table) {
            if (! Schema::hasTable($table)) {
                return $this->unavailableActual(
                    'student_progress',
                    'Learning Progress',
                    'Tabel batch, enrollment, atau learning progress belum tersedia.'
                );
            }
        }

        foreach ([
            'batches' => ['start_date', 'end_date', 'program_id'],
            'student_enrollments' => [
                'student_id',
                'program_id',
                'batch_id',
                'status',
                'access_status',
            ],
            'student_lesson_progresses' => [
                'student_id',
                'sub_topic_id',
                'progress_percentage',
            ],
        ] as $table => $columns) {
            if (collect($columns)->contains(
                fn (string $column): bool => ! Schema::hasColumn($table, $column)
            )) {
                return $this->unavailableActual(
                    'student_progress',
                    'Learning Progress',
                    'Kolom timeline batch, enrollment aktif, atau progress belum lengkap.'
                );
            }
        }

        $enrollments = DB::table('student_enrollments as se')
            ->join('batches as b', 'se.batch_id', '=', 'b.id')
            ->where('se.status', 'active')
            ->where('se.access_status', 'active')
            ->select([
                'se.student_id',
                'se.program_id',
                'se.batch_id',
                'b.start_date as batch_start_date',
                'b.end_date as batch_end_date',
            ])
            ->get()
            ->unique(fn (object $row): string => $row->batch_id . ':' . $row->student_id)
            ->values();

        $programIds = $enrollments
            ->pluck('program_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $subTopicsByProgram = $this->getActiveSubTopicsByProgram($programIds);
        $allSubTopicIds = collect($subTopicsByProgram)
            ->flatten()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $studentIds = $enrollments
            ->pluck('student_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $progressRows = empty($studentIds) || empty($allSubTopicIds)
            ? collect()
            : DB::table('student_lesson_progresses')
                ->whereIn('student_id', $studentIds)
                ->whereIn('sub_topic_id', $allSubTopicIds)
                ->select([
                    'student_id',
                    'sub_topic_id',
                    'progress_percentage',
                    'updated_at',
                ])
                ->get()
                ->groupBy(fn (object $row): int => (int) $row->student_id);

        $students = $enrollments->map(function (object $enrollment) use (
            $subTopicsByProgram,
            $progressRows
        ): array {
            $programSubTopics = $subTopicsByProgram[(int) $enrollment->program_id] ?? [];
            $programSet = array_fill_keys($programSubTopics, true);
            $studentProgressRows = $progressRows
                ->get((int) $enrollment->student_id, collect())
                ->filter(fn (object $row): bool => isset(
                    $programSet[(int) $row->sub_topic_id]
                ));

            $actualProgress = ! empty($programSubTopics) && $studentProgressRows->isNotEmpty()
                ? $studentProgressRows->sum(fn (object $row): float => max(
                    0,
                    min(100, (float) $row->progress_percentage)
                )) / count($programSubTopics)
                : null;

            return [
                'student_id' => (int) $enrollment->student_id,
                'batch_id' => (int) $enrollment->batch_id,
                'program_id' => (int) $enrollment->program_id,
                'batch_start_date' => $enrollment->batch_start_date,
                'batch_end_date' => $enrollment->batch_end_date,
                'actual_progress' => $actualProgress,
            ];
        })->all();

        $result = $this->studentOnTrackRateCalculator->calculate(
            $students,
            $evaluationDate
        );
        $summaryMeta = collect($result)->except('students')->all();
        $lastRecordedAt = $progressRows
            ->flatten(1)
            ->pluck('updated_at')
            ->filter()
            ->sortDesc()
            ->first();

        if (! $result['has_data']) {
            return $this->availableActual(
                value: 0,
                sourceKey: 'student_progress',
                sourceLabel: 'Learning Progress',
                lastRecordedAt: $lastRecordedAt,
                hasData: false,
                message: 'Tidak ada student aktif dengan timeline batch yang dapat dinilai pada periode ini.',
                meta: $summaryMeta
            );
        }

        $excluded = (int) $result['excluded_timeline_count']
            + (int) $result['excluded_progress_count'];
        $message = number_format((int) $result['on_track_students'])
            . ' dari '
            . number_format((int) $result['eligible_students'])
            . ' student aktif berada sesuai jadwal. Average progress '
            . number_format((float) $result['average_actual_progress'], 1, ',', '.')
            . '% · Expected progress '
            . number_format((float) $result['average_expected_progress'], 1, ',', '.')
            . '%.';

        if ($excluded > 0) {
            $message .= ' ' . number_format($excluded)
                . ' enrollment dikeluarkan karena timeline atau data progress belum lengkap.';
        }

        return $this->availableActual(
            value: (float) $result['rate'],
            sourceKey: 'student_progress',
            sourceLabel: 'Learning Progress',
            lastRecordedAt: $lastRecordedAt,
            hasData: true,
            message: $message,
            meta: $summaryMeta
        );
    }

    /**
     * Legacy completion calculator retained for future completed-batch KPI use.
     */
    private function calculateCompletionRate(
        string $dateFrom,
        string $dateTo
    ): array {
        $batchesTable = $this->firstExistingTable(['batches']);
        $enrollmentsTable = $this->firstExistingTable([
            'student_enrollments',
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        if (! $batchesTable || ! $enrollmentsTable) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                'Tabel batch atau enrollment belum tersedia.'
            );
        }

        $scheduledEndColumn = $this->firstExistingColumn($batchesTable, [
            'end_date',
            'scheduled_end_date',
            'completion_date',
            'finish_date',
            'ended_at',
            'finished_at',
            'end_at',
        ]);
        $batchIdColumn = $this->firstExistingColumn(
            $enrollmentsTable,
            ['batch_id']
        );
        $studentIdColumn = $this->firstExistingColumn(
            $enrollmentsTable,
            ['student_id', 'user_id']
        );

        if (! $scheduledEndColumn || ! $batchIdColumn || ! $studentIdColumn) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                'Tanggal selesai batch atau relasi enrollment belum tersedia.'
            );
        }

        $batchProgramColumn = $this->firstExistingColumn(
            $batchesTable,
            ['program_id']
        );
        $enrollmentProgramColumn = $this->firstExistingColumn(
            $enrollmentsTable,
            ['program_id']
        );

        if (! $batchProgramColumn && ! $enrollmentProgramColumn) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                'Program enrollment belum dapat ditentukan.'
            );
        }

        $eligibleBatches = DB::table($batchesTable)
            ->whereDate($scheduledEndColumn, '>=', $dateFrom)
            ->whereDate($scheduledEndColumn, '<=', $dateTo)
            ->pluck(
                $batchProgramColumn ?: 'id',
                'id'
            );

        if ($eligibleBatches->isEmpty()) {
            return $this->availableActual(
                value: 0,
                sourceKey: 'student_progress',
                sourceLabel: 'Learning Progress',
                lastRecordedAt: null,
                hasData: false,
                message: 'Tidak ada batch yang dijadwalkan selesai pada periode ini.',
                meta: [
                    'eligible_students' => 0,
                    'completed_students' => 0,
                    'completion_threshold' => 95,
                ]
            );
        }

        $enrollmentQuery = DB::table($enrollmentsTable . ' as se')
            ->join(
                $batchesTable . ' as b',
                'se.' . $batchIdColumn,
                '=',
                'b.id'
            )
            ->whereIn('se.' . $batchIdColumn, $eligibleBatches->keys()->all())
            ->selectRaw('se.' . $batchIdColumn . ' as batch_id')
            ->selectRaw('se.' . $studentIdColumn . ' as student_id');

        if ($enrollmentProgramColumn) {
            $enrollmentQuery->selectRaw(
                'se.' . $enrollmentProgramColumn . ' as program_id'
            );
        } else {
            $enrollmentQuery->selectRaw(
                'b.' . $batchProgramColumn . ' as program_id'
            );
        }

        $this->applyEnrollmentEligibility(
            $enrollmentQuery,
            $enrollmentsTable,
            'se'
        );

        $enrollments = $enrollmentQuery
            ->get()
            ->filter(fn (object $row) => (
                (int) $row->student_id > 0
                && (int) $row->program_id > 0
            ))
            ->unique(fn (object $row) => (
                (int) $row->batch_id
                . ':'
                . (int) $row->student_id
                . ':'
                . (int) $row->program_id
            ))
            ->values();

        if ($enrollments->isEmpty()) {
            return $this->availableActual(
                value: 0,
                sourceKey: 'student_progress',
                sourceLabel: 'Learning Progress',
                lastRecordedAt: null,
                hasData: false,
                message: 'Batch selesai ditemukan, tetapi tidak memiliki student eligible.',
                meta: [
                    'eligible_students' => 0,
                    'completed_students' => 0,
                    'completion_threshold' => 95,
                ]
            );
        }

        $programIds = $enrollments
            ->pluck('program_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $studentIds = $enrollments
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $subTopicsByProgram = $this->getActiveSubTopicsByProgram($programIds);

        if (collect($subTopicsByProgram)->flatten()->isEmpty()) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                'Active subtopics program belum tersedia untuk menghitung completion.'
            );
        }

        $completedMap = $this->getCompletedSubTopicMap(
            $studentIds,
            collect($subTopicsByProgram)
                ->flatten()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all()
        );

        if (! $completedMap['available']) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                $completedMap['message']
            );
        }

        $completedStudents = 0;
        $measurableStudents = 0;

        foreach ($enrollments as $enrollment) {
            $studentId = (int) $enrollment->student_id;
            $programId = (int) $enrollment->program_id;
            $programSubTopics = $subTopicsByProgram[$programId] ?? [];

            if (empty($programSubTopics)) {
                continue;
            }

            $measurableStudents++;
            $programSet = array_fill_keys($programSubTopics, true);
            $studentCompleted = $completedMap['rows'][$studentId] ?? [];
            $completedCount = count(
                array_intersect_key($studentCompleted, $programSet)
            );
            $studentProgress = (
                $completedCount / count($programSubTopics)
            ) * 100;

            if ($studentProgress >= 95) {
                $completedStudents++;
            }
        }

        if ($measurableStudents <= 0) {
            return $this->unavailableActual(
                'student_progress',
                'Learning Progress',
                'Student eligible tidak dapat dipetakan ke active subtopics program.'
            );
        }

        $rate = round(
            ($completedStudents / $measurableStudents) * 100,
            2
        );

        return $this->availableActual(
            value: $rate,
            sourceKey: 'student_progress',
            sourceLabel: 'Learning Progress',
            lastRecordedAt: $completedMap['last_recorded_at'],
            hasData: true,
            meta: [
                'eligible_students' => $measurableStudents,
                'completed_students' => $completedStudents,
                'completion_threshold' => 95,
                'scheduled_finish_from' => $dateFrom,
                'scheduled_finish_to' => $dateTo,
            ]
        );
    }

    private function applyEnrollmentEligibility(
        Builder $query,
        string $table,
        string $alias
    ): void {
        if (Schema::hasColumn($table, 'status')) {
            $query->whereIn($alias . '.status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
                'completed',
            ]);
        }

        if (Schema::hasColumn($table, 'access_status')) {
            $query->whereIn($alias . '.access_status', [
                'active',
                'granted',
                'available',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $programIds
     * @return array<int, array<int, int>>
     */
    private function getActiveSubTopicsByProgram(array $programIds): array
    {
        $subTopics = $this->firstExistingTable([
            'sub_topics',
            'subtopics',
            'lessons',
        ]);
        $topics = $this->firstExistingTable([
            'topics',
            'module_topics',
        ]);
        $modules = $this->firstExistingTable([
            'modules',
            'program_modules',
        ]);
        $stages = $this->firstExistingTable([
            'program_stages',
            'stages',
        ]);

        if (! $subTopics || ! $topics || ! $modules) {
            return [];
        }

        $subTopicTopic = $this->firstExistingColumn(
            $subTopics,
            ['topic_id']
        );
        $topicModule = $this->firstExistingColumn(
            $topics,
            ['module_id']
        );
        $moduleProgram = $this->firstExistingColumn(
            $modules,
            ['program_id']
        );
        $moduleStage = $this->firstExistingColumn(
            $modules,
            ['program_stage_id', 'stage_id']
        );

        if (! $subTopicTopic || ! $topicModule) {
            return [];
        }

        $query = DB::table($subTopics . ' as st')
            ->join($topics . ' as t', 'st.' . $subTopicTopic, '=', 't.id')
            ->join($modules . ' as m', 't.' . $topicModule, '=', 'm.id')
            ->selectRaw('st.id as sub_topic_id');

        if ($moduleProgram) {
            $query
                ->selectRaw('m.' . $moduleProgram . ' as program_id')
                ->whereIn('m.' . $moduleProgram, $programIds);
        } elseif ($moduleStage && $stages) {
            $stageProgram = $this->firstExistingColumn(
                $stages,
                ['program_id']
            );

            if (! $stageProgram) {
                return [];
            }

            $query
                ->join($stages . ' as ps', 'm.' . $moduleStage, '=', 'ps.id')
                ->selectRaw('ps.' . $stageProgram . ' as program_id')
                ->whereIn('ps.' . $stageProgram, $programIds);

            $this->applyActiveContentFilter($query, $stages, 'ps');
        } else {
            return [];
        }

        $this->applyActiveContentFilter($query, $subTopics, 'st');
        $this->applyActiveContentFilter($query, $topics, 't');
        $this->applyActiveContentFilter($query, $modules, 'm');

        return $query
            ->get()
            ->groupBy('program_id')
            ->map(fn (Collection $rows) => $rows
                ->pluck('sub_topic_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    private function applyActiveContentFilter(
        Builder $query,
        string $table,
        string $alias
    ): void {
        if (Schema::hasColumn($table, 'is_active')) {
            $query->where($alias . '.is_active', true);
        } elseif (Schema::hasColumn($table, 'status')) {
            $query->whereIn($alias . '.status', [
                'active',
                'published',
                'open',
                'scheduled',
                'ready',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $studentIds
     * @param  array<int, int>  $subTopicIds
     * @return array<string, mixed>
     */
    private function getCompletedSubTopicMap(
        array $studentIds,
        array $subTopicIds
    ): array {
        $table = $this->firstExistingTable([
            'student_lesson_progress',
            'student_lesson_progresses',
            'student_learning_progress',
            'learning_progress',
        ]);

        if (! $table) {
            return [
                'available' => false,
                'message' => 'Tabel learning progress belum tersedia.',
                'rows' => [],
                'last_recorded_at' => null,
            ];
        }

        $studentColumn = $this->firstExistingColumn($table, [
            'student_id',
            'user_id',
        ]);
        $subTopicColumn = $this->firstExistingColumn($table, [
            'sub_topic_id',
            'subtopic_id',
            'lesson_id',
        ]);
        $completionSignal = $this->firstExistingColumn($table, [
            'is_completed',
            'completed',
            'completed_at',
            'status',
            'progress_percentage',
            'percentage_watched',
            'progress',
            'completion_percentage',
        ]);

        if (! $studentColumn || ! $subTopicColumn || ! $completionSignal) {
            return [
                'available' => false,
                'message' => 'Kolom student, lesson, atau completion signal belum tersedia.',
                'rows' => [],
                'last_recorded_at' => null,
            ];
        }

        $query = DB::table($table)
            ->whereIn($studentColumn, $studentIds)
            ->whereIn($subTopicColumn, $subTopicIds);

        $this->applyCompletionFilter($query, $table);

        $rows = (clone $query)
            ->selectRaw($studentColumn . ' as student_id')
            ->selectRaw($subTopicColumn . ' as sub_topic_id')
            ->get();

        $completed = [];

        foreach ($rows as $row) {
            $completed[(int) $row->student_id][(int) $row->sub_topic_id] = true;
        }

        $freshnessColumn = $this->firstExistingColumn($table, [
            'updated_at',
            'completed_at',
            'created_at',
        ]);

        return [
            'available' => true,
            'message' => null,
            'rows' => $completed,
            'last_recorded_at' => $freshnessColumn
                ? (clone $query)->max($freshnessColumn)
                : null,
        ];
    }

    private function applyCompletionFilter(
        Builder $query,
        string $table
    ): void {
        $percentageColumns = collect([
            'progress_percentage',
            'percentage_watched',
            'progress',
            'completion_percentage',
        ])->filter(fn (string $column) => Schema::hasColumn($table, $column));

        $query->where(function (Builder $query) use (
            $table,
            $percentageColumns
        ) {
            if (Schema::hasColumn($table, 'is_completed')) {
                $query->orWhere('is_completed', true);
            }

            if (Schema::hasColumn($table, 'completed')) {
                $query->orWhere('completed', true);
            }

            if (Schema::hasColumn($table, 'completed_at')) {
                $query->orWhereNotNull('completed_at');
            }

            if (Schema::hasColumn($table, 'status')) {
                $query->orWhereIn('status', [
                    'completed',
                    'done',
                    'finished',
                    'passed',
                ]);
            }

            foreach ($percentageColumns as $column) {
                $query->orWhere($column, '>=', 95);
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildScorecard(
        Collection $definitions,
        Collection $targets,
        array $actuals,
        array $previousActuals,
        array $period
    ): array {
        return $definitions
            ->map(function (KpiDefinition $definition) use (
                $targets,
                $actuals,
                $previousActuals,
                $period
            ) {
                /** @var KpiTarget|null $target */
                $target = $targets->get($definition->id);
                $actual = $actuals[$definition->code]
                    ?? $this->unavailableActual(
                        'unknown',
                        'Unknown',
                        'Calculator KPI belum tersedia.'
                    );
                $previous = $previousActuals[$definition->code] ?? null;
                $targetValue = $target
                    ? (float) $target->target_value
                    : null;

                $evaluation = $this->evaluateKpi(
                    definition: $definition,
                    targetValue: $targetValue,
                    actual: $actual,
                    period: $period,
                );
                $trend = $this->buildTrend(
                    definition: $definition,
                    actual: $actual,
                    previous: $previous,
                );
                $scope = $definition->resolveTargetScope();

                return [
                    'code' => $definition->code,
                    'name' => $definition->code === 'student_completion_rate'
                        ? 'Student On-Track Rate'
                        : $definition->name,
                    'description' => $definition->code === 'student_completion_rate'
                        ? 'Persentase student aktif yang progress aktualnya mencapai minimal 90% dari expected progress timeline batch.'
                        : $definition->description,
                    'division' => $definition->division,
                    'category' => $definition->category,
                    'unit' => $definition->unit,
                    'direction' => $definition->direction,
                    'scope_type' => $scope['scope_type'],
                    'scope_identifier' => $scope['scope_identifier'],
                    'scope_label' => $scope['scope_label'],
                    'target_id' => $target?->id,
                    'target_value' => $targetValue,
                    'target_formatted' => $targetValue !== null
                        ? $this->formatValue($targetValue, $definition->unit)
                        : 'Not configured',
                    'target_status' => $target?->status,
                    'actual_value' => $actual['value'],
                    'actual_formatted' => $actual['available'] && $actual['has_data']
                        ? $this->formatValue(
                            (float) $actual['value'],
                            $definition->unit
                        )
                        : ($actual['available'] ? 'No data' : 'Unavailable'),
                    'actual_available' => (bool) $actual['available'],
                    'has_data' => (bool) $actual['has_data'],
                    'source_key' => $actual['source_key'],
                    'source_label' => $actual['source_label'],
                    'source_message' => $actual['message'],
                    'last_recorded_at' => $actual['last_recorded_at'],
                    'actual_meta' => $actual['meta'],
                    ...$evaluation,
                    'trend' => $trend,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateKpi(
        KpiDefinition $definition,
        ?float $targetValue,
        array $actual,
        array $period
    ): array {
        if ($targetValue === null || $targetValue <= 0) {
            return $this->evaluation(
                'not_configured',
                'Not configured',
                'Target Active atau Locked belum tersedia.',
                null,
                null,
                null
            );
        }

        if ($period['is_future']) {
            return $this->evaluation(
                'pending',
                'Pending',
                'Periode target belum dimulai.',
                null,
                null,
                null
            );
        }

        if (! $actual['available']) {
            return $this->evaluation(
                'unavailable',
                'Unavailable',
                $actual['message'] ?: 'Sumber actual belum tersedia.',
                null,
                null,
                null
            );
        }

        if (! $actual['has_data']) {
            return $this->evaluation(
                'no_data',
                'No data',
                $actual['message'] ?: 'Belum ada data pada periode ini.',
                null,
                null,
                null
            );
        }

        $actualValue = max(0, (float) $actual['value']);
        $elapsedRatio = max(0.000001, (float) $period['elapsed_ratio']);
        $expectedToDate = $definition->code === 'student_completion_rate'
            ? $targetValue
            : $targetValue * $elapsedRatio;

        if ($definition->isLowerBetter()) {
            $achievement = $actualValue <= 0
                ? 100.0
                : min(100, ($targetValue / $actualValue) * 100);
            $pace = $expectedToDate <= 0
                ? null
                : ($actualValue / $expectedToDate) * 100;

            if ($actualValue <= $expectedToDate) {
                return $this->evaluation(
                    'healthy',
                    'On track',
                    'Actual masih berada di dalam batas target berjalan.',
                    $achievement,
                    $pace,
                    $expectedToDate
                );
            }

            if ($actualValue <= $expectedToDate * 1.1) {
                return $this->evaluation(
                    'watch',
                    'Watch',
                    'Actual melewati batas target berjalan, tetapi belum lebih dari 10%.',
                    $achievement,
                    $pace,
                    $expectedToDate
                );
            }

            return $this->evaluation(
                'critical',
                'Critical',
                'Actual sudah lebih dari 10% di atas batas target berjalan.',
                $achievement,
                $pace,
                $expectedToDate
            );
        }

        $achievement = min(100, ($actualValue / $targetValue) * 100);
        $pace = $expectedToDate > 0
            ? ($actualValue / $expectedToDate) * 100
            : null;

        if ($actualValue >= $expectedToDate) {
            return $this->evaluation(
                'healthy',
                'On track',
                'Actual sudah memenuhi target yang diharapkan sampai hari ini.',
                $achievement,
                $pace,
                $expectedToDate
            );
        }

        if ($actualValue >= $expectedToDate * 0.8) {
            return $this->evaluation(
                'watch',
                'Watch',
                'Actual berada pada 80–99% dari target yang diharapkan sampai hari ini.',
                $achievement,
                $pace,
                $expectedToDate
            );
        }

        return $this->evaluation(
            'critical',
            'Critical',
            'Actual masih di bawah 80% dari target yang diharapkan sampai hari ini.',
            $achievement,
            $pace,
            $expectedToDate
        );
    }

    private function evaluation(
        string $status,
        string $statusLabel,
        string $statusReason,
        ?float $achievement,
        ?float $pace,
        ?float $expectedToDate
    ): array {
        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'status_reason' => $statusReason,
            'achievement_percentage' => $achievement !== null
                ? round($achievement, 1)
                : null,
            'pace_percentage' => $pace !== null
                ? round($pace, 1)
                : null,
            'expected_to_date' => $expectedToDate !== null
                ? round($expectedToDate, 4)
                : null,
        ];
    }

    private function buildTrend(
        KpiDefinition $definition,
        array $actual,
        ?array $previous
    ): array {
        if (
            ! $actual['available']
            || ! $actual['has_data']
            || ! $previous
            || ! $previous['available']
            || ! $previous['has_data']
        ) {
            return [
                'available' => false,
                'previous_value' => null,
                'change_value' => null,
                'change_percentage' => null,
                'is_new' => false,
                'is_positive' => null,
            ];
        }

        $currentValue = (float) $actual['value'];
        $previousValue = (float) $previous['value'];
        $change = $currentValue - $previousValue;
        $isNew = $previousValue <= 0 && $currentValue > 0;
        $changePercentage = $previousValue > 0
            ? round(($change / $previousValue) * 100, 1)
            : null;

        return [
            'available' => true,
            'previous_value' => $previousValue,
            'change_value' => round($change, 4),
            'change_percentage' => $changePercentage,
            'is_new' => $isNew,
            'is_positive' => $definition->isLowerBetter()
                ? $change <= 0
                : $change >= 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildHighlights(array $scorecard): array
    {
        $byCode = collect($scorecard)->keyBy('code');
        $highlights = collect([
            ['code' => 'confirmed_revenue', 'icon' => 'bi-cash-stack'],
            ['code' => 'total_leads', 'icon' => 'bi-person-plus-fill'],
            ['code' => 'closed_deals', 'icon' => 'bi-trophy-fill'],
            ['code' => 'paid_students', 'icon' => 'bi-mortarboard-fill'],
        ])->map(function (array $highlight) use ($byCode) {
            $kpi = $byCode->get($highlight['code']);

            if (! $kpi) {
                return null;
            }

            return [
                'code' => $kpi['code'],
                'label' => $kpi['name'],
                'value' => $kpi['actual_value'],
                'value_formatted' => $kpi['actual_formatted'],
                'status' => $kpi['status'],
                'status_label' => $kpi['status_label'],
                'trend' => $kpi['trend'],
                'icon' => $highlight['icon'],
            ];
        })->filter()->values();

        $criticalCount = collect($scorecard)
            ->where('status', 'critical')
            ->count();

        $highlights->push([
            'code' => 'critical_kpis',
            'label' => 'Critical KPI',
            'value' => $criticalCount,
            'value_formatted' => number_format($criticalCount),
            'status' => $criticalCount > 0 ? 'critical' : 'healthy',
            'status_label' => $criticalCount > 0 ? 'Needs action' : 'Clear',
            'trend' => [
                'available' => false,
                'previous_value' => null,
                'change_value' => null,
                'change_percentage' => null,
                'is_new' => false,
                'is_positive' => $criticalCount === 0,
            ],
            'icon' => 'bi-exclamation-triangle-fill',
        ]);

        return $highlights->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBusinessHealth(
        array $scorecard,
        array $operationsHealth
    ): array
    {
        $byCode = collect($scorecard)->keyBy('code');

        return collect(self::CENTRES)
            ->map(function (array $centre, string $key) use (
                $byCode,
                $operationsHealth
            ) {
                if ($key === 'operations_centre') {
                    return $operationsHealth;
                }

                if ($key === 'learning_centre') {
                    return $this->buildLearningCentreHealth(
                        $centre,
                        $byCode->get('student_completion_rate')
                    );
                }

                $kpis = collect($centre['kpi_codes'])
                    ->map(fn (string $code) => $byCode->get($code))
                    ->filter()
                    ->values();
                $measurable = $kpis->whereIn('status', [
                    'healthy',
                    'watch',
                    'critical',
                ]);

                if ($measurable->isEmpty()) {
                    $state = $this->resolveUnmeasurableCentreState($kpis);

                    return [
                        'key' => $key,
                        'name' => $centre['name'],
                        'description' => $centre['description'],
                        'icon' => $centre['icon'],
                        'status' => $state['status'],
                        'status_label' => $state['status_label'],
                        'health_percentage' => null,
                        'kpi_count' => $kpis->count(),
                        'measurable_kpi_count' => 0,
                        'critical_count' => 0,
                        'watch_count' => 0,
                        'message' => $state['message'],
                    ];
                }

                $status = match (true) {
                    $measurable->contains(
                        fn (array $kpi) => $kpi['status'] === 'critical'
                    ) => 'critical',
                    $measurable->contains(
                        fn (array $kpi) => $kpi['status'] === 'watch'
                    ) => 'watch',
                    default => 'healthy',
                };

                return [
                    'key' => $key,
                    'name' => $centre['name'],
                    'description' => $centre['description'],
                    'icon' => $centre['icon'],
                    'status' => $status,
                    'status_label' => match ($status) {
                        'healthy' => 'Healthy',
                        'watch' => 'Needs attention',
                        default => 'Critical',
                    },
                    'health_percentage' => round(
                        (float) $measurable->avg(
                            fn (array $kpi) => $this->healthScoreForKpi($kpi)
                        ),
                        1
                    ),
                    'kpi_count' => $kpis->count(),
                    'measurable_kpi_count' => $measurable->count(),
                    'critical_count' => $measurable
                        ->where('status', 'critical')
                        ->count(),
                    'watch_count' => $measurable
                        ->where('status', 'watch')
                        ->count(),
                    'message' => null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Learning Centre uses the existing reusable KPI status evaluation while
     * the actual measures active students against each batch timeline.
     *
     * @return array<string, mixed>
     */
    private function buildLearningCentreHealth(
        array $centre,
        ?array $completionKpi
    ): array {
        $base = [
            'key' => 'learning_centre',
            'name' => $centre['name'],
            'description' => $centre['description'],
            'icon' => $centre['icon'],
            'kpi_count' => $completionKpi ? 1 : 0,
            'measurable_kpi_count' => 0,
            'critical_count' => 0,
            'watch_count' => 0,
            'source_key' => 'student_progress',
            'metrics' => [
                'actual_on_track_rate' => $completionKpi['actual_value'] ?? null,
                'target_on_track_rate' => $completionKpi['target_value'] ?? null,
                'eligible_students' => data_get(
                    $completionKpi,
                    'actual_meta.eligible_students'
                ),
                'on_track_students' => data_get(
                    $completionKpi,
                    'actual_meta.on_track_students'
                ),
                'average_actual_progress' => data_get(
                    $completionKpi,
                    'actual_meta.average_actual_progress'
                ),
                'average_expected_progress' => data_get(
                    $completionKpi,
                    'actual_meta.average_expected_progress'
                ),
            ],
        ];

        if (! $completionKpi) {
            return [
                ...$base,
                'status' => 'not_configured',
                'status_label' => 'Not configured',
                'health_percentage' => null,
                'message' => 'KPI Student On-Track Rate belum tersedia.',
            ];
        }

        $target = (float) ($completionKpi['target_value'] ?? 0);

        if ($target <= 0) {
            return [
                ...$base,
                'status' => 'not_configured',
                'status_label' => 'Not configured',
                'health_percentage' => null,
                'message' => 'Target Student On-Track Rate Active atau Locked belum tersedia.',
            ];
        }

        if (! ($completionKpi['actual_available'] ?? false)) {
            return [
                ...$base,
                'status' => 'unavailable',
                'status_label' => 'Unavailable',
                'health_percentage' => null,
                'message' => $completionKpi['source_message']
                    ?: 'Progress student belum dapat dihitung.',
            ];
        }

        if (! ($completionKpi['has_data'] ?? false)) {
            return [
                ...$base,
                'status' => 'no_data',
                'status_label' => 'No data',
                'health_percentage' => null,
                'message' => $completionKpi['source_message']
                    ?: 'Belum ada student eligible pada periode ini.',
            ];
        }

        $actual = max(0, (float) ($completionKpi['actual_value'] ?? 0));
        $achievement = min(100, ($actual / $target) * 100);
        $status = match (true) {
            $actual >= $target => 'healthy',
            $actual >= $target * 0.8 => 'watch',
            default => 'critical',
        };
        $eligibleStudents = (int) data_get(
            $completionKpi,
            'actual_meta.eligible_students',
            0
        );
        $onTrackStudents = (int) data_get(
            $completionKpi,
            'actual_meta.on_track_students',
            0
        );
        $averageActual = (float) data_get(
            $completionKpi,
            'actual_meta.average_actual_progress',
            0
        );
        $averageExpected = (float) data_get(
            $completionKpi,
            'actual_meta.average_expected_progress',
            0
        );

        return [
            ...$base,
            'status' => $status,
            'status_label' => match ($status) {
                'healthy' => 'Healthy',
                'watch' => 'Needs attention',
                default => 'Critical',
            },
            'health_percentage' => round($achievement, 1),
            'measurable_kpi_count' => 1,
            'critical_count' => $status === 'critical' ? 1 : 0,
            'watch_count' => $status === 'watch' ? 1 : 0,
            'message' => number_format($actual, 1, ',', '.')
                . '% on-track dari target '
                . number_format($target, 1, ',', '.')
                . '%. '
                . number_format($onTrackStudents)
                . ' dari '
                . number_format($eligibleStudents)
                . ' student aktif berada sesuai jadwal. Average progress '
                . number_format($averageActual, 1, ',', '.')
                . '% · Expected progress '
                . number_format($averageExpected, 1, ',', '.')
                . '%.',
        ];
    }

    /**
     * Operations Centre membaca seluruh source Trello yang tersinkron.
     *
     * Health score = completion rate - overdue rate.
     *
     * @return array<string, mixed>
     */
    private function buildOperationsHealth(): array
    {
        $centre = self::CENTRES['operations_centre'];
        $sources = collect();
        $failedSources = [];

        foreach ($this->trelloSourceKeys() as $sourceKey) {
            try {
                $stats = $this->trelloDashboardStatsService
                    ->getStats($sourceKey);

                if (is_array($stats)) {
                    $sources->put($sourceKey, $stats);
                }
            } catch (Throwable $exception) {
                $failedSources[] = $sourceKey;

                Log::warning(
                    'Executive Dashboard Trello source calculation failed.',
                    [
                        'source_key' => $sourceKey,
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        $configuredSources = $sources
            ->filter(fn (array $stats) => $this->isTrelloSourceConfigured($stats));

        $base = [
            'key' => 'operations_centre',
            'name' => $centre['name'],
            'description' => $centre['description'],
            'icon' => $centre['icon'],
            'kpi_count' => 0,
            'source_key' => 'trello',
        ];

        if ($configuredSources->isEmpty()) {
            $hasFailure = ! empty($failedSources);

            return [
                ...$base,
                'status' => $hasFailure ? 'unavailable' : 'not_configured',
                'status_label' => $hasFailure
                    ? 'Unavailable'
                    : 'Not configured',
                'health_percentage' => null,
                'measurable_kpi_count' => 0,
                'critical_count' => 0,
                'watch_count' => 0,
                'message' => $hasFailure
                    ? 'Data workload Trello belum dapat ditarik.'
                    : 'Belum ada board Trello yang tersinkron ke FlexOps.',
                'last_recorded_at' => null,
                'metrics' => $this->emptyTrelloMetrics(),
                'sources' => $sources->all(),
            ];
        }

        $metrics = $configuredSources->reduce(
            function (array $carry, array $stats) {
                $summary = is_array($stats['summary'] ?? null)
                    ? $stats['summary']
                    : [];

                $carry['total_tasks'] += max(
                    0,
                    (int) ($summary['total_open_cards'] ?? 0)
                );
                $carry['completed'] += max(
                    0,
                    (int) ($summary['completed'] ?? 0)
                );
                $carry['active_work'] += max(
                    0,
                    (int) ($summary['active_work'] ?? 0)
                );
                $carry['due_today'] += max(
                    0,
                    (int) ($summary['due_today'] ?? 0)
                );
                $carry['overdue'] += max(
                    0,
                    (int) ($summary['overdue'] ?? 0)
                );
                $carry['unmapped'] += max(
                    0,
                    (int) ($summary['unmapped'] ?? 0)
                );

                return $carry;
            },
            $this->emptyTrelloMetrics()
        );

        $totalTasks = $metrics['total_tasks'];

        if ($totalTasks <= 0) {
            return [
                ...$base,
                'status' => 'no_data',
                'status_label' => 'No data',
                'health_percentage' => null,
                'measurable_kpi_count' => 0,
                'critical_count' => 0,
                'watch_count' => 0,
                'message' => 'Board Trello sudah tersinkron, tetapi belum memiliki task yang dapat diukur.',
                'last_recorded_at' => $this->latestTrelloRecordedAt(
                    $configuredSources
                ),
                'metrics' => $metrics,
                'sources' => $configuredSources->all(),
            ];
        }

        $completionRate = min(
            100,
            ($metrics['completed'] / $totalTasks) * 100
        );
        $overdueRate = min(
            100,
            ($metrics['overdue'] / $totalTasks) * 100
        );
        $health = max(0, min(100, $completionRate - $overdueRate));

        $inactiveSourceCount = $configuredSources
            ->filter(function (array $stats) {
                return ! in_array(
                    strtolower((string) ($stats['webhook_status'] ?? '')),
                    ['active', 'synced'],
                    true
                );
            })
            ->count();

        $status = match (true) {
            $health < 50 || $overdueRate >= 10 => 'critical',
            $health < 80,
            $metrics['overdue'] > 0,
            $metrics['due_today'] > 0,
            $metrics['unmapped'] > 0,
            $inactiveSourceCount > 0,
            ! empty($failedSources) => 'watch',
            default => 'healthy',
        };

        $metrics['completion_rate'] = round($completionRate, 1);
        $metrics['overdue_rate'] = round($overdueRate, 1);
        $metrics['health_score'] = round($health, 1);
        $metrics['board_count'] = $configuredSources->count();
        $metrics['inactive_board_count'] = $inactiveSourceCount;

        return [
            ...$base,
            'status' => $status,
            'status_label' => match ($status) {
                'healthy' => 'Healthy',
                'watch' => 'Needs attention',
                default => 'Critical',
            },
            'health_percentage' => round($health, 1),
            'measurable_kpi_count' => 1,
            'critical_count' => $status === 'critical' ? 1 : 0,
            'watch_count' => $status === 'watch' ? 1 : 0,
            'message' => number_format($totalTasks)
                . ' total task · '
                . number_format($metrics['completed'])
                . ' selesai · '
                . number_format($metrics['active_work'])
                . ' active work · '
                . number_format($metrics['overdue'])
                . ' overdue.',
            'last_recorded_at' => $this->latestTrelloRecordedAt(
                $configuredSources
            ),
            'metrics' => $metrics,
            'sources' => $configuredSources->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function trelloSourceKeys(): array
    {
        $configured = config(
            'services.trello.dashboard_source_keys',
            []
        );

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        if (! is_array($configured)) {
            $configured = [];
        }

        return collect([
            ...self::TRELLO_SOURCE_KEYS,
            ...$configured,
        ])
            ->map(fn ($key) => strtolower(trim((string) $key)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isTrelloSourceConfigured(array $stats): bool
    {
        $webhookStatus = strtolower(
            trim((string) ($stats['webhook_status'] ?? ''))
        );

        return trim((string) ($stats['board_id'] ?? '')) !== ''
            || trim((string) ($stats['board_name'] ?? '')) !== ''
            || in_array($webhookStatus, ['active', 'synced'], true);
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyTrelloMetrics(): array
    {
        return [
            'total_tasks' => 0,
            'completed' => 0,
            'active_work' => 0,
            'due_today' => 0,
            'overdue' => 0,
            'unmapped' => 0,
            'completion_rate' => 0.0,
            'overdue_rate' => 0.0,
            'health_score' => 0.0,
            'board_count' => 0,
            'inactive_board_count' => 0,
        ];
    }

    private function latestTrelloRecordedAt(Collection $sources): ?string
    {
        return $sources
            ->flatMap(fn (array $stats) => [
                $stats['last_synced_at'] ?? null,
                $stats['last_webhook_at'] ?? null,
            ])
            ->filter()
            ->sortByDesc(function ($value) {
                try {
                    return Carbon::parse($value)->getTimestamp();
                } catch (Throwable) {
                    return 0;
                }
            })
            ->first();
    }

    /**
     * Jangan menyamakan source unavailable/no data dengan target yang memang
     * belum dikonfigurasi.
     *
     * @return array{status: string, status_label: string, message: string}
     */
    private function resolveUnmeasurableCentreState(Collection $kpis): array
    {
        if ($kpis->isEmpty()) {
            return [
                'status' => 'not_configured',
                'status_label' => 'Not configured',
                'message' => 'KPI source belum dipetakan untuk Centre ini.',
            ];
        }

        $firstUnavailable = $kpis->firstWhere('status', 'unavailable');

        if ($firstUnavailable) {
            return [
                'status' => 'unavailable',
                'status_label' => 'Unavailable',
                'message' => $firstUnavailable['source_message']
                    ?: 'Actual KPI belum dapat dihitung.',
            ];
        }

        $firstNoData = $kpis->firstWhere('status', 'no_data');

        if ($firstNoData) {
            return [
                'status' => 'no_data',
                'status_label' => 'No data',
                'message' => $firstNoData['source_message']
                    ?: 'Belum ada data pada periode ini.',
            ];
        }

        if ($kpis->every(fn (array $kpi) => $kpi['status'] === 'pending')) {
            return [
                'status' => 'pending',
                'status_label' => 'Pending',
                'message' => 'Periode target belum dimulai.',
            ];
        }

        return [
            'status' => 'not_configured',
            'status_label' => 'Not configured',
            'message' => 'Target Active atau Locked belum lengkap.',
        ];
    }

    private function healthScoreForKpi(array $kpi): float
    {
        $pace = $kpi['pace_percentage'];

        if ($pace === null) {
            return (float) ($kpi['achievement_percentage'] ?? 0);
        }

        if ($kpi['direction'] === KpiDefinition::DIRECTION_LOWER) {
            return $pace <= 100
                ? 100.0
                : max(0, min(100, 10000 / $pace));
        }

        return max(0, min(100, (float) $pace));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBusinessAttention(array $scorecard): array
    {
        $priority = [
            'critical' => 1,
            'unavailable' => 2,
            'no_data' => 3,
            'watch' => 4,
            'not_configured' => 5,
        ];

        return collect($scorecard)
            ->filter(fn (array $kpi) => isset($priority[$kpi['status']]))
            ->map(function (array $kpi) use ($priority) {
                return [
                    'kpi_code' => $kpi['code'],
                    'title' => $kpi['name'],
                    'centre' => $this->centreNameForKpi($kpi['code']),
                    'severity' => $kpi['status'],
                    'severity_label' => $kpi['status_label'],
                    'priority_order' => $priority[$kpi['status']],
                    'message' => $kpi['status_reason'],
                    'actual_formatted' => $kpi['actual_formatted'],
                    'target_formatted' => $kpi['target_formatted'],
                    'recommended_action' => $this->recommendedAction($kpi),
                    'source_message' => $this->sameMessage(
                        $kpi['source_message'] ?? null,
                        $kpi['status_reason'] ?? null
                    )
                        ? null
                        : ($kpi['source_message'] ?? null),
                ];
            })
            ->sortBy([
                ['priority_order', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function sameMessage(
        mixed $first,
        mixed $second
    ): bool {
        $normalize = static function (mixed $message): string {
            $message = trim((string) $message);
            $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

            return strtolower($message);
        };

        $normalizedFirst = $normalize($first);
        $normalizedSecond = $normalize($second);

        return $normalizedFirst !== ''
            && $normalizedFirst === $normalizedSecond;
    }

    private function buildExecutiveBriefFallback(
        array $scorecard,
        array $attention,
        array $period
    ): array {
        $healthy = collect($scorecard)->where('status', 'healthy');
        $critical = collect($scorecard)->where('status', 'critical');
        $watch = collect($scorecard)->where('status', 'watch');
        $unavailable = collect($scorecard)->whereIn(
            'status',
            ['unavailable', 'no_data']
        );

        $headline = match (true) {
            $critical->isNotEmpty() => $critical->count()
                . ' KPI kritis membutuhkan tindakan manajemen',
            $watch->isNotEmpty() => $watch->count()
                . ' KPI perlu dijaga agar tetap sesuai target',
            $healthy->isNotEmpty() => 'KPI terukur berada dalam jalur target',
            default => 'Executive Dashboard belum memiliki data terukur lengkap',
        };

        $summaryParts = [];

        if ($healthy->isNotEmpty()) {
            $summaryParts[] = $healthy->pluck('name')->join(', ')
                . ' berada dalam jalur target.';
        }

        if ($critical->isNotEmpty()) {
            $summaryParts[] = $critical->pluck('name')->join(', ')
                . ' berstatus kritis berdasarkan target berjalan.';
        }

        if ($unavailable->isNotEmpty()) {
            $summaryParts[] = $unavailable->pluck('name')->join(', ')
                . ' belum memiliki actual yang cukup untuk dinilai.';
        }

        if (empty($summaryParts)) {
            $summaryParts[] = 'Belum ada KPI yang dapat dievaluasi pada periode ini.';
        }

        return [
            'title' => 'Executive Brief — ' . $period['label'],
            'headline' => $headline,
            'summary' => implode(' ', $summaryParts),
            'root_causes' => collect($attention)
                ->whereIn('severity', ['critical', 'watch'])
                ->take(3)
                ->map(fn (array $item) => $item['title'] . ': ' . $item['message'])
                ->values()
                ->all(),
            'recommendations' => collect($attention)
                ->take(3)
                ->pluck('recommended_action')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'priority' => $critical->isNotEmpty()
                ? 'Immediate'
                : ($watch->isNotEmpty() ? 'This Week' : 'Monitor'),
            'generated_at' => now()->toIso8601String(),
            'generation_type' => 'local_fallback',
            'is_ai_generated' => false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDataFreshness(
        array $scorecard,
        array $operationsHealth
    ): array
    {
        $freshness = collect($scorecard)
            ->groupBy('source_key')
            ->map(function (Collection $rows, string $sourceKey) {
                $latest = $rows
                    ->pluck('last_recorded_at')
                    ->filter()
                    ->sortDesc()
                    ->first();

                return [
                    'source_key' => $sourceKey,
                    'source_label' => $rows->first()['source_label'] ?? $sourceKey,
                    'is_available' => $rows->contains(
                        fn (array $row) => $row['actual_available']
                    ),
                    'last_recorded_at' => $latest,
                ];
            })
            ->values();

        $freshness->push([
            'source_key' => 'trello',
            'source_label' => 'Trello Workload',
            'is_available' => in_array(
                $operationsHealth['status'] ?? 'unavailable',
                ['healthy', 'watch', 'critical', 'no_data'],
                true
            ),
            'last_recorded_at' => $operationsHealth['last_recorded_at']
                ?? null,
        ]);

        return $freshness
            ->unique('source_key')
            ->values()
            ->all();
    }

    private function recommendedAction(array $kpi): string
    {
        return match ($kpi['status']) {
            'critical' => $kpi['direction'] === KpiDefinition::DIRECTION_LOWER
                ? 'Tinjau komponen biaya terbesar dan hentikan pemborosan yang tidak menghasilkan outcome.'
                : 'Tetapkan PIC dan recovery action untuk menutup gap terhadap target berjalan.',
            'watch' => 'Pantau harian dan siapkan corrective action sebelum KPI masuk status kritis.',
            'unavailable' => 'Lengkapi source atau snapshot periode agar actual dapat dihitung secara akurat.',
            'no_data' => 'Pastikan aktivitas periode dan proses pencatatan data sudah berjalan.',
            'not_configured' => 'Aktifkan atau lock Monthly Target KPI ini sebelum dipakai untuk monitoring.',
            default => 'Pertahankan eksekusi dan monitor konsistensi pencapaian.',
        };
    }

    private function centreNameForKpi(string $code): string
    {
        foreach (self::CENTRES as $centre) {
            if (in_array($code, $centre['kpi_codes'], true)) {
                return $centre['name'];
            }
        }

        return 'Executive Center';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function futureActuals(): array
    {
        return collect(self::KPI_CODES)
            ->mapWithKeys(fn (string $code) => [
                $code => [
                    'value' => 0.0,
                    'available' => true,
                    'has_data' => false,
                    'source_key' => 'future_period',
                    'source_label' => 'Future Period',
                    'last_recorded_at' => null,
                    'message' => 'Periode belum dimulai.',
                    'meta' => [],
                ],
            ])
            ->all();
    }

    private function availableActual(
        float|int $value,
        string $sourceKey,
        string $sourceLabel,
        mixed $lastRecordedAt = null,
        bool $hasData = true,
        ?string $message = null,
        array $meta = []
    ): array {
        return [
            'value' => (float) $value,
            'available' => true,
            'has_data' => $hasData,
            'source_key' => $sourceKey,
            'source_label' => $sourceLabel,
            'last_recorded_at' => $lastRecordedAt
                ? Carbon::parse($lastRecordedAt)->toIso8601String()
                : null,
            'message' => $message,
            'meta' => $meta,
        ];
    }

    private function unavailableActual(
        string $sourceKey,
        string $sourceLabel,
        string $message
    ): array {
        return [
            'value' => 0.0,
            'available' => false,
            'has_data' => false,
            'source_key' => $sourceKey,
            'source_label' => $sourceLabel,
            'last_recorded_at' => null,
            'message' => $message,
            'meta' => [],
        ];
    }

    private function sourceLabel(string $sourceKey): string
    {
        return match ($sourceKey) {
            'sales_daily_reports' => 'Sales Daily Report',
            'payments' => 'Confirmed Payments',
            'marketing_platforms' => 'Meta Ads + Google Ads',
            'student_progress' => 'Learning Progress',
            default => 'Data Source',
        };
    }

    private function formatValue(float $value, string $unit): string
    {
        return match ($unit) {
            KpiDefinition::UNIT_CURRENCY => 'Rp '
                . number_format($value, 0, ',', '.'),
            KpiDefinition::UNIT_PERCENTAGE => number_format(
                $value,
                1,
                ',',
                '.'
            ) . '%',
            KpiDefinition::UNIT_DECIMAL => number_format(
                $value,
                2,
                ',',
                '.'
            ),
            default => number_format($value, 0, ',', '.'),
        };
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function firstExistingColumn(
        string $table,
        array $columns
    ): ?string {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function wrap(string $column): string
    {
        return DB::connection()->getQueryGrammar()->wrap($column);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
