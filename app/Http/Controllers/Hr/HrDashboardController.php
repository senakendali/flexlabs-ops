<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HrDashboardController extends Controller
{
    public function index(): View
    {
        return view('hr.dashboard.index', [
            'pageTitle' => 'HR Dashboard',
            'eyebrow' => 'People Operations',
            'description' => 'Monitor staff attendance, punctuality, leave, and workforce activity from one HR workspace.',
            'departmentIcon' => 'bi bi-person-badge-fill',
            'statusLabel' => 'Dashboard scaffold ready',
            'stats' => [
                [
                    'label' => 'Active Staff',
                    'value' => '0',
                    'description' => 'Active employees included in attendance monitoring.',
                    'icon' => 'bi bi-people-fill',
                ],
                [
                    'label' => 'Present Today',
                    'value' => '0',
                    'description' => 'Staff who have checked in today.',
                    'icon' => 'bi bi-person-check-fill',
                ],
                [
                    'label' => 'Late Today',
                    'value' => '0',
                    'description' => 'Check-ins recorded after the attendance threshold.',
                    'icon' => 'bi bi-clock-history',
                ],
                [
                    'label' => 'Leave / Permit',
                    'value' => '0',
                    'description' => 'Approved leave or permit records for today.',
                    'icon' => 'bi bi-calendar2-x-fill',
                ],
            ],
            'focusItems' => [
                [
                    'title' => 'Define staff attendance source',
                    'description' => 'Prepare employee, shift, check-in, check-out, leave, and permit records.',
                    'icon' => 'bi bi-database-fill-gear',
                ],
                [
                    'title' => 'Create daily attendance recap',
                    'description' => 'Summarize present, late, absent, leave, and missing check-out records.',
                    'icon' => 'bi bi-calendar2-check-fill',
                ],
                [
                    'title' => 'Prepare monthly attendance report',
                    'description' => 'Provide attendance rate and punctuality trends per employee and department.',
                    'icon' => 'bi bi-bar-chart-line-fill',
                ],
            ],
            'activityItems' => [
                'HR dashboard controller and route are active.',
                'The first HR module will focus on staff attendance.',
                'Attendance management can be opened from the HR menu.',
            ],
            'generatedAt' => now()->format('d M Y H:i'),
        ]);
    }
}
