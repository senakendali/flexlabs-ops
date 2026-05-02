<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\Assessment\ReportCardService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportCardService $reportCardService
    ) {
    }

    public function index(Request $request)
    {
        $query = ReportCard::query()
            ->with(['student', 'batch', 'program', 'template'])
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            })
            ->when($request->filled('program_id'), function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest();

        $reportCards = $query->paginate(15)->withQueryString();

        $batches = Batch::query()
            ->with('program')
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $reportCards,
                'batches' => $batches,
            ]);
        }

        return view('academic.report-cards.index', compact(
            'reportCards',
            'batches'
        ));
    }

    public function show(Request $request, ReportCard $reportCard)
    {
        $reportCard->load([
            'student',
            'batch',
            'program',
            'template',
            'certificate',
            'generator',
            'publisher',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $reportCard,
            ]);
        }

        return view('academic.report-cards.show', compact('reportCard'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['required', 'exists:batches,id'],
            'summary' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'improvements' => ['nullable', 'string'],
            'instructor_note' => ['nullable', 'string'],
            'academic_note' => ['nullable', 'string'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $batch = Batch::query()->findOrFail($validated['batch_id']);

        $reportCard = $this->reportCardService->generate(
            student: $student,
            batch: $batch,
            generatedBy: $request->user()?->id,
            extraData: [
                'summary' => $validated['summary'] ?? null,
                'strengths' => $validated['strengths'] ?? null,
                'improvements' => $validated['improvements'] ?? null,
                'instructor_note' => $validated['instructor_note'] ?? null,
                'academic_note' => $validated['academic_note'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Report card generated successfully.',
                'data' => $reportCard,
            ]);
        }

        return redirect()
            ->route('academic.report-cards.show', $reportCard)
            ->with('success', 'Report card generated successfully.');
    }

    public function regenerate(Request $request, ReportCard $reportCard)
    {
        $validated = $request->validate([
            'summary' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'improvements' => ['nullable', 'string'],
            'instructor_note' => ['nullable', 'string'],
            'academic_note' => ['nullable', 'string'],
        ]);

        $reportCard = $this->reportCardService->regenerate(
            reportCard: $reportCard,
            generatedBy: $request->user()?->id,
            extraData: $validated
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Report card regenerated successfully.',
                'data' => $reportCard,
            ]);
        }

        return redirect()
            ->route('academic.report-cards.show', $reportCard)
            ->with('success', 'Report card regenerated successfully.');
    }

    public function publish(Request $request, ReportCard $reportCard)
    {
        $reportCard = $this->reportCardService->publish(
            reportCard: $reportCard,
            publishedBy: $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Report card published successfully.',
                'redirect_url' => route('academic.report-cards.show', $reportCard),
                'data' => $reportCard,
            ]);
        }

        return back()->with('success', 'Report card published successfully.');
    }

    public function cancel(Request $request, ReportCard $reportCard)
    {
        $reportCard = $this->reportCardService->cancel($reportCard);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Report card cancelled successfully.',
                'data' => $reportCard,
            ]);
        }

        return back()->with('success', 'Report card cancelled successfully.');
    }

    public function downloadPdf(ReportCard $reportCard)
    {
        $reportCard->load([
            'student',
            'batch',
            'program',
            'template',
            'certificate',
        ]);

        $student = $reportCard->student;

        $studentName = $student->name
            ?? $student->full_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        if (! $studentName) {
            $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
        }

        $safeStudentName = Str::slug($studentName ?: 'student');
        $safeReportNo = Str::slug($reportCard->report_no ?: 'report-card');

        $fileName = "report-card-{$safeReportNo}-{$safeStudentName}.pdf";

        $pdf = Pdf::loadView('academic.report-cards.pdf', [
            'reportCard' => $reportCard,
            'studentName' => $studentName,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }
}