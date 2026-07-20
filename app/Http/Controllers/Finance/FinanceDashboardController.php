<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FinanceDashboardController extends Controller
{
    public function index(): View
    {
        return view('finance.dashboard.index', [
            'pageTitle' => 'Finance Dashboard',
            'eyebrow' => 'Financial Operations',
            'description' => 'Monitor revenue, payment status, receivables, and overdue schedules from a dedicated finance workspace.',
            'departmentIcon' => 'bi bi-wallet2',
            'statusLabel' => 'Dashboard scaffold ready',
            'stats' => [
                [
                    'label' => 'Revenue This Month',
                    'value' => 'Rp 0',
                    'description' => 'Confirmed paid amount for the current month.',
                    'icon' => 'bi bi-cash-stack',
                ],
                [
                    'label' => 'Pending Payments',
                    'value' => '0',
                    'description' => 'Payments still waiting for confirmation.',
                    'icon' => 'bi bi-hourglass-split',
                ],
                [
                    'label' => 'Overdue Schedules',
                    'value' => '0',
                    'description' => 'Payment schedules past their due date.',
                    'icon' => 'bi bi-exclamation-octagon-fill',
                ],
                [
                    'label' => 'Paid Transactions',
                    'value' => '0',
                    'description' => 'Successfully confirmed payment records.',
                    'icon' => 'bi bi-check-circle-fill',
                ],
            ],
            'focusItems' => [
                [
                    'title' => 'Connect payment revenue metrics',
                    'description' => 'Read revenue only from payments with confirmed paid or settled status.',
                    'icon' => 'bi bi-graph-up-arrow',
                ],
                [
                    'title' => 'Monitor outstanding receivables',
                    'description' => 'Show pending balances and payment schedules that require collection follow-up.',
                    'icon' => 'bi bi-receipt-cutoff',
                ],
                [
                    'title' => 'Add monthly comparison',
                    'description' => 'Compare current-month revenue and transaction count against the previous month.',
                    'icon' => 'bi bi-calendar3',
                ],
            ],
            'activityItems' => [
                'Finance dashboard controller and route are active.',
                'Real payment, invoice, receipt, and overdue metrics will be connected later.',
                'This placeholder keeps Finance isolated from Sales and Academic queries.',
            ],
            'generatedAt' => now()->format('d M Y H:i'),
        ]);
    }
}
