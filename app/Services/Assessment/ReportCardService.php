<?php

namespace App\Services\Assessment;

use App\Models\Batch;
use App\Models\ReportCard;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportCardService
{
    public function __construct(
        protected AssessmentCalculatorService $calculator
    ) {
    }

    public function generate(Student $student, Batch $batch, ?int $generatedBy = null, array $extraData = []): ReportCard
    {
        return DB::transaction(function () use ($student, $batch, $generatedBy, $extraData) {
            $result = $this->calculator->calculate($student, $batch);

            $reportCard = ReportCard::query()->firstOrNew([
                'student_id' => $student->id,
                'batch_id' => $batch->id,
            ]);

            if (! $reportCard->exists) {
                $reportCard->report_no = $this->generateReportNo($batch);
            }

            $reportCard->program_id = $result['program_id'] ?? $batch->program_id;
            $reportCard->assessment_template_id = $result['assessment_template_id'] ?? null;

            $reportCard->attendance_percent = $result['attendance_percent'] ?? 0;
            $reportCard->progress_percent = $result['progress_percent'] ?? 0;
            $reportCard->final_score = $result['final_score'] ?? 0;
            $reportCard->grade = $result['grade'] ?? null;

            $reportCard->status = $result['status'] ?? 'draft';
            $reportCard->is_certificate_eligible = (bool) ($result['is_certificate_eligible'] ?? false);

            $reportCard->summary = $extraData['summary'] ?? $reportCard->summary;
            $reportCard->strengths = $extraData['strengths'] ?? $reportCard->strengths;
            $reportCard->improvements = $extraData['improvements'] ?? $reportCard->improvements;
            $reportCard->instructor_note = $extraData['instructor_note'] ?? $reportCard->instructor_note;
            $reportCard->academic_note = $extraData['academic_note'] ?? $reportCard->academic_note;

            $reportCard->score_snapshot = $result['score_snapshot'] ?? [];
            $reportCard->rubric_snapshot = $result['rubric_snapshot'] ?? [];
            $reportCard->rule_snapshot = $result['rule_snapshot'] ?? [];

            $reportCard->generated_by = $generatedBy;
            $reportCard->generated_at = now();

            $reportCard->save();

            return $reportCard->fresh([
                'student',
                'batch',
                'program',
                'template',
                'certificate',
            ]);
        });
    }

    public function generateByIds(int $studentId, int $batchId, ?int $generatedBy = null, array $extraData = []): ReportCard
    {
        $student = Student::query()->findOrFail($studentId);
        $batch = Batch::query()->findOrFail($batchId);

        return $this->generate(
            student: $student,
            batch: $batch,
            generatedBy: $generatedBy,
            extraData: $extraData
        );
    }

    public function publish(ReportCard $reportCard, ?int $publishedBy = null): ReportCard
    {
        return DB::transaction(function () use ($reportCard, $publishedBy) {
            $reportCard->loadMissing([
                'student',
                'batch',
                'program',
                'template',
                'certificate',
            ]);

            if ($reportCard->status === 'cancelled') {
                throw new \RuntimeException('Cancelled report card cannot be published.');
            }

            /*
            |--------------------------------------------------------------------------
            | Publish Report Card
            |--------------------------------------------------------------------------
            | Publish = dokumen report card dianggap final.
            | Certificate eligibility tetap terpisah dari status publish.
            */
            $reportCard->status = 'published';
            $reportCard->published_by = $publishedBy;
            $reportCard->published_at = now();
            $reportCard->save();

            /*
            |--------------------------------------------------------------------------
            | Generate / Update PDF
            |--------------------------------------------------------------------------
            | Storage::put akan overwrite file kalau path yang sama sudah ada.
            | Jadi kalau publish berkali-kali, file PDF final akan ter-update.
            */
            $pdfPath = $this->generateAndStorePdf($reportCard->fresh([
                'student',
                'batch',
                'program',
                'template',
                'certificate',
            ]));

            $reportCard->pdf_path = $pdfPath;
            $reportCard->save();

            return $reportCard->fresh([
                'student',
                'batch',
                'program',
                'template',
                'certificate',
            ]);
        });
    }

    public function publishById(int $reportCardId, ?int $publishedBy = null): ReportCard
    {
        $reportCard = ReportCard::query()->findOrFail($reportCardId);

        return $this->publish($reportCard, $publishedBy);
    }

    public function cancel(ReportCard $reportCard): ReportCard
    {
        $reportCard->status = 'cancelled';
        $reportCard->is_certificate_eligible = false;
        $reportCard->save();

        return $reportCard->fresh([
            'student',
            'batch',
            'program',
            'template',
            'certificate',
        ]);
    }

    public function cancelById(int $reportCardId): ReportCard
    {
        $reportCard = ReportCard::query()->findOrFail($reportCardId);

        return $this->cancel($reportCard);
    }

    public function regenerate(ReportCard $reportCard, ?int $generatedBy = null, array $extraData = []): ReportCard
    {
        $reportCard->loadMissing(['student', 'batch']);

        return $this->generate(
            student: $reportCard->student,
            batch: $reportCard->batch,
            generatedBy: $generatedBy,
            extraData: $extraData
        );
    }

    public function generateReportNo(Batch $batch): string
    {
        $prefix = 'RC';
        $date = now()->format('Ymd');

        $programCode = $this->resolveProgramCode($batch);
        $batchCode = $this->resolveBatchCode($batch);

        $base = "{$prefix}-{$date}-{$programCode}-{$batchCode}";

        $latestCount = ReportCard::query()
            ->where('report_no', 'like', "{$base}-%")
            ->withTrashed()
            ->count();

        $sequence = str_pad((string) ($latestCount + 1), 4, '0', STR_PAD_LEFT);

        $reportNo = "{$base}-{$sequence}";

        while (ReportCard::query()->withTrashed()->where('report_no', $reportNo)->exists()) {
            $latestCount++;
            $sequence = str_pad((string) ($latestCount + 1), 4, '0', STR_PAD_LEFT);
            $reportNo = "{$base}-{$sequence}";
        }

        return $reportNo;
    }

    private function generateAndStorePdf(ReportCard $reportCard): string
    {
        $reportCard->loadMissing([
            'student',
            'batch',
            'program',
            'template',
            'certificate',
        ]);

        $studentName = $this->resolveStudentName($reportCard);

        $safeReportNo = Str::slug($reportCard->report_no ?: 'report-card');
        $safeStudentName = Str::slug($studentName ?: 'student');

        /*
        |--------------------------------------------------------------------------
        | Stable Path
        |--------------------------------------------------------------------------
        | Path dibuat stabil berdasarkan report_no + student.
        | Kalau publish ulang, Storage::put overwrite file yang sama.
        */
        $path = 'report-cards/'
            . now()->format('Y')
            . '/'
            . "{$safeReportNo}-{$safeStudentName}.pdf";

        $pdf = Pdf::loadView('academic.report-cards.pdf', [
            'reportCard' => $reportCard,
            'studentName' => $studentName,
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function resolveStudentName(ReportCard $reportCard): string
    {
        $student = $reportCard->student;

        if (! $student) {
            return 'Student #' . $reportCard->student_id;
        }

        $studentName = $student->name
            ?? $student->full_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        if (! $studentName) {
            $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
        }

        return $studentName;
    }

    private function resolveProgramCode(Batch $batch): string
    {
        $batch->loadMissing('program');

        $name = $batch->program?->name ?? 'PROGRAM';

        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(16, '')
            ->toString() ?: 'PROGRAM';
    }

    private function resolveBatchCode(Batch $batch): string
    {
        $name = $batch->name
            ?? $batch->title
            ?? $batch->code
            ?? "BATCH-{$batch->id}";

        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(16, '')
            ->toString() ?: "BATCH-{$batch->id}";
    }
}