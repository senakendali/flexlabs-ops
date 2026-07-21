<?php

namespace App\Services\Dashboard;

use App\Models\SalesDailyReport;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use App\Services\KommoService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SalesDashboardService
{
    public function __construct(
        protected KommoService $kommoService
    ) {
    }

    /**
     * Menyiapkan seluruh data yang dibutuhkan oleh Sales Dashboard.
     *
     * Filter yang didukung:
     * - date_from: Y-m-d
     * - date_to: Y-m-d
     *
     * Default periode adalah 30 hari terakhir, termasuk hari ini.
     */
    public function getData(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($filters);
        [$previousFrom, $previousTo] = $this->resolvePreviousPeriod($dateFrom, $dateTo);

        $currentReport = $this->getSalesReportSummary($dateFrom, $dateTo);
        $previousReport = $this->getSalesReportSummary($previousFrom, $previousTo);

        $currentPayments = $this->getPaidPaymentSummary($dateFrom, $dateTo);
        $previousPayments = $this->getPaidPaymentSummary($previousFrom, $previousTo);

        $salesInsight = $this->buildSalesInsight(
            currentReport: $currentReport,
            previousReport: $previousReport,
            currentPayments: $currentPayments,
            previousPayments: $previousPayments,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            previousFrom: $previousFrom,
            previousTo: $previousTo,
        );

        $salesPerformanceChart = $this->getSalesPerformanceChart($dateFrom, $dateTo);
        $revenueChart = $this->getRevenueChart($dateFrom, $dateTo);
        $kommoTodayLeadInsight = $this->getKommoTodayLeadInsight();

        $trialStats = $this->getTrialStats($dateFrom, $dateTo);
        $trialParticipantStatusCounts = $this->getTrialParticipantStatusCounts($dateFrom, $dateTo);
        $trialFollowUpProgress = $this->getTrialFollowUpProgress($dateFrom, $dateTo);
        $upcomingTrialSchedules = $this->getUpcomingTrialSchedules();

        $workshopStats = $this->getWorkshopStats($dateFrom, $dateTo);
        $workshopParticipantStatusCounts = $this->getWorkshopParticipantStatusCounts($dateFrom, $dateTo);
        $workshopFollowUpProgress = $this->getWorkshopFollowUpProgress($dateFrom, $dateTo);
        $upcomingWorkshopSchedules = $this->getUpcomingWorkshopSchedules();

        $financeInsight = $this->getFinanceInsight($dateFrom, $dateTo);
        $orderInsight = $this->getOrderInsight($dateFrom, $dateTo);
        $batchCapacity = $this->getBatchCapacitySummary();
        $upcomingBatches = $this->getUpcomingBatches();

        $salesSummary = $this->buildSalesDashboardSummary([
            'sales_insight' => $salesInsight,
            'kommo_today_lead_insight' => $kommoTodayLeadInsight,
            'trial_stats' => $trialStats,
            'trial_status_counts' => $trialParticipantStatusCounts,
            'trial_follow_up_progress' => $trialFollowUpProgress,
            'workshop_stats' => $workshopStats,
            'workshop_status_counts' => $workshopParticipantStatusCounts,
            'workshop_follow_up_progress' => $workshopFollowUpProgress,
            'finance_insight' => $financeInsight,
            'order_insight' => $orderInsight,
            'batch_capacity' => $batchCapacity,
            'upcoming_batches' => $upcomingBatches,
        ]);

        return [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => $this->formatPeriodLabel($dateFrom, $dateTo),
                'previous_date_from' => $previousFrom,
                'previous_date_to' => $previousTo,
                'previous_label' => $this->formatPeriodLabel($previousFrom, $previousTo),
            ],

            'salesInsight' => $salesInsight,
            'salesPerformanceChart' => $salesPerformanceChart,

            'kommoTodayLeadInsight' => $kommoTodayLeadInsight,

            'trialStats' => $trialStats,
            'trialParticipantStatusCounts' => $trialParticipantStatusCounts,
            'trialFollowUpProgress' => $trialFollowUpProgress,
            'upcomingTrialSchedules' => $upcomingTrialSchedules,

            'workshopStats' => $workshopStats,
            'workshopParticipantStatusCounts' => $workshopParticipantStatusCounts,
            'workshopFollowUpProgress' => $workshopFollowUpProgress,
            'upcomingWorkshopSchedules' => $upcomingWorkshopSchedules,

            'financeInsight' => $financeInsight,
            'orderInsight' => $orderInsight,
            'revenueChart' => $revenueChart,

            'batchCapacity' => $batchCapacity,
            'upcomingBatches' => $upcomingBatches,

            'salesSummary' => $salesSummary,
            'salesDashboardAiSummaryText' => $salesSummary['summary_text'] ?? '',
        ];
    }

    /**
     * Payload chart terpisah jika nanti dipakai oleh endpoint JSON.
     */
    public function getChartData(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($filters);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => $this->formatPeriodLabel($dateFrom, $dateTo),
            ],
            'sales_performance' => $this->getSalesPerformanceChart($dateFrom, $dateTo),
            'revenue' => $this->getRevenueChart($dateFrom, $dateTo),
        ];
    }

    protected function resolvePeriod(array $filters): array
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        try {
            $from = $dateFrom !== ''
                ? Carbon::parse($dateFrom)->startOfDay()
                : now()->subDays(29)->startOfDay();
        } catch (Throwable) {
            $from = now()->subDays(29)->startOfDay();
        }

        try {
            $to = $dateTo !== ''
                ? Carbon::parse($dateTo)->startOfDay()
                : now()->startOfDay();
        } catch (Throwable) {
            $to = now()->startOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    protected function resolvePreviousPeriod(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $periodDays = max(1, $from->diffInDays($to) + 1);

        $previousTo = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($periodDays - 1);

        return [$previousFrom->toDateString(), $previousTo->toDateString()];
    }

    protected function getSalesReportSummary(string $dateFrom, string $dateTo): array
    {
        $defaults = [
            'reports' => 0,
            'total_leads' => 0,
            'interacted' => 0,
            'ignored' => 0,
            'closed_lost' => 0,
            'not_related' => 0,
            'warm_leads' => 0,
            'hot_leads' => 0,
            'consultation' => 0,
            'closed_deal' => 0,
            'revenue' => 0,
            'latest_report_date' => null,
        ];

        if (! class_exists(SalesDailyReport::class)) {
            return $defaults;
        }

        $model = new SalesDailyReport();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return $defaults;
        }

        $dateColumn = $this->findExistingColumn($table, ['report_date', 'date', 'created_at']);

        if (! $dateColumn) {
            return $defaults;
        }

        $query = DB::table($table)
            ->whereDate($dateColumn, '>=', $dateFrom)
            ->whereDate($dateColumn, '<=', $dateTo);

        $latestReportDate = (clone $query)->max($dateColumn);

        return [
            'reports' => (int) (clone $query)->count(),
            'total_leads' => (int) $this->sumExistingColumn($query, $table, ['total_leads', 'leads', 'lead_count']),
            'interacted' => (int) $this->sumExistingColumn($query, $table, ['interacted', 'interaction', 'contacted']),
            'ignored' => (int) $this->sumExistingColumn($query, $table, ['ignored']),
            'closed_lost' => (int) $this->sumExistingColumn($query, $table, ['closed_lost']),
            'not_related' => (int) $this->sumExistingColumn($query, $table, ['not_related']),
            'warm_leads' => (int) $this->sumExistingColumn($query, $table, ['warm_leads', 'warm_lead', 'warm']),
            'hot_leads' => (int) $this->sumExistingColumn($query, $table, ['hot_leads', 'hot_lead', 'hot']),
            'consultation' => (int) $this->sumExistingColumn($query, $table, ['consultation', 'consulted', 'consultation_count']),
            'closed_deal' => (int) $this->sumExistingColumn($query, $table, ['closed_deal', 'closing', 'deal']),
            'revenue' => (float) $this->sumExistingColumn($query, $table, ['revenue', 'total_revenue', 'sales_revenue']),
            'latest_report_date' => $latestReportDate
                ? Carbon::parse($latestReportDate)->toDateString()
                : null,
        ];
    }

    protected function buildSalesInsight(
        array $currentReport,
        array $previousReport,
        array $currentPayments,
        array $previousPayments,
        string $dateFrom,
        string $dateTo,
        string $previousFrom,
        string $previousTo,
    ): array {
        $leads = max((int) ($currentReport['total_leads'] ?? 0), 0);
        $interacted = max((int) ($currentReport['interacted'] ?? 0), 0);
        $consultation = max((int) ($currentReport['consultation'] ?? 0), 0);
        $closedDeal = max((int) ($currentReport['closed_deal'] ?? 0), 0);
        $ignored = max((int) ($currentReport['ignored'] ?? 0), 0);
        $closedLost = max((int) ($currentReport['closed_lost'] ?? 0), 0);
        $notRelated = max((int) ($currentReport['not_related'] ?? 0), 0);
        $badLeadCount = $ignored + $closedLost + $notRelated;

        $paidPayments = max((int) ($currentPayments['payment_count'] ?? 0), 0);
        $paidOrders = max((int) ($currentPayments['order_count'] ?? 0), 0);
        $paid = $paidOrders > 0 ? $paidOrders : $paidPayments;

        $confirmedRevenue = (float) ($currentPayments['total'] ?? 0);
        $reportedRevenue = (float) ($currentReport['revenue'] ?? 0);

        $previousLeads = max((int) ($previousReport['total_leads'] ?? 0), 0);
        $previousClosedDeal = max((int) ($previousReport['closed_deal'] ?? 0), 0);
        $previousPaidPayments = max((int) ($previousPayments['payment_count'] ?? 0), 0);
        $previousPaidOrders = max((int) ($previousPayments['order_count'] ?? 0), 0);
        $previousPaid = $previousPaidOrders > 0 ? $previousPaidOrders : $previousPaidPayments;
        $previousConfirmedRevenue = (float) ($previousPayments['total'] ?? 0);

        $latestReportDate = $currentReport['latest_report_date'] ?? null;
        $daysSinceLatestReport = $latestReportDate
            ? Carbon::parse($latestReportDate)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period_label' => $this->formatPeriodLabel($dateFrom, $dateTo),

            'reports' => (int) ($currentReport['reports'] ?? 0),
            'latest_report_date' => $latestReportDate,
            'days_since_latest_report' => $daysSinceLatestReport,

            'leads' => $leads,
            'interacted' => $interacted,
            'warm_leads' => max((int) ($currentReport['warm_leads'] ?? 0), 0),
            'hot_leads' => max((int) ($currentReport['hot_leads'] ?? 0), 0),
            'consultation' => $consultation,
            'closing' => $closedDeal,
            'closed_deal' => $closedDeal,

            // Funnel paid memakai distinct order bila order_id tersedia.
            // Raw payment count tetap disediakan agar transparan.
            'paid' => $paid,
            'paid_order_count' => $paidOrders,
            'paid_payment_count' => $paidPayments,

            'ignored' => $ignored,
            'closed_lost' => $closedLost,
            'not_related' => $notRelated,
            'bad_lead_count' => $badLeadCount,

            // Revenue report adalah input sales; confirmed revenue berasal dari payments.
            'reported_revenue' => $reportedRevenue,
            'confirmed_revenue' => $confirmedRevenue,
            'revenue' => $confirmedRevenue,

            'interaction_rate' => $this->percentage($interacted, $leads),
            'consultation_rate' => $this->percentage($consultation, $leads),
            'closing_rate' => $this->percentage($closedDeal, $leads),
            'paid_rate' => $this->percentage($paid, $closedDeal),
            'lead_to_paid_rate' => $this->percentage($paid, $leads),
            'bad_lead_rate' => $this->percentage($badLeadCount, $leads),
            'revenue_per_deal' => $closedDeal > 0 ? round($confirmedRevenue / $closedDeal) : 0,
            'revenue_per_lead' => $leads > 0 ? round($confirmedRevenue / $leads) : 0,

            'previous' => [
                'date_from' => $previousFrom,
                'date_to' => $previousTo,
                'period_label' => $this->formatPeriodLabel($previousFrom, $previousTo),
                'leads' => $previousLeads,
                'interacted' => max((int) ($previousReport['interacted'] ?? 0), 0),
                'consultation' => max((int) ($previousReport['consultation'] ?? 0), 0),
                'closed_deal' => $previousClosedDeal,
                'paid' => $previousPaid,
                'reported_revenue' => (float) ($previousReport['revenue'] ?? 0),
                'confirmed_revenue' => $previousConfirmedRevenue,
            ],

            'changes' => [
                'leads' => $this->buildChange($leads, $previousLeads),
                'interacted' => $this->buildChange(
                    (int) ($currentReport['interacted'] ?? 0),
                    (int) ($previousReport['interacted'] ?? 0),
                ),
                'closed_deal' => $this->buildChange($closedDeal, $previousClosedDeal),
                'paid' => $this->buildChange($paid, $previousPaid),
                'confirmed_revenue' => $this->buildChange($confirmedRevenue, $previousConfirmedRevenue),
            ],
        ];
    }

    protected function getSalesPerformanceChart(string $dateFrom, string $dateTo): array
    {
        $labels = [];
        $datasets = [
            'total_leads' => [],
            'interacted' => [],
            'consultation' => [],
            'hot_leads' => [],
            'closed_deal' => [],
        ];

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $periodDays = max(1, $from->diffInDays($to) + 1);
        $granularity = $periodDays <= 62 ? 'daily' : 'monthly';

        $rows = collect();

        if (class_exists(SalesDailyReport::class)) {
            $table = (new SalesDailyReport())->getTable();

            if (Schema::hasTable($table)) {
                $dateColumn = $this->findExistingColumn($table, ['report_date', 'date', 'created_at']);

                if ($dateColumn) {
                    $groupExpression = $granularity === 'daily'
                        ? 'DATE(' . $this->wrapColumn($dateColumn) . ')'
                        : 'DATE_FORMAT(' . $this->wrapColumn($dateColumn) . ', "%Y-%m-01")';

                    $rows = DB::table($table)
                        ->selectRaw($groupExpression . ' as period_key')
                        ->selectRaw('SUM(' . $this->wrapExistingOrZero($table, ['total_leads', 'leads', 'lead_count']) . ') as total_leads')
                        ->selectRaw('SUM(' . $this->wrapExistingOrZero($table, ['interacted', 'interaction', 'contacted']) . ') as interacted')
                        ->selectRaw('SUM(' . $this->wrapExistingOrZero($table, ['consultation', 'consulted', 'consultation_count']) . ') as consultation')
                        ->selectRaw('SUM(' . $this->wrapExistingOrZero($table, ['hot_leads', 'hot_lead', 'hot']) . ') as hot_leads')
                        ->selectRaw('SUM(' . $this->wrapExistingOrZero($table, ['closed_deal', 'closing', 'deal']) . ') as closed_deal')
                        ->whereDate($dateColumn, '>=', $dateFrom)
                        ->whereDate($dateColumn, '<=', $dateTo)
                        ->groupByRaw($groupExpression)
                        ->orderByRaw($groupExpression)
                        ->get()
                        ->keyBy('period_key');
                }
            }
        }

        $periodKeys = $this->buildPeriodKeys($dateFrom, $dateTo, $granularity);

        foreach ($periodKeys as $key => $label) {
            $row = $rows->get($key);

            $labels[] = $label;
            $datasets['total_leads'][] = (int) ($row->total_leads ?? 0);
            $datasets['interacted'][] = (int) ($row->interacted ?? 0);
            $datasets['consultation'][] = (int) ($row->consultation ?? 0);
            $datasets['hot_leads'][] = (int) ($row->hot_leads ?? 0);
            $datasets['closed_deal'][] = (int) ($row->closed_deal ?? 0);
        }

        return [
            'granularity' => $granularity,
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => [
                'total_leads' => array_sum($datasets['total_leads']),
                'interacted' => array_sum($datasets['interacted']),
                'consultation' => array_sum($datasets['consultation']),
                'hot_leads' => array_sum($datasets['hot_leads']),
                'closed_deal' => array_sum($datasets['closed_deal']),
            ],
        ];
    }

    protected function getRevenueChart(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $periodDays = max(1, $from->diffInDays($to) + 1);
        $granularity = $periodDays <= 62 ? 'daily' : 'monthly';

        $labels = [];
        $confirmedData = [];
        $reportedData = [];

        $paymentRows = collect();
        $reportRows = collect();

        $paymentsTable = $this->findExistingTable(['payments']);

        if ($paymentsTable) {
            $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
            $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
            $dateExpression = $this->buildPaymentDateExpression($paymentsTable);

            if ($amountColumn && $dateExpression) {
                $groupExpression = $granularity === 'daily'
                    ? 'DATE(' . $dateExpression . ')'
                    : 'DATE_FORMAT(' . $dateExpression . ', "%Y-%m-01")';

                $query = DB::table($paymentsTable)
                    ->selectRaw($groupExpression . ' as period_key')
                    ->selectRaw('SUM(' . $this->wrapColumn($paymentsTable . '.' . $amountColumn) . ') as total_amount')
                    ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
                    ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

                if ($statusColumn) {
                    $query->whereIn($paymentsTable . '.' . $statusColumn, $this->getPaidPaymentStatuses());
                }

                $paymentRows = $query
                    ->groupByRaw($groupExpression)
                    ->orderByRaw($groupExpression)
                    ->get()
                    ->keyBy('period_key');
            }
        }

        if (class_exists(SalesDailyReport::class)) {
            $table = (new SalesDailyReport())->getTable();

            if (Schema::hasTable($table)) {
                $dateColumn = $this->findExistingColumn($table, ['report_date', 'date', 'created_at']);
                $revenueColumn = $this->findExistingColumn($table, ['revenue', 'total_revenue', 'sales_revenue']);

                if ($dateColumn && $revenueColumn) {
                    $groupExpression = $granularity === 'daily'
                        ? 'DATE(' . $this->wrapColumn($dateColumn) . ')'
                        : 'DATE_FORMAT(' . $this->wrapColumn($dateColumn) . ', "%Y-%m-01")';

                    $reportRows = DB::table($table)
                        ->selectRaw($groupExpression . ' as period_key')
                        ->selectRaw('SUM(' . $this->wrapColumn($revenueColumn) . ') as total_amount')
                        ->whereDate($dateColumn, '>=', $dateFrom)
                        ->whereDate($dateColumn, '<=', $dateTo)
                        ->groupByRaw($groupExpression)
                        ->orderByRaw($groupExpression)
                        ->get()
                        ->keyBy('period_key');
                }
            }
        }

        $periodKeys = $this->buildPeriodKeys($dateFrom, $dateTo, $granularity);

        foreach ($periodKeys as $key => $label) {
            $labels[] = $label;
            $confirmedData[] = (float) ($paymentRows->get($key)?->total_amount ?? 0);
            $reportedData[] = (float) ($reportRows->get($key)?->total_amount ?? 0);
        }

        return [
            'granularity' => $granularity,
            'labels' => $labels,
            'datasets' => [
                'confirmed_revenue' => $confirmedData,
                'reported_revenue' => $reportedData,
            ],
            'total_confirmed_revenue' => array_sum($confirmedData),
            'total_reported_revenue' => array_sum($reportedData),
        ];
    }

    protected function getKommoTodayLeadInsight(): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = now($timezone)->toDateString();

        $empty = [
            'date' => $today,
            'timezone' => $timezone,
            'source' => 'kommo',
            'is_available' => false,
            'error_message' => null,
            'total_leads' => 0,
            'incoming_leads' => 0,
            'lead_masuk' => 0,
            'followed_up' => 0,
            'processed_leads' => 0,
            'not_followed_up' => 0,
            'pending_incoming_leads' => 0,
            'need_action' => 0,
            'needs_attention' => 0,
            'follow_up_rate' => 0,
            'regular_incoming_leads' => 0,
            'unsorted_total' => 0,
            'unsorted_accepted' => 0,
            'unsorted_declined' => 0,
            'unsorted_pending' => 0,
            'unsorted_average_sort_time' => 0,
            'unsorted_forms_total' => 0,
            'unsorted_chats_total' => 0,
            'initial_contact' => 0,
            'new_leads' => 0,
            'interacted' => 0,
            'ignored' => 0,
            'closed_lost' => 0,
            'not_related' => 0,
            'warm_leads' => 0,
            'hot_leads' => 0,
            'consultation' => 0,
            'register' => 0,
            'data_storage' => 0,
            'paid' => 0,
            'trial_class' => 0,
            'wa_first_bubble' => 0,
            'filtered_out' => 0,
            'status_breakdown' => [],
            'pipeline_id' => config('services.kommo.pipeline_id'),
            'start_timestamp' => null,
            'end_timestamp' => null,
            'summary_text' => 'Data Kommo hari ini belum tersedia.',
            'last_synced_at' => null,
        ];

        try {
            $summary = $this->kommoService->getDailyLeadSummary(
                date: $today,
                timezone: $timezone,
            );

            $totalLeads = max((int) ($summary['total_leads'] ?? 0), 0);
            $incomingLeads = max((int) ($summary['incoming_leads'] ?? $summary['lead_masuk'] ?? 0), 0);
            $leadMasuk = max((int) ($summary['lead_masuk'] ?? $summary['incoming_leads'] ?? 0), 0);

            $data = [
                'incoming_leads' => $incomingLeads,
                'initial_contact' => max((int) ($summary['initial_contact'] ?? 0), 0),
                'new_leads' => max((int) ($summary['new_leads'] ?? 0), 0),
                'interacted' => max((int) ($summary['interacted'] ?? 0), 0),
                'warm_leads' => max((int) ($summary['warm_leads'] ?? 0), 0),
                'hot_leads' => max((int) ($summary['hot_leads'] ?? 0), 0),
                'trial_class' => max((int) ($summary['trial_class'] ?? 0), 0),
                'wa_first_bubble' => max((int) ($summary['wa_first_bubble'] ?? 0), 0),
                'consultation' => max((int) ($summary['consultation'] ?? 0), 0),
                'register' => max((int) ($summary['register'] ?? 0), 0),
                'data_storage' => max((int) ($summary['data_storage'] ?? 0), 0),
                'ignored' => max((int) ($summary['ignored'] ?? 0), 0),
                'closed_lost' => max((int) ($summary['closed_lost'] ?? 0), 0),
                'not_related' => max((int) ($summary['not_related'] ?? 0), 0),
                'paid' => max((int) ($summary['paid'] ?? 0), 0),
            ];

            $notFollowedUp = min($incomingLeads, $totalLeads);
            $followedUp = max($totalLeads - $notFollowedUp, 0);
            $followUpRate = $this->percentage($followedUp, $totalLeads, 0);
            $filteredOut = array_key_exists('filtered_out', $summary)
                ? max((int) $summary['filtered_out'], 0)
                : ($data['ignored'] + $data['closed_lost'] + $data['not_related']);

            $summaryText = match (true) {
                $totalLeads <= 0 => 'Belum ada lead baru dari Kommo hari ini.',
                $notFollowedUp > 0 => 'Kommo mencatat ' . number_format($totalLeads) . ' lead hari ini. ' . number_format($followedUp) . ' lead sudah diproses dan ' . number_format($notFollowedUp) . ' lead masih berada di Incoming Leads.',
                default => 'Semua ' . number_format($totalLeads) . ' lead Kommo hari ini sudah keluar dari Incoming Leads.',
            };

            return array_merge($empty, $data, [
                'is_available' => true,
                'error_message' => null,
                'total_leads' => $totalLeads,
                'incoming_leads' => $incomingLeads,
                'lead_masuk' => $leadMasuk,
                'regular_incoming_leads' => max((int) ($summary['regular_incoming_leads'] ?? 0), 0),
                'unsorted_total' => max((int) ($summary['unsorted_total'] ?? 0), 0),
                'unsorted_accepted' => max((int) ($summary['unsorted_accepted'] ?? 0), 0),
                'unsorted_declined' => max((int) ($summary['unsorted_declined'] ?? 0), 0),
                'unsorted_pending' => max((int) ($summary['unsorted_pending'] ?? 0), 0),
                'unsorted_average_sort_time' => max((int) ($summary['unsorted_average_sort_time'] ?? 0), 0),
                'unsorted_forms_total' => max((int) ($summary['unsorted_forms_total'] ?? 0), 0),
                'unsorted_chats_total' => max((int) ($summary['unsorted_chats_total'] ?? 0), 0),
                'filtered_out' => $filteredOut,
                'followed_up' => $followedUp,
                'processed_leads' => $followedUp,
                'not_followed_up' => $notFollowedUp,
                'pending_incoming_leads' => $notFollowedUp,
                'follow_up_rate' => $followUpRate,
                'needs_attention' => $notFollowedUp,
                'need_action' => $notFollowedUp,
                'status_breakdown' => $this->buildKommoLeadStatusBreakdown($data),
                'pipeline_id' => $summary['pipeline_id'] ?? config('services.kommo.pipeline_id'),
                'start_timestamp' => $summary['start_timestamp'] ?? null,
                'end_timestamp' => $summary['end_timestamp'] ?? null,
                'summary_text' => $summaryText,
                'last_synced_at' => now($timezone)->format('d M Y H:i'),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Kommo today lead insight for Sales Dashboard.', [
                'date' => $today,
                'message' => $exception->getMessage(),
            ]);

            return array_merge($empty, [
                'error_message' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : 'Data Kommo belum bisa ditarik.',
                'summary_text' => 'Data lead Kommo hari ini belum bisa ditarik. Cek koneksi atau konfigurasi Kommo.',
                'last_synced_at' => now($timezone)->format('d M Y H:i'),
            ]);
        }
    }

    protected function buildKommoLeadStatusBreakdown(array $data): array
    {
        return [
            $this->kommoStatusItem('incoming_leads', 'Incoming Leads', $data['incoming_leads'] ?? 0, 'Lead baru yang masih berada di Incoming Leads dan perlu dicek sales.', true),
            $this->kommoStatusItem('initial_contact', 'Initial Contact', $data['initial_contact'] ?? 0, 'Lead sudah masuk tahap kontak awal.'),
            $this->kommoStatusItem('new_leads', 'New Leads', $data['new_leads'] ?? 0, 'Lead baru yang sudah tercatat di pipeline Kommo.'),
            $this->kommoStatusItem('interacted', 'Interacted', $data['interacted'] ?? 0, 'Lead sudah memiliki interaksi awal.'),
            $this->kommoStatusItem('warm_leads', 'Warm Leads', $data['warm_leads'] ?? 0, 'Lead mulai menunjukkan minat.'),
            $this->kommoStatusItem('hot_leads', 'Hot Leads', $data['hot_leads'] ?? 0, 'Lead prioritas tinggi untuk dikejar closing.'),
            $this->kommoStatusItem('trial_class', 'Trial Class', $data['trial_class'] ?? 0, 'Lead sudah diarahkan ke trial class.'),
            $this->kommoStatusItem('wa_first_bubble', 'WA First Bubble', $data['wa_first_bubble'] ?? 0, 'Lead sudah masuk interaksi awal WhatsApp.'),
            $this->kommoStatusItem('consultation', 'Consultation', $data['consultation'] ?? 0, 'Lead sudah masuk tahap konsultasi.'),
            $this->kommoStatusItem('register', 'Register', $data['register'] ?? 0, 'Lead sudah masuk tahap registrasi.'),
            $this->kommoStatusItem('data_storage', 'Data Storage', $data['data_storage'] ?? 0, 'Lead disimpan untuk referensi atau follow-up lanjutan.'),
            $this->kommoStatusItem('ignored', 'Ignored', $data['ignored'] ?? 0, 'Lead sudah dicek tetapi tidak dilanjutkan untuk saat ini.', false, true),
            $this->kommoStatusItem('closed_lost', 'Closed Lost', $data['closed_lost'] ?? 0, 'Lead sudah diproses tetapi tidak berhasil closing.', false, true),
            $this->kommoStatusItem('not_related', 'Not Related', $data['not_related'] ?? 0, 'Lead sudah dicek dan dinilai tidak relevan.', false, true),
            $this->kommoStatusItem('paid', 'Paid', $data['paid'] ?? 0, 'Lead sudah berhasil menjadi pembayaran.'),
        ];
    }

    protected function kommoStatusItem(
        string $key,
        string $label,
        int $total,
        string $description,
        bool $isNeedAction = false,
        bool $isFilteredOut = false,
    ): array {
        $category = match (true) {
            $isNeedAction => 'Need Action',
            $isFilteredOut => 'Filtered Leads',
            in_array($key, ['warm_leads', 'hot_leads', 'trial_class', 'consultation', 'register'], true) => 'Sales Process',
            $key === 'paid' => 'Converted',
            default => 'Followed Up',
        };

        return [
            'key' => $key,
            'status' => $label,
            'label' => $label,
            'total' => max($total, 0),
            'category' => $category,
            'badge_class' => match ($category) {
                'Need Action' => 'bg-warning-subtle text-warning',
                'Filtered Leads' => 'bg-secondary-subtle text-secondary',
                'Sales Process' => 'bg-primary-subtle text-primary',
                'Converted' => 'bg-success-subtle text-success',
                default => 'bg-success-subtle text-success',
            },
            'description' => $description,
            'is_need_action' => $isNeedAction,
            'is_filtered_out' => $isFilteredOut,
        ];
    }

    protected function getTrialStats(string $dateFrom, string $dateTo): array
    {
        $themeTable = class_exists(TrialTheme::class) ? (new TrialTheme())->getTable() : null;
        $scheduleTable = class_exists(TrialSchedule::class) ? (new TrialSchedule())->getTable() : null;
        $participantTable = class_exists(TrialParticipant::class) ? (new TrialParticipant())->getTable() : null;

        $themesTotal = ($themeTable && Schema::hasTable($themeTable))
            ? (int) DB::table($themeTable)->count()
            : 0;

        $themesActive = 0;

        if ($themeTable && Schema::hasTable($themeTable)) {
            $query = DB::table($themeTable);

            if (Schema::hasColumn($themeTable, 'is_active')) {
                $query->where('is_active', true);
            }

            $themesActive = (int) $query->count();
        }

        $schedulesTotal = 0;
        $schedulesActive = 0;

        if ($scheduleTable && Schema::hasTable($scheduleTable)) {
            $dateColumn = $this->findExistingColumn($scheduleTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $activeColumn = $this->findExistingColumn($scheduleTable, ['is_active', 'status']);

            $query = DB::table($scheduleTable);

            if ($dateColumn) {
                $query
                    ->whereDate($dateColumn, '>=', $dateFrom)
                    ->whereDate($dateColumn, '<=', $dateTo);
            }

            $schedulesTotal = (int) (clone $query)->count();

            if ($activeColumn === 'is_active') {
                $query->where('is_active', true);
            } elseif ($activeColumn === 'status') {
                $query->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }

            $schedulesActive = (int) $query->count();
        }

        $participantsTotal = 0;

        if ($participantTable && Schema::hasTable($participantTable)) {
            $dateColumn = $this->findExistingColumn($participantTable, ['registered_at', 'created_at']);
            $query = DB::table($participantTable);

            if ($dateColumn) {
                $query
                    ->whereDate($dateColumn, '>=', $dateFrom)
                    ->whereDate($dateColumn, '<=', $dateTo);
            }

            $participantsTotal = (int) $query->count();
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'themes_total' => $themesTotal,
            'themes_active' => $themesActive,
            'schedules_total' => $schedulesTotal,
            'schedules_active' => $schedulesActive,
            'participants_total' => $participantsTotal,
            'participants_this_period' => $participantsTotal,
        ];
    }

    protected function getTrialParticipantStatusCounts(string $dateFrom, string $dateTo): Collection
    {
        $defaults = collect([
            'registered' => 0,
            'contacted' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ]);

        if (! class_exists(TrialParticipant::class)) {
            return $defaults;
        }

        $table = (new TrialParticipant())->getTable();

        if (! Schema::hasTable($table)) {
            return $defaults;
        }

        $dateColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        $statusColumn = $this->findExistingColumn($table, ['status']);

        if (! $statusColumn) {
            return $defaults;
        }

        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', $dateFrom)
                ->whereDate($dateColumn, '<=', $dateTo);
        }

        return $defaults->merge(
            $query
                ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
                ->groupBy($statusColumn)
                ->pluck('total', 'status')
                ->map(fn ($value) => (int) $value),
        );
    }

    protected function getTrialFollowUpProgress(string $dateFrom, string $dateTo): int
    {
        if (! class_exists(TrialParticipant::class)) {
            return 0;
        }

        $table = (new TrialParticipant())->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $dateColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        $statusColumn = $this->findExistingColumn($table, ['status']);

        if (! $statusColumn) {
            return 0;
        }

        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', $dateFrom)
                ->whereDate($dateColumn, '<=', $dateTo);
        }

        $total = (int) (clone $query)->count();
        $followedUp = (int) (clone $query)
            ->whereIn($statusColumn, ['contacted', 'confirmed', 'attended'])
            ->count();

        return (int) $this->percentage($followedUp, $total, 0);
    }

    protected function getUpcomingTrialSchedules(): Collection
    {
        if (! class_exists(TrialSchedule::class)) {
            return collect();
        }

        $table = (new TrialSchedule())->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'schedule_date')) {
            return collect();
        }

        return TrialSchedule::query()
            ->with([
                'trialTheme:id,name',
                'program:id,name',
            ])
            ->when(
                Schema::hasColumn($table, 'is_active'),
                fn ($query) => $query->where('is_active', true),
            )
            ->whereDate('schedule_date', '>=', now()->toDateString())
            ->orderBy('schedule_date')
            ->when(Schema::hasColumn($table, 'start_time'), fn ($query) => $query->orderBy('start_time'))
            ->limit(5)
            ->get();
    }

    protected function getWorkshopStats(string $dateFrom, string $dateTo): array
    {
        $workshopsTable = $this->findExistingTable(['workshops']);
        $schedulesTable = $this->findExistingTable(['workshop_schedules']);
        $participantsTable = $this->findExistingTable(['workshop_participants']);

        $statusCounts = $this->getWorkshopParticipantStatusCounts($dateFrom, $dateTo);
        $paymentSummary = $this->getWorkshopPaidPaymentSummary($dateFrom, $dateTo);

        $stats = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'workshops_total' => 0,
            'workshops_active' => 0,
            'schedules_total' => 0,
            'schedules_active' => 0,
            'upcoming_schedules' => 0,
            'participants_total' => 0,
            'registered' => (int) ($statusCounts['registered'] ?? 0),
            'pending_payment' => (int) ($statusCounts['pending_payment'] ?? 0),
            'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
            'attended' => (int) ($statusCounts['attended'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            'paid_count' => (int) ($paymentSummary['count'] ?? 0),
            'revenue' => (float) ($paymentSummary['total'] ?? 0),
            'conversion_percent' => 0,
            'attendance_percent' => 0,
            'top_source' => null,
            'top_source_total' => 0,
        ];

        if ($workshopsTable) {
            $activeColumn = $this->findExistingColumn($workshopsTable, ['is_active', 'status']);
            $stats['workshops_total'] = (int) DB::table($workshopsTable)->count();

            $activeQuery = DB::table($workshopsTable);

            if ($activeColumn === 'is_active') {
                $activeQuery->where('is_active', true);
            } elseif ($activeColumn === 'status') {
                $activeQuery->whereIn('status', ['active', 'open', 'published']);
            }

            $stats['workshops_active'] = (int) $activeQuery->count();
        }

        if ($schedulesTable) {
            $dateColumn = $this->findExistingColumn($schedulesTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $activeColumn = $this->findExistingColumn($schedulesTable, ['is_active', 'status']);

            $periodQuery = DB::table($schedulesTable);

            if ($dateColumn) {
                $periodQuery
                    ->whereDate($dateColumn, '>=', $dateFrom)
                    ->whereDate($dateColumn, '<=', $dateTo);
            }

            $stats['schedules_total'] = (int) (clone $periodQuery)->count();

            if ($activeColumn === 'is_active') {
                $periodQuery->where('is_active', true);
            } elseif ($activeColumn === 'status') {
                $periodQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }

            $stats['schedules_active'] = (int) $periodQuery->count();

            if ($dateColumn) {
                $upcomingQuery = DB::table($schedulesTable)
                    ->whereDate($dateColumn, '>=', now()->toDateString());

                if ($activeColumn === 'is_active') {
                    $upcomingQuery->where('is_active', true);
                } elseif ($activeColumn === 'status') {
                    $upcomingQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
                }

                $stats['upcoming_schedules'] = (int) $upcomingQuery->count();
            }
        }

        if ($participantsTable) {
            $dateColumn = $this->findExistingColumn($participantsTable, ['registered_at', 'created_at']);
            $sourceColumn = $this->findExistingColumn($participantsTable, ['utm_source', 'input_source']);

            $query = DB::table($participantsTable);

            if ($dateColumn) {
                $query
                    ->whereDate($dateColumn, '>=', $dateFrom)
                    ->whereDate($dateColumn, '<=', $dateTo);
            }

            $stats['participants_total'] = (int) (clone $query)->count();

            if ($sourceColumn) {
                $source = (clone $query)
                    ->selectRaw('COALESCE(NULLIF(' . $this->wrapColumn($sourceColumn) . ', ""), "unknown") as source_name, COUNT(*) as total')
                    ->groupByRaw('COALESCE(NULLIF(' . $this->wrapColumn($sourceColumn) . ', ""), "unknown")')
                    ->orderByDesc('total')
                    ->first();

                if ($source) {
                    $stats['top_source'] = $source->source_name;
                    $stats['top_source_total'] = (int) $source->total;
                }
            }
        }

        $participants = max((int) $stats['participants_total'], 0);
        $converted = (int) $stats['confirmed'] + (int) $stats['attended'];

        $stats['conversion_percent'] = (int) $this->percentage($converted, $participants, 0);
        $stats['attendance_percent'] = (int) $this->percentage((int) $stats['attended'], $participants, 0);

        return $stats;
    }

    protected function getWorkshopParticipantStatusCounts(string $dateFrom, string $dateTo): Collection
    {
        $defaults = collect([
            'registered' => 0,
            'pending_payment' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
        ]);

        $table = $this->findExistingTable(['workshop_participants']);

        if (! $table) {
            return $defaults;
        }

        $dateColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        $statusColumn = $this->findExistingColumn($table, ['status']);

        if (! $statusColumn) {
            return $defaults;
        }

        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', $dateFrom)
                ->whereDate($dateColumn, '<=', $dateTo);
        }

        return $defaults->merge(
            $query
                ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
                ->groupBy($statusColumn)
                ->pluck('total', 'status')
                ->map(fn ($value) => (int) $value),
        );
    }

    protected function getWorkshopFollowUpProgress(string $dateFrom, string $dateTo): int
    {
        $table = $this->findExistingTable(['workshop_participants']);

        if (! $table) {
            return 0;
        }

        $dateColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        $statusColumn = $this->findExistingColumn($table, ['status']);

        if (! $statusColumn) {
            return 0;
        }

        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', $dateFrom)
                ->whereDate($dateColumn, '<=', $dateTo);
        }

        $total = (int) (clone $query)->count();
        $converted = (int) (clone $query)
            ->whereIn($statusColumn, ['confirmed', 'attended'])
            ->count();

        return (int) $this->percentage($converted, $total, 0);
    }

    protected function getUpcomingWorkshopSchedules(): Collection
    {
        $table = $this->findExistingTable(['workshop_schedules']);

        if (! $table) {
            return collect();
        }

        $nameColumn = $this->findExistingColumn($table, ['title', 'name']);
        $dateColumn = $this->findExistingColumn($table, ['schedule_date', 'date', 'start_date']);
        $startTimeColumn = $this->findExistingColumn($table, ['start_time']);
        $endTimeColumn = $this->findExistingColumn($table, ['end_time']);
        $quotaColumn = $this->findExistingColumn($table, ['quota', 'capacity']);
        $registeredColumn = $this->findExistingColumn($table, ['registered_count']);
        $statusColumn = $this->findExistingColumn($table, ['status']);
        $activeColumn = $this->findExistingColumn($table, ['is_active']);
        $workshopIdColumn = $this->findExistingColumn($table, ['workshop_id']);

        if (! $dateColumn) {
            return collect();
        }

        $query = DB::table($table)
            ->select([
                $table . '.id',
                DB::raw(($nameColumn ? $table . '.' . $nameColumn : '"Workshop Schedule"') . ' as title'),
                DB::raw($table . '.' . $dateColumn . ' as schedule_date'),
                DB::raw(($startTimeColumn ? $table . '.' . $startTimeColumn : 'NULL') . ' as start_time'),
                DB::raw(($endTimeColumn ? $table . '.' . $endTimeColumn : 'NULL') . ' as end_time'),
                DB::raw(($quotaColumn ? $table . '.' . $quotaColumn : '0') . ' as quota'),
                DB::raw(($registeredColumn ? $table . '.' . $registeredColumn : '0') . ' as registered_count'),
            ])
            ->whereDate($table . '.' . $dateColumn, '>=', now()->toDateString());

        if ($activeColumn) {
            $query->where($table . '.' . $activeColumn, true);
        }

        if ($statusColumn) {
            $query->whereIn($table . '.' . $statusColumn, ['open', 'scheduled', 'active']);
        }

        if ($workshopIdColumn && Schema::hasTable('workshops')) {
            $query->leftJoin('workshops', 'workshops.id', '=', $table . '.' . $workshopIdColumn);
            $workshopTitleColumn = $this->findExistingColumn('workshops', ['title', 'name']);

            if ($workshopTitleColumn) {
                $query->addSelect(DB::raw('workshops.' . $workshopTitleColumn . ' as workshop_title'));
            }
        }

        return $query
            ->orderBy($table . '.' . $dateColumn)
            ->when($startTimeColumn, fn ($builder) => $builder->orderBy($table . '.' . $startTimeColumn))
            ->limit(5)
            ->get();
    }

    protected function getFinanceInsight(string $dateFrom, string $dateTo): array
    {
        $current = $this->getPaidPaymentSummary($dateFrom, $dateTo);
        [$previousFrom, $previousTo] = $this->resolvePreviousPeriod($dateFrom, $dateTo);
        $previous = $this->getPaidPaymentSummary($previousFrom, $previousTo);

        $today = now()->toDateString();
        $todaySummary = $this->getPaidPaymentSummary($today, $today);

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $thisMonth = $this->getPaidPaymentSummary($monthStart, $monthEnd);

        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $lastMonth = $this->getPaidPaymentSummary($lastMonthStart, $lastMonthEnd);

        $lastPayment = $this->getLastPaidPayment();
        $lastPaymentDate = $lastPayment['date'] ?? null;

        $pending = $this->getPaymentStatusSummary(['pending']);
        $expired = $this->getPaymentStatusSummary(['expired']);
        $overdue = $this->getOverduePaymentScheduleSummary();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period_label' => $this->formatPeriodLabel($dateFrom, $dateTo),

            'revenue_selected_period' => (float) ($current['total'] ?? 0),
            'paid_payment_count_selected_period' => (int) ($current['payment_count'] ?? 0),
            'paid_order_count_selected_period' => (int) ($current['order_count'] ?? 0),

            'revenue_previous_period' => (float) ($previous['total'] ?? 0),
            'paid_payment_count_previous_period' => (int) ($previous['payment_count'] ?? 0),
            'paid_order_count_previous_period' => (int) ($previous['order_count'] ?? 0),

            'revenue_period_diff' => (float) ($current['total'] ?? 0) - (float) ($previous['total'] ?? 0),
            'revenue_period_growth_percent' => $this->growthPercentage(
                (float) ($current['total'] ?? 0),
                (float) ($previous['total'] ?? 0),
            ),

            'revenue_today' => (float) ($todaySummary['total'] ?? 0),
            'paid_count_today' => (int) ($todaySummary['payment_count'] ?? 0),

            'revenue_this_month' => (float) ($thisMonth['total'] ?? 0),
            'paid_count_this_month' => (int) ($thisMonth['payment_count'] ?? 0),
            'revenue_last_month' => (float) ($lastMonth['total'] ?? 0),
            'paid_count_last_month' => (int) ($lastMonth['payment_count'] ?? 0),
            'revenue_month_growth_percent' => $this->growthPercentage(
                (float) ($thisMonth['total'] ?? 0),
                (float) ($lastMonth['total'] ?? 0),
            ),

            'last_payment_date' => $lastPaymentDate,
            'last_payment_amount' => (float) ($lastPayment['amount'] ?? 0),
            'days_since_last_payment' => $lastPaymentDate
                ? Carbon::parse($lastPaymentDate)->startOfDay()->diffInDays(now()->startOfDay())
                : null,

            'pending_payment_count' => (int) ($pending['count'] ?? 0),
            'pending_payment_total' => (float) ($pending['total'] ?? 0),
            'expired_payment_count' => (int) ($expired['count'] ?? 0),
            'expired_payment_total' => (float) ($expired['total'] ?? 0),
            'overdue_schedule_count' => (int) ($overdue['count'] ?? 0),
            'overdue_schedule_total' => (float) ($overdue['total'] ?? 0),
        ];
    }

    protected function getOrderInsight(string $dateFrom, string $dateTo): array
    {
        $table = $this->findExistingTable(['orders']);

        $empty = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'orders_selected_period' => 0,
            'orders_total' => 0,
            'pending_orders' => 0,
            'partial_orders' => 0,
            'paid_orders' => 0,
            'cancelled_orders' => 0,
            'pending_order_value' => 0,
            'partial_order_value' => 0,
            'paid_order_value' => 0,
            'potential_revenue' => 0,
            'program_orders_selected_period' => 0,
            'workshop_orders_selected_period' => 0,
        ];

        if (! $table) {
            return $empty;
        }

        $statusColumn = $this->findExistingColumn($table, ['status']);
        $amountColumn = $this->findExistingColumn($table, ['final_price', 'total_amount', 'amount']);
        $dateColumn = $this->findExistingColumn($table, ['created_at', 'order_date']);
        $typeColumn = $this->findExistingColumn($table, ['order_type']);

        $periodQuery = DB::table($table);

        if ($dateColumn) {
            $periodQuery
                ->whereDate($dateColumn, '>=', $dateFrom)
                ->whereDate($dateColumn, '<=', $dateTo);
        }

        $summary = $empty;
        $summary['orders_selected_period'] = (int) (clone $periodQuery)->count();
        $summary['orders_total'] = (int) DB::table($table)->count();

        if ($statusColumn) {
            foreach (['pending', 'partial', 'paid', 'cancelled'] as $status) {
                $statusQuery = DB::table($table)->where($statusColumn, $status);
                $summary[$status . '_orders'] = (int) (clone $statusQuery)->count();

                if ($amountColumn && in_array($status, ['pending', 'partial', 'paid'], true)) {
                    $summary[$status . '_order_value'] = (float) (clone $statusQuery)->sum($amountColumn);
                }
            }
        }

        if ($typeColumn) {
            $summary['program_orders_selected_period'] = (int) (clone $periodQuery)
                ->whereIn($typeColumn, ['program', 'batch', 'course'])
                ->count();

            $summary['workshop_orders_selected_period'] = (int) (clone $periodQuery)
                ->where($typeColumn, 'workshop')
                ->count();
        }

        $summary['potential_revenue'] = (float) $summary['pending_order_value'] + (float) $summary['partial_order_value'];

        return $summary;
    }

    protected function getBatchCapacitySummary(): array
    {
        $batchesTable = $this->findExistingTable(['batches']);

        if (! $batchesTable) {
            return [
                'total_capacity' => 0,
                'filled_seats' => 0,
                'remaining_seats' => 0,
                'utilization_percent' => 0,
            ];
        }

        $capacityColumn = $this->findExistingColumn($batchesTable, [
            'capacity',
            'seat_capacity',
            'quota',
            'max_students',
            'max_seats',
            'total_seats',
        ]);
        $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

        $query = DB::table($batchesTable);

        if ($activeColumn === 'is_active') {
            $query->where('is_active', true);
        } elseif ($activeColumn === 'status') {
            $query->whereIn('status', $this->getActiveBatchStatuses());
        }

        $totalCapacity = $capacityColumn
            ? (int) (clone $query)->sum($capacityColumn)
            : 0;

        $filledSeats = $this->getFilledSeatCount(true);
        $remainingSeats = max($totalCapacity - $filledSeats, 0);

        return [
            'total_capacity' => $totalCapacity,
            'filled_seats' => $filledSeats,
            'remaining_seats' => $remainingSeats,
            'utilization_percent' => (int) $this->percentage($filledSeats, $totalCapacity, 0),
        ];
    }

    protected function getUpcomingBatches(): Collection
    {
        $table = $this->findExistingTable(['batches']);

        if (! $table) {
            return collect();
        }

        $nameColumn = $this->findExistingColumn($table, ['name', 'title']);
        $startDateColumn = $this->findExistingColumn($table, ['start_date', 'start_at', 'batch_start_date']);
        $capacityColumn = $this->findExistingColumn($table, [
            'capacity',
            'seat_capacity',
            'quota',
            'max_students',
            'max_seats',
            'total_seats',
        ]);
        $activeColumn = $this->findExistingColumn($table, ['is_active', 'status']);
        $programIdColumn = $this->findExistingColumn($table, ['program_id']);

        if (! $nameColumn || ! $startDateColumn) {
            return collect();
        }

        $query = DB::table($table)
            ->select([
                $table . '.id',
                DB::raw($table . '.' . $nameColumn . ' as name'),
                DB::raw($table . '.' . $startDateColumn . ' as start_date'),
                DB::raw(($capacityColumn ? $table . '.' . $capacityColumn : '0') . ' as capacity'),
            ])
            ->whereDate($table . '.' . $startDateColumn, '>=', now()->toDateString());

        if ($programIdColumn && Schema::hasTable('programs')) {
            $query->leftJoin('programs', 'programs.id', '=', $table . '.' . $programIdColumn);
            $programNameColumn = $this->findExistingColumn('programs', ['name', 'title']);

            if ($programNameColumn) {
                $query->addSelect(DB::raw('programs.' . $programNameColumn . ' as program_name'));
            }
        }

        if ($activeColumn === 'is_active') {
            $query->where($table . '.is_active', true);
        } elseif ($activeColumn === 'status') {
            $query->whereIn($table . '.status', $this->getActiveBatchStatuses());
        }

        $batches = $query
            ->orderBy($table . '.' . $startDateColumn)
            ->limit(5)
            ->get();

        $filledMap = $this->getFilledSeatMap($batches->pluck('id')->all());

        return $batches->map(function ($batch) use ($filledMap) {
            $batch->filled_seats = (int) ($filledMap[$batch->id] ?? 0);
            $batch->remaining_seats = max((int) $batch->capacity - (int) $batch->filled_seats, 0);
            $batch->utilization_percent = (int) $this->percentage(
                (int) $batch->filled_seats,
                (int) $batch->capacity,
                0,
            );

            return $batch;
        });
    }

    protected function buildSalesDashboardSummary(array $context): array
    {
        $sales = $context['sales_insight'] ?? [];
        $kommo = $context['kommo_today_lead_insight'] ?? [];
        $trialStats = $context['trial_stats'] ?? [];
        $trialStatus = collect($context['trial_status_counts'] ?? []);
        $trialProgress = (int) ($context['trial_follow_up_progress'] ?? 0);
        $workshop = $context['workshop_stats'] ?? [];
        $finance = $context['finance_insight'] ?? [];
        $orders = $context['order_insight'] ?? [];
        $batch = $context['batch_capacity'] ?? [];
        $upcomingBatches = collect($context['upcoming_batches'] ?? []);

        $items = [];

        $leads = (int) ($sales['leads'] ?? 0);
        $interacted = (int) ($sales['interacted'] ?? 0);
        $consultation = (int) ($sales['consultation'] ?? 0);
        $hotLeads = (int) ($sales['hot_leads'] ?? 0);
        $closedDeal = (int) ($sales['closed_deal'] ?? 0);
        $paid = (int) ($sales['paid'] ?? 0);
        $confirmedRevenue = (float) ($sales['confirmed_revenue'] ?? 0);
        $reportedRevenue = (float) ($sales['reported_revenue'] ?? 0);
        $daysSinceLatestReport = $sales['days_since_latest_report'] ?? null;

        if ((int) ($sales['reports'] ?? 0) <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Sales report belum tersedia',
                'Belum ada Sales Daily Report pada periode terpilih. Angka funnel belum bisa dibaca secara lengkap.',
                1000,
            );
        } elseif ($leads <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Leads belum masuk',
                'Sales report sudah tersedia, tetapi belum ada leads pada periode terpilih.',
                940,
            );
        } elseif ($interacted <= 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Leads belum diinteraksikan',
                number_format($leads) . ' leads sudah tercatat, tetapi belum ada interaksi. Kontak awal perlu dipercepat.',
                920,
            );
        } elseif ($closedDeal <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Funnel belum menghasilkan closed deal',
                'Sudah ada ' . number_format($interacted) . ' interaksi dan ' . number_format($consultation) . ' consultation, tetapi closed deal masih 0.',
                880,
            );
        } elseif ($paid <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Closed deal belum menjadi payment',
                number_format($closedDeal) . ' closed deal sudah tercatat, tetapi belum ada paid order/payment terkonfirmasi.',
                870,
            );
        } else {
            $items[] = $this->summaryItem(
                'good',
                'Sales funnel menghasilkan payment',
                'Periode ini menghasilkan ' . number_format($leads) . ' leads, ' . number_format($closedDeal) . ' closed deal, dan ' . number_format($paid) . ' paid order/payment.',
                700,
            );
        }

        $needAction = (int) ($kommo['need_action'] ?? 0);
        $kommoAvailable = (bool) ($kommo['is_available'] ?? false);

        if (! $kommoAvailable) {
            $items[] = $this->summaryItem(
                'warning',
                'Kommo belum tersinkron',
                (string) ($kommo['summary_text'] ?? 'Data Kommo belum tersedia.'),
                910,
            );
        } elseif ($needAction > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Incoming Leads perlu action',
                number_format($needAction) . ' lead Kommo hari ini masih berada di Incoming Leads dan perlu dicek sales.',
                900,
            );
        } elseif ((int) ($kommo['total_leads'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'good',
                'Lead Kommo hari ini sudah diproses',
                (string) ($kommo['summary_text'] ?? 'Semua lead hari ini sudah diproses.'),
                620,
            );
        }

        if ($hotLeads > 0 && $closedDeal <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Hot leads perlu dikonversi',
                number_format($hotLeads) . ' hot leads belum menghasilkan closed deal. Prioritaskan follow-up personal.',
                850,
            );
        }

        if ($consultation > 0 && $closedDeal <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Consultation belum menjadi closed deal',
                number_format($consultation) . ' consultation perlu dikawal dengan next step, deadline, dan offer yang jelas.',
                840,
            );
        }

        if ($confirmedRevenue <= 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Confirmed revenue belum masuk',
                'Belum ada payment paid/settled pada periode terpilih.',
                890,
            );
        } else {
            $items[] = $this->summaryItem(
                'good',
                'Confirmed revenue tercatat',
                'Payment terkonfirmasi pada periode ini mencapai ' . $this->formatCurrency($confirmedRevenue) . '.',
                650,
            );
        }

        if ($reportedRevenue > 0 && abs($reportedRevenue - $confirmedRevenue) > 1) {
            $items[] = $this->summaryItem(
                'info',
                'Reported dan confirmed revenue berbeda',
                'Sales report mencatat ' . $this->formatCurrency($reportedRevenue) . ', sementara payments terkonfirmasi mencatat ' . $this->formatCurrency($confirmedRevenue) . '. Perbedaan ini perlu dipahami sebagai status pencatatan, bukan otomatis error.',
                580,
            );
        }

        if ((int) ($finance['overdue_schedule_count'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Ada jadwal pembayaran overdue',
                number_format((int) $finance['overdue_schedule_count']) . ' jadwal pembayaran overdue dengan nilai sekitar ' . $this->formatCurrency((float) ($finance['overdue_schedule_total'] ?? 0)) . '.',
                930,
            );
        } elseif ((int) ($finance['pending_payment_count'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Payment pending perlu follow-up',
                number_format((int) $finance['pending_payment_count']) . ' payment masih pending dengan nilai ' . $this->formatCurrency((float) ($finance['pending_payment_total'] ?? 0)) . '.',
                820,
            );
        }

        $trialParticipants = (int) ($trialStats['participants_total'] ?? 0);
        $trialRegistered = (int) ($trialStatus['registered'] ?? 0);
        $trialAttended = (int) ($trialStatus['attended'] ?? 0);

        if ($trialParticipants > 0 && $trialProgress < 50) {
            $items[] = $this->summaryItem(
                'warning',
                'Follow-up trial masih rendah',
                number_format($trialParticipants) . ' peserta trial tercatat, tetapi follow-up progress baru ' . number_format($trialProgress) . '%.',
                780,
            );
        } elseif ($trialRegistered > 0 && $trialAttended <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Trial belum menghasilkan attendance',
                'Peserta trial sudah terdaftar, tetapi belum ada attended pada periode ini.',
                740,
            );
        }

        if ((int) ($workshop['pending_payment'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Workshop pending payment',
                number_format((int) $workshop['pending_payment']) . ' peserta workshop masih pending payment.',
                790,
            );
        } elseif ((float) ($workshop['revenue'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'good',
                'Workshop menghasilkan revenue',
                'Workshop menghasilkan payment terkonfirmasi sebesar ' . $this->formatCurrency((float) $workshop['revenue']) . '.',
                610,
            );
        }

        $remainingSeats = (int) ($batch['remaining_seats'] ?? 0);
        $utilization = (int) ($batch['utilization_percent'] ?? 0);

        if ($remainingSeats > 0 && $closedDeal <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Seat tersedia belum menghasilkan closed deal',
                'Masih ada ' . number_format($remainingSeats) . ' seat aktif. Sales perlu memprioritaskan program yang paling dekat start date.',
                770,
            );
        } elseif ($utilization >= 80) {
            $items[] = $this->summaryItem(
                'good',
                'Utilisasi batch sehat',
                'Utilisasi batch aktif sudah ' . number_format($utilization) . '%.',
                560,
            );
        }

        if ($upcomingBatches->isNotEmpty() && $remainingSeats > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Upcoming batch masih dapat dijual',
                'Ada ' . number_format($upcomingBatches->count()) . ' upcoming batch pada dashboard dengan seat yang masih tersedia.',
                500,
            );
        }

        if ($daysSinceLatestReport !== null && $daysSinceLatestReport >= 2) {
            $items[] = $this->summaryItem(
                'warning',
                'Sales report belum diperbarui',
                'Sales Daily Report terakhir diperbarui sekitar ' . number_format((int) $daysSinceLatestReport) . ' hari lalu.',
                860,
            );
        }

        if ((float) ($orders['potential_revenue'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Ada potential revenue dari order aktif',
                'Nilai pending dan partial order mencapai ' . $this->formatCurrency((float) $orders['potential_revenue']) . '.',
                540,
            );
        }

        if (empty($items)) {
            $items[] = $this->summaryItem(
                'info',
                'Sales Dashboard siap dipantau',
                'Data utama sales tersedia. Pantau lead follow-up, conversion, payment, dan seat secara berkala.',
                300,
            );
        }

        usort($items, function (array $a, array $b) {
            $scoreCompare = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return $this->severityWeight($b['type'] ?? 'info') <=> $this->severityWeight($a['type'] ?? 'info');
        });

        $focus = collect($items)
            ->filter(fn ($item) => in_array($item['type'] ?? null, ['critical', 'warning', 'action'], true))
            ->take(4)
            ->map(fn ($item) => [
                'type' => $item['type'],
                'level' => $item['type'],
                'title' => $item['title'],
                'message' => $item['message'],
                'description' => $item['message'],
            ])
            ->values()
            ->all();

        if (empty($focus)) {
            $focus = collect($items)
                ->take(3)
                ->map(fn ($item) => [
                    'type' => $item['type'],
                    'level' => $item['type'],
                    'title' => $item['title'],
                    'message' => $item['message'],
                    'description' => $item['message'],
                ])
                ->values()
                ->all();
        }

        $summaryText = collect($items)
            ->take(4)
            ->pluck('message')
            ->filter()
            ->unique()
            ->implode("\n\n");

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Sales Dashboard Insight',
            'mode' => 'local_smart',
            'headline' => $items[0]['title'] ?? 'Sales Dashboard Summary',
            'summary_text' => $summaryText,
            'items' => array_slice($items, 0, 8),
            'focus' => $focus,
        ];
    }

    protected function getPaidPaymentSummary(string $dateFrom, string $dateTo): array
    {
        $table = $this->findExistingTable(['payments']);

        if (! $table) {
            return [
                'payment_count' => 0,
                'order_count' => 0,
                'total' => 0,
            ];
        }

        $amountColumn = $this->findExistingColumn($table, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->findExistingColumn($table, ['status', 'payment_status']);
        $orderIdColumn = $this->findExistingColumn($table, ['order_id']);
        $dateExpression = $this->buildPaymentDateExpression($table);

        if (! $dateExpression) {
            return [
                'payment_count' => 0,
                'order_count' => 0,
                'total' => 0,
            ];
        }

        $query = DB::table($table)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        if ($statusColumn) {
            $query->whereIn($table . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        return [
            'payment_count' => (int) (clone $query)->count(),
            'order_count' => $orderIdColumn
                ? (int) (clone $query)->whereNotNull($table . '.' . $orderIdColumn)->distinct()->count($table . '.' . $orderIdColumn)
                : 0,
            'total' => $amountColumn
                ? (float) (clone $query)->sum($table . '.' . $amountColumn)
                : 0,
        ];
    }

    protected function getWorkshopPaidPaymentSummary(string $dateFrom, string $dateTo): array
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        $ordersTable = $this->findExistingTable(['orders']);

        if (! $paymentsTable) {
            return ['count' => 0, 'total' => 0];
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
        $orderIdColumn = $this->findExistingColumn($paymentsTable, ['order_id']);
        $workshopParticipantIdColumn = $this->findExistingColumn($paymentsTable, ['workshop_participant_id']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);

        if (! $amountColumn || ! $dateExpression) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($paymentsTable)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        if ($statusColumn) {
            $query->whereIn($paymentsTable . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        $workshopScopeApplied = false;

        if ($ordersTable && $orderIdColumn) {
            $orderTypeColumn = $this->findExistingColumn($ordersTable, ['order_type']);
            $workshopIdColumn = $this->findExistingColumn($ordersTable, ['workshop_id']);

            if ($orderTypeColumn || $workshopIdColumn) {
                $query->join($ordersTable, $ordersTable . '.id', '=', $paymentsTable . '.' . $orderIdColumn);

                if ($orderTypeColumn && $workshopIdColumn) {
                    $query->where(function ($builder) use ($ordersTable, $orderTypeColumn, $workshopIdColumn) {
                        $builder
                            ->where($ordersTable . '.' . $orderTypeColumn, 'workshop')
                            ->orWhereNotNull($ordersTable . '.' . $workshopIdColumn);
                    });
                } elseif ($orderTypeColumn) {
                    $query->where($ordersTable . '.' . $orderTypeColumn, 'workshop');
                } else {
                    $query->whereNotNull($ordersTable . '.' . $workshopIdColumn);
                }

                $workshopScopeApplied = true;
            }
        }

        if (! $workshopScopeApplied && $workshopParticipantIdColumn) {
            $query->whereNotNull($paymentsTable . '.' . $workshopParticipantIdColumn);
            $workshopScopeApplied = true;
        }

        /*
         * Jangan menghitung seluruh payments sebagai revenue workshop ketika tidak
         * ada relasi yang dapat membedakan workshop dari program reguler.
         */
        if (! $workshopScopeApplied) {
            return ['count' => 0, 'total' => 0];
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (float) (clone $query)->sum($paymentsTable . '.' . $amountColumn),
        ];
    }

    protected function getLastPaidPayment(): ?array
    {
        $table = $this->findExistingTable(['payments']);

        if (! $table) {
            return null;
        }

        $amountColumn = $this->findExistingColumn($table, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->findExistingColumn($table, ['status', 'payment_status']);
        $dateExpression = $this->buildPaymentDateExpression($table);

        if (! $amountColumn || ! $dateExpression) {
            return null;
        }

        $query = DB::table($table)
            ->selectRaw($dateExpression . ' as payment_effective_date')
            ->selectRaw($this->wrapColumn($table . '.' . $amountColumn) . ' as amount')
            ->whereNotNull($table . '.' . $amountColumn)
            ->orderByRaw($dateExpression . ' desc');

        if ($statusColumn) {
            $query->whereIn($table . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        $payment = $query->first();

        return $payment
            ? [
                'date' => $payment->payment_effective_date,
                'amount' => (float) $payment->amount,
            ]
            : null;
    }

    protected function getPaymentStatusSummary(array $statuses): array
    {
        $table = $this->findExistingTable(['payments']);

        if (! $table) {
            return ['count' => 0, 'total' => 0];
        }

        $statusColumn = $this->findExistingColumn($table, ['status', 'payment_status']);
        $amountColumn = $this->findExistingColumn($table, ['amount', 'paid_amount', 'total_amount']);

        if (! $statusColumn) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($table)->whereIn($statusColumn, $statuses);

        return [
            'count' => (int) (clone $query)->count(),
            'total' => $amountColumn ? (float) (clone $query)->sum($amountColumn) : 0,
        ];
    }

    protected function getOverduePaymentScheduleSummary(): array
    {
        $table = $this->findExistingTable(['payment_schedules']);

        if (! $table) {
            return ['count' => 0, 'total' => 0];
        }

        $idColumn = $this->findExistingColumn($table, ['id']);
        $dueDateColumn = $this->findExistingColumn($table, ['due_date', 'payment_due_date', 'schedule_date']);
        $amountColumn = $this->findExistingColumn($table, ['amount', 'total_amount', 'installment_amount']);
        $paidAmountColumn = $this->findExistingColumn($table, ['paid_amount', 'amount_paid']);
        $statusColumn = $this->findExistingColumn($table, ['status']);
        $paidAtColumn = $this->findExistingColumn($table, ['paid_at', 'completed_at']);
        $orderIdColumn = $this->findExistingColumn($table, ['order_id']);

        if (! $dueDateColumn) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($table)
            ->whereDate($table . '.' . $dueDateColumn, '<', now()->toDateString());

        if ($statusColumn) {
            $query->whereNotIn($table . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        if ($paidAtColumn) {
            $query->whereNull($table . '.' . $paidAtColumn);
        }

        if ($amountColumn && $paidAmountColumn) {
            $query->whereRaw(
                'COALESCE(' . $this->wrapColumn($table . '.' . $paidAmountColumn) . ', 0) < COALESCE(' . $this->wrapColumn($table . '.' . $amountColumn) . ', 0)',
            );
        }

        $paymentsTable = $this->findExistingTable(['payments']);

        if ($paymentsTable && $idColumn) {
            $paymentScheduleIdColumn = $this->findExistingColumn($paymentsTable, ['payment_schedule_id']);
            $paymentStatusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);

            if ($paymentScheduleIdColumn) {
                $query->whereNotExists(function ($subQuery) use (
                    $paymentsTable,
                    $paymentScheduleIdColumn,
                    $paymentStatusColumn,
                    $table,
                    $idColumn,
                ) {
                    $subQuery
                        ->selectRaw('1')
                        ->from($paymentsTable)
                        ->whereColumn(
                            $paymentsTable . '.' . $paymentScheduleIdColumn,
                            $table . '.' . $idColumn,
                        );

                    if ($paymentStatusColumn) {
                        $subQuery->whereIn(
                            $paymentsTable . '.' . $paymentStatusColumn,
                            $this->getPaidPaymentStatuses(),
                        );
                    }
                });
            }
        }

        $ordersTable = $this->findExistingTable(['orders']);

        if ($ordersTable && $orderIdColumn) {
            $orderStatusColumn = $this->findExistingColumn($ordersTable, ['status']);

            if ($orderStatusColumn) {
                $query->whereNotExists(function ($subQuery) use (
                    $ordersTable,
                    $orderStatusColumn,
                    $table,
                    $orderIdColumn,
                ) {
                    $subQuery
                        ->selectRaw('1')
                        ->from($ordersTable)
                        ->whereColumn(
                            $ordersTable . '.id',
                            $table . '.' . $orderIdColumn,
                        )
                        ->whereIn($ordersTable . '.' . $orderStatusColumn, ['paid']);
                });
            }
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => $amountColumn ? (float) (clone $query)->sum($table . '.' . $amountColumn) : 0,
        ];
    }

    protected function getFilledSeatCount(bool $activeBatchOnly = false): int
    {
        $pivotTable = $this->findExistingTable([
            'student_enrollments',
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        if (! $pivotTable) {
            return 0;
        }

        $batchIdColumn = $this->findExistingColumn($pivotTable, ['batch_id']);

        if (! $batchIdColumn) {
            return 0;
        }

        $studentColumn = $this->findExistingColumn($pivotTable, ['student_id', 'user_id', 'participant_id']);
        $statusColumn = $this->findExistingColumn($pivotTable, ['status']);

        $query = DB::table($pivotTable);

        if ($statusColumn) {
            $query->whereIn($pivotTable . '.' . $statusColumn, $this->getFilledEnrollmentStatuses());
        }

        if ($activeBatchOnly) {
            $batchesTable = $this->findExistingTable(['batches']);

            if ($batchesTable) {
                $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

                $query->join($batchesTable, $batchesTable . '.id', '=', $pivotTable . '.' . $batchIdColumn);

                if ($activeColumn === 'is_active') {
                    $query->where($batchesTable . '.is_active', true);
                } elseif ($activeColumn === 'status') {
                    $query->whereIn($batchesTable . '.status', $this->getActiveBatchStatuses());
                }
            }
        }

        if ($studentColumn) {
            $distinct = (clone $query)
                ->select([
                    $pivotTable . '.' . $batchIdColumn,
                    $pivotTable . '.' . $studentColumn,
                ])
                ->distinct();

            return (int) DB::query()->fromSub($distinct, 'filled_seats')->count();
        }

        return (int) $query->count();
    }

    protected function getFilledSeatMap(array $batchIds): array
    {
        if (empty($batchIds)) {
            return [];
        }

        $pivotTable = $this->findExistingTable([
            'student_enrollments',
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        if (! $pivotTable) {
            return [];
        }

        $batchIdColumn = $this->findExistingColumn($pivotTable, ['batch_id']);

        if (! $batchIdColumn) {
            return [];
        }

        $studentColumn = $this->findExistingColumn($pivotTable, ['student_id', 'user_id', 'participant_id']);
        $statusColumn = $this->findExistingColumn($pivotTable, ['status']);

        $query = DB::table($pivotTable)
            ->whereIn($pivotTable . '.' . $batchIdColumn, $batchIds)
            ->groupBy($pivotTable . '.' . $batchIdColumn);

        if ($statusColumn) {
            $query->whereIn($pivotTable . '.' . $statusColumn, $this->getFilledEnrollmentStatuses());
        }

        if ($studentColumn) {
            $query->select([
                $pivotTable . '.' . $batchIdColumn . ' as batch_id',
                DB::raw('COUNT(DISTINCT ' . $this->wrapColumn($pivotTable . '.' . $studentColumn) . ') as total'),
            ]);
        } else {
            $query->select([
                $pivotTable . '.' . $batchIdColumn . ' as batch_id',
                DB::raw('COUNT(*) as total'),
            ]);
        }

        return $query
            ->pluck('total', 'batch_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    protected function buildPaymentDateExpression(string $table): ?string
    {
        $existing = [];

        foreach (['paid_at', 'payment_date', 'created_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $existing[] = $this->wrapColumn($table . '.' . $column);
            }
        }

        if (empty($existing)) {
            return null;
        }

        return count($existing) === 1
            ? $existing[0]
            : 'COALESCE(' . implode(', ', $existing) . ')';
    }

    protected function buildPeriodKeys(string $dateFrom, string $dateTo, string $granularity): array
    {
        if ($granularity === 'monthly') {
            $from = Carbon::parse($dateFrom)->startOfMonth();
            $to = Carbon::parse($dateTo)->startOfMonth();
            $keys = [];

            while ($from->lte($to)) {
                $keys[$from->format('Y-m-01')] = $from->translatedFormat('M Y');
                $from->addMonth();
            }

            return $keys;
        }

        $keys = [];

        foreach (CarbonPeriod::create($dateFrom, $dateTo) as $date) {
            $keys[$date->format('Y-m-d')] = $date->translatedFormat('d M');
        }

        return $keys;
    }

    protected function wrapExistingOrZero(string $table, array $columns): string
    {
        $column = $this->findExistingColumn($table, $columns);

        return $column ? $this->wrapColumn($column) : '0';
    }

    protected function sumExistingColumn(Builder $baseQuery, string $table, array $columns): float
    {
        $column = $this->findExistingColumn($table, $columns);

        return $column ? (float) (clone $baseQuery)->sum($column) : 0;
    }

    protected function buildChange(float|int $current, float|int $previous): array
    {
        $difference = $current - $previous;

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percentage' => $this->growthPercentage((float) $current, (float) $previous),
            'direction' => match (true) {
                $difference > 0 => 'up',
                $difference < 0 => 'down',
                default => 'flat',
            },
        ];
    }

    protected function percentage(float|int $numerator, float|int $denominator, int $precision = 1): float|int
    {
        if ((float) $denominator <= 0) {
            return 0;
        }

        $value = (($numerator / $denominator) * 100);

        return $precision === 0
            ? (int) round($value)
            : round($value, $precision);
    }

    protected function growthPercentage(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    protected function formatPeriodLabel(string $dateFrom, string $dateTo): string
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        if ($from->isSameDay($to)) {
            return $from->translatedFormat('d M Y');
        }

        if ($from->isSameMonth($to)) {
            return $from->translatedFormat('d') . '–' . $to->translatedFormat('d M Y');
        }

        return $from->translatedFormat('d M Y') . ' – ' . $to->translatedFormat('d M Y');
    }

    protected function summaryItem(
        string $type,
        string $title,
        string $message,
        int $score,
    ): array {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ];
    }

    protected function severityWeight(string $type): int
    {
        return match ($type) {
            'critical' => 5,
            'warning' => 4,
            'action' => 3,
            'good' => 2,
            'info' => 1,
            default => 0,
        };
    }

    protected function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    protected function getPaidPaymentStatuses(): array
    {
        return [
            'paid',
            'success',
            'settled',
            'completed',
            'confirmed',
            'verified',
        ];
    }

    protected function getActiveBatchStatuses(): array
    {
        return [
            'active',
            'running',
            'ongoing',
            'open',
            'preparing',
            'scheduled',
        ];
    }

    protected function getFilledEnrollmentStatuses(): array
    {
        return [
            'active',
            'ongoing',
            'enrolled',
            'approved',
            'paid',
            'completed',
        ];
    }

    protected function findExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function findExistingColumn(?string $table, array $columns): ?string
    {
        if (! $table || ! Schema::hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function wrapColumn(string $column): string
    {
        return DB::connection()->getQueryGrammar()->wrap($column);
    }
}
