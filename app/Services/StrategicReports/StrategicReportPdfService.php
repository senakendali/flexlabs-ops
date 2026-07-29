<?php

namespace App\Services\StrategicReports;

use App\Models\StrategicReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class StrategicReportPdfService
{
    public function download(StrategicReport $report): Response
    {
        return Pdf::loadView('executive-center.strategic-reports.pdf', ['report' => $report])->setPaper('a4', 'portrait')->download('strategic-report-'.$report->period_start->format('Y-m').'-r'.$report->revision.'.pdf');
    }
}
