<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SalesDashboardController extends Controller
{
    public function index(): View
    {
        return view('sales.dashboard.index', [
            'pageTitle' => 'Sales Dashboard',
            'eyebrow' => 'Sales Performance',
            'description' => 'Monitor lead movement, follow-up workload, conversion, and closing activity from one focused workspace.',
            'departmentIcon' => 'bi bi-graph-up-arrow',
            'statusLabel' => 'Dashboard scaffold ready',
            'stats' => [
                [
                    'label' => 'Leads Today',
                    'value' => '0',
                    'description' => 'Kommo lead data will be connected here.',
                    'icon' => 'bi bi-person-plus-fill',
                ],
                [
                    'label' => 'Need Follow-up',
                    'value' => '0',
                    'description' => 'Incoming leads waiting for sales action.',
                    'icon' => 'bi bi-telephone-forward-fill',
                ],
                [
                    'label' => 'Hot Leads',
                    'value' => '0',
                    'description' => 'Priority prospects closest to conversion.',
                    'icon' => 'bi bi-fire',
                ],
                [
                    'label' => 'Closing This Month',
                    'value' => '0',
                    'description' => 'Confirmed paid transactions this month.',
                    'icon' => 'bi bi-trophy-fill',
                ],
            ],
            'focusItems' => [
                [
                    'title' => 'Connect Kommo daily summary',
                    'description' => 'Use Kommo as the primary source for today’s lead status and follow-up workload.',
                    'icon' => 'bi bi-diagram-3-fill',
                ],
                [
                    'title' => 'Build sales conversion funnel',
                    'description' => 'Track leads from incoming, interaction, consultation, registration, until paid.',
                    'icon' => 'bi bi-funnel-fill',
                ],
                [
                    'title' => 'Add payment-based closing',
                    'description' => 'Closing KPI should use confirmed payment data instead of manual report input.',
                    'icon' => 'bi bi-credit-card-2-front-fill',
                ],
            ],
            'activityItems' => [
                'Sales dashboard controller and route are active.',
                'Real Kommo, sales report, and payment metrics will be connected in the next phase.',
                'This placeholder does not query Finance, Academic, or Marketing data.',
            ],
            'generatedAt' => now()->format('d M Y H:i'),
        ]);
    }
}
