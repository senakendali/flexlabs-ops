<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinanceDashboardService
{
    /**
     * Menyiapkan seluruh data Finance Dashboard.
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

        $currentPayments = $this->getPaidPaymentSummary($dateFrom, $dateTo);
        $previousPayments = $this->getPaidPaymentSummary($previousFrom, $previousTo);

        $receivables = $this->getReceivablesSummary();
        $scheduleReceivables = $this->getScheduleReceivablesSummary();
        $orderSnapshot = $this->getOrderStatusSummary();
        $orderPeriod = $this->getOrderPeriodSummary($dateFrom, $dateTo);

        $pendingPayments = $this->getPaymentAttemptSummary('pending');
        $expiredPayments = $this->getPaymentAttemptSummary('expired');
        $failedPayments = $this->getPaymentAttemptSummary('failed');
        $cancelledPayments = $this->getPaymentAttemptSummary('cancelled');

        $financePerformance = $this->buildFinancePerformance(
            currentPayments: $currentPayments,
            previousPayments: $previousPayments,
            receivables: $receivables,
            orderSnapshot: $orderSnapshot,
            orderPeriod: $orderPeriod,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            previousFrom: $previousFrom,
            previousTo: $previousTo,
        );

        $actionCenter = [
            'overdue_schedules' => [
                'count' => (int) ($scheduleReceivables['overdue_count'] ?? 0),
                'total' => (float) ($scheduleReceivables['overdue_total'] ?? 0),
            ],
            'pending_payments' => $pendingPayments,
            'expired_payments' => $expiredPayments,
            'partial_orders' => [
                'count' => (int) ($receivables['partial_order_count'] ?? 0),
                'total' => (float) ($receivables['partial_order_outstanding'] ?? 0),
            ],
        ];

        $revenueChart = $this->getRevenueChart($dateFrom, $dateTo);
        $paymentStatusOverview = $this->getPaymentStatusOverview($dateFrom, $dateTo);
        $revenueByOrderType = $this->getRevenueByOrderType($dateFrom, $dateTo);
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($dateFrom, $dateTo);
        $gatewayBreakdown = $this->getGatewayBreakdown($dateFrom, $dateTo);

        $overdueSchedules = $this->getOverdueSchedules();
        $recentPayments = $this->getRecentPaidPayments($dateFrom, $dateTo);
        $largestReceivables = $this->getLargestReceivables();

        $financeSummary = $this->buildFinanceSummary([
            'finance_performance' => $financePerformance,
            'action_center' => $actionCenter,
            'receivables' => $receivables,
            'schedule_receivables' => $scheduleReceivables,
            'order_snapshot' => $orderSnapshot,
            'order_period' => $orderPeriod,
            'failed_payments' => $failedPayments,
            'cancelled_payments' => $cancelledPayments,
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

            'financePerformance' => $financePerformance,
            'financeActionCenter' => $actionCenter,

            'receivables' => $receivables,
            'scheduleReceivables' => $scheduleReceivables,
            'orderSnapshot' => $orderSnapshot,
            'orderPeriod' => $orderPeriod,

            'paymentStatusOverview' => $paymentStatusOverview,
            'pendingPayments' => $pendingPayments,
            'expiredPayments' => $expiredPayments,
            'failedPayments' => $failedPayments,
            'cancelledPayments' => $cancelledPayments,

            'revenueChart' => $revenueChart,
            'revenueByOrderType' => $revenueByOrderType,
            'paymentMethodBreakdown' => $paymentMethodBreakdown,
            'gatewayBreakdown' => $gatewayBreakdown,

            'overdueSchedules' => $overdueSchedules,
            'recentPayments' => $recentPayments,
            'largestReceivables' => $largestReceivables,

            'financeSummary' => $financeSummary,
            'financeDashboardAiSummaryText' => $financeSummary['summary_text'] ?? '',
        ];
    }

    /**
     * Payload chart terpisah untuk endpoint JSON Finance Dashboard.
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
            'revenue' => $this->getRevenueChart($dateFrom, $dateTo),
            'revenue_by_order_type' => $this->getRevenueByOrderType($dateFrom, $dateTo),
            'payment_status' => $this->getPaymentStatusOverview($dateFrom, $dateTo),
            'payment_methods' => $this->getPaymentMethodBreakdown($dateFrom, $dateTo),
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

    protected function getPaidPaymentSummary(string $dateFrom, string $dateTo): array
    {
        $defaults = [
            'revenue' => 0,
            'paid_transactions' => 0,
            'orders_with_payment' => 0,
            'fully_paid_orders' => 0,
            'average_payment_value' => 0,
            'latest_payment_date' => null,
            'latest_payment_amount' => 0,
        ];

        if (! $this->hasTable('payments')) {
            return $defaults;
        }

        $dateExpression = $this->buildPaidDateExpression('p');

        if (! $dateExpression) {
            return $defaults;
        }

        $query = DB::table('payments as p')
            ->where('p.status', 'paid')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        $revenue = (float) (clone $query)->sum('p.amount');
        $paidTransactions = (int) (clone $query)->count();
        $ordersWithPayment = (int) (clone $query)
            ->whereNotNull('p.order_id')
            ->distinct()
            ->count('p.order_id');

        $fullyPaidOrders = 0;
        if ($this->hasTable('orders')) {
            $fullyPaidOrders = (int) (clone $query)
                ->join('orders as o', 'o.id', '=', 'p.order_id')
                ->where('o.status', 'paid')
                ->distinct()
                ->count('o.id');
        }

        $latestPayment = (clone $query)
            ->selectRaw($dateExpression . ' as effective_date')
            ->addSelect('p.amount')
            ->orderByRaw($dateExpression . ' desc')
            ->orderByDesc('p.id')
            ->first();

        return [
            'revenue' => $revenue,
            'paid_transactions' => $paidTransactions,
            'orders_with_payment' => $ordersWithPayment,
            'fully_paid_orders' => $fullyPaidOrders,
            'average_payment_value' => $paidTransactions > 0
                ? round($revenue / $paidTransactions, 2)
                : 0,
            'latest_payment_date' => $latestPayment?->effective_date,
            'latest_payment_amount' => (float) ($latestPayment?->amount ?? 0),
        ];
    }

    protected function buildFinancePerformance(
        array $currentPayments,
        array $previousPayments,
        array $receivables,
        array $orderSnapshot,
        array $orderPeriod,
        string $dateFrom,
        string $dateTo,
        string $previousFrom,
        string $previousTo,
    ): array {
        $currentRevenue = (float) ($currentPayments['revenue'] ?? 0);
        $previousRevenue = (float) ($previousPayments['revenue'] ?? 0);
        $revenueDifference = $currentRevenue - $previousRevenue;

        $revenueGrowth = $previousRevenue > 0
            ? round(($revenueDifference / $previousRevenue) * 100, 1)
            : ($currentRevenue > 0 ? null : 0);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'previous_date_from' => $previousFrom,
            'previous_date_to' => $previousTo,

            'confirmed_revenue' => $currentRevenue,
            'previous_confirmed_revenue' => $previousRevenue,
            'revenue_difference' => $revenueDifference,
            'revenue_growth_percent' => $revenueGrowth,
            'revenue_growth_is_new' => $previousRevenue <= 0 && $currentRevenue > 0,

            'paid_transactions' => (int) ($currentPayments['paid_transactions'] ?? 0),
            'previous_paid_transactions' => (int) ($previousPayments['paid_transactions'] ?? 0),
            'orders_with_payment' => (int) ($currentPayments['orders_with_payment'] ?? 0),
            'fully_paid_orders_in_period' => (int) ($currentPayments['fully_paid_orders'] ?? 0),
            'average_payment_value' => (float) ($currentPayments['average_payment_value'] ?? 0),

            'outstanding_receivables' => (float) ($receivables['outstanding_total'] ?? 0),
            'outstanding_order_count' => (int) ($receivables['outstanding_order_count'] ?? 0),
            'fully_paid_orders_snapshot' => (int) ($orderSnapshot['statuses']['paid']['count'] ?? 0),

            'new_order_count' => (int) ($orderPeriod['order_count'] ?? 0),
            'new_order_value' => (float) ($orderPeriod['final_price_total'] ?? 0),
            'discount_total' => (float) ($orderPeriod['discount_total'] ?? 0),

            'latest_payment_date' => $currentPayments['latest_payment_date'] ?? null,
            'latest_payment_amount' => (float) ($currentPayments['latest_payment_amount'] ?? 0),
        ];
    }

    /**
     * Outstanding receivable dihitung per order:
     * MAX(orders.final_price - SUM(payments.amount WHERE paid), 0).
     */
    protected function getReceivablesSummary(): array
    {
        $defaults = [
            'outstanding_order_count' => 0,
            'outstanding_total' => 0,
            'pending_order_count' => 0,
            'pending_order_outstanding' => 0,
            'partial_order_count' => 0,
            'partial_order_outstanding' => 0,
        ];

        if (! $this->hasTable('orders')) {
            return $defaults;
        }

        $query = $this->buildReceivablesBaseQuery();

        if (! $query) {
            return $defaults;
        }

        $rows = $query->get();

        $pendingRows = $rows->where('order_status', 'pending');
        $partialRows = $rows->where('order_status', 'partial');

        return [
            'outstanding_order_count' => $rows->count(),
            'outstanding_total' => (float) $rows->sum('outstanding_amount'),
            'pending_order_count' => $pendingRows->count(),
            'pending_order_outstanding' => (float) $pendingRows->sum('outstanding_amount'),
            'partial_order_count' => $partialRows->count(),
            'partial_order_outstanding' => (float) $partialRows->sum('outstanding_amount'),
        ];
    }

    protected function buildReceivablesBaseQuery(): ?Builder
    {
        if (! $this->hasTable('orders')) {
            return null;
        }

        $paidByOrder = $this->hasTable('payments')
            ? DB::table('payments')
                ->select('order_id')
                ->selectRaw('SUM(amount) as paid_amount')
                ->where('status', 'paid')
                ->whereNotNull('order_id')
                ->groupBy('order_id')
            : null;

        $query = DB::table('orders as o');

        if ($paidByOrder) {
            $query->leftJoinSub($paidByOrder, 'paid_by_order', function ($join) {
                $join->on('paid_by_order.order_id', '=', 'o.id');
            });
        }

        $paidExpression = $paidByOrder
            ? 'COALESCE(paid_by_order.paid_amount, 0)'
            : '0';

        return $query
            ->where('o.status', '!=', 'cancelled')
            ->whereRaw('(o.final_price - ' . $paidExpression . ') > 0')
            ->select([
                'o.id as order_id',
                'o.student_id',
                'o.order_type',
                'o.batch_id',
                'o.workshop_id',
                'o.original_price',
                'o.discount',
                'o.final_price',
                'o.status as order_status',
                'o.created_at as order_created_at',
            ])
            ->selectRaw($paidExpression . ' as confirmed_paid_amount')
            ->selectRaw('GREATEST(o.final_price - ' . $paidExpression . ', 0) as outstanding_amount');
    }

    protected function getScheduleReceivablesSummary(): array
    {
        $defaults = [
            'overdue_count' => 0,
            'overdue_total' => 0,
            'due_today_count' => 0,
            'due_today_total' => 0,
            'due_next_7_days_count' => 0,
            'due_next_7_days_total' => 0,
            'due_next_30_days_count' => 0,
            'due_next_30_days_total' => 0,
        ];

        $baseQuery = $this->buildScheduleReceivablesBaseQuery();

        if (! $baseQuery) {
            return $defaults;
        }

        $today = now()->toDateString();
        $next7Days = now()->addDays(7)->toDateString();
        $next30Days = now()->addDays(30)->toDateString();

        $overdueQuery = (clone $baseQuery)
            ->whereDate('ps.due_date', '<', $today);

        $dueTodayQuery = (clone $baseQuery)
            ->whereDate('ps.due_date', $today);

        $dueNext7DaysQuery = (clone $baseQuery)
            ->whereDate('ps.due_date', '>', $today)
            ->whereDate('ps.due_date', '<=', $next7Days);

        $dueNext30DaysQuery = (clone $baseQuery)
            ->whereDate('ps.due_date', '>', $today)
            ->whereDate('ps.due_date', '<=', $next30Days);

        return [
            'overdue_count' => (int) (clone $overdueQuery)->count(),
            'overdue_total' => (float) (clone $overdueQuery)->sum(DB::raw($this->scheduleOutstandingExpression())),
            'due_today_count' => (int) (clone $dueTodayQuery)->count(),
            'due_today_total' => (float) (clone $dueTodayQuery)->sum(DB::raw($this->scheduleOutstandingExpression())),
            'due_next_7_days_count' => (int) (clone $dueNext7DaysQuery)->count(),
            'due_next_7_days_total' => (float) (clone $dueNext7DaysQuery)->sum(DB::raw($this->scheduleOutstandingExpression())),
            'due_next_30_days_count' => (int) (clone $dueNext30DaysQuery)->count(),
            'due_next_30_days_total' => (float) (clone $dueNext30DaysQuery)->sum(DB::raw($this->scheduleOutstandingExpression())),
        ];
    }

    protected function buildScheduleReceivablesBaseQuery(): ?Builder
    {
        if (! $this->hasTable('payment_schedules')) {
            return null;
        }

        $paidBySchedule = $this->hasTable('payments')
            ? DB::table('payments')
                ->select('payment_schedule_id')
                ->selectRaw('SUM(amount) as paid_amount')
                ->where('status', 'paid')
                ->whereNotNull('payment_schedule_id')
                ->groupBy('payment_schedule_id')
            : null;

        $query = DB::table('payment_schedules as ps');

        if ($paidBySchedule) {
            $query->leftJoinSub($paidBySchedule, 'paid_by_schedule', function ($join) {
                $join->on('paid_by_schedule.payment_schedule_id', '=', 'ps.id');
            });
        }

        if ($this->hasTable('orders')) {
            $query->join('orders as o', 'o.id', '=', 'ps.order_id')
                ->where('o.status', '!=', 'cancelled');
        }

        return $query
            ->whereNotIn('ps.status', ['paid', 'cancelled'])
            ->whereRaw($this->scheduleOutstandingExpression() . ' > 0');
    }

    protected function scheduleOutstandingExpression(): string
    {
        if ($this->hasTable('payments')) {
            return 'GREATEST(ps.amount - COALESCE(paid_by_schedule.paid_amount, 0), 0)';
        }

        return 'GREATEST(ps.amount, 0)';
    }

    protected function getOrderStatusSummary(): array
    {
        $defaults = [
            'total_orders' => 0,
            'total_final_price' => 0,
            'statuses' => [
                'pending' => ['count' => 0, 'value' => 0],
                'partial' => ['count' => 0, 'value' => 0],
                'paid' => ['count' => 0, 'value' => 0],
                'cancelled' => ['count' => 0, 'value' => 0],
            ],
        ];

        if (! $this->hasTable('orders')) {
            return $defaults;
        }

        $rows = DB::table('orders')
            ->select('status')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(final_price) as total_value')
            ->groupBy('status')
            ->get();

        $statuses = $defaults['statuses'];

        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');

            if (! array_key_exists($status, $statuses)) {
                continue;
            }

            $statuses[$status] = [
                'count' => (int) $row->total_count,
                'value' => (float) $row->total_value,
            ];
        }

        return [
            'total_orders' => (int) $rows->sum('total_count'),
            'total_final_price' => (float) $rows->sum('total_value'),
            'statuses' => $statuses,
        ];
    }

    protected function getOrderPeriodSummary(string $dateFrom, string $dateTo): array
    {
        $defaults = [
            'order_count' => 0,
            'original_price_total' => 0,
            'discount_total' => 0,
            'final_price_total' => 0,
            'by_type' => [],
        ];

        if (! $this->hasTable('orders')) {
            return $defaults;
        }

        $query = DB::table('orders')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', '!=', 'cancelled');

        $rowsByType = (clone $query)
            ->selectRaw("COALESCE(NULLIF(order_type, ''), 'unknown') as type_name")
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(original_price) as original_total')
            ->selectRaw('SUM(discount) as discount_total')
            ->selectRaw('SUM(final_price) as final_total')
            ->groupBy('type_name')
            ->orderByDesc('final_total')
            ->get()
            ->map(fn ($row) => [
                'order_type' => (string) $row->type_name,
                'count' => (int) $row->total_count,
                'original_price' => (float) $row->original_total,
                'discount' => (float) $row->discount_total,
                'final_price' => (float) $row->final_total,
            ])
            ->values()
            ->all();

        return [
            'order_count' => (int) (clone $query)->count(),
            'original_price_total' => (float) (clone $query)->sum('original_price'),
            'discount_total' => (float) (clone $query)->sum('discount'),
            'final_price_total' => (float) (clone $query)->sum('final_price'),
            'by_type' => $rowsByType,
        ];
    }

    protected function getPaymentAttemptSummary(string $status): array
    {
        if (! $this->hasTable('payments')) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table('payments')
            ->where('status', $status);

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (float) (clone $query)->sum('amount'),
        ];
    }

    protected function getPaymentStatusOverview(string $dateFrom, string $dateTo): array
    {
        $defaults = collect([
            'pending' => ['count' => 0, 'total' => 0],
            'paid' => ['count' => 0, 'total' => 0],
            'failed' => ['count' => 0, 'total' => 0],
            'expired' => ['count' => 0, 'total' => 0],
            'cancelled' => ['count' => 0, 'total' => 0],
        ]);

        if (! $this->hasTable('payments')) {
            return $defaults->all();
        }

        $dateExpression = $this->buildPaymentLifecycleDateExpression('payments');

        if (! $dateExpression) {
            return $defaults->all();
        }

        $rows = DB::table('payments')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo])
            ->select('status')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->status => [
                    'count' => (int) $row->total_count,
                    'total' => (float) $row->total_amount,
                ],
            ]);

        return $defaults->merge($rows)->all();
    }

    protected function getRevenueChart(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $days = max(1, $from->diffInDays($to) + 1);
        $granularity = $days <= 62 ? 'day' : 'month';

        $buckets = $this->buildChartBuckets($from, $to, $granularity);
        $revenueMap = collect();
        $orderValueMap = collect();
        $transactionMap = collect();

        if ($this->hasTable('payments')) {
            $dateExpression = $this->buildPaidDateExpression('p');

            if ($dateExpression) {
                $bucketExpression = $granularity === 'day'
                    ? 'DATE(' . $dateExpression . ')'
                    : 'DATE_FORMAT(' . $dateExpression . ', "%Y-%m")';

                $paymentRows = DB::table('payments as p')
                    ->where('p.status', 'paid')
                    ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
                    ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo])
                    ->selectRaw($bucketExpression . ' as bucket_key')
                    ->selectRaw('SUM(p.amount) as total_revenue')
                    ->selectRaw('COUNT(*) as total_transactions')
                    ->groupBy('bucket_key')
                    ->get();

                $revenueMap = $paymentRows->pluck('total_revenue', 'bucket_key');
                $transactionMap = $paymentRows->pluck('total_transactions', 'bucket_key');
            }
        }

        if ($this->hasTable('orders')) {
            $bucketExpression = $granularity === 'day'
                ? 'DATE(created_at)'
                : 'DATE_FORMAT(created_at, "%Y-%m")';

            $orderRows = DB::table('orders')
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->where('status', '!=', 'cancelled')
                ->selectRaw($bucketExpression . ' as bucket_key')
                ->selectRaw('SUM(final_price) as total_order_value')
                ->groupBy('bucket_key')
                ->get();

            $orderValueMap = $orderRows->pluck('total_order_value', 'bucket_key');
        }

        return [
            'granularity' => $granularity,
            'labels' => $buckets->pluck('label')->all(),
            'keys' => $buckets->pluck('key')->all(),
            'datasets' => [
                'confirmed_revenue' => $buckets
                    ->map(fn ($bucket) => (float) ($revenueMap[$bucket['key']] ?? 0))
                    ->all(),
                'new_order_value' => $buckets
                    ->map(fn ($bucket) => (float) ($orderValueMap[$bucket['key']] ?? 0))
                    ->all(),
                'paid_transactions' => $buckets
                    ->map(fn ($bucket) => (int) ($transactionMap[$bucket['key']] ?? 0))
                    ->all(),
            ],
            'summary' => [
                'confirmed_revenue' => (float) $revenueMap->sum(),
                'new_order_value' => (float) $orderValueMap->sum(),
                'paid_transactions' => (int) $transactionMap->sum(),
            ],
        ];
    }

    protected function buildChartBuckets(Carbon $from, Carbon $to, string $granularity): Collection
    {
        if ($granularity === 'month') {
            $cursor = $from->copy()->startOfMonth();
            $lastMonth = $to->copy()->startOfMonth();
            $buckets = collect();

            while ($cursor->lte($lastMonth)) {
                $buckets->push([
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->translatedFormat('M Y'),
                ]);

                $cursor->addMonth();
            }

            return $buckets;
        }

        return collect(CarbonPeriod::create($from, $to))
            ->map(fn (Carbon $date) => [
                'key' => $date->format('Y-m-d'),
                'label' => $date->translatedFormat('d M'),
            ])
            ->values();
    }

    protected function getRevenueByOrderType(string $dateFrom, string $dateTo): array
    {
        if (! $this->hasTable('payments') || ! $this->hasTable('orders')) {
            return [];
        }

        $dateExpression = $this->buildPaidDateExpression('p');

        if (! $dateExpression) {
            return [];
        }

        return DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('p.status', 'paid')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo])
            ->selectRaw("COALESCE(NULLIF(o.order_type, ''), 'unknown') as order_type")
            ->selectRaw('SUM(p.amount) as revenue')
            ->selectRaw('COUNT(p.id) as payment_count')
            ->selectRaw('COUNT(DISTINCT p.order_id) as order_count')
            ->groupBy('order_type')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'order_type' => (string) $row->order_type,
                'revenue' => (float) $row->revenue,
                'payment_count' => (int) $row->payment_count,
                'order_count' => (int) $row->order_count,
            ])
            ->values()
            ->all();
    }

    protected function getPaymentMethodBreakdown(string $dateFrom, string $dateTo): array
    {
        if (! $this->hasTable('payments')) {
            return [];
        }

        $dateExpression = $this->buildPaidDateExpression('payments');

        if (! $dateExpression) {
            return [];
        }

        return DB::table('payments')
            ->where('status', 'paid')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo])
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'Unknown') as method_name")
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('method_name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'payment_method' => (string) $row->method_name,
                'count' => (int) $row->payment_count,
                'total' => (float) $row->total_amount,
            ])
            ->values()
            ->all();
    }

    protected function getGatewayBreakdown(string $dateFrom, string $dateTo): array
    {
        if (! $this->hasTable('payments')) {
            return [];
        }

        $dateExpression = $this->buildPaymentLifecycleDateExpression('payments');

        if (! $dateExpression) {
            return [];
        }

        return DB::table('payments')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo])
            ->selectRaw("COALESCE(NULLIF(gateway_provider, ''), 'Manual / Unknown') as gateway_name")
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count")
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount")
            ->groupBy('gateway_name')
            ->orderByDesc('paid_amount')
            ->get()
            ->map(fn ($row) => [
                'gateway_provider' => (string) $row->gateway_name,
                'total_count' => (int) $row->total_count,
                'paid_count' => (int) $row->paid_count,
                'failed_count' => (int) $row->failed_count,
                'expired_count' => (int) $row->expired_count,
                'paid_amount' => (float) $row->paid_amount,
                'success_rate' => (int) $row->total_count > 0
                    ? round(((int) $row->paid_count / (int) $row->total_count) * 100, 1)
                    : 0,
            ])
            ->values()
            ->all();
    }

    protected function getOverdueSchedules(int $limit = 10): Collection
    {
        $query = $this->buildScheduleReceivablesBaseQuery();

        if (! $query) {
            return collect();
        }

        $studentNameExpression = $this->hasTable('orders')
            ? $this->joinStudentData($query, 'o.student_id')
            : null;

        $rowsQuery = $query
            ->whereDate('ps.due_date', '<', now()->toDateString())
            ->select([
                'ps.id',
                'ps.order_id',
                'ps.title',
                'ps.amount',
                'ps.due_date',
                'ps.status',
                'ps.notes',
            ])
            ->when($this->hasTable('orders'), function ($query) {
                $query->addSelect([
                    'o.student_id',
                    'o.order_type',
                    'o.final_price as order_final_price',
                    'o.status as order_status',
                ]);
            })
            ->selectRaw($this->hasTable('payments')
                ? 'COALESCE(paid_by_schedule.paid_amount, 0) as paid_amount'
                : '0 as paid_amount')
            ->selectRaw($this->scheduleOutstandingExpression() . ' as outstanding_amount');

        if ($studentNameExpression) {
            $rowsQuery->addSelect(DB::raw($studentNameExpression . ' as student_name'));
        }

        $rows = $rowsQuery
            ->orderBy('ps.due_date')
            ->orderByDesc('outstanding_amount')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            $dueDate = $row->due_date ? Carbon::parse($row->due_date) : null;

            return [
                'id' => (int) $row->id,
                'order_id' => (int) $row->order_id,
                'student_id' => isset($row->student_id) ? (int) $row->student_id : null,
                'student_name' => $row->student_name ?? null,
                'title' => (string) $row->title,
                'amount' => (float) $row->amount,
                'paid_amount' => (float) $row->paid_amount,
                'outstanding_amount' => (float) $row->outstanding_amount,
                'due_date' => $dueDate?->toDateString(),
                'due_date_label' => $dueDate?->translatedFormat('d M Y'),
                'days_overdue' => $dueDate
                    ? $dueDate->startOfDay()->diffInDays(now()->startOfDay())
                    : 0,
                'status' => (string) $row->status,
                'order_type' => $row->order_type ?? null,
                'order_status' => $row->order_status ?? null,
                'order_final_price' => (float) ($row->order_final_price ?? 0),
                'notes' => $row->notes ?? null,
            ];
        })->values();
    }

    protected function getRecentPaidPayments(
        string $dateFrom,
        string $dateTo,
        int $limit = 10
    ): Collection {
        if (! $this->hasTable('payments')) {
            return collect();
        }

        $dateExpression = $this->buildPaidDateExpression('p');

        if (! $dateExpression) {
            return collect();
        }

        $query = DB::table('payments as p')
            ->where('p.status', 'paid')
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        $studentNameExpression = null;

        if ($this->hasTable('orders')) {
            $query->leftJoin('orders as o', 'o.id', '=', 'p.order_id');
            $studentNameExpression = $this->joinStudentData($query, 'o.student_id');
        }

        $rowsQuery = $query
            ->select([
                'p.id',
                'p.order_id',
                'p.payment_schedule_id',
                'p.invoice_number',
                'p.amount',
                'p.payment_date',
                'p.payment_method',
                'p.reference_number',
                'p.gateway_transaction_id',
                'p.gateway_provider',
                'p.status',
                'p.paid_at',
            ])
            ->selectRaw($dateExpression . ' as effective_date')
            ->when($this->hasTable('orders'), function ($query) {
                $query->addSelect([
                    'o.student_id',
                    'o.order_type',
                    'o.final_price as order_final_price',
                    'o.status as order_status',
                ]);
            });

        if ($studentNameExpression) {
            $rowsQuery->addSelect(DB::raw($studentNameExpression . ' as student_name'));
        }

        $rows = $rowsQuery
            ->orderByRaw($dateExpression . ' desc')
            ->orderByDesc('p.id')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            $effectiveDate = $row->effective_date
                ? Carbon::parse($row->effective_date)
                : null;

            return [
                'id' => (int) $row->id,
                'order_id' => isset($row->order_id) ? (int) $row->order_id : null,
                'payment_schedule_id' => isset($row->payment_schedule_id)
                    ? (int) $row->payment_schedule_id
                    : null,
                'student_id' => isset($row->student_id) ? (int) $row->student_id : null,
                'student_name' => $row->student_name ?? null,
                'invoice_number' => $row->invoice_number,
                'amount' => (float) $row->amount,
                'effective_date' => $effectiveDate?->toDateString(),
                'effective_date_label' => $effectiveDate?->translatedFormat('d M Y H:i'),
                'payment_method' => $row->payment_method,
                'reference_number' => $row->reference_number,
                'gateway_transaction_id' => $row->gateway_transaction_id,
                'gateway_provider' => $row->gateway_provider,
                'status' => $row->status,
                'order_type' => $row->order_type ?? null,
                'order_status' => $row->order_status ?? null,
                'order_final_price' => (float) ($row->order_final_price ?? 0),
            ];
        })->values();
    }

    protected function getLargestReceivables(int $limit = 10): Collection
    {
        $query = $this->buildReceivablesBaseQuery();

        if (! $query) {
            return collect();
        }

        $studentNameExpression = $this->joinStudentData($query, 'o.student_id');

        if ($studentNameExpression) {
            $query->addSelect(DB::raw($studentNameExpression . ' as student_name'));
        }

        $rows = $query
            ->orderByDesc('outstanding_amount')
            ->orderBy('o.created_at')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'order_id' => (int) $row->order_id,
            'student_id' => isset($row->student_id) ? (int) $row->student_id : null,
            'student_name' => $row->student_name ?? null,
            'order_type' => $row->order_type,
            'order_status' => $row->order_status,
            'original_price' => (float) $row->original_price,
            'discount' => (float) $row->discount,
            'final_price' => (float) $row->final_price,
            'confirmed_paid_amount' => (float) $row->confirmed_paid_amount,
            'outstanding_amount' => (float) $row->outstanding_amount,
            'order_created_at' => $row->order_created_at,
        ])->values();
    }

    protected function joinStudentData(
        Builder $query,
        string $qualifiedStudentIdColumn
    ): ?string {
        if (! $this->hasTable('students')) {
            return null;
        }

        $nameColumn = $this->findExistingColumn('students', [
            'name',
            'full_name',
            'student_name',
        ]);

        if (! $nameColumn) {
            return null;
        }

        $query->leftJoin(
            'students as student_lookup',
            'student_lookup.id',
            '=',
            $qualifiedStudentIdColumn
        );

        return 'student_lookup.' . $this->wrapColumn($nameColumn);
    }

    protected function buildFinanceSummary(array $context): array
    {
        $performance = $context['finance_performance'] ?? [];
        $actionCenter = $context['action_center'] ?? [];
        $receivables = $context['receivables'] ?? [];
        $scheduleReceivables = $context['schedule_receivables'] ?? [];
        $orderSnapshot = $context['order_snapshot'] ?? [];
        $failedPayments = $context['failed_payments'] ?? [];

        $items = [];

        $revenue = (float) ($performance['confirmed_revenue'] ?? 0);
        $previousRevenue = (float) ($performance['previous_confirmed_revenue'] ?? 0);
        $growth = $performance['revenue_growth_percent'] ?? null;
        $paidTransactions = (int) ($performance['paid_transactions'] ?? 0);

        $outstanding = (float) ($receivables['outstanding_total'] ?? 0);
        $outstandingOrders = (int) ($receivables['outstanding_order_count'] ?? 0);
        $partialOrders = (int) ($receivables['partial_order_count'] ?? 0);

        $overdueCount = (int) ($scheduleReceivables['overdue_count'] ?? 0);
        $overdueTotal = (float) ($scheduleReceivables['overdue_total'] ?? 0);
        $dueNext7DaysCount = (int) ($scheduleReceivables['due_next_7_days_count'] ?? 0);
        $dueNext7DaysTotal = (float) ($scheduleReceivables['due_next_7_days_total'] ?? 0);

        $pendingPaymentCount = (int) ($actionCenter['pending_payments']['count'] ?? 0);
        $pendingPaymentTotal = (float) ($actionCenter['pending_payments']['total'] ?? 0);
        $expiredPaymentCount = (int) ($actionCenter['expired_payments']['count'] ?? 0);
        $failedPaymentCount = (int) ($failedPayments['count'] ?? 0);

        if ($overdueCount > 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Collection overdue perlu diprioritaskan',
                number_format($overdueCount) . ' jadwal pembayaran sudah melewati jatuh tempo dengan outstanding sekitar ' . $this->formatCurrency($overdueTotal) . '.'
            );
        }

        if ($pendingPaymentCount > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Payment link masih menunggu pembayaran',
                number_format($pendingPaymentCount) . ' payment masih berstatus pending dengan nominal ' . $this->formatCurrency($pendingPaymentTotal) . '. Follow-up payment link perlu dilakukan.'
            );
        }

        if ($expiredPaymentCount > 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Ada payment link expired',
                number_format($expiredPaymentCount) . ' payment sudah expired. Cek apakah calon pembayar masih aktif dan buat ulang payment link bila diperlukan.'
            );
        }

        if ($failedPaymentCount > 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Ada transaksi gagal',
                number_format($failedPaymentCount) . ' payment berstatus failed. Finance perlu mengecek metode pembayaran atau kendala gateway.'
            );
        }

        if ($revenue <= 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Belum ada confirmed revenue pada periode ini',
                'Belum ada pembayaran berstatus paid pada periode terpilih. Cek overdue schedule, pending payment link, dan order partial yang masih bisa ditagih.'
            );
        } elseif ($growth !== null && $growth < 0) {
            $items[] = $this->summaryItem(
                'warning',
                'Confirmed revenue turun',
                'Revenue periode ini ' . $this->formatCurrency($revenue) . ', turun ' . number_format(abs((float) $growth), 1) . '% dibanding periode sebelumnya sebesar ' . $this->formatCurrency($previousRevenue) . '.'
            );
        } elseif ($growth !== null && $growth > 0) {
            $items[] = $this->summaryItem(
                'good',
                'Confirmed revenue bertumbuh',
                'Revenue periode ini mencapai ' . $this->formatCurrency($revenue) . ', naik ' . number_format((float) $growth, 1) . '% dibanding periode sebelumnya.'
            );
        } else {
            $items[] = $this->summaryItem(
                'good',
                'Confirmed revenue sudah tercatat',
                number_format($paidTransactions) . ' transaksi paid menghasilkan revenue ' . $this->formatCurrency($revenue) . ' pada periode terpilih.'
            );
        }

        if ($partialOrders > 0) {
            $items[] = $this->summaryItem(
                'action',
                'Order partial masih perlu diselesaikan',
                number_format($partialOrders) . ' order berstatus partial dengan outstanding ' . $this->formatCurrency((float) ($receivables['partial_order_outstanding'] ?? 0)) . '.'
            );
        } elseif ($outstandingOrders > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Outstanding receivable masih tersedia',
                number_format($outstandingOrders) . ' order masih memiliki outstanding total ' . $this->formatCurrency($outstanding) . '.'
            );
        }

        if ($dueNext7DaysCount > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Collection tujuh hari ke depan perlu disiapkan',
                number_format($dueNext7DaysCount) . ' jadwal akan jatuh tempo dalam tujuh hari dengan nilai ' . $this->formatCurrency($dueNext7DaysTotal) . '.'
            );
        }

        $paidOrderCount = (int) ($orderSnapshot['statuses']['paid']['count'] ?? 0);
        $totalOrderCount = (int) ($orderSnapshot['total_orders'] ?? 0);

        if ($totalOrderCount > 0 && $paidOrderCount === $totalOrderCount) {
            $items[] = $this->summaryItem(
                'good',
                'Seluruh order sudah lunas',
                'Semua ' . number_format($totalOrderCount) . ' order tercatat berstatus paid.'
            );
        }

        if (empty($items)) {
            $items[] = $this->summaryItem(
                'info',
                'Finance dashboard siap dipantau',
                'Pantau confirmed revenue, outstanding receivable, payment status, dan jadwal jatuh tempo secara rutin.'
            );
        }

        $priority = collect($items)
            ->sortBy(fn ($item) => $this->summarySeverityRank($item['type']))
            ->values();

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Smart Local Finance Insight',
            'headline' => $priority->first()['title'] ?? 'Finance Summary',
            'summary_text' => $priority
                ->take(4)
                ->pluck('message')
                ->implode("\n\n"),
            'items' => $priority->all(),
            'focus' => $priority->take(4)->values()->all(),
        ];
    }

    protected function summaryItem(string $type, string $title, string $message): array
    {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
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

    protected function buildPaidDateExpression(string $alias): ?string
    {
        if (! $this->hasTable('payments')) {
            return null;
        }

        $columns = [];

        foreach (['paid_at', 'payment_date', 'created_at'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $columns[] = $this->wrapColumn($alias . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    protected function buildPaymentLifecycleDateExpression(string $alias): ?string
    {
        if (! $this->hasTable('payments')) {
            return null;
        }

        $statusColumn = $this->wrapColumn($alias . '.status');
        $paidAt = Schema::hasColumn('payments', 'paid_at')
            ? $this->wrapColumn($alias . '.paid_at')
            : 'NULL';
        $expiredAt = Schema::hasColumn('payments', 'expired_at')
            ? $this->wrapColumn($alias . '.expired_at')
            : 'NULL';
        $paymentDate = Schema::hasColumn('payments', 'payment_date')
            ? $this->wrapColumn($alias . '.payment_date')
            : 'NULL';
        $createdAt = Schema::hasColumn('payments', 'created_at')
            ? $this->wrapColumn($alias . '.created_at')
            : 'NULL';

        if ($paidAt === 'NULL'
            && $expiredAt === 'NULL'
            && $paymentDate === 'NULL'
            && $createdAt === 'NULL') {
            return null;
        }

        return 'CASE '
            . 'WHEN ' . $statusColumn . " = 'paid' THEN COALESCE(" . $paidAt . ', ' . $paymentDate . ', ' . $createdAt . ') '
            . 'WHEN ' . $statusColumn . " = 'expired' THEN COALESCE(" . $expiredAt . ', ' . $paymentDate . ', ' . $createdAt . ') '
            . 'ELSE COALESCE(' . $paymentDate . ', ' . $createdAt . ') '
            . 'END';
    }

    protected function buildPaymentRecordDateExpression(string $alias): ?string
    {
        if (! $this->hasTable('payments')) {
            return null;
        }

        $columns = [];

        foreach (['payment_date', 'created_at'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $columns[] = $this->wrapColumn($alias . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    protected function formatPeriodLabel(string $dateFrom, string $dateTo): string
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        if ($from->isSameDay($to)) {
            return $from->translatedFormat('d M Y');
        }

        return $from->translatedFormat('d M Y')
            . ' – '
            . $to->translatedFormat('d M Y');
    }

    protected function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    protected function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function findExistingColumn(string $table, array $columns): ?string
    {
        if (! $this->hasTable($table)) {
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
