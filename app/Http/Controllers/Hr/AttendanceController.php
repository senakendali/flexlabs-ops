<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        $summary = [
            'present' => 0,
            'late' => 0,
            'leave' => 0,
            'absent' => 0,
        ];

        $attendanceRows = [
            [
                'employee' => 'Dummy Employee',
                'department' => 'HR',
                'date' => now()->toDateString(),
                'check_in' => '-',
                'check_out' => '-',
                'status' => 'Not Recorded',
            ],
        ];

        return view('hr.attendances.index', [
            'summary' => $summary,
            'attendanceRows' => $attendanceRows,
            'selectedDate' => now()->toDateString(),
        ]);
    }
}
