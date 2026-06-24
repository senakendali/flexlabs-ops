<?php

namespace App\Http\Controllers;

use App\Models\SalesDailyReport;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use App\Services\KommoService;
use App\Services\LocalDashboardInsightService;
use App\Services\Trello\TrelloDashboardStatsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(
        LocalDashboardInsightService $localDashboardInsightService,
        KommoService $kommoService,
        TrelloDashboardStatsService $trelloDashboardStatsService
    ): View {
        $academicStats = $this->getAcademicStats();
        $batchCapacity = $this->getBatchCapacitySummary();
        $revenueChart = $this->getMonthlyRevenueChart();
        $upcomingBatches = $this->getUpcomingBatches();
        $salesInsight = $this->getSalesInsight();

        $trialStats = $this->getTrialStats();
        $upcomingTrialSchedules = $this->getUpcomingTrialSchedules();
        $trialParticipantStatusCounts = $this->getTrialParticipantStatusCounts();
        $trialFollowUpProgress = $this->getTrialFollowUpProgress();

        $financeInsight = $this->getFinanceInsight();
        $orderInsight = $this->getOrderInsight();
        $workshopInsight = $this->getWorkshopInsight();
        $workshopStats = $this->getWorkshopStats();
        $workshopParticipantStatusCounts = $this->getWorkshopParticipantStatusCounts();
        $workshopFollowUpProgress = $this->getWorkshopFollowUpProgress();
        $upcomingWorkshopSchedules = $this->getUpcomingWorkshopSchedules();

        $kommoTodayLeadInsight = $this->getKommoTodayLeadInsight($kommoService);
        $trelloAcademicStats = $this->getTrelloDashboardInsight($trelloDashboardStatsService, 'academic');
        $trelloMarketingStats = $this->getTrelloDashboardInsight($trelloDashboardStatsService, 'marketing');

        $trelloDashboardStats = [
            'academic' => $trelloAcademicStats,
            'marketing' => $trelloMarketingStats,
        ];

        $summaryContext = [
            'academic_stats' => $academicStats,
            'batch_capacity' => $batchCapacity,
            'revenue_chart' => $revenueChart,
            'upcoming_batches' => $upcomingBatches,
            'sales_insight' => $salesInsight,
            'trial_stats' => $trialStats,
            'trial_status_counts' => $trialParticipantStatusCounts,
            'trial_follow_up_progress' => $trialFollowUpProgress,
            'finance_insight' => $financeInsight,
            'order_insight' => $orderInsight,
            'workshop_insight' => $workshopInsight,
            'workshop_stats' => $workshopStats,
            'workshop_status_counts' => $workshopParticipantStatusCounts,
            'workshop_follow_up_progress' => $workshopFollowUpProgress,
            'upcoming_workshop_schedules' => $upcomingWorkshopSchedules,
            'kommo_today_lead_insight' => $kommoTodayLeadInsight,
            'trello_academic_stats' => $trelloAcademicStats,
            'trello_marketing_stats' => $trelloMarketingStats,
            'trello_dashboard_stats' => $trelloDashboardStats,
        ];

        $managementSummary = $localDashboardInsightService->generate($summaryContext);
        $managementSummary = $this->mergeKommoTodayLeadInsightIntoManagementSummary(
            $managementSummary,
            $kommoTodayLeadInsight
        );
        $managementSummary = $this->mergeTrelloDashboardStatsIntoManagementSummary(
            $managementSummary,
            $trelloAcademicStats
        );
        $managementSummary = $this->mergeTrelloDashboardStatsIntoManagementSummary(
            $managementSummary,
            $trelloMarketingStats
        );

        return view('dashboard', [
            'academicStats' => $academicStats,
            'batchCapacity' => $batchCapacity,
            'revenueChart' => $revenueChart,
            'upcomingBatches' => $upcomingBatches,

            'trialStats' => $trialStats,
            'upcomingTrialSchedules' => $upcomingTrialSchedules,
            'trialParticipantStatusCounts' => $trialParticipantStatusCounts,
            'trialFollowUpProgress' => $trialFollowUpProgress,
            'salesInsight' => $salesInsight,

            // Data tambahan untuk reusable insight widget / management insight lokal.
            'financeInsight' => $financeInsight,
            'orderInsight' => $orderInsight,
            'workshopInsight' => $workshopInsight,
            'workshopStats' => $workshopStats,
            'workshopParticipantStatusCounts' => $workshopParticipantStatusCounts,
            'workshopFollowUpProgress' => $workshopFollowUpProgress,
            'upcomingWorkshopSchedules' => $upcomingWorkshopSchedules,
            'kommoTodayLeadInsight' => $kommoTodayLeadInsight,
            'trelloAcademicStats' => $trelloAcademicStats,
            'trelloMarketingStats' => $trelloMarketingStats,
            'trelloDashboardStats' => $trelloDashboardStats,
            'managementSummary' => $managementSummary,
            'dashboardAiSummaryText' => $managementSummary['summary_text'] ?? '',
        ]);
    }

    protected function getAcademicStats(): array
    {
        $programs = $this->safeCount('programs');

        $batchesTable = $this->findExistingTable(['batches']);
        $batchActiveColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

        $activeBatches = 0;

        if ($batchesTable) {
            $query = DB::table($batchesTable);

            if ($batchActiveColumn === 'is_active') {
                $query->where('is_active', 1);
            } elseif ($batchActiveColumn === 'status') {
                $query->whereIn('status', $this->getActiveBatchStatuses());
            }

            $activeBatches = (int) $query->count();
        }

        $filledSeats = $this->getFilledSeatCount();

        $upcomingBatches = 0;
        if ($batchesTable) {
            $startDateColumn = $this->findExistingColumn($batchesTable, ['start_date', 'start_at', 'batch_start_date']);

            if ($startDateColumn) {
                $upcomingBatches = (int) DB::table($batchesTable)
                    ->whereDate($startDateColumn, '>=', now()->toDateString())
                    ->count();
            }
        }

        return [
            'programs' => $programs,
            'active_batches' => $activeBatches,
            'filled_seats' => $filledSeats,
            'upcoming_batches' => $upcomingBatches,
        ];
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

        $batchQuery = DB::table($batchesTable);

        if ($activeColumn === 'is_active') {
            $batchQuery->where('is_active', 1);
        } elseif ($activeColumn === 'status') {
            $batchQuery->whereIn('status', $this->getActiveBatchStatuses());
        }

        $totalCapacity = 0;
        if ($capacityColumn) {
            $totalCapacity = (int) $batchQuery->sum($capacityColumn);
        }

        $filledSeats = $this->getFilledSeatCount(true);

        $remainingSeats = max($totalCapacity - $filledSeats, 0);
        $utilizationPercent = $totalCapacity > 0
            ? (int) round(($filledSeats / $totalCapacity) * 100)
            : 0;

        return [
            'total_capacity' => $totalCapacity,
            'filled_seats' => $filledSeats,
            'remaining_seats' => $remainingSeats,
            'utilization_percent' => $utilizationPercent,
        ];
    }

    protected function getMonthlyRevenueChart(): array
    {
        $year = now()->year;

        $labels = [];
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = Carbon::create($year, $month, 1)->translatedFormat('M');
            $data[] = 0;
        }

        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return [
                'year' => $year,
                'labels' => $labels,
                'data' => $data,
                'total' => 0,
            ];
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, [
            'amount',
            'paid_amount',
            'total_amount',
        ]);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);

        if (! $amountColumn || ! $dateExpression) {
            return [
                'year' => $year,
                'labels' => $labels,
                'data' => $data,
                'total' => 0,
            ];
        }

        $amountExpression = $this->wrapColumn($amountColumn);

        $query = DB::table($paymentsTable)
            ->selectRaw('MONTH(' . $dateExpression . ') as month_number, SUM(' . $amountExpression . ') as total_amount')
            ->whereRaw('YEAR(' . $dateExpression . ') = ?', [$year]);

        if ($statusColumn) {
            $query->whereIn($statusColumn, $this->getPaidPaymentStatuses());
        }

        $rows = $query
            ->groupByRaw('MONTH(' . $dateExpression . ')')
            ->orderByRaw('MONTH(' . $dateExpression . ')')
            ->get();

        foreach ($rows as $row) {
            $index = ((int) $row->month_number) - 1;

            if ($index >= 0 && $index < 12) {
                $data[$index] = (float) $row->total_amount;
            }
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
        ];
    }

    protected function getSalesInsight(): array
    {
        $dateFrom = now()->subDays(29)->toDateString();
        $dateTo = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $last30Days = $this->getSalesReportSummary($dateFrom, $dateTo);
        $thisMonth = $this->getSalesReportSummary($monthStart, $monthEnd);
        $lastMonth = $this->getSalesReportSummary($lastMonthStart, $lastMonthEnd);

        /**
         * Management definition:
         * Closing = payment yang sudah terkonfirmasi paid.
         *
         * Jadi angka closing di dashboard tidak lagi bergantung pada input manual
         * sales daily report, melainkan pada data transaksi real di table payments.
         */
        $paid = $this->getPaidPaymentCount($dateFrom, $dateTo);
        $paidThisMonth = $this->getPaidPaymentCount($monthStart, $monthEnd);
        $paidLastMonth = $this->getPaidPaymentCount($lastMonthStart, $lastMonthEnd);

        $closedDeal = $paid;
        $closedDealThisMonth = $paidThisMonth;
        $closedDealLastMonth = $paidLastMonth;

        $totalLeads = (int) $last30Days['leads'];
        $interacted = (int) $last30Days['interacted'];

        $interactionRate = $totalLeads > 0
            ? round(($interacted / $totalLeads) * 100, 1)
            : 0;

        $closingRate = $totalLeads > 0
            ? round(($closedDeal / $totalLeads) * 100, 1)
            : 0;

        $paidRate = $closedDeal > 0
            ? round(($paid / $closedDeal) * 100, 1)
            : 0;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'last_month_from' => $lastMonthStart,
            'last_month_to' => $lastMonthEnd,

            // Main dashboard stats: rolling 30 days.
            'leads' => $totalLeads,
            'interacted' => $interacted,
            'consultation' => (int) $last30Days['consultation'],
            'hot_leads' => (int) $last30Days['hot_leads'],
            'closing' => $closedDeal,
            'closed_deal' => $closedDeal,
            'paid' => $paid,
            'revenue' => (float) $last30Days['revenue'],

            // Reference only: value from sales daily report, not used as closing KPI.
            'reported_closing' => (int) $last30Days['closed_deal'],
            'reported_closed_deal' => (int) $last30Days['closed_deal'],

            // Current month.
            'leads_this_month' => (int) $thisMonth['leads'],
            'interacted_this_month' => (int) $thisMonth['interacted'],
            'consultation_this_month' => (int) $thisMonth['consultation'],
            'hot_leads_this_month' => (int) $thisMonth['hot_leads'],
            'closing_this_month' => $closedDealThisMonth,
            'closed_deal_this_month' => $closedDealThisMonth,
            'paid_this_month' => $paidThisMonth,
            'reported_closing_this_month' => (int) $thisMonth['closed_deal'],
            'reported_closed_deal_this_month' => (int) $thisMonth['closed_deal'],
            'reported_revenue_this_month' => (float) $thisMonth['revenue'],

            // Previous month comparison.
            'leads_last_month' => (int) $lastMonth['leads'],
            'interacted_last_month' => (int) $lastMonth['interacted'],
            'closing_last_month' => $closedDealLastMonth,
            'closed_deal_last_month' => $closedDealLastMonth,
            'paid_last_month' => $paidLastMonth,
            'reported_closing_last_month' => (int) $lastMonth['closed_deal'],
            'reported_closed_deal_last_month' => (int) $lastMonth['closed_deal'],
            'reported_revenue_last_month' => (float) $lastMonth['revenue'],

            // KPI rates.
            'interaction_rate' => $interactionRate,
            'closing_rate' => $closingRate,
            'deal_rate' => $closingRate,
            'paid_rate' => $paidRate,

            // Temporary aliases for existing Blade compatibility.
            'trial' => $interacted,
            'join' => $closedDeal,
            'conversion_trial' => $interactionRate,
            'conversion_join' => $closingRate,
            'conversion_paid' => $paidRate,
        ];
    }

    protected function getSalesReportSummary(string $dateFrom, string $dateTo): array
    {
        $defaults = [
            'leads' => 0,
            'interacted' => 0,
            'consultation' => 0,
            'hot_leads' => 0,
            'closed_deal' => 0,
            'revenue' => 0,
        ];

        if (! class_exists(SalesDailyReport::class)) {
            return $defaults;
        }

        $table = (new SalesDailyReport())->getTable();
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

        return [
            'leads' => $this->sumExistingColumn($query, $table, ['total_leads', 'leads', 'lead_count']),
            'interacted' => $this->sumExistingColumn($query, $table, ['interacted', 'interaction', 'contacted', 'trial']),
            'consultation' => $this->sumExistingColumn($query, $table, ['consultation', 'consulted', 'consultation_count']),
            'hot_leads' => $this->sumExistingColumn($query, $table, ['hot_leads', 'hot_lead', 'hot']),
            'closed_deal' => $this->sumExistingColumn($query, $table, ['closed_deal', 'closing', 'join', 'deal']),
            'revenue' => $this->sumExistingColumn($query, $table, ['revenue', 'total_revenue', 'sales_revenue']),
        ];
    }

    protected function getKommoTodayLeadInsight(KommoService $kommoService): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = now($timezone)->toDateString();

        $empty = [
            'date' => $today,
            'timezone' => $timezone,
            'source' => 'kommo',
            'is_available' => false,
            'error_message' => null,

            /*
            |--------------------------------------------------------------------------
            | Main dashboard stats
            |--------------------------------------------------------------------------
            | Final FlexLabs definition:
            | - Lead Hari Ini     = semua lead Kommo yang masuk hari ini.
            | - Incoming Leads    = lead yang masih berada di Incoming Leads.
            | - Sudah Follow-up   = semua lead selain Incoming Leads.
            | - Belum Follow-up   = Incoming Leads.
            | - Need Action       = Incoming Leads.
            | - Follow-up Rate    = Sudah Follow-up / Lead Hari Ini.
            |
            | Important:
            | Closed Lost, Not Related, dan Ignored tetap dihitung sebagai
            | Sudah Follow-up karena lead tersebut sudah diproses/diputuskan.
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | Kommo status counters
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | Detail helper
            |--------------------------------------------------------------------------
            */
            'filtered_out' => 0,
            'status_breakdown' => [],

            'pipeline_id' => config('services.kommo.pipeline_id'),
            'start_timestamp' => null,
            'end_timestamp' => null,
            'summary_text' => 'Data Kommo hari ini belum tersedia.',
            'last_synced_at' => null,
        ];

        try {
            $summary = $kommoService->getDailyLeadSummary(
                date: $today,
                timezone: $timezone
            );

            $totalLeads = max((int) ($summary['total_leads'] ?? 0), 0);

            $incomingLeads = max((int) (
                $summary['incoming_leads']
                ?? $summary['lead_masuk']
                ?? 0
            ), 0);

            $leadMasuk = max((int) (
                $summary['lead_masuk']
                ?? $summary['incoming_leads']
                ?? 0
            ), 0);

            $regularIncomingLeads = max((int) ($summary['regular_incoming_leads'] ?? 0), 0);
            $unsortedTotal = max((int) ($summary['unsorted_total'] ?? 0), 0);
            $unsortedAccepted = max((int) ($summary['unsorted_accepted'] ?? 0), 0);
            $unsortedDeclined = max((int) ($summary['unsorted_declined'] ?? 0), 0);
            $unsortedPending = max((int) ($summary['unsorted_pending'] ?? 0), 0);
            $unsortedAverageSortTime = max((int) ($summary['unsorted_average_sort_time'] ?? 0), 0);
            $unsortedFormsTotal = max((int) ($summary['unsorted_forms_total'] ?? 0), 0);
            $unsortedChatsTotal = max((int) ($summary['unsorted_chats_total'] ?? 0), 0);

            $initialContact = max((int) ($summary['initial_contact'] ?? 0), 0);
            $newLeads = max((int) ($summary['new_leads'] ?? 0), 0);
            $interacted = max((int) ($summary['interacted'] ?? 0), 0);
            $ignored = max((int) ($summary['ignored'] ?? 0), 0);
            $closedLost = max((int) ($summary['closed_lost'] ?? 0), 0);
            $notRelated = max((int) ($summary['not_related'] ?? 0), 0);
            $warmLeads = max((int) ($summary['warm_leads'] ?? 0), 0);
            $hotLeads = max((int) ($summary['hot_leads'] ?? 0), 0);
            $consultation = max((int) ($summary['consultation'] ?? 0), 0);
            $register = max((int) ($summary['register'] ?? 0), 0);
            $dataStorage = max((int) ($summary['data_storage'] ?? 0), 0);
            $paid = max((int) ($summary['paid'] ?? 0), 0);
            $trialClass = max((int) ($summary['trial_class'] ?? 0), 0);
            $waFirstBubble = max((int) ($summary['wa_first_bubble'] ?? 0), 0);

            $filteredOut = array_key_exists('filtered_out', $summary)
                ? max((int) $summary['filtered_out'], 0)
                : ($ignored + $closedLost + $notRelated);

            /*
            |--------------------------------------------------------------------------
            | Final follow-up metrics
            |--------------------------------------------------------------------------
            | Controller sengaja re-calculate supaya aman walaupun service lama/cache
            | masih mengirim derived metric versi lama.
            |
            | Rule final:
            | - Incoming Leads adalah satu-satunya Belum Follow-up.
            | - Semua status selain Incoming Leads dihitung Sudah Follow-up.
            | - Filtered Out tetap masuk processed/followed-up, bukan dibuang.
            |--------------------------------------------------------------------------
            */
            $notFollowedUp = $incomingLeads;
            $needsAttention = $incomingLeads;
            $followedUp = max($totalLeads - min($incomingLeads, $totalLeads), 0);

            $followUpRate = $totalLeads > 0
                ? (int) round(($followedUp / $totalLeads) * 100)
                : 0;

            $kommoData = [
                'incoming_leads' => $incomingLeads,
                'initial_contact' => $initialContact,
                'new_leads' => $newLeads,
                'interacted' => $interacted,
                'warm_leads' => $warmLeads,
                'hot_leads' => $hotLeads,
                'trial_class' => $trialClass,
                'wa_first_bubble' => $waFirstBubble,
                'consultation' => $consultation,
                'register' => $register,
                'data_storage' => $dataStorage,
                'ignored' => $ignored,
                'closed_lost' => $closedLost,
                'not_related' => $notRelated,
                'paid' => $paid,
            ];

            $statusBreakdown = $this->buildKommoLeadStatusBreakdown($kommoData);

            $summaryText = match (true) {
                $totalLeads <= 0 => 'Belum ada lead baru dari Kommo hari ini.',
                $notFollowedUp > 0 => 'Kommo mencatat ' . number_format($totalLeads) . ' lead hari ini. Dari jumlah tersebut, ' . number_format($followedUp) . ' lead sudah keluar dari Incoming Leads dan ' . number_format($notFollowedUp) . ' lead masih perlu dicek tim sales agar tidak dingin.',
                default => 'Semua ' . number_format($totalLeads) . ' lead Kommo hari ini sudah keluar dari Incoming Leads. Mantap, tinggal jaga kualitas follow-up berikutnya.',
            };

            return [
                'date' => $today,
                'timezone' => $summary['timezone'] ?? $timezone,
                'source' => 'kommo',
                'is_available' => true,
                'error_message' => null,

                'total_leads' => $totalLeads,

                'incoming_leads' => $incomingLeads,
                'lead_masuk' => $leadMasuk,
                'regular_incoming_leads' => $regularIncomingLeads,

                'unsorted_total' => $unsortedTotal,
                'unsorted_accepted' => $unsortedAccepted,
                'unsorted_declined' => $unsortedDeclined,
                'unsorted_pending' => $unsortedPending,
                'unsorted_average_sort_time' => $unsortedAverageSortTime,
                'unsorted_forms_total' => $unsortedFormsTotal,
                'unsorted_chats_total' => $unsortedChatsTotal,

                'initial_contact' => $initialContact,
                'new_leads' => $newLeads,
                'interacted' => $interacted,
                'ignored' => $ignored,
                'closed_lost' => $closedLost,
                'not_related' => $notRelated,
                'warm_leads' => $warmLeads,
                'hot_leads' => $hotLeads,
                'consultation' => $consultation,
                'register' => $register,
                'data_storage' => $dataStorage,
                'paid' => $paid,
                'trial_class' => $trialClass,
                'wa_first_bubble' => $waFirstBubble,

                'filtered_out' => $filteredOut,
                'followed_up' => $followedUp,
                'processed_leads' => $followedUp,
                'not_followed_up' => $notFollowedUp,
                'pending_incoming_leads' => $notFollowedUp,
                'follow_up_rate' => $followUpRate,
                'needs_attention' => $needsAttention,
                'need_action' => $needsAttention,
                'status_breakdown' => $statusBreakdown,

                'pipeline_id' => $summary['pipeline_id'] ?? config('services.kommo.pipeline_id'),
                'start_timestamp' => $summary['start_timestamp'] ?? null,
                'end_timestamp' => $summary['end_timestamp'] ?? null,
                'summary_text' => $summaryText,
                'last_synced_at' => now($timezone)->format('d M Y H:i'),
            ];
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Kommo today lead insight for dashboard.', [
                'date' => $today,
                'message' => $exception->getMessage(),
            ]);

            return array_merge($empty, [
                'error_message' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : 'Data Kommo belum bisa ditarik.',
                'summary_text' => 'Data lead Kommo hari ini belum bisa ditarik. Dashboard tetap aman, tapi tim perlu cek koneksi atau konfigurasi Kommo.',
                'last_synced_at' => now($timezone)->format('d M Y H:i'),
            ]);
        }
    }

    protected function buildKommoLeadStatusBreakdown(array $data): array
    {
        return [
            $this->kommoStatusItem(
                key: 'incoming_leads',
                label: 'Incoming Leads',
                total: (int) ($data['incoming_leads'] ?? 0),
                description: 'Lead baru yang masih berada di Incoming Leads dan perlu dicek sales.',
                isNeedAction: true
            ),
            $this->kommoStatusItem(
                key: 'initial_contact',
                label: 'Initial Contact',
                total: (int) ($data['initial_contact'] ?? 0),
                description: 'Lead sudah masuk tahap kontak awal.'
            ),
            $this->kommoStatusItem(
                key: 'new_leads',
                label: 'New Leads',
                total: (int) ($data['new_leads'] ?? 0),
                description: 'Lead baru yang sudah tercatat di pipeline Kommo.'
            ),
            $this->kommoStatusItem(
                key: 'interacted',
                label: 'Interacted',
                total: (int) ($data['interacted'] ?? 0),
                description: 'Lead sudah ada interaksi awal.'
            ),
            $this->kommoStatusItem(
                key: 'warm_leads',
                label: 'Warm Leads',
                total: (int) ($data['warm_leads'] ?? 0),
                description: 'Lead mulai menunjukkan minat.'
            ),
            $this->kommoStatusItem(
                key: 'hot_leads',
                label: 'Hot Leads',
                total: (int) ($data['hot_leads'] ?? 0),
                description: 'Lead prioritas tinggi untuk dikejar closing.'
            ),
            $this->kommoStatusItem(
                key: 'trial_class',
                label: 'Trial Class',
                total: (int) ($data['trial_class'] ?? 0),
                description: 'Lead sudah diarahkan ke trial/webinar.'
            ),
            $this->kommoStatusItem(
                key: 'wa_first_bubble',
                label: 'WA First Bubble',
                total: (int) ($data['wa_first_bubble'] ?? 0),
                description: 'Lead sudah masuk interaksi awal WhatsApp.'
            ),
            $this->kommoStatusItem(
                key: 'consultation',
                label: 'Consultation',
                total: (int) ($data['consultation'] ?? 0),
                description: 'Lead sudah masuk tahap konsultasi.'
            ),
            $this->kommoStatusItem(
                key: 'register',
                label: 'Register',
                total: (int) ($data['register'] ?? 0),
                description: 'Lead sudah masuk tahap registrasi.'
            ),
            $this->kommoStatusItem(
                key: 'data_storage',
                label: 'Data Storage',
                total: (int) ($data['data_storage'] ?? 0),
                description: 'Lead sudah disimpan sebagai data referensi/follow-up lanjutan.'
            ),
            $this->kommoStatusItem(
                key: 'ignored',
                label: 'Ignored',
                total: (int) ($data['ignored'] ?? 0),
                description: 'Lead tidak merespons atau belum lanjut, tapi sudah diproses sales.',
                isFilteredOut: true
            ),
            $this->kommoStatusItem(
                key: 'closed_lost',
                label: 'Closed Lost',
                total: (int) ($data['closed_lost'] ?? 0),
                description: 'Lead sudah diproses tapi tidak berhasil closing.',
                isFilteredOut: true
            ),
            $this->kommoStatusItem(
                key: 'not_related',
                label: 'Not Related',
                total: (int) ($data['not_related'] ?? 0),
                description: 'Lead sudah dicek dan dinilai tidak relevan.',
                isFilteredOut: true
            ),
            $this->kommoStatusItem(
                key: 'paid',
                label: 'Paid',
                total: (int) ($data['paid'] ?? 0),
                description: 'Lead sudah berhasil menjadi pembayaran.'
            ),
        ];
    }

    protected function kommoStatusItem(
        string $key,
        string $label,
        int $total,
        string $description,
        bool $isNeedAction = false,
        bool $isFilteredOut = false
    ): array {
        $category = $this->getKommoStatusCategory(
            key: $key,
            isNeedAction: $isNeedAction,
            isFilteredOut: $isFilteredOut
        );

        return [
            'key' => $key,

            // Blade compatibility: dashboard table reads `status`, while older code may read `label`.
            'status' => $label,
            'label' => $label,

            'total' => max($total, 0),
            'category' => $category,
            'badge_class' => $this->getKommoStatusBadgeClass($category),
            'description' => $description,

            // Keep helper flags for future logic.
            'is_need_action' => $isNeedAction,
            'is_filtered_out' => $isFilteredOut,
        ];
    }

    protected function getKommoStatusCategory(
        string $key,
        bool $isNeedAction = false,
        bool $isFilteredOut = false
    ): string {
        if ($isNeedAction) {
            return 'Need Action';
        }

        if ($isFilteredOut) {
            return 'Filtered Leads';
        }

        return match ($key) {
            'warm_leads',
            'hot_leads',
            'trial_class',
            'consultation',
            'register' => 'Sales Process',

            'paid' => 'Converted',

            'initial_contact',
            'new_leads',
            'interacted',
            'wa_first_bubble',
            'data_storage' => 'Followed Up',

            default => 'Followed Up',
        };
    }

    protected function getKommoStatusBadgeClass(string $category): string
    {
        return match ($category) {
            'Need Action' => 'bg-warning-subtle text-warning',
            'Filtered Leads' => 'bg-secondary-subtle text-secondary',
            'Sales Process' => 'bg-primary-subtle text-primary',
            'Converted' => 'bg-success-subtle text-success',
            'Followed Up' => 'bg-success-subtle text-success',
            default => 'bg-light text-muted',
        };
    }




    protected function getTrelloDashboardInsight(
        TrelloDashboardStatsService $trelloDashboardStatsService,
        string $sourceKey = 'academic'
    ): array {
        try {
            return $trelloDashboardStatsService->getStats($sourceKey);
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Trello dashboard stats.', [
                'source_key' => $sourceKey,
                'message' => $exception->getMessage(),
            ]);

            return $this->emptyTrelloDashboardStats(
                sourceKey: $sourceKey,
                insight: 'Data Trello belum bisa ditarik. Dashboard tetap aman, tapi koneksi Trello atau struktur table perlu dicek.'
            );
        }
    }

    protected function emptyTrelloDashboardStats(string $sourceKey, ?string $insight = null): array
    {
        return [
            'source_key' => $sourceKey,
            'integration_name' => null,
            'department' => $sourceKey,
            'board_id' => null,
            'board_name' => null,
            'webhook_status' => 'inactive',
            'last_synced_at' => null,
            'last_webhook_at' => null,

            'summary' => [
                'total_open_cards' => 0,
                'active_work' => 0,
                'completed' => 0,
                'due_today' => 0,
                'overdue' => 0,
                'unmapped' => 0,
                'completion_rate' => 0,
                'active_work_rate' => 0,
            ],

            'statuses' => [
                'notes' => 0,
                'todo' => 0,
                'in_progress' => 0,
                'review' => 0,
                'scheduled' => 0,
                'done' => 0,
                'archived' => 0,
                'ignored' => 0,
            ],

            'due_today_cards' => [],
            'overdue_cards' => [],
            'active_cards' => [],
            'recent_cards' => [],

            'insight' => $insight ?: 'Trello integration belum aktif atau belum ditemukan.',
        ];
    }

    protected function mergeTrelloDashboardStatsIntoManagementSummary(
        array $managementSummary,
        array $trelloStats
    ): array {
        $summary = $trelloStats['summary'] ?? [];

        $sourceKey = (string) ($trelloStats['source_key'] ?? 'academic');
        $boardName = trim((string) ($trelloStats['board_name'] ?? ''));
        $webhookStatus = (string) ($trelloStats['webhook_status'] ?? 'inactive');

        $totalOpenCards = (int) ($summary['total_open_cards'] ?? 0);
        $activeWork = (int) ($summary['active_work'] ?? 0);
        $dueToday = (int) ($summary['due_today'] ?? 0);
        $overdue = (int) ($summary['overdue'] ?? 0);
        $unmapped = (int) ($summary['unmapped'] ?? 0);
        $completionRate = (int) ($summary['completion_rate'] ?? 0);

        $insight = trim((string) ($trelloStats['insight'] ?? ''));

        if ($insight === '') {
            return $managementSummary;
        }

        $departmentLabel = match ($sourceKey) {
            'academic' => 'Academic',
            'marketing' => 'Marketing',
            default => ucfirst($sourceKey),
        };

        $type = 'info';
        $title = "{$departmentLabel} Trello Workload";

        if ($webhookStatus !== 'active') {
            $type = 'warning';
            $title = "{$departmentLabel} Trello belum aktif";
        } elseif ($unmapped > 0) {
            $type = 'warning';
            $title = "{$departmentLabel} Trello perlu mapping";
            $insight = "{$departmentLabel} Trello masih memiliki {$unmapped} card tanpa mapping status. Mapping list perlu dicek sebelum angka dashboard dijadikan acuan.";
        } elseif ($overdue > 0) {
            $type = 'critical';
            $title = "{$departmentLabel} Trello overdue";
        } elseif ($dueToday > 0) {
            $type = 'action';
            $title = "{$departmentLabel} Trello due today";
        } elseif ($activeWork > 0) {
            $type = 'info';
            $title = "{$departmentLabel} Trello aktif";
        } elseif ($totalOpenCards > 0 && $completionRate >= 80) {
            $type = 'good';
            $title = "{$departmentLabel} Trello sehat";
        }

        if ($boardName !== '') {
            $insight .= ' Board: ' . $boardName . '.';
        }

        $trelloItem = $this->summaryItem($type, $title, $insight);

        $existingItems = $managementSummary['items'] ?? [];
        $existingFocus = $managementSummary['focus'] ?? [];
        $existingSummaryText = trim((string) ($managementSummary['summary_text'] ?? ''));

        $managementSummary['items'] = array_values(array_merge([$trelloItem], $existingItems));
        $managementSummary['focus'] = array_slice(array_values(array_merge([$trelloItem], $existingFocus)), 0, 3);
        $managementSummary['summary_text'] = $this->joinSummaryParagraphs([
            $insight,
            ...$this->managementSummaryParagraphs($managementSummary),
        ]);

        if (! isset($managementSummary['headline']) || $overdue > 0 || $unmapped > 0 || $webhookStatus !== 'active') {
            $managementSummary['headline'] = $title;
        }

        return $managementSummary;
    }

    protected function mergeKommoTodayLeadInsightIntoManagementSummary(
        array $managementSummary,
        array $kommoTodayLeadInsight
    ): array {
        $totalLeads = (int) ($kommoTodayLeadInsight['total_leads'] ?? 0);
        $notFollowedUp = (int) ($kommoTodayLeadInsight['not_followed_up'] ?? 0);
        $followedUp = (int) ($kommoTodayLeadInsight['followed_up'] ?? 0);
        $isAvailable = (bool) ($kommoTodayLeadInsight['is_available'] ?? false);
        $summaryText = trim((string) ($kommoTodayLeadInsight['summary_text'] ?? ''));

        if ($summaryText === '') {
            return $managementSummary;
        }

        $type = 'info';
        $title = 'Kommo Lead Hari Ini';

        if (! $isAvailable) {
            $type = 'warning';
            $title = 'Kommo belum bisa ditarik';
        } elseif ($notFollowedUp > 0) {
            $type = 'action';
            $title = 'Lead Kommo masih di Incoming';
        } elseif ($totalLeads > 0 && $followedUp >= $totalLeads) {
            $type = 'good';
            $title = 'Lead Kommo sudah diproses';
        } elseif ($totalLeads > 0) {
            $type = 'info';
            $title = 'Lead Kommo sudah masuk';
        }

        $kommoItem = $this->summaryItem($type, $title, $summaryText);

        $existingItems = $managementSummary['items'] ?? [];
        $existingFocus = $managementSummary['focus'] ?? [];
        $existingSummaryText = trim((string) ($managementSummary['summary_text'] ?? ''));

        $managementSummary['items'] = array_values(array_merge([$kommoItem], $existingItems));
        $managementSummary['focus'] = array_slice(array_values(array_merge([$kommoItem], $existingFocus)), 0, 3);
        $managementSummary['summary_text'] = $this->joinSummaryParagraphs([
            $summaryText,
            ...$this->managementSummaryParagraphs($managementSummary),
        ]);

        if (! isset($managementSummary['headline']) || $notFollowedUp > 0 || ! $isAvailable) {
            $managementSummary['headline'] = $title;
        }

        return $managementSummary;
    }


    protected function getFinanceInsight(): array
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        $now = now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $empty = [
            'today' => $today,
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'last_month_from' => $lastMonthStart,
            'last_month_to' => $lastMonthEnd,
            'revenue_today' => 0,
            'revenue_this_month' => 0,
            'revenue_last_month' => 0,
            'revenue_month_diff' => 0,
            'revenue_month_growth_percent' => 0,
            'paid_count_today' => 0,
            'paid_count_this_month' => 0,
            'paid_count_last_month' => 0,
            'last_payment_date' => null,
            'last_payment_amount' => 0,
            'days_since_last_payment' => null,
            'pending_payment_count' => 0,
            'pending_payment_total' => 0,
            'expired_payment_count' => 0,
            'expired_payment_total' => 0,
            'overdue_schedule_count' => 0,
            'overdue_schedule_total' => 0,
        ];

        if (! $paymentsTable) {
            return $empty;
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);

        if (! $amountColumn || ! $dateExpression) {
            return $empty;
        }

        $revenueToday = $this->getPaidPaymentAmountBetween($today, $today);
        $revenueThisMonth = $this->getPaidPaymentAmountBetween($monthStart, $monthEnd);
        $revenueLastMonth = $this->getPaidPaymentAmountBetween($lastMonthStart, $lastMonthEnd);
        $revenueDiff = $revenueThisMonth - $revenueLastMonth;
        $growthPercent = $revenueLastMonth > 0
            ? round(($revenueDiff / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        $lastPayment = $this->getLastPaidPayment();
        $lastPaymentDate = $lastPayment['date'] ?? null;
        $daysSinceLastPayment = $lastPaymentDate
            ? Carbon::parse($lastPaymentDate)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        $pendingPayment = $this->getPaymentStatusSummary(['pending']);
        $expiredPayment = $this->getPaymentStatusSummary(['expired']);
        $overdueSchedule = $this->getOverduePaymentScheduleSummary();

        return [
            'today' => $today,
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'last_month_from' => $lastMonthStart,
            'last_month_to' => $lastMonthEnd,
            'revenue_today' => $revenueToday,
            'revenue_this_month' => $revenueThisMonth,
            'revenue_last_month' => $revenueLastMonth,
            'revenue_month_diff' => $revenueDiff,
            'revenue_month_growth_percent' => $growthPercent,
            'paid_count_today' => $this->getPaidPaymentCount($today, $today),
            'paid_count_this_month' => $this->getPaidPaymentCount($monthStart, $monthEnd),
            'paid_count_last_month' => $this->getPaidPaymentCount($lastMonthStart, $lastMonthEnd),
            'last_payment_date' => $lastPaymentDate,
            'last_payment_amount' => (float) ($lastPayment['amount'] ?? 0),
            'days_since_last_payment' => $daysSinceLastPayment,
            'pending_payment_count' => $pendingPayment['count'],
            'pending_payment_total' => $pendingPayment['total'],
            'expired_payment_count' => $expiredPayment['count'],
            'expired_payment_total' => $expiredPayment['total'],
            'overdue_schedule_count' => $overdueSchedule['count'],
            'overdue_schedule_total' => $overdueSchedule['total'],
        ];
    }

    protected function getOrderInsight(): array
    {
        $ordersTable = $this->findExistingTable(['orders']);
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $empty = [
            'orders_this_month' => 0,
            'orders_total' => 0,
            'pending_orders' => 0,
            'partial_orders' => 0,
            'paid_orders' => 0,
            'cancelled_orders' => 0,
            'pending_order_value' => 0,
            'partial_order_value' => 0,
            'paid_order_value' => 0,
            'potential_revenue' => 0,
            'program_orders_this_month' => 0,
            'workshop_orders_this_month' => 0,
        ];

        if (! $ordersTable) {
            return $empty;
        }

        $statusColumn = $this->findExistingColumn($ordersTable, ['status']);
        $amountColumn = $this->findExistingColumn($ordersTable, ['final_price', 'total_amount', 'amount']);
        $createdColumn = $this->findExistingColumn($ordersTable, ['created_at']);
        $typeColumn = $this->findExistingColumn($ordersTable, ['order_type']);

        $ordersThisMonth = 0;
        if ($createdColumn) {
            $ordersThisMonth = (int) DB::table($ordersTable)
                ->whereDate($createdColumn, '>=', $monthStart)
                ->whereDate($createdColumn, '<=', $monthEnd)
                ->count();
        }

        $summary = [
            'pending_orders' => 0,
            'partial_orders' => 0,
            'paid_orders' => 0,
            'cancelled_orders' => 0,
            'pending_order_value' => 0,
            'partial_order_value' => 0,
            'paid_order_value' => 0,
        ];

        if ($statusColumn) {
            foreach (['pending', 'partial', 'paid', 'cancelled'] as $status) {
                $query = DB::table($ordersTable)->where($statusColumn, $status);
                $summary[$status . '_orders'] = (int) $query->count();

                if ($amountColumn && in_array($status, ['pending', 'partial', 'paid'], true)) {
                    $summary[$status . '_order_value'] = (float) DB::table($ordersTable)
                        ->where($statusColumn, $status)
                        ->sum($amountColumn);
                }
            }
        }

        $programOrdersThisMonth = 0;
        $workshopOrdersThisMonth = 0;
        if ($typeColumn && $createdColumn) {
            $programOrdersThisMonth = (int) DB::table($ordersTable)
                ->whereDate($createdColumn, '>=', $monthStart)
                ->whereDate($createdColumn, '<=', $monthEnd)
                ->whereIn($typeColumn, ['program', 'batch', 'course'])
                ->count();

            $workshopOrdersThisMonth = (int) DB::table($ordersTable)
                ->whereDate($createdColumn, '>=', $monthStart)
                ->whereDate($createdColumn, '<=', $monthEnd)
                ->where($typeColumn, 'workshop')
                ->count();
        }

        $potentialRevenue = (float) ($summary['pending_order_value'] + $summary['partial_order_value']);

        return array_merge($empty, $summary, [
            'orders_this_month' => $ordersThisMonth,
            'orders_total' => (int) DB::table($ordersTable)->count(),
            'potential_revenue' => $potentialRevenue,
            'program_orders_this_month' => $programOrdersThisMonth,
            'workshop_orders_this_month' => $workshopOrdersThisMonth,
        ]);
    }


    protected function getWorkshopInsight(): array
    {
        /**
         * Workshop insight dipakai untuk summary management.
         * Angka status di sini sengaja fokus ke bulan berjalan supaya insight
         * yang muncul lebih relevan untuk action bulan ini.
         */
        $stats = $this->getWorkshopStats();
        $statusCounts = $this->getWorkshopParticipantStatusCounts();

        return array_merge($stats, [
            'participants_total' => (int) ($stats['participants_all_time'] ?? 0),
            'registered' => (int) ($statusCounts['registered'] ?? 0),
            'pending_payment' => (int) ($statusCounts['pending_payment'] ?? 0),
            'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
            'attended' => (int) ($statusCounts['attended'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            'paid_this_month' => (int) ($stats['paid_count_this_month'] ?? 0),
        ]);
    }

    protected function getManagementSummary(array $context): array
    {
        $sales = $context['sales_insight'] ?? [];
        $finance = $context['finance_insight'] ?? [];
        $orders = $context['order_insight'] ?? [];
        $batch = $context['batch_capacity'] ?? [];
        $trialStats = $context['trial_stats'] ?? [];
        $trialStatus = collect($context['trial_status_counts'] ?? []);
        $trialProgress = (int) ($context['trial_follow_up_progress'] ?? 0);
        $workshop = $context['workshop_insight'] ?? [];
        $upcomingBatches = $context['upcoming_batches'] ?? collect();

        $items = [];

        $leadsThisMonth = (int) ($sales['leads_this_month'] ?? 0);
        $interactedThisMonth = (int) ($sales['interacted_this_month'] ?? 0);
        $closingThisMonth = (int) ($sales['closing_this_month'] ?? 0);
        $paidThisMonth = (int) ($sales['paid_this_month'] ?? 0);
        $hotLeadsThisMonth = (int) ($sales['hot_leads_this_month'] ?? 0);
        $revenueThisMonth = (float) ($finance['revenue_this_month'] ?? 0);
        $revenueLastMonth = (float) ($finance['revenue_last_month'] ?? 0);
        $pendingPaymentTotal = (float) ($finance['pending_payment_total'] ?? 0);
        $pendingPaymentCount = (int) ($finance['pending_payment_count'] ?? 0);
        $overdueScheduleCount = (int) ($finance['overdue_schedule_count'] ?? 0);
        $overdueScheduleTotal = (float) ($finance['overdue_schedule_total'] ?? 0);
        $daysSinceLastPayment = $finance['days_since_last_payment'] ?? null;
        $remainingSeats = (int) ($batch['remaining_seats'] ?? 0);
        $utilizationPercent = (int) ($batch['utilization_percent'] ?? 0);
        $upcomingBatchCount = is_countable($upcomingBatches) ? count($upcomingBatches) : 0;

        if ($leadsThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Leads bulan ini belum masuk',
                'Belum ada leads baru yang tercatat bulan ini. Fokus awal perlu diarahkan ke campaign, referral, dan follow-up database lama.'
            );
        } elseif ($interactedThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Leads belum berinteraksi',
                'Leads bulan ini sudah masuk ' . number_format($leadsThisMonth) . ', tapi belum ada interaksi tercatat. Tim sales perlu mempercepat kontak awal.'
            );
        } elseif ($closingThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Belum ada closing bulan ini',
                'Sudah ada ' . number_format($leadsThisMonth) . ' leads dan ' . number_format($interactedThisMonth) . ' interaksi, tapi belum ada closing. Prioritasnya dorong consultation, hot leads, dan follow-up intensif.'
            );
        } elseif ($paidThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Closing belum berubah jadi payment',
                'Closing bulan ini sudah ada ' . number_format($closingThisMonth) . ', tapi pembayaran terkonfirmasi belum ada. Cek invoice, reminder pembayaran, dan payment URL.'
            );
        } else {
            $items[] = $this->summaryItem(
                'good',
                'Sales funnel mulai berjalan',
                'Bulan ini ada ' . number_format($leadsThisMonth) . ' leads, ' . number_format($closingThisMonth) . ' closing, dan ' . number_format($paidThisMonth) . ' payment terkonfirmasi.'
            );
        }

        if ($revenueThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Belum ada pemasukan bulan ini',
                'Revenue bulan ini masih Rp 0. Management perlu cek pipeline closing, pending invoice, dan jadwal pembayaran yang belum dikonfirmasi.'
            );
        } elseif ($revenueLastMonth > 0 && $revenueThisMonth < $revenueLastMonth) {
            $items[] = $this->summaryItem(
                'warning',
                'Revenue bulan ini turun',
                'Revenue bulan ini Rp ' . $this->formatRupiah($revenueThisMonth) . ', masih di bawah bulan lalu Rp ' . $this->formatRupiah($revenueLastMonth) . '.'
            );
        } else {
            $items[] = $this->summaryItem(
                'good',
                'Revenue bulan ini tercatat',
                'Pemasukan bulan ini sudah mencapai Rp ' . $this->formatRupiah($revenueThisMonth) . '.'
            );
        }

        if ($daysSinceLastPayment !== null && $daysSinceLastPayment >= 7) {
            $items[] = $this->summaryItem(
                'warning',
                'Payment terakhir sudah cukup lama',
                'Payment terakhir tercatat sekitar ' . number_format((int) $daysSinceLastPayment) . ' hari lalu. Perlu follow-up invoice dan prospek yang sudah dekat pembayaran.'
            );
        }

        if ($overdueScheduleCount > 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Ada jadwal pembayaran overdue',
                number_format($overdueScheduleCount) . ' jadwal pembayaran sudah lewat jatuh tempo dengan nilai sekitar Rp ' . $this->formatRupiah($overdueScheduleTotal) . '.'
            );
        } elseif ($pendingPaymentCount > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Ada payment pending',
                number_format($pendingPaymentCount) . ' pembayaran masih pending dengan estimasi nilai Rp ' . $this->formatRupiah($pendingPaymentTotal) . '. Ini bisa jadi prioritas follow-up cepat.'
            );
        }

        if ($remainingSeats > 0 && $closingThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Seat tersedia belum terisi closing baru',
                'Masih ada ' . number_format($remainingSeats) . ' seat aktif, sementara closing bulan ini belum terjadi. Sales perlu diarahkan untuk mengisi kapasitas batch.'
            );
        } elseif ($utilizationPercent > 0 && $utilizationPercent < 50) {
            $items[] = $this->summaryItem(
                'warning',
                'Utilisasi batch masih rendah',
                'Utilisasi batch aktif baru ' . number_format($utilizationPercent) . '%. Masih ada ruang besar untuk dorongan akuisisi student.'
            );
        } elseif ($utilizationPercent >= 80) {
            $items[] = $this->summaryItem(
                'good',
                'Utilisasi batch sehat',
                'Utilisasi batch aktif sudah ' . number_format($utilizationPercent) . '%, sisa seat sekitar ' . number_format($remainingSeats) . '.'
            );
        }

        $registeredTrial = (int) ($trialStatus['registered'] ?? 0);
        $contactedTrial = (int) ($trialStatus['contacted'] ?? 0);
        $confirmedTrial = (int) ($trialStatus['confirmed'] ?? 0);
        $attendedTrial = (int) ($trialStatus['attended'] ?? 0);
        $newTrialThisMonth = (int) ($trialStats['participants_new_this_month'] ?? 0);

        if ($newTrialThisMonth > 0 && $trialProgress < 50) {
            $items[] = $this->summaryItem(
                'warning',
                'Follow-up trial masih rendah',
                'Ada ' . number_format($newTrialThisMonth) . ' peserta trial baru bulan ini, tapi progress follow-up baru ' . number_format($trialProgress) . '%. Prioritaskan registered ke contacted dan confirmed.'
            );
        } elseif ($registeredTrial > $contactedTrial && $registeredTrial > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Banyak trial belum dihubungi',
                'Status registered masih lebih tinggi dari contacted. Tim perlu mempercepat follow-up peserta trial agar tidak dingin.'
            );
        } elseif ($confirmedTrial > 0 && $attendedTrial <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Trial perlu reminder attendance',
                'Peserta confirmed sudah ada, tapi attended belum terlihat. Reminder H-1 dan hari-H perlu diperkuat.'
            );
        }

        $workshopPending = (int) ($workshop['pending_payment'] ?? $workshop['pending_payment_this_month'] ?? 0);
        $workshopParticipantsThisMonth = (int) ($workshop['participants_this_month'] ?? 0);
        $workshopConfirmedThisMonth = (int) ($workshop['confirmed'] ?? $workshop['confirmed_this_month'] ?? 0);
        $workshopAttendedThisMonth = (int) ($workshop['attended'] ?? $workshop['attended_this_month'] ?? 0);
        $workshopRevenueThisMonth = (float) ($workshop['revenue_this_month'] ?? 0);
        $workshopSchedulesThisMonth = (int) ($workshop['schedules_active_this_month'] ?? 0);

        if ($workshopPending > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Workshop pending payment',
                'Ada ' . number_format($workshopPending) . ' peserta workshop bulan ini yang masih pending payment. Follow-up pembayaran bisa langsung diprioritaskan.'
            );
        } elseif ($workshopParticipantsThisMonth > 0 && ($workshopConfirmedThisMonth + $workshopAttendedThisMonth) <= 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Workshop belum terkonfirmasi',
                'Bulan ini ada ' . number_format($workshopParticipantsThisMonth) . ' peserta workshop masuk, tapi belum ada yang confirmed/attended. Cek follow-up dan status payment.'
            );
        } elseif ($workshopRevenueThisMonth > 0) {
            $items[] = $this->summaryItem(
                'good',
                'Workshop menghasilkan revenue',
                'Revenue workshop bulan ini sudah mencapai Rp ' . $this->formatRupiah($workshopRevenueThisMonth) . ' dari ' . number_format((int) ($workshop['paid_count_this_month'] ?? 0)) . ' payment terkonfirmasi.'
            );
        } elseif ($workshopParticipantsThisMonth > 0) {
            $items[] = $this->summaryItem(
                'good',
                'Workshop mulai menghasilkan demand',
                'Bulan ini ada ' . number_format($workshopParticipantsThisMonth) . ' peserta workshop yang masuk ke sistem.'
            );
        } elseif ($workshopSchedulesThisMonth > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Workshop bulan ini belum ada pendaftar',
                'Ada jadwal workshop aktif bulan ini, tapi belum ada peserta masuk. Campaign dan distribusi landing page perlu didorong.'
            );
        }

        if ($hotLeadsThisMonth > 0 && $closingThisMonth <= 0) {
            $items[] = $this->summaryItem(
                'action',
                'Hot leads perlu dikonversi',
                'Ada ' . number_format($hotLeadsThisMonth) . ' hot leads bulan ini. Karena closing belum ada, follow-up personal perlu diprioritaskan.'
            );
        }

        if ($upcomingBatchCount > 0 && $remainingSeats > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Batch mendatang perlu dipantau',
                'Ada ' . number_format($upcomingBatchCount) . ' batch mendatang dan seat masih tersedia. Pastikan sales push dan readiness akademik berjalan paralel.'
            );
        }

        if (empty($items)) {
            $items[] = $this->summaryItem(
                'info',
                'Dashboard stabil',
                'Data utama dashboard terlihat stabil. Tetap pantau sales funnel, revenue, seat utilization, dan follow-up trial secara berkala.'
            );
        }

        $priority = collect($items)
            ->sortBy(fn ($item) => $this->summarySeverityRank($item['type']))
            ->values();

        $summaryText = $this->joinSummaryParagraphs(
            $priority
                ->take(4)
                ->pluck('message')
                ->all()
        );

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'headline' => $priority->first()['title'] ?? 'Management Summary',
            'summary_text' => $summaryText,
            'items' => $priority->all(),
            'focus' => $priority->take(3)->values()->all(),
        ];
    }

    protected function joinSummaryParagraphs(array $paragraphs): string
    {
        return collect($paragraphs)
            ->flatten()
            ->flatMap(function ($paragraph) {
                return preg_split('/\n{2,}/', (string) $paragraph) ?: [];
            })
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n\n");
    }

    protected function managementSummaryParagraphs(array $managementSummary): array
    {
        $summaryText = trim((string) ($managementSummary['summary_text'] ?? ''));

        $paragraphs = collect(preg_split('/\n{2,}/', $summaryText) ?: [])
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->values();

        if ($paragraphs->count() > 1) {
            return $paragraphs->all();
        }

        $itemParagraphs = collect($managementSummary['items'] ?? [])
            ->pluck('message')
            ->map(fn ($message) => trim((string) $message))
            ->filter()
            ->values();

        if ($itemParagraphs->isNotEmpty()) {
            return $itemParagraphs
                ->take(4)
                ->all();
        }

        return $summaryText !== ''
            ? [$summaryText]
            : [];
    }

    protected function summaryItem(string $type, string $title, string $message): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ];
    }

    protected function summarySeverityRank(string $type): int
    {
        return match ($type) {
            'critical' => 1,
            'warning' => 2,
            'action' => 3,
            'good' => 4,
            'info' => 5,
            default => 6,
        };
    }

    protected function getPaidPaymentCount(?string $dateFrom = null, ?string $dateTo = null): int
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return 0;
        }

        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);

        $query = DB::table($paymentsTable);

        if ($statusColumn) {
            $query->whereIn($statusColumn, $this->getPaidPaymentStatuses());
        }

        if ($dateExpression && $dateFrom && $dateTo) {
            $query->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
                ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);
        }

        return (int) $query->count();
    }

    protected function getPaidPaymentAmountBetween(string $dateFrom, string $dateTo): float
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return 0;
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);

        if (! $amountColumn || ! $dateExpression) {
            return 0;
        }

        $query = DB::table($paymentsTable)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        if ($statusColumn) {
            $query->whereIn($statusColumn, $this->getPaidPaymentStatuses());
        }

        return (float) $query->sum($amountColumn);
    }

    protected function getLastPaidPayment(): ?array
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return null;
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);

        if (! $amountColumn || ! $dateExpression) {
            return null;
        }

        $query = DB::table($paymentsTable)
            ->selectRaw($dateExpression . ' as payment_effective_date, ' . $this->wrapColumn($amountColumn) . ' as amount')
            ->whereNotNull($amountColumn)
            ->orderByRaw($dateExpression . ' desc');

        if ($statusColumn) {
            $query->whereIn($statusColumn, $this->getPaidPaymentStatuses());
        }

        $payment = $query->first();

        if (! $payment) {
            return null;
        }

        return [
            'date' => $payment->payment_effective_date,
            'amount' => (float) $payment->amount,
        ];
    }

    protected function getPaymentStatusSummary(array $statuses): array
    {
        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return ['count' => 0, 'total' => 0];
        }

        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);

        if (! $statusColumn) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($paymentsTable)
            ->whereIn($statusColumn, $statuses);

        return [
            'count' => (int) (clone $query)->count(),
            'total' => $amountColumn ? (float) (clone $query)->sum($amountColumn) : 0,
        ];
    }

    protected function getOverduePaymentScheduleSummary(): array
    {
        $schedulesTable = $this->findExistingTable(['payment_schedules']);
        if (! $schedulesTable) {
            return ['count' => 0, 'total' => 0];
        }

        $scheduleIdColumn = $this->findExistingColumn($schedulesTable, ['id']);
        $dueDateColumn = $this->findExistingColumn($schedulesTable, ['due_date', 'payment_due_date', 'schedule_date']);
        $amountColumn = $this->findExistingColumn($schedulesTable, ['amount', 'total_amount', 'installment_amount']);
        $paidAmountColumn = $this->findExistingColumn($schedulesTable, ['paid_amount', 'amount_paid']);
        $statusColumn = $this->findExistingColumn($schedulesTable, ['status']);
        $paidAtColumn = $this->findExistingColumn($schedulesTable, ['paid_at', 'completed_at']);
        $orderIdColumn = $this->findExistingColumn($schedulesTable, ['order_id']);

        if (! $dueDateColumn) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($schedulesTable)
            ->whereDate($schedulesTable . '.' . $dueDateColumn, '<', now()->toDateString());

        /**
         * Overdue harus benar-benar berarti: jadwal sudah jatuh tempo dan belum lunas.
         *
         * Di database kita, pembayaran real ada di table payments. Kadang status
         * payment_schedules belum ikut berubah menjadi paid walaupun payments sudah paid.
         * Karena itu overdue schedule wajib mengecualikan:
         * - schedule yang statusnya sudah paid/completed/etc
         * - schedule yang punya paid_at/completed_at
         * - schedule yang paid_amount sudah memenuhi amount
         * - schedule yang punya payment paid lewat payment_schedule_id
         * - schedule yang order-nya sudah paid
         */
        if ($statusColumn) {
            $query->whereNotIn(
                $schedulesTable . '.' . $statusColumn,
                $this->getPaidPaymentStatuses()
            );
        }

        if ($paidAtColumn) {
            $query->whereNull($schedulesTable . '.' . $paidAtColumn);
        }

        if ($amountColumn && $paidAmountColumn) {
            $query->whereRaw(
                'COALESCE(' . $schedulesTable . '.' . $this->wrapColumn($paidAmountColumn) . ', 0) < COALESCE(' . $schedulesTable . '.' . $this->wrapColumn($amountColumn) . ', 0)'
            );
        }

        $paymentsTable = $this->findExistingTable(['payments']);
        if ($paymentsTable && $scheduleIdColumn) {
            $paymentScheduleIdColumn = $this->findExistingColumn($paymentsTable, ['payment_schedule_id']);
            $paymentStatusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);

            if ($paymentScheduleIdColumn) {
                $query->whereNotExists(function ($subQuery) use (
                    $paymentsTable,
                    $paymentScheduleIdColumn,
                    $paymentStatusColumn,
                    $schedulesTable,
                    $scheduleIdColumn
                ) {
                    $subQuery
                        ->selectRaw('1')
                        ->from($paymentsTable)
                        ->whereColumn(
                            $paymentsTable . '.' . $paymentScheduleIdColumn,
                            $schedulesTable . '.' . $scheduleIdColumn
                        );

                    if ($paymentStatusColumn) {
                        $subQuery->whereIn(
                            $paymentsTable . '.' . $paymentStatusColumn,
                            $this->getPaidPaymentStatuses()
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
                    $schedulesTable,
                    $orderIdColumn
                ) {
                    $subQuery
                        ->selectRaw('1')
                        ->from($ordersTable)
                        ->whereColumn(
                            $ordersTable . '.id',
                            $schedulesTable . '.' . $orderIdColumn
                        )
                        ->whereIn($ordersTable . '.' . $orderStatusColumn, ['paid']);
                });
            }
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => $amountColumn ? (float) (clone $query)->sum($amountColumn) : 0,
        ];
    }

    protected function getUpcomingBatches()
    {
        $batchesTable = $this->findExistingTable(['batches']);
        if (! $batchesTable) {
            return collect();
        }

        $nameColumn = $this->findExistingColumn($batchesTable, ['name', 'title']);
        $startDateColumn = $this->findExistingColumn($batchesTable, ['start_date', 'start_at', 'batch_start_date']);
        $capacityColumn = $this->findExistingColumn($batchesTable, [
            'capacity',
            'seat_capacity',
            'quota',
            'max_students',
            'max_seats',
            'total_seats',
        ]);
        $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);
        $programIdColumn = $this->findExistingColumn($batchesTable, ['program_id']);

        if (! $nameColumn || ! $startDateColumn) {
            return collect();
        }

        $query = DB::table($batchesTable)
            ->select([
                $batchesTable . '.id',
                DB::raw($batchesTable . '.' . $nameColumn . ' as name'),
                DB::raw($batchesTable . '.' . $startDateColumn . ' as start_date'),
                DB::raw(($capacityColumn ? $batchesTable . '.' . $capacityColumn : '0') . ' as capacity'),
            ])
            ->whereDate($batchesTable . '.' . $startDateColumn, '>=', now()->toDateString());

        if ($programIdColumn && Schema::hasTable('programs')) {
            $query->leftJoin('programs', 'programs.id', '=', $batchesTable . '.' . $programIdColumn);

            $programNameColumn = $this->findExistingColumn('programs', ['name', 'title']);
            if ($programNameColumn) {
                $query->addSelect(DB::raw('programs.' . $programNameColumn . ' as program_name'));
            }
        }

        if ($activeColumn === 'is_active') {
            $query->where($batchesTable . '.is_active', 1);
        } elseif ($activeColumn === 'status') {
            $query->whereIn($batchesTable . '.status', $this->getActiveBatchStatuses());
        }

        $batches = $query
            ->orderBy($batchesTable . '.' . $startDateColumn)
            ->limit(5)
            ->get();

        $filledMap = $this->getFilledSeatMap($batches->pluck('id')->all());

        return $batches->map(function ($batch) use ($filledMap) {
            $batch->filled_seats = (int) ($filledMap[$batch->id] ?? 0);
            $batch->remaining_seats = max(((int) $batch->capacity) - ((int) $batch->filled_seats), 0);

            return $batch;
        });
    }


    protected function getTrialStats(): array
    {
        /**
         * Trial dashboard sekarang fokus ke bulan berjalan.
         * Agar tetap aman untuk kebutuhan lain, angka all-time tetap dikirim
         * lewat key *_all_time.
         */
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $themeTable = class_exists(TrialTheme::class) ? (new TrialTheme())->getTable() : null;
        $scheduleTable = class_exists(TrialSchedule::class) ? (new TrialSchedule())->getTable() : null;
        $participantTable = class_exists(TrialParticipant::class) ? (new TrialParticipant())->getTable() : null;

        $themesTotal = ($themeTable && Schema::hasTable($themeTable))
            ? (int) DB::table($themeTable)->count()
            : 0;

        $themesActive = 0;
        if ($themeTable && Schema::hasTable($themeTable)) {
            $themesActiveQuery = DB::table($themeTable);
            if ($this->hasColumn($themeTable, 'is_active')) {
                $themesActiveQuery->where('is_active', true);
            }
            $themesActive = (int) $themesActiveQuery->count();
        }

        $schedulesAllTime = 0;
        $schedulesThisMonth = 0;
        $schedulesActiveThisMonth = 0;
        if ($scheduleTable && Schema::hasTable($scheduleTable)) {
            $scheduleDateColumn = $this->findExistingColumn($scheduleTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $scheduleActiveColumn = $this->findExistingColumn($scheduleTable, ['is_active', 'status']);

            $schedulesAllTime = (int) DB::table($scheduleTable)->count();

            $monthScheduleQuery = DB::table($scheduleTable);
            if ($scheduleDateColumn) {
                $monthScheduleQuery
                    ->whereDate($scheduleDateColumn, '>=', $monthStart)
                    ->whereDate($scheduleDateColumn, '<=', $monthEnd);
            }
            $schedulesThisMonth = (int) (clone $monthScheduleQuery)->count();

            $activeMonthScheduleQuery = clone $monthScheduleQuery;
            if ($scheduleActiveColumn === 'is_active') {
                $activeMonthScheduleQuery->where('is_active', true);
            } elseif ($scheduleActiveColumn === 'status') {
                $activeMonthScheduleQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }
            $schedulesActiveThisMonth = (int) $activeMonthScheduleQuery->count();
        }

        $participantsAllTime = 0;
        $participantsThisMonth = 0;
        if ($participantTable && Schema::hasTable($participantTable)) {
            $participantDateColumn = $this->findExistingColumn($participantTable, ['registered_at', 'created_at']);
            $participantsAllTime = (int) DB::table($participantTable)->count();

            $participantQuery = DB::table($participantTable);
            if ($participantDateColumn) {
                $participantQuery
                    ->whereDate($participantDateColumn, '>=', $monthStart)
                    ->whereDate($participantDateColumn, '<=', $monthEnd);
            }
            $participantsThisMonth = (int) $participantQuery->count();
        }

        return [
            'month_from' => $monthStart,
            'month_to' => $monthEnd,

            // Theme tidak time-based, jadi tetap total/active keseluruhan.
            'themes_total' => $themesTotal,
            'themes_active' => $themesActive,

            // Key lama dipertahankan, tapi sekarang diarahkan ke bulan berjalan.
            'schedules_total' => $schedulesThisMonth,
            'schedules_active' => $schedulesActiveThisMonth,
            'participants_total' => $participantsThisMonth,
            'participants_new_this_month' => $participantsThisMonth,

            // Key baru untuk kebutuhan management/report jika butuh all-time.
            'schedules_all_time' => $schedulesAllTime,
            'schedules_this_month' => $schedulesThisMonth,
            'schedules_active_this_month' => $schedulesActiveThisMonth,
            'participants_all_time' => $participantsAllTime,
            'participants_this_month' => $participantsThisMonth,
        ];
    }

    protected function getUpcomingTrialSchedules()
    {
        if (! class_exists(TrialSchedule::class)) {
            return collect();
        }

        $today = Carbon::today();

        return TrialSchedule::query()
            ->with([
                'trialTheme:id,name',
                'program:id,name',
            ])
            ->when(
                $this->hasColumn((new TrialSchedule())->getTable(), 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->whereDate('schedule_date', '>=', $today)
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();
    }


    protected function getTrialParticipantStatusCounts()
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
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        return $defaults->merge(
            $query
                ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
                ->groupBy($statusColumn)
                ->pluck('total', 'status')
        );
    }


    protected function getTrialFollowUpProgress(): int
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

        $baseQuery = DB::table($table);
        if ($dateColumn) {
            $baseQuery
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $totalParticipants = (int) (clone $baseQuery)->count();
        if ($totalParticipants <= 0) {
            return 0;
        }

        $followUpStatuses = ['contacted', 'confirmed', 'attended'];
        $followedUpCount = (int) (clone $baseQuery)
            ->whereIn($statusColumn, $followUpStatuses)
            ->count();

        return (int) round(($followedUpCount / $totalParticipants) * 100);
    }


    protected function getWorkshopStats(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $workshopsTable = $this->findExistingTable(['workshops']);
        $schedulesTable = $this->findExistingTable(['workshop_schedules']);
        $participantsTable = $this->findExistingTable(['workshop_participants']);

        $paymentSummary = $this->getWorkshopPaidPaymentSummary($monthStart, $monthEnd);
        $statusCounts = $this->getWorkshopParticipantStatusCounts();

        $stats = [
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'workshops_total' => 0,
            'workshops_active' => 0,
            'schedules_all_time' => 0,
            'schedules_this_month' => 0,
            'schedules_active_this_month' => 0,
            'upcoming_schedules' => 0,
            'participants_all_time' => 0,
            'participants_this_month' => 0,
            'registered_this_month' => (int) ($statusCounts['registered'] ?? 0),
            'pending_payment_this_month' => (int) ($statusCounts['pending_payment'] ?? 0),
            'confirmed_this_month' => (int) ($statusCounts['confirmed'] ?? 0),
            'attended_this_month' => (int) ($statusCounts['attended'] ?? 0),
            'cancelled_this_month' => (int) ($statusCounts['cancelled'] ?? 0),
            'paid_count_this_month' => (int) ($paymentSummary['count'] ?? 0),
            'revenue_this_month' => (float) ($paymentSummary['total'] ?? 0),
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
            $scheduleDateColumn = $this->findExistingColumn($schedulesTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $scheduleActiveColumn = $this->findExistingColumn($schedulesTable, ['is_active', 'status']);

            $stats['schedules_all_time'] = (int) DB::table($schedulesTable)->count();

            $monthQuery = DB::table($schedulesTable);
            if ($scheduleDateColumn) {
                $monthQuery
                    ->whereDate($scheduleDateColumn, '>=', $monthStart)
                    ->whereDate($scheduleDateColumn, '<=', $monthEnd);
            }
            $stats['schedules_this_month'] = (int) (clone $monthQuery)->count();

            $activeMonthQuery = clone $monthQuery;
            if ($scheduleActiveColumn === 'is_active') {
                $activeMonthQuery->where('is_active', true);
            } elseif ($scheduleActiveColumn === 'status') {
                $activeMonthQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }
            $stats['schedules_active_this_month'] = (int) $activeMonthQuery->count();

            if ($scheduleDateColumn) {
                $upcomingQuery = DB::table($schedulesTable)
                    ->whereDate($scheduleDateColumn, '>=', now()->toDateString());

                if ($scheduleActiveColumn === 'is_active') {
                    $upcomingQuery->where('is_active', true);
                } elseif ($scheduleActiveColumn === 'status') {
                    $upcomingQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
                }

                $stats['upcoming_schedules'] = (int) $upcomingQuery->count();
            }
        }

        if ($participantsTable) {
            $createdColumn = $this->findExistingColumn($participantsTable, ['registered_at', 'created_at']);
            $sourceColumn = $this->findExistingColumn($participantsTable, ['utm_source', 'input_source']);

            $stats['participants_all_time'] = (int) DB::table($participantsTable)->count();

            $participantsQuery = DB::table($participantsTable);
            if ($createdColumn) {
                $participantsQuery
                    ->whereDate($createdColumn, '>=', $monthStart)
                    ->whereDate($createdColumn, '<=', $monthEnd);
            }
            $stats['participants_this_month'] = (int) (clone $participantsQuery)->count();

            if ($sourceColumn) {
                $sourceBaseQuery = DB::table($participantsTable)
                    ->selectRaw('COALESCE(NULLIF(' . $this->wrapColumn($sourceColumn) . ', ""), "unknown") as source_name');

                if ($createdColumn) {
                    $sourceBaseQuery
                        ->whereDate($createdColumn, '>=', $monthStart)
                        ->whereDate($createdColumn, '<=', $monthEnd);
                }

                $source = DB::query()
                    ->fromSub($sourceBaseQuery, 'workshop_sources')
                    ->selectRaw('source_name, COUNT(*) as total')
                    ->groupBy('source_name')
                    ->orderByDesc('total')
                    ->first();

                if ($source) {
                    $stats['top_source'] = $source->source_name;
                    $stats['top_source_total'] = (int) $source->total;
                }
            }
        }

        $participantsThisMonth = max((int) $stats['participants_this_month'], 0);
        $convertedThisMonth = (int) $stats['confirmed_this_month'] + (int) $stats['attended_this_month'];

        if ($participantsThisMonth > 0) {
            $stats['conversion_percent'] = (int) round(($convertedThisMonth / $participantsThisMonth) * 100);
            $stats['attendance_percent'] = (int) round(((int) $stats['attended_this_month'] / $participantsThisMonth) * 100);
        }

        return $stats;
    }

    protected function getWorkshopParticipantStatusCounts()
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

        $statusColumn = $this->findExistingColumn($table, ['status']);
        $createdColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        if (! $statusColumn) {
            return $defaults;
        }

        $query = DB::table($table);
        if ($createdColumn) {
            $query
                ->whereDate($createdColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($createdColumn, '<=', now()->endOfMonth()->toDateString());
        }

        return $defaults->merge(
            $query
                ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
                ->groupBy($statusColumn)
                ->pluck('total', 'status')
        );
    }

    protected function getWorkshopFollowUpProgress(): int
    {
        $table = $this->findExistingTable(['workshop_participants']);
        if (! $table) {
            return 0;
        }

        $statusColumn = $this->findExistingColumn($table, ['status']);
        $createdColumn = $this->findExistingColumn($table, ['registered_at', 'created_at']);
        if (! $statusColumn) {
            return 0;
        }

        $baseQuery = DB::table($table);
        if ($createdColumn) {
            $baseQuery
                ->whereDate($createdColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($createdColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $totalParticipants = (int) (clone $baseQuery)->count();
        if ($totalParticipants <= 0) {
            return 0;
        }

        $convertedCount = (int) (clone $baseQuery)
            ->whereIn($statusColumn, ['confirmed', 'attended'])
            ->count();

        return (int) round(($convertedCount / $totalParticipants) * 100);
    }

    protected function getUpcomingWorkshopSchedules()
    {
        $schedulesTable = $this->findExistingTable(['workshop_schedules']);
        if (! $schedulesTable) {
            return collect();
        }

        $nameColumn = $this->findExistingColumn($schedulesTable, ['title', 'name']);
        $dateColumn = $this->findExistingColumn($schedulesTable, ['schedule_date', 'date', 'start_date']);
        $startTimeColumn = $this->findExistingColumn($schedulesTable, ['start_time']);
        $endTimeColumn = $this->findExistingColumn($schedulesTable, ['end_time']);
        $quotaColumn = $this->findExistingColumn($schedulesTable, ['quota', 'capacity']);
        $registeredColumn = $this->findExistingColumn($schedulesTable, ['registered_count']);
        $statusColumn = $this->findExistingColumn($schedulesTable, ['status']);
        $activeColumn = $this->findExistingColumn($schedulesTable, ['is_active']);
        $workshopIdColumn = $this->findExistingColumn($schedulesTable, ['workshop_id']);

        if (! $dateColumn) {
            return collect();
        }

        $query = DB::table($schedulesTable)
            ->select([
                $schedulesTable . '.id',
                DB::raw(($nameColumn ? $schedulesTable . '.' . $nameColumn : '"Workshop Schedule"') . ' as title'),
                DB::raw($schedulesTable . '.' . $dateColumn . ' as schedule_date'),
                DB::raw(($startTimeColumn ? $schedulesTable . '.' . $startTimeColumn : 'NULL') . ' as start_time'),
                DB::raw(($endTimeColumn ? $schedulesTable . '.' . $endTimeColumn : 'NULL') . ' as end_time'),
                DB::raw(($quotaColumn ? $schedulesTable . '.' . $quotaColumn : '0') . ' as quota'),
                DB::raw(($registeredColumn ? $schedulesTable . '.' . $registeredColumn : '0') . ' as registered_count'),
            ])
            ->whereDate($schedulesTable . '.' . $dateColumn, '>=', now()->toDateString());

        if ($activeColumn) {
            $query->where($schedulesTable . '.' . $activeColumn, true);
        }

        if ($statusColumn) {
            $query->whereIn($schedulesTable . '.' . $statusColumn, ['open', 'scheduled', 'active']);
        }

        if ($workshopIdColumn && Schema::hasTable('workshops')) {
            $query->leftJoin('workshops', 'workshops.id', '=', $schedulesTable . '.' . $workshopIdColumn);
            $workshopTitleColumn = $this->findExistingColumn('workshops', ['title', 'name']);
            if ($workshopTitleColumn) {
                $query->addSelect(DB::raw('workshops.' . $workshopTitleColumn . ' as workshop_title'));
            }
        }

        return $query
            ->orderBy($schedulesTable . '.' . $dateColumn)
            ->when($startTimeColumn, fn ($query) => $query->orderBy($schedulesTable . '.' . $startTimeColumn))
            ->limit(5)
            ->get();
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
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);
        $orderIdColumn = $this->findExistingColumn($paymentsTable, ['order_id']);

        if (! $amountColumn || ! $dateExpression) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($paymentsTable)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        if ($statusColumn) {
            $query->whereIn($paymentsTable . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        if ($ordersTable && $orderIdColumn) {
            $orderTypeColumn = $this->findExistingColumn($ordersTable, ['order_type']);
            $workshopIdColumn = $this->findExistingColumn($ordersTable, ['workshop_id']);

            $query->join($ordersTable, $ordersTable . '.id', '=', $paymentsTable . '.' . $orderIdColumn);

            if ($orderTypeColumn && $workshopIdColumn) {
                $query->where(function ($query) use ($ordersTable, $orderTypeColumn, $workshopIdColumn) {
                    $query
                        ->where($ordersTable . '.' . $orderTypeColumn, 'workshop')
                        ->orWhereNotNull($ordersTable . '.' . $workshopIdColumn);
                });
            } elseif ($orderTypeColumn) {
                $query->where($ordersTable . '.' . $orderTypeColumn, 'workshop');
            } elseif ($workshopIdColumn) {
                $query->whereNotNull($ordersTable . '.' . $workshopIdColumn);
            }
        } else {
            /**
             * Fallback jika payment belum punya order_id/order table tidak tersedia:
             * gunakan relasi workshop_participants.order_id bila ada.
             */
            $participantsTable = $this->findExistingTable(['workshop_participants']);
            if ($participantsTable && $orderIdColumn) {
                $participantOrderIdColumn = $this->findExistingColumn($participantsTable, ['order_id']);
                if ($participantOrderIdColumn) {
                    $query->whereExists(function ($subQuery) use (
                        $participantsTable,
                        $participantOrderIdColumn,
                        $paymentsTable,
                        $orderIdColumn
                    ) {
                        $subQuery
                            ->selectRaw('1')
                            ->from($participantsTable)
                            ->whereColumn(
                                $participantsTable . '.' . $participantOrderIdColumn,
                                $paymentsTable . '.' . $orderIdColumn
                            );
                    });
                }
            }
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (float) (clone $query)->sum($paymentsTable . '.' . $amountColumn),
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

        $studentColumn = $this->findExistingColumn($pivotTable, [
            'student_id',
            'user_id',
            'participant_id',
        ]);

        $query = DB::table($pivotTable);

        if ($this->findExistingColumn($pivotTable, ['status']) === 'status') {
            $query->whereIn($pivotTable . '.status', $this->getFilledEnrollmentStatuses());
        }

        if ($activeBatchOnly) {
            $batchesTable = $this->findExistingTable(['batches']);

            if ($batchesTable) {
                $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

                $query->join($batchesTable, $batchesTable . '.id', '=', $pivotTable . '.' . $batchIdColumn);

                if ($activeColumn === 'is_active') {
                    $query->where($batchesTable . '.is_active', 1);
                } elseif ($activeColumn === 'status') {
                    $query->whereIn($batchesTable . '.status', $this->getActiveBatchStatuses());
                }
            }
        }

        if ($studentColumn) {
            $distinctQuery = clone $query;

            $distinctQuery
                ->select([
                    $pivotTable . '.' . $batchIdColumn,
                    $pivotTable . '.' . $studentColumn,
                ])
                ->distinct();

            return (int) DB::query()
                ->fromSub($distinctQuery, 'filled_seats')
                ->count();
        }

        return (int) $query->count();
    }

    protected function getFilledSeatCountForBatch(int $batchId): int
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

        $query = DB::table($pivotTable)
            ->where($pivotTable . '.' . $batchIdColumn, $batchId);

        if ($this->findExistingColumn($pivotTable, ['status']) === 'status') {
            $query->whereIn($pivotTable . '.status', $this->getFilledEnrollmentStatuses());
        }

        $studentColumn = $this->findExistingColumn($pivotTable, [
            'student_id',
            'user_id',
            'participant_id',
        ]);

        if ($studentColumn) {
            return (int) $query
                ->distinct()
                ->count($pivotTable . '.' . $studentColumn);
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

        $studentColumn = $this->findExistingColumn($pivotTable, [
            'student_id',
            'user_id',
            'participant_id',
        ]);

        $query = DB::table($pivotTable)
            ->whereIn($pivotTable . '.' . $batchIdColumn, $batchIds)
            ->groupBy($pivotTable . '.' . $batchIdColumn);

        if ($this->findExistingColumn($pivotTable, ['status']) === 'status') {
            $query->whereIn($pivotTable . '.status', $this->getFilledEnrollmentStatuses());
        }

        if ($studentColumn) {
            $query->select([
                $pivotTable . '.' . $batchIdColumn . ' as batch_id',
                DB::raw('COUNT(DISTINCT ' . $pivotTable . '.' . $studentColumn . ') as total'),
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

    protected function sumExistingColumn($baseQuery, string $table, array $columns): float
    {
        $column = $this->findExistingColumn($table, $columns);

        if (! $column) {
            return 0;
        }

        return (float) (clone $baseQuery)->sum($column);
    }

    protected function buildPaymentDateExpression(string $paymentsTable): ?string
    {
        $dateColumns = [
            'paid_at',
            'payment_date',
            'created_at',
        ];

        $existingColumns = [];

        foreach ($dateColumns as $column) {
            if (Schema::hasColumn($paymentsTable, $column)) {
                /**
                 * Wajib qualify pakai nama table karena beberapa query payment
                 * melakukan join ke orders/workshop_participants yang juga punya
                 * kolom created_at/updated_at. Kalau tidak, MySQL akan error:
                 * Column created_at in where clause is ambiguous.
                 */
                $existingColumns[] = $this->wrapColumn($paymentsTable . '.' . $column);
            }
        }

        if (empty($existingColumns)) {
            return null;
        }

        if (count($existingColumns) === 1) {
            return $existingColumns[0];
        }

        return 'COALESCE(' . implode(', ', $existingColumns) . ')';
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

    protected function wrapColumn(string $column): string
    {
        return DB::connection()->getQueryGrammar()->wrap($column);
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

    protected function formatRupiah(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    protected function safeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
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

    protected function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}
